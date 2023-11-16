<?php
/**************************************************************************
 *	kvmbackupjobs
 *	© 2023 by Fabrizio Vettore - fabrizio(at)vettore.org
 *	V 0.2
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 **************************************************************************/
//Update all VMs status
require_once __DIR__ . "/getstatus.php";

$r = $db->query("select * from backup_jobs where enabled=1");
$p=0;//index for increasing MDb port
while ($l = $r->fetch_array()) {
    $backupres = NULL;
    $BACKUPFAIL = FALSE;
    list($job_id, $job_name, $job_max_inc, $job_inc_nr, $job_full_nr, $job_enabled, $job_lastrun, $job_lastcompletion, $job_path, $job_checkmount) = $l;

    $is_mounted = null;
    $SKIPBACKUP = FALSE;
    //check if scheduled for days
    $d = $db->query("select * from backup_days where idbackup_job=$job_id");
    if ($d->num_rows) { //a day schedule is defined
        $days = $d->fetch_array();
        $curday = dayOfWeek();
        echo "Checking scheduled for day $curday\n";
        $is_to_backup = $days[$curday];
        if ($is_to_backup) {
            echo "Backup is to be performed on day $curday\n";
        } else {
            echo "Backup is to be skipped on day $curday\n";
            $SKIPBACKUP = TRUE;
        }
    }
    //check if scheduled weeks
    if (!$SKIPBACKUP) {
        $d = $db->query("select * from backup_weeks where idbackup_job=$job_id");
        if ($d->num_rows) { //a week schedule is defined
            $weeks = $d->fetch_array();
            $curweek = WeekOfMonth();
            echo "Checking scheduled for week $curweek\n";
            $is_to_backup = $weeks[$curweek];
            if ($is_to_backup) {
                echo "Backup is to be performed on week $curweek\n";
            } else {
                echo "Backup is to be skipped on week $curweek\n";
                $SKIPBACKUP = TRUE;
            }
        }
    }    
    if (!$SKIPBACKUP) {
        //if checkmount is set, check for mount    
        if ($job_checkmount) {
            echo "CHECKING mountpoint $job_path... ";
            $is_mounted = boolval(trim(shell_exec("mount | grep -c $job_path")));
            if ($is_mounted) {
                echo "OK!\n";
            }
        }
        if ($job_checkmount && !$is_mounted) {
            //JOB already running
            $BACKUPFAIL = true;
            $report="FAIL";
            $ERROR = "Mountpoint $job_path not mounted";
            echo "$ERROR\n";
        } else if ($job_lastrun > $job_lastcompletion) {
            //JOB already running
            $BACKUPFAIL = true;
            $report="FAIL";
            $ERROR = "same JOB already running from $job_lastrun";
            echo "$ERROR\n";
        } else { //backup can start now 
            $backupstarted = date("Y-m-d H:i:s");
            if ($job_inc_nr >= $job_max_inc || $job_full_nr == 0) {
                $backuptype = 'full';
                $job_inc_nr = 0;
                $job_full_nr++;
            } else {
                $backuptype = 'inc';
                $job_inc_nr++;
            }
            $indir = str_pad($job_full_nr, 6, '0', STR_PAD_LEFT);
            //get VMs
            //update Job record
            $db->query("update backup_jobs set
                inc_nr=$job_inc_nr, full_nr=$job_full_nr,
                lastrun=now()
                where idbackup_jobs=$job_id");

            $r1 = $db->query("
        SELECT 
            vm, node, ip
            FROM
            backup_vms b
            INNER JOIN
                vms v ON v.idvms = b.idvms
            INNER JOIN
                nodes n ON n.idnodes = v.idnodes
                where idbackup_jobs=$job_id");
            //number of objects to be backed-up        
            $numvms = $r1->num_rows;
            while ($l1 = $r1->fetch_array()) {
                list($vms, $node,$ip) = $l1;

                $backupdir = "$job_path/$job_name/$vms";
                echo "performing $backuptype in $backupdir for $vms on node $node\n";
                $vmbackuptype = $backuptype;
                if ($backuptype === 'inc') {
                    //check if preexisting FULL
                    echo "looking for existance of FULL in $backupdir/$indir/*.full.data \n";
                    $cmd = "ls $backupdir/$indir/*.full.data";
                    passthru($cmd, $result_code);
                    if ($result_code != 0) {
                        echo "Not previous FULL present, performing FULL instead of INC\n";
                        $vmbackuptype = 'full';
                    }
                }

                $vmbackupstarted = date("Y-m-d H:i:s");
                //use different NBD port for each JOB on order to avoid conflicts between jobs
                $p++;
                $nbdport = $job_id*100 + 10809+$p;
                $cmd = "/usr/bin/virtnbdbackup -I $ip -P $nbdport -l $vmbackuptype -U qemu+ssh://root@$ip/system -d $vms -o  $backupdir/$indir/  --checkpointdir $backupdir/checkpoints";
                echo "$cmd\n";                
                ob_start();
                passthru($cmd . " 2>&1", $result_code);
                $var = ob_get_contents();
                ob_end_clean();
                $vmbackupended = date("Y-m-d H:i:s");
                if ($result_code) {
                    //get last row of error message
                    $v = explode("\n", $var);
                    $bkerror = $v[count($v) - 2];
                    echo "error: " . escapeshellcmd($bkerror) . "\n";
                    $result = 'FAIL';
                    $BACKUPFAIL = TRUE;
                } else {
                    echo "$vms backup success\n";
                    $result = 'SUCCESS';
                    $bkerror = "";
                }
                //update array with vms backup data for notification
                $bkres[] = array('vm' => $vms, 'start' => $vmbackupstarted, 'end' => $vmbackupended, 'result' => $result, 'error' => $bkerror, 'type' => $vmbackuptype);
                $err = $db->real_escape_string($bkerror);
                $qi="insert into backup_log set vm=\"$vms\", job=\"$job_name\", 
                timestart='$vmbackupstarted',timeend='$vmbackupended',result='$result',type='$vmbackuptype',error='$err',path=\" $backupdir/$indir/\"";
                $db->query($qi);                
            }
        }
        //END OF SINGLE JOB
        if (isset($bkres)) { //the backup completed
            $db->query("update backup_jobs set lastcompletion=now() where idbackup_jobs=$job_id");
            //backup completed
            if ($BACKUPFAIL) {
                $color = "red";
                $report = "FAIL";
            } else {
                $color = "green";
                $report = "SUCCESS";
            }
            $thstyle = "style=\"padding-top: 12px;padding-bottom: 12px;text-align: left;background-color: $color;color: white; border: 1px solid #ddd;padding: 8px;\"";
            $tdstyle = "style=\"border: 1px solid #ddd; padding: 8px;\"";
            $tablestyle = "style=\"font-family: Arial, Helvetica, sans-serif; border-collapse: collapse;width: 100%;\"";
            $message = "
        <table $tablestyle>
            <tr>
                <th $thstyle>Name</th><th $thstyle>Start time</th><th $thstyle>End time</th><th $thstyle>Status</th><th $thstyle>Type</th><th $thstyle>Duration</th><th $thstyle>Details</th>
            </tr>
        ";
            foreach ($bkres as $vmres) {

                $origin = date_create($vmres['start']);
                $target = date_create($vmres['end']);
                $interval = date_diff($origin, $target);

                if ($vmres['result'] == 'SUCCESS') $rescolor = 'green';
                else $rescolor = 'red';
                $message .= "
            <tr>
                <td $tdstyle>" . $vmres['vm'] . "</td>
                <td $tdstyle> " . $vmres['start'] . "</td>
                <td $tdstyle>" . $vmres['end'] . "</td>                
                <td $tdstyle><span style=\"color:$rescolor;\">" . $vmres['result'] . "</span></td>            
                <td $tdstyle>" . $vmres['type'] . "</td>
                <td $tdstyle>" . $interval->format("%H:%I:%S") . "</td>
                <td $tdstyle>" . $vmres['error'] . "</td>
            </tr>    
            ";
            }
            $message .= "</table>";
            emailnotify("[$report] Backup $job_name ($numvms objects)", $message);
            unset($bkres);
        } else { //backup aboreted with error
            $message = "<h3>$ERROR</h3>";
            emailnotify("[$report] Backup $job_name ($numvms objects)", $message);
        }
    }
}

function emailnotify($subject, $message)
{
    global $email_from;
    global $rcpt_to;

    $headers = "From: backup <$email_from>
User-Agent: PHP Mailer
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8
";
    foreach ($rcpt_to as $recipient) {
        mail($recipient, $subject, $message, $headers, "-f $email_from");
    }
}

function weekOfMonth()
{
    $date = date("Y-m-d");
    $firstOfMonth = date("Y-m-01", strtotime($date));
    return (intval(date("W", strtotime($date))) - intval(date("W", strtotime($firstOfMonth))) + 1);
}

function dayOfWeek()
{
    return date("w");
}
