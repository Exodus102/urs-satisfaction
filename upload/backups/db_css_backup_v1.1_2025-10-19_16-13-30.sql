-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: db_css
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `credentials`
--

DROP TABLE IF EXISTS `credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credentials` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` text NOT NULL,
  `middle_name` text DEFAULT NULL,
  `last_name` text NOT NULL,
  `contact_number` varchar(100) NOT NULL,
  `campus` text NOT NULL,
  `unit` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `dp` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `status` text NOT NULL,
  `date_created` date NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credentials`
--

LOCK TABLES `credentials` WRITE;
/*!40000 ALTER TABLE `credentials` DISABLE KEYS */;
INSERT INTO `credentials` VALUES (1,'Jenric','Panopio','Aran','09158100920','Morong','Campus Management Information System','University MIS','','aranjenrick@gmail.com','polskie123','Active','0000-00-00'),(6,'Jenrick','Dela Cruz','Aran','09208256071','Binangonan','Campus Directors','Campus Director','','aaaaaa@gmail.com','bdsadasdsad','Inactive','2025-09-12'),(7,'Ambient','Ikli','Aran','09208256071','Binangonan','Campus Management Information System','CSS Coordinator','upload/profile-picture/user_7_1760882984.jpg','ferf96989@gmail.com','polskie456','Active','2025-09-12'),(8,'Ramirr','Oppus','Villamarin','09158100920','Morong','Campus Management Information System','DCC','','dlhor65@gmail.com','polskie123','Active','2025-09-18'),(9,'Jefferson','Panopio','Aran','09653644238','Binangonan','Internal Audit Services','DCC','','shshshshsh@gmail.com','polskie123','Inactive','2025-09-26'),(13,'Deib','Panopio','Lhor','09208256071','Binangonan','Campus Directors','Campus Director','','polpol31@gmail.com','polskie456','Active','2025-10-13');
/*!40000 ALTER TABLE `credentials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_active`
--

DROP TABLE IF EXISTS `tbl_active`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_active` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_active`
--

LOCK TABLES `tbl_active` WRITE;
/*!40000 ALTER TABLE `tbl_active` DISABLE KEYS */;
INSERT INTO `tbl_active` VALUES (1,1);
/*!40000 ALTER TABLE `tbl_active` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_audit_trail`
--

DROP TABLE IF EXISTS `tbl_audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unit_name` varchar(100) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `action` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_audit_trail`
--

LOCK TABLES `tbl_audit_trail` WRITE;
/*!40000 ALTER TABLE `tbl_audit_trail` DISABLE KEYS */;
INSERT INTO `tbl_audit_trail` VALUES (1,'2025-09-26 05:08:17','Registrar','Jenrick Aran','User logged in'),(2,'2025-09-26 05:08:51','Registrar','Jenrick Aran','User logged in'),(3,'2025-09-26 05:15:10','Registrar','Jenrick Aran','Added new user: Jefferson Aran (shshshshsh@gmail.com)'),(4,'2025-09-26 05:17:34','Registrar','Jenrick Aran','Updated user account: Jefferson Aran (shshshshsh@gmail.com)'),(5,'2025-09-26 05:19:51','Registrar','Jenrick Aran','Added new campus: Pilaypilay'),(6,'2025-09-26 05:24:14','Registrar','Jenrick Aran','Removed campus: Pilaypilay'),(7,'2025-09-26 05:26:19','Registrar','Jenrick Aran','Added new customer type: Polskie'),(8,'2025-09-26 05:26:34','Registrar','Jenrick Aran','Removed customer type: Polskie'),(10,'2025-09-26 05:31:31','Registrar','Jenrick Aran','Removed unit: Internal Audit Services from Binangonan campus'),(11,'2025-09-26 05:35:07','Registrar','Jenrick Aran','Updated campus name from \'Angono\' to \'Bilibiran\''),(12,'2025-09-26 05:35:28','Registrar','Jenrick Aran','Updated campus name from \'Bilibiran\' to \'Angono\''),(13,'2025-09-26 05:38:21','Registrar','Jenrick Aran','Added new division: polskie'),(14,'2025-09-26 05:38:39','Registrar','Jenrick Aran','Updated division name from \'polskie\' to \'maharani\''),(16,'2025-09-26 05:44:52','Registrar','Jenrick Aran','Removed unit: College of Accountancy from Binangonan campus'),(17,'2025-09-26 05:45:11','Registrar','Jenrick Aran','Removed division: maharani'),(19,'2025-09-26 05:47:09','University MIS','Jenrick Aran','User logged in'),(20,'2025-09-26 05:47:50','University MIS','Jenrick Aran','Added new unit: Abusayah under division: Academic Affairs'),(21,'2025-09-26 05:48:44','University MIS','Jenrick Aran','Updated unit from \'Abusayah\' (Division: Academic Affairs) to \'Labar\' (Division: Academic Affairs)'),(22,'2025-09-26 05:57:05','University MIS','Jenrick Aran','Created a new system backup: db_css_backup_v1.0_2025-09-26_07-57-04.sql'),(23,'2025-09-26 05:58:51','University MIS','Jenrick Aran','Deleted backup: db_css_backup_v1.0_2025-09-26_07-57-04.sql'),(24,'2025-09-28 04:29:09','Registrar','Jenrick Aran','User logged in'),(25,'2025-09-28 04:30:25','College of Computer Studies','Ramirr Villamarin','User logged in'),(27,'2025-09-28 04:52:05','College of Computer Studies','Ramirr Villamarin','Resolved NCAR of Binangonan campus for the College of Accountancy office'),(28,'2025-09-28 04:55:59','Registrar','Jenrick Aran','User logged in'),(29,'2025-09-28 05:07:28','College of Computer Studies','Ramirr Villamarin','User logged in'),(30,'2025-09-28 05:11:49','Registrar','Jenrick Aran','User logged in'),(31,'2025-09-28 05:17:43','College of Computer Studies','Ramirr Villamarin','User logged in'),(32,'2025-09-28 05:24:33','College of Computer Studies','Ramirr Villamarin','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(33,'2025-09-28 05:25:03','Campus Management Information System','Ramirr Villamarin','User logged in'),(34,'2025-09-28 05:26:21','Registrar','Jenrick Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(35,'2025-09-28 05:26:55','Campus Directors','Ramirr Villamarin','User logged in'),(36,'2025-09-28 05:27:04','Campus Directors','Ramirr Villamarin','Resolved NCAR of Binangonan Campus for the College of Accountancy'),(37,'2025-09-28 10:44:34','Registrar','Jenrick Aran','User logged in'),(38,'2025-10-06 23:25:14','Registrar','Jenrick Aran','User logged in'),(39,'2025-10-07 12:09:23','Registrar','Jenrick Aran','User logged in'),(40,'2025-10-08 05:59:10','Registrar','Jenrick Aran','User logged in'),(41,'2025-10-08 07:05:20','Registrar','Jenrick Aran','User logged in'),(42,'2025-10-08 07:30:01','Registrar','Jenrick Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(43,'2025-10-08 07:30:47','Campus Directors','Ramirr Villamarin','User logged in'),(44,'2025-10-08 13:58:46','Registrar','Jenrick Aran','User logged in'),(45,'2025-10-08 14:40:10','Registrar','Jenrick Aran','User logged in'),(46,'2025-10-08 14:46:26','Registrar','Jenrick Aran','User logged in'),(47,'2025-10-08 14:48:22','Registrar','Jenrick Aran','User logged in'),(48,'2025-10-08 15:07:09','Registrar','Jenrick Aran','Updated own profile information.'),(49,'2025-10-08 15:09:25','Registrar','Jenrick Aran','Updated own profile information.'),(50,'2025-10-08 15:10:45','Registrar','Jenrick Aran','Updated own profile information.'),(51,'2025-10-08 15:11:05','Registrar','Ambient Aran','Updated own profile information.'),(52,'2025-10-08 15:13:28','Registrar','Ambient Aran','Updated own profile information.'),(53,'2025-10-08 15:13:43','Registrar','Ambient Aran','Updated own profile information.'),(54,'2025-10-08 16:15:57','Registrar','Ambient Aran','Updated own profile picture.'),(55,'2025-10-08 16:19:40','Registrar','Ambient Aran','User logged in'),(56,'2025-10-08 16:40:22','Registrar','Ambient Aran','Updated user account: Ambient Aran (ferf96989@gmail.com)'),(57,'2025-10-08 16:40:57','Campus Management Information System','Ambient Aran','User logged in'),(58,'2025-10-08 16:47:52','Campus Management Information System','Ambient Aran','Updated user account: Ambient Aran (ferf96989@gmail.com)'),(59,'2025-10-08 16:48:14','Campus Management Information System','Ambient Aran','User logged in'),(60,'2025-10-09 06:39:07','Campus Management Information System','Ambient Aran','User logged in'),(61,'2025-10-09 07:16:46','Campus Management Information System','Ambient Aran','Updated user account: Jenrick Aran (aranjenrick@gmail.com)'),(62,'2025-10-10 09:06:05','Campus Management Information System','Ambient Aran','User logged in'),(63,'2025-10-10 09:12:30','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(64,'2025-10-10 09:13:09','Campus Management Information System','Ramirr Villamarin','User logged in'),(65,'2025-10-10 09:54:42','Campus Management Information System','Ambient Aran','User logged in'),(66,'2025-10-10 09:58:47','Campus Management Information System','Ramirr Villamarin','User logged in'),(67,'2025-10-10 10:00:53','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(68,'2025-10-10 10:01:33','Campus Management Information System','Ramirr Villamarin','User logged in'),(69,'2025-10-10 10:19:35','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(70,'2025-10-10 10:24:15','Campus Management Information System','Ramirr Villamarin','User logged in'),(71,'2025-10-10 10:27:32','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(72,'2025-10-10 10:28:03','Campus Management Information System','Ramirr Villamarin','User logged in'),(73,'2025-10-10 10:31:19','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(74,'2025-10-10 10:31:38','Campus Management Information System','Ramirr Villamarin','User logged in'),(75,'2025-10-10 13:05:27','Campus Management Information System','Ambient Aran','User logged in'),(76,'2025-10-10 13:05:47','Campus Management Information System','Ambient Aran','Updated user account: Jenric Aran (aranjenrick@gmail.com)'),(77,'2025-10-11 02:12:41','Campus Management Information System','Ambient Aran','User logged in'),(78,'2025-10-11 03:05:54','Campus Management Information System','Ambient Aran','Uploaded a new system logo: logo_1760151954.png'),(79,'2025-10-11 03:09:24','Campus Management Information System','Ambient Aran','Uploaded a new system logo: logo_1760152164.png'),(80,'2025-10-11 03:25:22','Campus Management Information System','Ambient Aran','User logged in'),(81,'2025-10-11 03:32:14','Campus Management Information System','Ambient Aran','User logged in'),(82,'2025-10-12 02:12:34','Campus Management Information System','Ambient Aran','User logged in'),(83,'2025-10-12 02:21:04','Campus Management Information System','Ambient Aran','User logged in'),(84,'2025-10-12 03:35:26','Campus Management Information System','Ambient Aran','User logged in'),(85,'2025-10-12 03:42:21','Campus Management Information System','Ambient Aran','User logged in'),(86,'2025-10-12 03:42:49','Campus Management Information System','Ambient Aran','User logged in'),(87,'2025-10-12 03:53:03','Campus Management Information System','Ambient Aran','User logged in'),(88,'2025-10-12 04:55:25','Campus Management Information System','Ambient Aran','User logged in'),(89,'2025-10-12 09:38:04','Campus Management Information System','Ambient Aran','User logged in'),(90,'2025-10-12 09:39:05','Campus Management Information System','Ramirr Villamarin','User logged in'),(91,'2025-10-12 09:42:24','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(92,'2025-10-12 09:42:54','Campus Management Information System','Ramirr Villamarin','User logged in'),(93,'2025-10-12 09:44:37','Campus Management Information System','Ambient Aran','Updated user account: Ramirr Villamarin (dlhor65@gmail.com)'),(94,'2025-10-12 09:45:06','Campus Management Information System','Ramirr Villamarin','User logged in'),(95,'2025-10-12 13:20:07','Campus Management Information System','Ambient Aran','User logged in'),(96,'2025-10-12 14:09:26','Campus Management Information System','Ambient Aran','Added new unit: Labar under division: Academic Affairs at Binangonan campus'),(97,'2025-10-13 01:01:22','Campus Management Information System','Ambient Aran','User logged in'),(98,'2025-10-13 10:26:46','Campus Management Information System','Ambient Aran','User logged in'),(99,'2025-10-13 11:14:57','Campus Management Information System','Ambient Aran','Added new user: Deib Lhor (polpol@gmail.com)'),(100,'2025-10-13 11:16:53','Campus Management Information System','Ambient Aran','Added new user: Deib Lhor (polpol3@gmail.com)'),(101,'2025-10-13 11:17:58','Campus Management Information System','Ambient Aran','Added new user: Deib Lhor (polpol31@gmail.com)'),(102,'2025-10-13 11:21:31','Campus Management Information System','Ambient Aran','Added new user: Deib Lhor (polpol31@gmail.com)'),(103,'2025-10-13 11:22:31','Campus Management Information System','Ambient Aran','Updated user account: Deib Lhor (polpol31@gmail.com)'),(104,'2025-10-13 11:26:13','Campus Management Information System','Ambient Aran','Updated own profile picture.'),(105,'2025-10-13 11:26:55','Campus Management Information System','Ambient Aran','Updated own profile picture.'),(106,'2025-10-13 13:02:55','Campus Management Information System','Ambient Aran','User logged in'),(107,'2025-10-18 04:56:23','Campus Management Information System','Ambient Aran','User logged in'),(108,'2025-10-19 13:59:58','Campus Management Information System','Ambient Aran','User logged in'),(109,'2025-10-19 14:07:40','Campus Management Information System','Ambient Aran','Uploaded a new system logo: logo_1760882860.png'),(110,'2025-10-19 14:08:40','Campus Management Information System','Ambient Aran','Updated own profile picture.'),(111,'2025-10-19 14:09:03','Campus Management Information System','Ambient Aran','Updated own profile picture.'),(112,'2025-10-19 14:09:44','Campus Management Information System','Ambient Aran','Updated own profile picture.'),(113,'2025-10-19 14:13:24','Campus Management Information System','Ambient Aran','Created a new system backup: db_css_backup_v1.0_2025-10-19_16-13-23.sql');
/*!40000 ALTER TABLE `tbl_audit_trail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_backup`
--

DROP TABLE IF EXISTS `tbl_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `available_backups` varchar(100) NOT NULL,
  `version` int(20) NOT NULL,
  `size` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_backup`
--

LOCK TABLES `tbl_backup` WRITE;
/*!40000 ALTER TABLE `tbl_backup` DISABLE KEYS */;
INSERT INTO `tbl_backup` VALUES (9,'db_css_backup_v1.0_2025-10-19_16-13-23.sql',1,'0.05 MB','C:\\xampp\\htdocs\\css_website_admin_migration\\upload\\backups\\db_css_backup_v1.0_2025-10-19_16-13-23.sql','2025-10-19 14:13:24');
/*!40000 ALTER TABLE `tbl_backup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_campus`
--

DROP TABLE IF EXISTS `tbl_campus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_campus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_campus`
--

LOCK TABLES `tbl_campus` WRITE;
/*!40000 ALTER TABLE `tbl_campus` DISABLE KEYS */;
INSERT INTO `tbl_campus` VALUES (1,'Antipolo'),(2,'Angono'),(3,'Binangonan'),(4,'Cardona'),(5,'Cainta'),(6,'Morong'),(7,'Pililia'),(8,'Rodriguez'),(10,'Tanay'),(11,'Taytay');
/*!40000 ALTER TABLE `tbl_campus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_choices`
--

DROP TABLE IF EXISTS `tbl_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_choices` (
  `choices_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(50) NOT NULL,
  `choice_text` varchar(100) NOT NULL,
  PRIMARY KEY (`choices_id`),
  KEY `fk_question_id` (`question_id`),
  CONSTRAINT `fk_question_id` FOREIGN KEY (`question_id`) REFERENCES `tbl_questionaire` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_choices`
--

LOCK TABLES `tbl_choices` WRITE;
/*!40000 ALTER TABLE `tbl_choices` DISABLE KEYS */;
INSERT INTO `tbl_choices` VALUES (76,15,'5'),(77,15,'4'),(78,15,'3'),(79,15,'2'),(80,15,'1'),(81,16,'5'),(82,16,'4'),(83,16,'3'),(84,16,'2'),(85,16,'1'),(86,17,'5'),(87,17,'4'),(88,17,'3'),(89,17,'2'),(90,17,'1'),(91,18,'5'),(92,18,'4'),(93,18,'3'),(94,18,'2'),(95,18,'1'),(96,19,'5'),(97,19,'4'),(98,19,'3'),(99,19,'2'),(100,19,'1'),(101,20,'5'),(102,20,'4'),(103,20,'3'),(104,20,'2'),(105,20,'1'),(106,21,'5'),(107,21,'4'),(108,21,'3'),(109,21,'2'),(110,21,'1'),(111,23,'5'),(112,23,'4'),(113,23,'3'),(114,23,'2'),(115,23,'1'),(116,24,'5'),(117,24,'4'),(118,24,'3'),(119,24,'2'),(120,24,'1'),(121,25,'5'),(122,25,'4'),(123,25,'3'),(124,25,'2'),(125,25,'1'),(126,26,'5'),(127,26,'4'),(128,26,'3'),(129,26,'2'),(130,26,'1'),(131,28,'5'),(132,28,'4'),(133,28,'3'),(134,28,'2'),(135,28,'1');
/*!40000 ALTER TABLE `tbl_choices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_customer_type`
--

DROP TABLE IF EXISTS `tbl_customer_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_customer_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_type` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_customer_type`
--

LOCK TABLES `tbl_customer_type` WRITE;
/*!40000 ALTER TABLE `tbl_customer_type` DISABLE KEYS */;
INSERT INTO `tbl_customer_type` VALUES (2,'Student'),(3,'Parent'),(4,'Faculty'),(5,'Alumni'),(6,'Staff'),(7,'Other');
/*!40000 ALTER TABLE `tbl_customer_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_division`
--

DROP TABLE IF EXISTS `tbl_division`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_division` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `division_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_division`
--

LOCK TABLES `tbl_division` WRITE;
/*!40000 ALTER TABLE `tbl_division` DISABLE KEYS */;
INSERT INTO `tbl_division` VALUES (1,'Office of The President'),(2,'Academic Affairs'),(3,'Administration and Finance Division'),(4,'Research, Development, Extension, and Production Development'),(6,'Top Management');
/*!40000 ALTER TABLE `tbl_division` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_logo`
--

DROP TABLE IF EXISTS `tbl_logo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_logo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `logo_path` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_logo`
--

LOCK TABLES `tbl_logo` WRITE;
/*!40000 ALTER TABLE `tbl_logo` DISABLE KEYS */;
INSERT INTO `tbl_logo` VALUES (1,'resources/img/logo_1760151954.png',0),(2,'resources/img/logo_1760152164.png',0),(3,'resources/img/logo_1760882860.png',1);
/*!40000 ALTER TABLE `tbl_logo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_ncar`
--

DROP TABLE IF EXISTS `tbl_ncar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_ncar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `status` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_ncar`
--

LOCK TABLES `tbl_ncar` WRITE;
/*!40000 ALTER TABLE `tbl_ncar` DISABLE KEYS */;
INSERT INTO `tbl_ncar` VALUES (1,'upload/pdf/ncar-report_Binangonan_College-of-Accountancy_2025_q3.pdf','Resolved'),(2,'upload/pdf/ncar-report_Binangonan_Campus-Directors_2025_q3.pdf','Unresolved'),(3,'upload/pdf/ncar-report_Morong_Campus-Management-Information-System_2025_q4.pdf','Unresolved');
/*!40000 ALTER TABLE `tbl_ncar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_questionaire`
--

DROP TABLE IF EXISTS `tbl_questionaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_questionaire` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_survey` varchar(200) NOT NULL,
  `section` varchar(200) NOT NULL,
  `question` varchar(200) NOT NULL,
  `status` int(50) NOT NULL,
  `question_type` varchar(100) NOT NULL,
  `required` int(50) NOT NULL,
  `header` int(50) NOT NULL,
  `transaction_type` int(50) NOT NULL,
  `question_rendering` varchar(100) NOT NULL,
  PRIMARY KEY (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_questionaire`
--

LOCK TABLES `tbl_questionaire` WRITE;
/*!40000 ALTER TABLE `tbl_questionaire` DISABLE KEYS */;
INSERT INTO `tbl_questionaire` VALUES (11,'2025 Questionaire_v1.2','Section 2','Name (Optional)',1,'Text',0,0,2,'None'),(12,'2025 Questionaire_v1.2','Section 2','Contact No. (Optional)',1,'Text',0,0,2,'None'),(13,'2025 Questionaire_v1.2','Section 2','Click on the item corresponding to your answer using the given scale below.\n\n5 - Excellent \n4 - Very Satisfactory \n3 - Satisfactory \n2 - Unsatisfactory \n1 - Needs Improvement',1,'Description',0,0,2,'None'),(14,'2025 Questionaire_v1.2','Section 2','1. How well were you served by the personnel during your visit or transaction in terms of the following: \nPaano ka pinagsilbihan ng kawani nang bumisita ka sa tanggapan ayon sa mga sumusunod:',1,'Description',0,1,0,'QoS'),(15,'2025 Questionaire_v1.2','Section 2','a. Knowledge of the job (Kaalaman sa trabaho)',1,'Multiple Choice',1,1,0,'QoS'),(16,'2025 Questionaire_v1.2','Section 2','b. Accuracy in providing information (Katumpakan sa pagbibigay ng impormasyon)',1,'Multiple Choice',1,1,0,'QoS'),(17,'2025 Questionaire_v1.2','Section 2','c. Delivery of prompt and appropriate service (Pagbibigay ng mabilis at nararapat na serbisyo)',1,'Multiple Choice',1,1,0,'QoS'),(18,'2025 Questionaire_v1.2','Section 2','d. Professionalism and skillfulness of the service personnel (Pagiging propesyunal at may kasanayan na kawani)',1,'Multiple Choice',1,1,0,'QoS'),(19,'2025 Questionaire_v1.2','Section 2','e. Flexibility in handling requests and inquiries (Kakayahang umangkop ng pagtugon sa mga kahilingan at katanungan)',1,'Multiple Choice',1,1,0,'QoS'),(20,'2025 Questionaire_v1.2','Section 2','f. Friendliness, attentiveness, helpfulness and courtesy (Pagiging magiliw, maasikaso, matulungin at magalang)',1,'Multiple Choice',1,1,0,'QoS'),(21,'2025 Questionaire_v1.2','Section 2','g. The physical appearance of service personnel (e.g. wearing the prescribed uniform, ID, etc.) (Pisikal na kaayusan ng kawani tulad ng pagsusuot ng akmang uniporme, pagkakakilanlan o ID, at iba pa)',1,'Multiple Choice',1,1,0,'QoS'),(22,'2025 Questionaire_v1.2','Section 2','2. How did you find our service unit as to: \nAno ang masasabi mo sa aming tanggapan ayon sa:',1,'Description',0,1,0,'Su'),(23,'2025 Questionaire_v1.2','Section 2','a. Accessibility/location of the office/unit (Lokasyon ng tanggapan)',1,'Multiple Choice',1,1,0,'Su'),(24,'2025 Questionaire_v1.2','Section 2','b. Physical setup, condition, and availability of facilities and equipment (Pisikal na kaayusan, kalagayan at pgkakaroon ng mga kagamitan)',1,'Multiple Choice',1,1,0,'Su'),(25,'2025 Questionaire_v1.2','Section 2','c. Cleanliness of the premises (Kalinisan ng kapaligiran)',1,'Multiple Choice',1,1,0,'Su'),(26,'2025 Questionaire_v1.2','Section 2','d. Processes and procedures of service delivery are customer-friendly (Kaangkupan ng mga pamamaraan sa pagbibigay ng serbisyo sa mga kliyente o bisita)',1,'Multiple Choice',1,1,0,'Su'),(27,'2025 Questionaire_v1.2','Section 2','2. How did you find our service unit as to: \nAno ang masasabi mo sa aming tanggapan ayon sa:',1,'Description',1,0,1,'Su'),(28,'2025 Questionaire_v1.2','Section 2','a. Online platform used is customer-friendly (Kaangkupan ng ginamit na online platform o pamamaraan mga kliyente o bisita)',1,'Multiple Choice',1,1,1,'Su'),(29,'2025 Questionaire_v1.3','Section 2','Name (Optional)',0,'Text',0,0,2,'None'),(30,'2025 Questionaire_v1.4','Section 2','Name(Optional)',0,'Text',0,0,2,'None'),(31,'2025 Questionaire_v1.5','Section 2','Name',0,'Text',1,0,2,'None');
/*!40000 ALTER TABLE `tbl_questionaire` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_questionaireform`
--

DROP TABLE IF EXISTS `tbl_questionaireform`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_questionaireform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_survey` varchar(100) NOT NULL,
  `change_log` varchar(255) NOT NULL,
  `date_approved` date DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_questionaireform`
--

LOCK TABLES `tbl_questionaireform` WRITE;
/*!40000 ALTER TABLE `tbl_questionaireform` DISABLE KEYS */;
INSERT INTO `tbl_questionaireform` VALUES (1,'2025 Questionaire_v1.2','The changes are many more','2025-09-26','2025-09-26 00:44:47'),(2,'2025 Questionaire_v1.3','Initial survey creation.',NULL,'2025-09-28 05:13:39'),(3,'2025 Questionaire_v1.4','Updated survey questions and/or name. making it more spacius adasdasdasdasdasdasdasdasdasdas','2025-10-06','2025-10-13 13:35:49'),(4,'2025 Questionaire_v1.5','Updated survey questions and/or name.','2025-10-29','2025-10-13 13:43:42');
/*!40000 ALTER TABLE `tbl_questionaireform` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_report`
--

DROP TABLE IF EXISTS `tbl_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_report`
--

LOCK TABLES `tbl_report` WRITE;
/*!40000 ALTER TABLE `tbl_report` DISABLE KEYS */;
INSERT INTO `tbl_report` VALUES (1,'upload/pdf/report_Binangonan_College-of-Accountancy_2025_q3.pdf','2025-09-20 14:54:26'),(2,'upload/pdf/report_Binangonan_College-of-Accountancy_2025_q3.pdf','2025-09-20 15:03:11'),(3,'upload/pdf/report_Binangonan_Campus-Directors_2025_q3.pdf','2025-09-22 14:21:23'),(4,'upload/pdf/report_Binangonan_Campus-Directors_2025_q3.pdf','2025-09-22 14:22:14'),(5,'upload/pdf/report_Binangonan_Campus-Directors_2025_q4.pdf','2025-10-06 23:27:13');
/*!40000 ALTER TABLE `tbl_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_responses`
--

DROP TABLE IF EXISTS `tbl_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `response_id` int(11) DEFAULT NULL,
  `response` varchar(255) DEFAULT NULL,
  `comment` varchar(255) NOT NULL,
  `analysis` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `header` int(11) NOT NULL,
  `transaction_type` varchar(255) NOT NULL,
  `question_rendering` varchar(255) DEFAULT NULL,
  `uploaded` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_responses`
--

LOCK TABLES `tbl_responses` WRITE;
/*!40000 ALTER TABLE `tbl_responses` DISABLE KEYS */;
INSERT INTO `tbl_responses` VALUES (1,-1,1,'Binangonan','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(2,-2,1,'Academic Affairs','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(3,-3,1,'College of Accountancy','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(4,-4,1,'Student','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(5,1,1,'Clearance','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(6,11,1,'','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(7,12,1,'','This is good','negative','2025-09-20 15:02:02',0,'0',NULL,0),(8,15,1,'5','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(9,16,1,'5','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(10,17,1,'5','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(11,18,1,'4','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(12,19,1,'3','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(13,20,1,'2','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(14,21,1,'1','This is good','negative','2025-09-20 15:02:02',1,'0','QoS',0),(15,23,1,'5','This is good','negative','2025-09-20 15:02:02',1,'0','Su',0),(16,24,1,'4','This is good','negative','2025-09-20 15:02:02',1,'0','Su',0),(17,25,1,'3','This is good','negative','2025-09-20 15:02:02',1,'0','Su',0),(18,26,1,'2','This is good','negative','2025-09-20 15:02:02',1,'0','Su',0),(19,-1,2,'Morong','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(20,-2,2,'Office of The President','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(21,-3,2,'Campus Management Information System','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(22,-4,2,'Student','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(23,1,2,'asdsaa','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(24,11,2,'Jenrick','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(25,12,2,'09158100920','This is terrible.','','2025-09-22 13:33:07',0,'0',NULL,0),(26,15,2,'1','This is terrible.','','2025-09-22 13:33:07',1,'0','QoS',0),(27,16,2,'1','This is terrible.','','2025-09-22 13:33:07',1,'0','QoS',0),(28,17,2,'1','This is terrible.','','2025-09-22 13:33:07',1,'0','QoS',0),(29,18,2,'1','This is terrible.','','2025-09-22 13:33:07',1,'0','QoS',0),(30,19,2,'1','This is terrible.','','2025-09-22 13:33:07',1,'0','QoS',0),(31,20,2,'1','This is terrible.','','2025-09-22 13:33:07',1,'0','QoS',0),(32,21,2,'1','This is terrible.','','2025-09-22 13:33:08',1,'0','QoS',0),(33,23,2,'1','This is terrible.','','2025-09-22 13:33:08',1,'0','Su',0),(34,24,2,'1','This is terrible.','','2025-09-22 13:33:08',1,'0','Su',0),(35,25,2,'1','This is terrible.','','2025-09-22 13:33:08',1,'0','Su',0),(36,26,2,'1','This is terrible.','','2025-09-22 13:33:08',1,'0','Su',0),(37,-1,3,'Binangonan','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(38,-2,3,'Academic Affairs','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(39,-3,3,'Graduate School','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(40,-4,3,'Alumni','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(41,1,3,'Clearance','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(42,11,3,'Jenrick','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(43,12,3,'09158100920','Pangit','negative','2025-09-24 19:32:37',0,'0',NULL,0),(44,15,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(45,16,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(46,17,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(47,18,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(48,19,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(49,20,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(50,21,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','QoS',0),(51,23,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','Su',0),(52,24,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','Su',0),(53,25,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','Su',0),(54,26,3,'3','Pangit','negative','2025-09-24 19:32:37',1,'0','Su',0),(55,-1,4,'Binangonan','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(56,-2,4,'Top Management','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(57,-3,4,'Campus Directors','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(58,-4,4,'Parent','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(59,1,4,'Clearance','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(60,11,4,'','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(61,12,4,'','pangit','negative','2025-09-28 10:47:39',0,'0',NULL,0),(62,15,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(63,16,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(64,17,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(65,18,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(66,19,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(67,20,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(68,21,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','QoS',0),(69,23,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','Su',0),(70,24,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','Su',0),(71,25,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','Su',0),(72,26,4,'5','pangit','negative','2025-09-28 10:47:39',1,'0','Su',0),(73,-1,5,'Binangonan','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(74,-2,5,'Top Management','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(75,-3,5,'Campus Directors','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(76,-4,5,'Faculty','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(77,1,5,'Clearance','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(78,11,5,'','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(79,12,5,'','mabait ang mga tao','positive','2025-09-28 10:48:33',0,'0',NULL,0),(80,15,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(81,16,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(82,17,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(83,18,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(84,19,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(85,20,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(86,21,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','QoS',0),(87,23,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','Su',0),(88,24,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','Su',0),(89,25,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','Su',0),(90,26,5,'1','mabait ang mga tao','positive','2025-09-28 10:48:33',1,'0','Su',0),(91,-1,6,'Morong','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(92,-2,6,'Office of The President','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(93,-3,6,'Campus Management Information System','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(94,-4,6,'Parent','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(95,1,6,'asd','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(96,11,6,'Jenrick','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(97,12,6,'09158100920','pangit','negative','2025-10-08 16:28:02',0,'0',NULL,0),(98,15,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(99,16,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(100,17,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(101,18,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(102,19,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(103,20,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(104,21,6,'5','pangit','negative','2025-10-08 16:28:02',1,'0','QoS',0),(105,23,6,'4','pangit','negative','2025-10-08 16:28:02',1,'0','Su',0),(106,24,6,'4','pangit','negative','2025-10-08 16:28:02',1,'0','Su',0),(107,25,6,'4','pangit','negative','2025-10-08 16:28:02',1,'0','Su',0),(108,26,6,'4','pangit','negative','2025-10-08 16:28:02',1,'0','Su',0),(109,-1,7,'Binangonan','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(110,-2,7,'Academic Affairs','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(111,-3,7,'College of Accountancy','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(112,-4,7,'Student','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(113,1,7,'Heello','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(114,11,7,'','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(115,12,7,'','panget magturo','negative','2025-10-10 13:33:18',0,'0',NULL,0),(116,15,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(117,16,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(118,17,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(119,18,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(120,19,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(121,20,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(122,21,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','QoS',0),(123,23,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','Su',0),(124,24,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','Su',0),(125,25,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','Su',0),(126,26,7,'5','panget magturo','negative','2025-10-10 13:33:18',1,'0','Su',0),(161,-1,8,'Binangonan','Great Service','positive','2024-09-16 02:00:00',0,'0','',0),(162,-2,8,'Office of The President','Great Service','positive','2024-09-17 02:00:00',0,'0','',0),(163,-3,8,'Campus Planning, Monitoring and Evaluation','Great Service','positive','2024-09-18 02:00:00',0,'0','',0),(164,-4,8,'Student','Great Service','positive','2024-09-19 02:00:00',0,'0','',0),(165,1,8,'Clearance','Great Service','positive','2024-09-20 02:00:00',0,'0','',0),(166,29,8,'Jenrick the great','Great Service','positive','2024-09-21 02:00:00',0,'0','',0),(167,12,8,'','Great Service','positive','2024-09-22 02:00:00',0,'0','',0),(168,15,8,'5','Great Service','positive','2024-09-22 02:00:00',1,'0','QoS',0),(169,16,8,'4','Great Service','positive','2024-09-23 02:00:00',1,'0','QoS',0),(170,17,8,'4','Great Service','positive','2024-09-24 02:00:00',1,'0','QoS',0),(171,18,8,'4','Great Service','positive','2024-09-25 02:00:00',1,'0','QoS',0),(172,19,8,'4','Great Service','positive','2024-09-26 02:00:00',1,'0','QoS',0),(173,20,8,'4','Great Service','positive','2024-09-27 02:00:00',1,'0','QoS',0),(174,21,8,'4','Great Service','positive','2024-09-28 02:00:00',1,'0','QoS',0),(175,23,8,'4','Great Service','positive','2024-09-29 02:00:00',1,'0','Su',0),(176,24,8,'4','Great Service','positive','2024-09-30 02:00:00',1,'0','Su',0),(177,25,8,'4','Great Service','positive','2024-10-01 02:00:00',1,'0','Su',0),(178,26,8,'4','Great Service','positive','2024-10-02 02:00:00',1,'0','Su',0),(179,-1,9,'Binangonan','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(180,-2,9,'Academic Affairs','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(181,-3,9,'College of Accountancy','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(182,-4,9,'Student','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(183,1,9,'asd','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(184,11,9,'','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(185,12,9,'','asd',NULL,'2025-10-18 05:30:37',0,'0',NULL,0),(186,15,9,'3','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(187,16,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(188,17,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(189,18,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(190,19,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(191,20,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(192,21,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','QoS',0),(193,23,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','Su',0),(194,24,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','Su',0),(195,25,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','Su',0),(196,26,9,'1','asd',NULL,'2025-10-18 05:30:37',1,'0','Su',0),(197,-1,10,'Binangonan','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(198,-2,10,'Academic Affairs','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(199,-3,10,'College of Accountancy','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(200,-4,10,'Student','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(201,1,10,'asd','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(202,11,10,'','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(203,12,10,'','pangit ng serbisyo','negative','2025-10-18 05:31:24',0,'1',NULL,0),(204,28,10,'5','pangit ng serbisyo','negative','2025-10-18 05:31:24',1,'1','Su',0);
/*!40000 ALTER TABLE `tbl_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_tally_report`
--

DROP TABLE IF EXISTS `tbl_tally_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_tally_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_tally_report`
--

LOCK TABLES `tbl_tally_report` WRITE;
/*!40000 ALTER TABLE `tbl_tally_report` DISABLE KEYS */;
INSERT INTO `tbl_tally_report` VALUES (1,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-20 14:48:08'),(2,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-20 14:51:06'),(3,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-20 14:52:44'),(4,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-20 15:02:29'),(5,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-22 13:33:28'),(6,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-26 00:36:04'),(7,'upload/pdf/tally-report_Binangonan_2025_q3.pdf','2025-09-26 00:36:44'),(8,'upload/pdf/tally-report_Binangonan_2025_q1.pdf','2025-10-06 23:27:52'),(9,'upload/pdf/tally-report_Morong_2025_q4.pdf','2025-10-08 16:41:26'),(10,'upload/pdf/tally-report_Morong_2025_q3.pdf','2025-10-08 16:42:29'),(11,'upload/pdf/tally-report_Morong_2025_q4.pdf','2025-10-08 16:42:44');
/*!40000 ALTER TABLE `tbl_tally_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_unit`
--

DROP TABLE IF EXISTS `tbl_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_name` varchar(100) NOT NULL,
  `division_name` varchar(100) NOT NULL,
  `unit_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_unit`
--

LOCK TABLES `tbl_unit` WRITE;
/*!40000 ALTER TABLE `tbl_unit` DISABLE KEYS */;
INSERT INTO `tbl_unit` VALUES (1,'Morong','Office of The President','Campus Management Information System'),(2,'Morong','Office of The President','Campus Planning, Monitoring and Evaluation'),(3,'Binangonan','Office of The President','Campus Planning, Monitoring and Evaluation'),(4,'Binangonan','Office of The President','Campus Management Information System'),(5,'Binangonan','Top Management','Campus Directors'),(7,'Binangonan','Academic Affairs','College of Business'),(8,'Binangonan','Academic Affairs','College of Computer Studies'),(11,'Binangonan','Academic Affairs','College of Social Work and Community Development'),(12,'Binangonan','Academic Affairs','Graduate School'),(13,'Binangonan','Academic Affairs','College of Accountancy'),(14,'Binangonan','Academic Affairs','Labar');
/*!40000 ALTER TABLE `tbl_unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_unit_mis`
--

DROP TABLE IF EXISTS `tbl_unit_mis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_unit_mis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `division_name` varchar(100) NOT NULL,
  `unit_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_unit_mis`
--

LOCK TABLES `tbl_unit_mis` WRITE;
/*!40000 ALTER TABLE `tbl_unit_mis` DISABLE KEYS */;
INSERT INTO `tbl_unit_mis` VALUES (1,'Office of The President','Campus Management Information System'),(2,'Office of The President','Campus Planning, Monitoring and Evaluation'),(4,'Top Management','Office of the President'),(5,'Top Management','VP for Academic Affairs'),(6,'Top Management','VP for Admin and Finance'),(7,'Top Management','VP for RDEP'),(8,'Top Management','Campus Directors'),(9,'Office of The President','University Management Information System'),(10,'Office of The President','International Development and Special Programs'),(11,'Office of The President','Center for Life Long Learning'),(12,'Office of The President','Campus Sports Development'),(13,'Office of The President','Culture and Arts'),(14,'Office of The President','ISO Command Center'),(15,'Office of The President','Document Control Center'),(16,'Academic Affairs','College of Accountancy'),(17,'Academic Affairs','College of Business'),(18,'Academic Affairs','College of Computer Studies'),(19,'Academic Affairs','College of Social Work and Community Development'),(20,'Academic Affairs','Graduate School'),(21,'Academic Affairs','General Education Center'),(22,'Academic Affairs','Laboratory Schools'),(23,'Administration and Finance Division','Internal Audit Services'),(24,'Research, Development, Extension, and Production Development','Campus Research'),(25,'Academic Affairs','Labar');
/*!40000 ALTER TABLE `tbl_unit_mis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `two_factor_codes`
--

DROP TABLE IF EXISTS `two_factor_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `two_factor_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `two_factor_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `credentials` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `two_factor_codes`
--

LOCK TABLES `two_factor_codes` WRITE;
/*!40000 ALTER TABLE `two_factor_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `two_factor_codes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-19 22:13:31
