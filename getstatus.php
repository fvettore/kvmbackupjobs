<?php
/*************************************************************
 *  GET and update VMs status on the cluster
 *  (in which clusternode are they defined/running?)
 */

require_once __DIR__ . "/config.php";
$db = new mysqli($dbhost, $dbuser, $dbpwd, $dbdatabase);
if (!$db) die("Unable to connect to database\n");
$db->query("update vms set running=0");
$sql = "select * from nodes";
$r = $db->query($sql);

//scan all the cluster nodes
while ($l = $r->fetch_array()) {
    list($node_id, $node) = $l;
    echo "NODE $node_id $node\n";
    $cmd = "/usr/bin/virsh -c qemu+ssh://root@$node/system list --all ";
    /*
answer is something like this:

[root@KVM1 ~]# virsh list
 Id   Name      State
-------------------------
 1    VPC01     running
 2    VPC02     running
 3    VPC03_1   running
 4    VPC04_1   running
 5    VPC05     running
 6    VPC06     running
 7    VPC10     running
 8    VPC11     running
 9    VPC12     running
*/
    if ($res = shell_exec($cmd)) {
        unset($rows);
        $rows = explode("\n", $res);
        for ($x = 2; $x < count($rows); $x++) {
            while (strpos($rows[$x], "  ")) {
                $rows[$x] = str_replace("  ", " ", $rows[$x]);
            }
            $rows[$x] = trim($rows[$x]);
            $data = explode(" ", $rows[$x]);
            if (count($data) > 1) {
                $id = trim($data[0]);
                if ($id === '-') $id = -1;
                if ($data[1]) $vm = trim($data[1]);
                $state = trim($data[2]);
                if ($state === "running") {
                    $running = 1;
                } else {
                    $running = 0;
                }
                if ($id) {
                    echo "$id $vm $state\n";
                    //check if existing VM row
                    $sq = "select idvms from vms where vm=\"$vm\"";
                    $rb = $db->query($sq);
                    if ($rb->num_rows) {
                        $db->query("update vms set  id=$id,running=$running,last_seen=now(),idnodes=$node_id where vm=\"$vm\"");
                    } else {
                        $db->query("insert into vms set id=$id,vm=\"$vm\",running=$running,last_seen=now(),idnodes=$node_id");
                    }
                }
            }
        }
    } else {
        echo "ERROR! node $node not responding\n";
    }
}
