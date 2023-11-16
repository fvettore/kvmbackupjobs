
<?php
/**************************************************************************
 *	kvmbackupjobs RETENTION
 *  delete expired backup folders
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
require_once __DIR__ . "/config.php";
$purgedfolder = array();
$db = new mysqli($dbhost, $dbuser, $dbpwd, $dbdatabase);
if (!$db) die("Unable to connect to database\n");
$r = $db->query("select name, idbackup_jobs,full_nr,retention,path from backup_jobs");
while ($bk = $r->fetch_array()) {
    list($name, $id, $full, $retention, $path) = $bk;
    if ($full > $retention) {
        $tobedeleted = $full - $retention;
        $checkdir = "$path/$name";
        echo "Purge needed on $name deleting older than " . ($tobedeleted + 1) . " on $checkdir\n";
        $d = dir($checkdir);

        while (($file = $d->read()) !== false) {
            if ($file <> '.' && $file <> '..') {
                echo "\n================\nFOLDER: " . $file;
                $dm = dir("$checkdir/$file");
                while ($dirbk = $dm->read()) {
                    if ($dirbk <> '.' && $dirbk <> '..' && is_dir("$checkdir/$file/$dirbk")) {
                        if (intval($dirbk)) {
                            echo "\n  checking $dirbk ";
                            if (intval($dirbk) <= $tobedeleted) {
                                echo " $checkdir/$file/$dirbk needs to be purged!";
                                $purgedfolder['job'][] = $name;
                                $purgedfolder['path'][] = "$checkdir/$file/$dirbk";
                                //DANGER. Be very careful
                                //double check to avoid problems with empty path
                                if (strlen("$checkdir/$file/$dirbk") > 8) {
                                    if (is_dir("$checkdir/$file/$dirbk")) {
                                        $cmd = "rm -rf $checkdir/$file/$dirbk";
                                        shell_exec($cmd);
                                    }
                                }
                                //Check if deletion succeded  (not working with is_dir())         
                                $cms = "ls $checkdir/$file/$dirbk";
                                echo "cheking: $cms\n";
                                passthru($cms, $result_code);                                
                                if ($result_code==0) {
                                    $purgedfolder['result'][] = 'FAIL';
                                } else {
                                    $purgedfolder['result'][] = 'SUCCESS';
                                }
                            }
                        }
                    }
                }
            }
        }
        $d->close();
        echo "\n";
    }
}
if (count($purgedfolder)) {

    $thstyle = "style=\"padding-top: 12px;padding-bottom: 12px;text-align: left;background-color: blue;color: white; border: 1px solid #ddd;padding: 8px;\"";
    $tdstyle = "style=\"border: 1px solid #ddd; padding: 8px;\"";
    $tablestyle = "style=\"font-family: Arial, Helvetica, sans-serif; border-collapse: collapse;width: 100%;\"";
    $message = "<h3>The following bakup folders needed to be purged</h3>
<table $tablestyle>
    <tr>
        <th $thstyle>JOB</th><th $thstyle>PATH</th><th $thstyle>RESULT</th>
    </tr>
";
    for ($x = 0; $x < count($purgedfolder['job']); $x++) {

        if ($purgedfolder['result'][$x] === 'FAIL') {
            $rescolor = 'red';
        } else {
            $rescolor = 'green';
        }
        $message .= "
            <tr>
                <td $tdstyle>" . $purgedfolder['job'][$x] . "</td>
                <td $tdstyle>" . $purgedfolder['path'][$x] . "</td>                              
                <td $tdstyle><span style=\"color:$rescolor;\">" . $purgedfolder['result'][$x] . "</span></td>            
            </tr>\n ";
    }
    $message .= "</table>";
    emailnotify("Backup retention", $message);
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
