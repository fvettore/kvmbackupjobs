DROP TABLE IF EXISTS `backup_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_jobs`
--

DROP TABLE IF EXISTS `backup_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`idbackup_jobs`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='                                                     ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_log`
--

DROP TABLE IF EXISTS `backup_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_log` (
  `idbackup_log` int(11) NOT NULL AUTO_INCREMENT,
  `job` varchar(45) DEFAULT NULL,
  `vm` varchar(128) DEFAULT NULL,
  `timestart` datetime DEFAULT NULL,
  `timeend` datetime DEFAULT NULL,
  `type` varchar(6) DEFAULT NULL,
  `result` varchar(20) DEFAULT '0',
  `error` text DEFAULT NULL,
  PRIMARY KEY (`idbackup_log`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_vms`
--

DROP TABLE IF EXISTS `backup_vms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_vms` (
  `idbackup_jobs` int(11) DEFAULT NULL,
  `idvms` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_weeks`
--

DROP TABLE IF EXISTS `backup_weeks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_weeks` (
  `idbackup_job` int(11) DEFAULT NULL,
  `week` int(11) DEFAULT NULL,
  `1` int(11) DEFAULT NULL,
  `2` int(11) DEFAULT NULL,
  `3` int(11) DEFAULT NULL,
  `4` int(11) DEFAULT NULL,
  `5` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `jobs_vms`
--

DROP TABLE IF EXISTS `jobs_vms`;
/*!50001 DROP VIEW IF EXISTS `jobs_vms`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `jobs_vms` AS SELECT
 1 AS `vm`,
  1 AS `node`,
  1 AS `job_name`,
  1 AS `max_inc`,
  1 AS `inc_nr`,
  1 AS `full_nr`,
  1 AS `enabled`,
  1 AS `lastrun`,
  1 AS `lastcompletion`,
  1 AS `path` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `nodes`
--

DROP TABLE IF EXISTS `nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nodes` (
  `idnodes` int(11) NOT NULL AUTO_INCREMENT,
  `node` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idnodes`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vms`
--

DROP TABLE IF EXISTS `vms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vms` (
  `idvms` int(11) NOT NULL AUTO_INCREMENT,
  `id` int(11) DEFAULT NULL,
  `idnodes` int(11) DEFAULT NULL,
  `vm` varchar(45) DEFAULT NULL,
  `running` int(11) DEFAULT 1,
  `last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`idvms`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

