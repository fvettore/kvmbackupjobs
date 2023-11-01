CREATE TABLE `backup_days` (
  `idbackup_days` int(11) NOT NULL AUTO_INCREMENT,
  `idbackup_job` int(11) DEFAULT NULL,
  `dayofweek` int(11) DEFAULT NULL,
  PRIMARY KEY (`idbackup_days`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `backup_jobs` (
  `idbackup_jobs` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `max_inc` int(11) DEFAULT 0 COMMENT 'Number of incremental backups before a new FULL',
  `inc_nr` int(11) DEFAULT 0,
  `full_nr` int(11) DEFAULT 0,
  `enabled` int(11) DEFAULT 1,
  `lastrun` datetime DEFAULT NULL,
  `lastcompletion` datetime DEFAULT NULL,
  `path` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`idbackup_jobs`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;	

CREATE TABLE `backup_vms` (
  `idbackup_jobs` int(11) DEFAULT NULL,
  `idvms` int(11) DEFAULT NULL,
  `idbackup_vms` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`idbackup_vms`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `nodes` (
  `idnodes` int(11) NOT NULL AUTO_INCREMENT,
  `node` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idnodes`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `vms` (
  `idvms` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) DEFAULT NULL,
  `idnodes` int(11) DEFAULT NULL,
  `vm` varchar(45) DEFAULT NULL,
  `running` int(11) DEFAULT 1,
  `last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`idvms`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
CREATE TABLE `backup_jobs` (
  `idbackup_jobs` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `max_inc` int(11) DEFAULT 0 COMMENT 'Number of incremental backups before a new FULL',
  `inc_nr` int(11) DEFAULT 0,
  `full_nr` int(11) DEFAULT 0,
  `enabled` int(11) DEFAULT 1,
  `lastrun` datetime DEFAULT NULL,
  `lastcompletion` datetime DEFAULT NULL,
  `path` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`idbackup_jobs`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ;
CREATE 
    ALGORITHM = UNDEFINED 
    DEFINER = `root`@`localhost` 
    SQL SECURITY DEFINER
VIEW `jobs_vms` AS
    SELECT 
        `v`.`vm` AS `vm`,
        `n`.`node` AS `node`,
        `j`.`name` AS `job_name`,
        `j`.`max_inc` AS `max_inc`,
        `j`.`inc_nr` AS `inc_nr`,
        `j`.`full_nr` AS `full_nr`,
        `j`.`enabled` AS `enabled`,
        `j`.`lastrun` AS `lastrun`,
        `j`.`lastcompletion` AS `lastcompletion`,
        `j`.`path` AS `path`
    FROM
        (((`backup_jobs` `j`
        JOIN `backup_vms` `bv` ON (`bv`.`idbackup_jobs` = `j`.`idbackup_jobs`))
        JOIN `vms` `v` ON (`bv`.`idvms` = `v`.`idvms`))
        JOIN `nodes` `n` ON (`n`.`idnodes` = `v`.`idnodes`))
    ORDER BY `j`.`name` , `v`.`vm`;

