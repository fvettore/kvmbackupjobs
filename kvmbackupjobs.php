<?php
/**************************************************************************
*	kvmbackupjobs
*	© 2023 by Fabrizio Vettore - fabrizio(at)vettore.org
*	V 0.1
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

$BACKUPFAIL = FALSE;

$r = $db->query("select * from backup_jobs where enabled=1");
while ($l = $r->fetch_array()) {
    $backupres = NULL;

    list($job_id, $job_name, $job_max_inc, $job_inc_nr, $job_full_nr, $job_enabled, $job_lastrun, $job_lastcompletion, $job_path) = $l;

    if ($job_lastrun > $job_lastcompletion) {
        //JOB already running
        $BACKUPFAIL = true;
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
            vm, node
            FROM
            backup_vms b
            INNER JOIN
                vms v ON v.idvms = b.idvms
            INNER JOIN
                nodes n ON n.idnodes = v.idnodes
                where idbackup_jobs=$job_id");

        while ($l1 = $r1->fetch_array()) {
            list($vms, $node) = $l1;

            $backupdir = "$job_path/$job_name/$vms";
            echo "performing $backuptype in $backupdir for $vms on node $node\n";
            $vmbackuptype=$backuptype;
            if ($backuptype === 'inc') {
                //check if preexisting FULL
                echo "checking existance of FULL in $backupdir/$indir/*.full.data \n";
                $cmd="ls $backupdir/$indir/*.full.data";
                passthru($cmd, $result_code);                
                if ($result_code!=0) {
                    echo "Not prevoius FULL present, performing FULL instead of INC\n";
                    $vmbackuptype = 'full';
                } 
            }

            $vmbackupstarted = date("Y-m-d H:i:s");
            $cmd = "/usr/bin/virtnbdbackup -l $vmbackuptype -U qemu+ssh://root@$node/system -d $vms -o  $backupdir/$indir/  --checkpointdir $backupdir/checkpoints";
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
            $db->query("insert into backup_log set vm=\"$vms\", job=\"$job_name\", 
                 timestart='$vmbackupstarted',timeend='$vmbackupended',result='$result',type='$vmbackuptype'");
        }
    }
    if (isset($bkres)) { //the backup is complete
        $db->query("update backup_jobs set lastcompletion=now() where idbackup_jobs=$job_id");
        //backup completed
        if ($BACKUPFAIL) {
            $color = "red";
        } else {
            $color = "green";
        }
        $thstyle="style=\"padding-top: 12px;padding-bottom: 12px;text-align: left;background-color: $color;color: white;\"";
        $tdstyle="style=\"border: 1px solid #ddd; padding: 8px;\"";
        $tablestyle="font-family: Arial, Helvetica, sans-serif; border-collapse: collapse;width: 100%;";
        $message .= "
        <table $tablestyle>
            <tr>
                <th $thstyle>VM</th><th $thstyle>start</th><th $thstyle>end</th><th $thstyle>result</th><th $thstyle>type</th><th $thstyle>error</th>
            </tr>
        ";
        foreach ($bkres as $vmres) {
            $message .= "
            <tr>
                <td $tdstyle>" . $vmres['vm'] . "</td>
                <td $tdstyle> " . $vmres['start'] . "</td>
                <td $tdstyle>" . $vmres['end'] . "</td>
                <td $tdstyle>" . $vmres['result'] . "</td>            
                <td $tdstyle>" . $vmres['type'] . "</td>
                <td $tdstyle>" . $vmres['error'] . "</td>
            </tr>    
            ";
        }
        $message .= "</table>";
        emailnotify("Backup $job_name", $message);
    } else { //backup aboreted with error
        $message = "<h3>$ERROR</h3>";
        emailnotify("Backup $job_name FAILED", $message);
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
