CREATE TABLE `backup_days` (
  `idbackup_job` int(11) DEFAULT NULL,
  `1` int(11) DEFAULT NULL,
  `2` int(11) DEFAULT NULL,
  `3` int(11) DEFAULT NULL,
  `4` int(11) DEFAULT NULL,
  `5` int(11) DEFAULT NULL,
  `6` int(11) DEFAULT NULL,
  `7` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
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
  `checkmount` int(11) DEFAULT 0,
  `retention` int(11) DEFAULT 10 COMMENT 'Number of full backup to retain (no deletion)',
  PRIMARY KEY (`idbackup_jobs`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='							';

CREATE TABLE `backup_log` (
  `idbackup_log` int(11) NOT NULL AUTO_INCREMENT,
  `job` varchar(45) DEFAULT NULL,
  `vm` varchar(128) DEFAULT NULL,
  `timestart` datetime DEFAULT NULL,
  `timeend` datetime DEFAULT NULL,
  `type` varchar(6) DEFAULT NULL,
  `result` varchar(20) DEFAULT '0',
  `path` varchar(255) DEFAULT NULL,
  `error` text DEFAULT NULL,
  PRIMARY KEY (`idbackup_log`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `backup_vms` (
  `idbackup_jobs` int(11) DEFAULT NULL,
  `idvms` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `backup_weeks` (
  `idbackup_job` int(11) DEFAULT NULL,
  `1` int(11) DEFAULT NULL,
  `2` int(11) DEFAULT NULL,
  `3` int(11) DEFAULT NULL,
  `4` int(11) DEFAULT NULL,
  `5` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `nodes` (
  `idnodes` int(11) NOT NULL AUTO_INCREMENT,
  `node` varchar(45) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `jobs_vms` AS select `v`.`vm` AS `vm`,`n`.`node` AS `node`,`j`.`name` AS `job_name`,`j`.`max_inc` AS `max_inc`,`j`.`inc_nr` AS `inc_nr`,`j`.`full_nr` AS `full_nr`,`j`.`enabled` AS `enabled`,`j`.`lastrun` AS `lastrun`,`j`.`lastcompletion` AS `lastcompletion`,`j`.`path` AS `path` from (((`backup_jobs` `j` join `backup_vms` `bv` on(`bv`.`idbackup_jobs` = `j`.`idbackup_jobs`)) join `vms` `v` on(`bv`.`idvms` = `v`.`idvms`)) join `nodes` `n` on(`n`.`idnodes` = `v`.`idnodes`)) order by `j`.`name`,`v`.`vm`;
