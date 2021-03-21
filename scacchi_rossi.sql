-- Progettazione Web 
DROP DATABASE if exists scacchi_rossi; 
CREATE DATABASE scacchi_rossi; 
USE scacchi_rossi; 
-- MySQL dump 10.13  Distrib 5.6.20, for Win32 (x86)
--
-- Host: localhost    Database: scacchi_rossi
-- ------------------------------------------------------
-- Server version	5.6.20

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `match_info`
--

DROP TABLE IF EXISTS `match_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `match_info` (
  `id` varchar(13) NOT NULL,
  `host` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `winner` int(11) DEFAULT NULL,
  `visibility` tinyint(1) NOT NULL,
  `num_players` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_info`
--

LOCK TABLES `match_info` WRITE;
/*!40000 ALTER TABLE `match_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `match_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `match_log`
--

DROP TABLE IF EXISTS `match_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `match_log` (
  `id` varchar(13) NOT NULL,
  `turn` int(11) NOT NULL,
  `control` int(11) DEFAULT NULL,
  `number` int(11) NOT NULL,
  `chessboard` text NOT NULL,
  `captured` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_log`
--

LOCK TABLES `match_log` WRITE;
/*!40000 ALTER TABLE `match_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `match_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `match_team`
--

DROP TABLE IF EXISTS `match_team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `match_team` (
  `match_id` varchar(13) NOT NULL,
  `user` int(11) NOT NULL,
  `team` int(11) DEFAULT NULL,
  `last_ping` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`match_id`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `match_team`
--

LOCK TABLES `match_team` WRITE;
/*!40000 ALTER TABLE `match_team` DISABLE KEYS */;
/*!40000 ALTER TABLE `match_team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'prova1','$2y$10$R.FbnPSNbYAg7qxkuAJqde2h7qoKF2VmZKnpFS5qCuzpa6.65lYxO',1),(2,'prova','$2y$10$TmQnai5ANvQ07fJnEipKPepRoJ6IWli8Sd3iRddmaTCmzqRTXVpQK',4);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-03-21  3:17:55
