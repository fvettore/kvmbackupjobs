# kvmbackupjobs
Simple script for executing VMs backup jobs on KVM cluster environment.

Create backup JOBs with thin provisioned full and incremental or differential backups of your kvm/qemu virtual machines using the latest changed block tracking features.

Supports backup rotation, VMs migration and checkpoints.

It is based on https://github.com/abbbi/virtnbdbackup

Simply add the definition of cluster nodes and backup jobs in mySQL/MariaDB tables and run it (or cron it).
No front end yet.

## How it works
The script scans all cluster nodes to map all VMs in the vms table, so it can locate them when executing backup.

This way a VM migratrion shouldn't affect backup operations.

After mapping it scans for all JOBs defined and execute them.

If a prevoius instance of the JOB is still running, the script terminate with email notification.

Otherwise a backup for each VMs defined in the JOB is performed.

If the VM have never been backed up, a FULL backup will be performed.

Otherwise the backup will be incremental until the max_inc thresold is reached.

After the threshold is reached the backup is rotated and a new full instance is located in a new folder.

When backup ends, a detailed report is sent by email.

Checkpoints are saved in the backup folder since, in transitional environments (as pacemaker clusters), when a VM migrates from a node to another, VM can loose informations about checkpoints.
(cfr: https://github.com/abbbi/virtnbdbackup/blob/master/README.md#transient-virtual-machines-checkpoint-persistency )

## Prerequisites
All cluster nodes must be set to be accessed by ssh (key, no password) from the host where you are going to run the scripts.

Firewall must be set to enable ssh.

Backup target must be mounted before backup starts.


## Getting started
Create your MySQL database. SQL is available in the SQL folder.

Edit *config.php* accordingly.

Insert your cluster nodes in the *nodes* table (FQDN or IP).

Execute *getstatus.php*.

If the script runs without errors, it should populate the *vms* table (double check it).

Define a backup job in the *backup_jobs* table filling the relevant fields (name, max_inc and path).

Ad VMs records in the *backup_vms* table filling *idbackup_jobs* (idbackup_job from backup_jobs_table) and *idvms* (idvms from vms table).

Try to start the job runing the *kvmbackupjobs.php* and monitor it.

If everything is ok you can add it to cron for daily execution.

## Under development

### Day-week selection for jobs
Will add the ability to plan a job execution only on some days or weeks

### Retention
The script is under development. 

After the retention threshold is reached (max number of full backup performed), the older backup folders are deleted.

If a hardened (immutable) backup is set on the storage side (strongly suggested!!!!) setup immutability in synch with the above retention thresold otherwise folder cleanup will fail.








