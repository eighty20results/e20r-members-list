-- MySQL dump 10.17  Distrib 10.3.11-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: wordpress
-- ------------------------------------------------------
-- Server version	10.3.11-MariaDB

USE wordpress;

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
-- Table structure for table `wptest_commentmeta`
--

DROP TABLE IF EXISTS `wptest_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_commentmeta`
--

LOCK TABLES `wptest_commentmeta` WRITE;
/*!40000 ALTER TABLE `wptest_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wptest_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_comments`
--

DROP TABLE IF EXISTS `wptest_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comment_author` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_author_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10)),
  KEY `woo_idx_comment_type` (`comment_type`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_comments`
--

LOCK TABLES `wptest_comments` WRITE;
/*!40000 ALTER TABLE `wptest_comments` DISABLE KEYS */;
INSERT INTO `wptest_comments` VALUES (1,1,'A WordPress Commenter','wapuu@wordpress.example','https://wordpress.org/','','2018-07-07 14:52:49','2018-07-07 14:52:49','Hi, this is a comment.\nTo get started with moderating, editing, and deleting comments, please visit the Comments screen in the dashboard.\nCommenter avatars come from <a href=\"https://gravatar.com\">Gravatar</a>.',0,'1','','',0,0);
/*!40000 ALTER TABLE `wptest_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_links`
--

DROP TABLE IF EXISTS `wptest_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_image` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_target` varchar(25) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_visible` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_notes` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `link_rss` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_links`
--

LOCK TABLES `wptest_links` WRITE;
/*!40000 ALTER TABLE `wptest_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wptest_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_options`
--

DROP TABLE IF EXISTS `wptest_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `option_value` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `autoload` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`)
) ENGINE=InnoDB AUTO_INCREMENT=84140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_options`
--

LOCK TABLES `wptest_options` WRITE;
/*!40000 ALTER TABLE `wptest_options` DISABLE KEYS */;
INSERT INTO `wptest_options` VALUES (1,'siteurl','http://development.local','yes'),(2,'home','http://development.local','yes'),(3,'blogname','Clean testbed','yes'),(4,'blogdescription','Just another WordPress site','yes'),(5,'users_can_register','0','yes'),(6,'admin_email','thomas@localhost.local','yes'),(7,'start_of_week','1','yes'),(8,'use_balanceTags','0','yes'),(9,'use_smilies','1','yes'),(10,'require_name_email','1','yes'),(11,'comments_notify','1','yes'),(12,'posts_per_rss','10','yes'),(13,'rss_use_excerpt','0','yes'),(14,'mailserver_url','mail.example.com','yes'),(15,'mailserver_login','login@example.com','yes'),(16,'mailserver_pass','password','yes'),(17,'mailserver_port','110','yes'),(18,'default_category','1','yes'),(19,'default_comment_status','open','yes'),(20,'default_ping_status','open','yes'),(21,'default_pingback_flag','0','yes'),(22,'posts_per_page','10','yes'),(23,'date_format','Y-m-d','yes'),(24,'time_format','H:i','yes'),(25,'links_updated_date_format','F j, Y g:i a','yes'),(26,'comment_moderation','0','yes'),(27,'moderation_notify','1','yes'),(28,'permalink_structure','/%postname%/','yes');
/*!40000 ALTER TABLE `wptest_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_discount_codes`
--

DROP TABLE IF EXISTS `wptest_pmpro_discount_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_discount_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `starts` date NOT NULL,
  `expires` date NOT NULL,
  `uses` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `starts` (`starts`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_discount_codes`
--

LOCK TABLES `wptest_pmpro_discount_codes` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_discount_codes` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_discount_codes` VALUES (1,'515AF6153B','2019-02-05','2020-02-05',10);
/*!40000 ALTER TABLE `wptest_pmpro_discount_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_discount_codes_levels`
--

DROP TABLE IF EXISTS `wptest_pmpro_discount_codes_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_discount_codes_levels` (
  `code_id` int(11) unsigned NOT NULL,
  `level_id` int(11) unsigned NOT NULL,
  `initial_payment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cycle_number` int(11) NOT NULL DEFAULT 0,
  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
  `trial_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `trial_limit` int(11) NOT NULL DEFAULT 0,
  `expiration_number` int(10) unsigned NOT NULL,
  `expiration_period` enum('Day','Week','Month','Year') NOT NULL,
  PRIMARY KEY (`code_id`,`level_id`),
  KEY `initial_payment` (`initial_payment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_discount_codes_levels`
--

LOCK TABLES `wptest_pmpro_discount_codes_levels` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_discount_codes_levels` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_discount_codes_levels` VALUES (1,1,10.00,0.00,0,'Month',0,0.00,0,1,'Year');
/*!40000 ALTER TABLE `wptest_pmpro_discount_codes_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_discount_codes_uses`
--

DROP TABLE IF EXISTS `wptest_pmpro_discount_codes_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_discount_codes_uses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_discount_codes_uses`
--

LOCK TABLES `wptest_pmpro_discount_codes_uses` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_discount_codes_uses` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_discount_codes_uses` VALUES (1,1,13,10,'2019-02-05 22:31:13');
/*!40000 ALTER TABLE `wptest_pmpro_discount_codes_uses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_membership_levelmeta`
--

DROP TABLE IF EXISTS `wptest_pmpro_membership_levelmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_membership_levelmeta` (
  `meta_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pmpro_membership_level_id` int(10) unsigned NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `pmpro_membership_level_id` (`pmpro_membership_level_id`),
  KEY `meta_key` (`meta_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_membership_levelmeta`
--

LOCK TABLES `wptest_pmpro_membership_levelmeta` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_membership_levelmeta` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_membership_levelmeta` VALUES (1,1,'confirmation_in_email','0'),(2,2,'confirmation_in_email','0'),(3,3,'confirmation_in_email','0'),(4,4,'confirmation_in_email','0'),(5,5,'confirmation_in_email','0'),(6,6,'confirmation_in_email','0'),(7,7,'confirmation_in_email','0'),(8,8,'confirmation_in_email','0'),(9,9,'confirmation_in_email','0'),(10,10,'confirmation_in_email','0'),(11,11,'confirmation_in_email','0'),(12,12,'confirmation_in_email','0'),(13,13,'confirmation_in_email','0');
/*!40000 ALTER TABLE `wptest_pmpro_membership_levelmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_membership_levels`
--

DROP TABLE IF EXISTS `wptest_pmpro_membership_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_membership_levels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `confirmation` longtext NOT NULL,
  `initial_payment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `billing_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cycle_number` int(11) NOT NULL DEFAULT 0,
  `cycle_period` enum('Day','Week','Month','Year') DEFAULT 'Month',
  `billing_limit` int(11) NOT NULL COMMENT 'After how many cycles should billing stop?',
  `trial_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `trial_limit` int(11) NOT NULL DEFAULT 0,
  `allow_signups` tinyint(4) NOT NULL DEFAULT 1,
  `expiration_number` int(10) unsigned NOT NULL,
  `expiration_period` enum('Day','Week','Month','Year') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `allow_signups` (`allow_signups`),
  KEY `initial_payment` (`initial_payment`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_membership_levels`
--

LOCK TABLES `wptest_pmpro_membership_levels` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_membership_levels` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_membership_levels` VALUES (1,'Attorneys 6','Admitted to Law Practice, 6 years or more','',240.00,0.00,0,'',0,0.00,0,1,1,'Year'),(2,'Attorneys 1-5','','',170.00,0.00,0,'',0,0.00,0,1,1,'Year'),(3,'Attorneys - New','','',95.00,0.00,0,'',0,0.00,0,1,1,'Year'),(4,'Attorneys - Govt','','',170.00,0.00,0,'',0,0.00,0,1,1,'Year'),(5,'Associate','','',220.00,0.00,0,'',0,0.00,0,1,1,'Year'),(6,'Law Students 1-2','','',0.00,0.00,0,'',0,0.00,0,1,1,'Year'),(7,'Retired Legal Professionals','','',105.00,0.00,0,'',0,0.00,0,1,1,'Year'),(8,'Law Students 3-4','','',45.00,0.00,0,'',0,0.00,0,1,1,'Year'),(9,'Legal Support','','',80.00,0.00,0,'',0,0.00,0,1,1,'Year'),(10,'Complimentary','','',0.00,0.00,0,'',0,0.00,0,0,0,'Year'),(11,'Not Applicable (Deleted)','','',0.00,0.00,0,'',0,0.00,0,0,0,''),(12,'Admin Support','','',0.00,0.00,0,'',0,0.00,0,0,0,''),(13,'Purchaser','','',0.00,0.00,0,'',0,0.00,0,0,0,'');
/*!40000 ALTER TABLE `wptest_pmpro_membership_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_membership_orders`
--

DROP TABLE IF EXISTS `wptest_pmpro_membership_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_membership_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `session_id` varchar(64) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `membership_id` int(11) unsigned NOT NULL DEFAULT 0,
  `paypal_token` varchar(64) NOT NULL DEFAULT '',
  `billing_name` varchar(128) NOT NULL DEFAULT '',
  `billing_street` varchar(128) NOT NULL DEFAULT '',
  `billing_city` varchar(128) NOT NULL DEFAULT '',
  `billing_state` varchar(32) NOT NULL DEFAULT '',
  `billing_zip` varchar(16) NOT NULL DEFAULT '',
  `billing_country` varchar(128) NOT NULL,
  `billing_phone` varchar(32) NOT NULL,
  `subtotal` varchar(16) NOT NULL DEFAULT '',
  `tax` varchar(16) NOT NULL DEFAULT '',
  `couponamount` varchar(16) NOT NULL DEFAULT '',
  `checkout_id` int(11) NOT NULL DEFAULT 0,
  `certificate_id` int(11) NOT NULL DEFAULT 0,
  `certificateamount` varchar(16) NOT NULL DEFAULT '',
  `total` varchar(16) NOT NULL DEFAULT '',
  `payment_type` varchar(64) NOT NULL DEFAULT '',
  `cardtype` varchar(32) NOT NULL DEFAULT '',
  `accountnumber` varchar(32) NOT NULL DEFAULT '',
  `expirationmonth` char(2) NOT NULL DEFAULT '',
  `expirationyear` varchar(4) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '',
  `gateway` varchar(64) NOT NULL,
  `gateway_environment` varchar(64) NOT NULL,
  `payment_transaction_id` varchar(64) NOT NULL,
  `subscription_transaction_id` varchar(32) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `affiliate_id` varchar(32) NOT NULL,
  `affiliate_subid` varchar(32) NOT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `membership_id` (`membership_id`),
  KEY `status` (`status`),
  KEY `timestamp` (`timestamp`),
  KEY `gateway` (`gateway`),
  KEY `gateway_environment` (`gateway_environment`),
  KEY `payment_transaction_id` (`payment_transaction_id`),
  KEY `subscription_transaction_id` (`subscription_transaction_id`),
  KEY `affiliate_id` (`affiliate_id`),
  KEY `affiliate_subid` (`affiliate_subid`),
  KEY `checkout_id` (`checkout_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1023 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_membership_orders`
--

LOCK TABLES `wptest_pmpro_membership_orders` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_membership_orders` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_membership_orders` VALUES (1,'D0E2A9B12F','',1,1,'','Jane Doe','123 Street','City','ST','12345','US','5558675309','1','0','',1,0,'','1','','Visa','XXXXXXXXXXXX1111','07','2019','','','sandbox','','','2018-07-07 14:55:20','','','This is a test order used with the PMPro Email Templates addon.');
/*!40000 ALTER TABLE `wptest_pmpro_membership_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_memberships_categories`
--

DROP TABLE IF EXISTS `wptest_pmpro_memberships_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_memberships_categories` (
  `membership_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned NOT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `membership_category` (`membership_id`,`category_id`),
  UNIQUE KEY `category_membership` (`category_id`,`membership_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_memberships_categories`
--

LOCK TABLES `wptest_pmpro_memberships_categories` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_memberships_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `wptest_pmpro_memberships_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_memberships_pages`
--

DROP TABLE IF EXISTS `wptest_pmpro_memberships_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_memberships_pages` (
  `membership_id` int(11) unsigned NOT NULL,
  `page_id` int(11) unsigned NOT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `category_membership` (`page_id`,`membership_id`),
  UNIQUE KEY `membership_page` (`membership_id`,`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_memberships_pages`
--

LOCK TABLES `wptest_pmpro_memberships_pages` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_memberships_pages` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_memberships_pages` VALUES (1,39,'2018-08-01 00:05:06');
/*!40000 ALTER TABLE `wptest_pmpro_memberships_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_pmpro_memberships_users`
--

DROP TABLE IF EXISTS `wptest_pmpro_memberships_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_pmpro_memberships_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `membership_id` int(11) unsigned NOT NULL,
  `code_id` int(11) unsigned NOT NULL,
  `initial_payment` decimal(10,2) NOT NULL,
  `billing_amount` decimal(10,2) NOT NULL,
  `cycle_number` int(11) NOT NULL,
  `cycle_period` enum('Day','Week','Month','Year') NOT NULL DEFAULT 'Month',
  `billing_limit` int(11) NOT NULL,
  `trial_amount` decimal(10,2) NOT NULL,
  `trial_limit` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `startdate` datetime NOT NULL,
  `enddate` datetime DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `membership_id` (`membership_id`),
  KEY `modified` (`modified`),
  KEY `code_id` (`code_id`),
  KEY `enddate` (`enddate`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1036 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_pmpro_memberships_users`
--

LOCK TABLES `wptest_pmpro_memberships_users` WRITE;
/*!40000 ALTER TABLE `wptest_pmpro_memberships_users` DISABLE KEYS */;
INSERT INTO `wptest_pmpro_memberships_users` VALUES (1,4,1,0,0.00,0.00,0,'Month',0,0.00,0,'admin_cancelled','2018-06-22 00:00:00',NULL,'2018-07-13 21:48:03');
/*!40000 ALTER TABLE `wptest_pmpro_memberships_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_postmeta`
--

DROP TABLE IF EXISTS `wptest_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=11097 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_postmeta`
--

LOCK TABLES `wptest_postmeta` WRITE;
/*!40000 ALTER TABLE `wptest_postmeta` DISABLE KEYS */;
INSERT INTO `wptest_postmeta` VALUES (1,2,'_wptest_page_template','default');
/*!40000 ALTER TABLE `wptest_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_posts`
--

DROP TABLE IF EXISTS `wptest_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_title` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_excerpt` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `post_password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `post_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `to_ping` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `pinged` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=6720 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_posts`
--

LOCK TABLES `wptest_posts` WRITE;
/*!40000 ALTER TABLE `wptest_posts` DISABLE KEYS */;
INSERT INTO `wptest_posts` VALUES (1,1,'2018-07-07 14:52:49','2018-07-07 14:52:49','Welcome to WordPress. This is your first post. Edit or delete it, then start writing!','Hello world!','','publish','open','open','','hello-world','','','2018-07-07 14:52:49','2018-07-07 14:52:49','',0,'http://clean.local/?p=1',0,'post','',1),(2,1,'2018-07-07 14:52:49','2018-07-07 14:52:49','This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\n\n<blockquote>Hi there! I\'m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin\' caught in the rain.)</blockquote>\n\n...or something like this:\n\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</blockquote>\n\nAs a new WordPress user, you should go to <a href=\"http://clean.local/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!','Sample Page','','publish','closed','open','','sample-page','','','2018-07-07 14:52:49','2018-07-07 14:52:49','',0,'http://clean.local/?page_id=2',0,'page','',0);
/*!40000 ALTER TABLE `wptest_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_term_relationships`
--

DROP TABLE IF EXISTS `wptest_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_term_relationships`
--

LOCK TABLES `wptest_term_relationships` WRITE;
/*!40000 ALTER TABLE `wptest_term_relationships` DISABLE KEYS */;
INSERT INTO `wptest_term_relationships` VALUES (1,1,0),(19,1,0),(39,2,0),(54,1,0),(57,3,0),(59,3,0),(60,3,0),(73,5,0),(73,18,0),(89,1,0),(101,5,0),(101,18,0),(105,5,0),(105,19,0),(1397,5,0),(1397,20,0),(1398,5,0),(1398,20,0),(1399,5,0),(1399,20,0),(1400,5,0),(1400,20,0),(1401,5,0),(1401,20,0),(1404,5,0),(1404,20,0),(1404,21,0),(1405,5,0),(1405,20,0),(1405,21,0),(1407,5,0),(1407,20,0),(1408,5,0),(1408,20,0),(1409,5,0),(1409,22,0),(1410,5,0),(1410,22,0),(1411,5,0),(1411,22,0),(1411,23,0),(1412,5,0),(1412,22,0),(1413,5,0),(1413,22,0),(1414,5,0),(1414,22,0),(1415,5,0),(1415,22,0),(1416,5,0),(1416,22,0),(1417,5,0),(1417,22,0),(1418,5,0),(1418,22,0),(1419,5,0),(1419,22,0),(1420,5,0),(1420,22,0),(1421,5,0),(1421,24,0),(1421,25,0),(1422,5,0),(1422,24,0),(1422,25,0),(1423,5,0),(1423,24,0),(1423,25,0),(1424,5,0),(1424,24,0),(1424,25,0),(1425,5,0),(1425,24,0),(1425,25,0),(1426,5,0),(1426,24,0),(1426,25,0),(1427,5,0),(1427,24,0),(1427,25,0),(1428,5,0),(1428,24,0),(1428,25,0),(1431,5,0),(1431,25,0),(1432,5,0),(1432,25,0),(1433,5,0),(1433,25,0),(1434,5,0),(1434,25,0),(1435,5,0),(1435,25,0),(1436,5,0),(1436,25,0),(1437,5,0),(1437,25,0),(1438,5,0),(1438,25,0),(1525,5,0),(1525,26,0),(1526,5,0),(1526,26,0),(1529,5,0),(1529,26,0),(1531,5,0),(1531,26,0),(1532,5,0),(1532,26,0),(1533,5,0),(1533,26,0),(1534,5,0),(1534,26,0),(1535,5,0),(1535,26,0),(1536,5,0),(1536,26,0),(1537,5,0),(1537,26,0),(1538,5,0),(1538,26,0),(1539,5,0),(1539,26,0),(1540,5,0),(1540,26,0),(1541,5,0),(1541,26,0),(1542,5,0),(1542,26,0),(1543,5,0),(1543,26,0),(1544,5,0),(1544,26,0),(1545,5,0),(1545,26,0),(1546,5,0),(1546,26,0),(1547,5,0),(1547,26,0),(1548,5,0),(1548,26,0),(1549,5,0),(1549,26,0),(1550,5,0),(1550,26,0),(1551,5,0),(1551,26,0),(1552,5,0),(1552,26,0),(1553,5,0),(1553,26,0),(1554,5,0),(1554,26,0),(1555,5,0),(1555,26,0),(1556,5,0),(1556,26,0),(1557,5,0),(1557,26,0),(1558,5,0),(1558,26,0),(1559,5,0),(1559,26,0),(1560,5,0),(1560,26,0),(1561,5,0),(1561,26,0),(1562,5,0),(1562,26,0),(1564,5,0),(1564,26,0),(1565,5,0),(1565,26,0),(1566,5,0),(1566,26,0),(1567,5,0),(1567,26,0),(1568,5,0),(1568,26,0),(1571,5,0),(1571,26,0),(1572,5,0),(1572,26,0),(1573,5,0),(1573,26,0),(1574,5,0),(1574,26,0),(1575,5,0),(1575,26,0),(1576,5,0),(1576,26,0),(1577,5,0),(1577,26,0),(1578,5,0),(1578,26,0),(1579,5,0),(1579,26,0),(1580,5,0),(1580,26,0),(1581,5,0),(1581,26,0),(1582,5,0),(1582,26,0),(1584,5,0),(1584,26,0),(1585,5,0),(1585,26,0),(1586,5,0),(1586,26,0),(1587,5,0),(1587,26,0),(1588,5,0),(1588,26,0),(1589,5,0),(1589,26,0),(1590,5,0),(1590,26,0),(1591,5,0),(1591,26,0),(1592,5,0),(1592,26,0),(1593,5,0),(1593,26,0),(1594,5,0),(1594,26,0),(1595,5,0),(1595,26,0),(1596,5,0),(1596,26,0),(1597,5,0),(1597,26,0),(1598,5,0),(1598,26,0),(1599,5,0),(1599,26,0),(1600,5,0),(1600,26,0),(1601,5,0),(1601,26,0),(1602,5,0),(1602,27,0),(1603,5,0),(1603,27,0),(2429,5,0),(2429,20,0),(2430,5,0),(2430,26,0),(2431,5,0),(2431,26,0),(2432,5,0),(2432,26,0),(2433,5,0),(2433,26,0),(2816,5,0),(2816,28,0),(2817,5,0),(2817,28,0),(2825,5,0),(2825,26,0),(2827,5,0),(2827,26,0),(2920,7,0),(2920,29,0),(2929,7,0),(2929,29,0),(3113,5,0),(3113,30,0),(3113,31,0),(3120,5,0),(3120,31,0),(3120,32,0),(3249,5,0),(3249,33,0),(3253,5,0),(3253,33,0),(3254,5,0),(3254,33,0),(3449,7,0),(3449,29,0),(3453,5,0),(3453,26,0),(3454,5,0),(3454,26,0),(3456,5,0),(3456,26,0),(3457,5,0),(3457,26,0),(3515,5,0),(3515,34,0),(3529,5,0),(3529,34,0),(3530,5,0),(3530,31,0),(3530,32,0),(3531,5,0),(3531,31,0),(3531,35,0),(3532,5,0),(3532,31,0),(3532,35,0),(3533,5,0),(3533,31,0),(3533,35,0),(3539,5,0),(3539,30,0),(3539,31,0),(3543,5,0),(3543,31,0),(3543,36,0),(3544,5,0),(3544,31,0),(3544,37,0),(3546,5,0),(3546,31,0),(3546,38,0),(3547,5,0),(3547,31,0),(3547,39,0),(3550,5,0),(3550,31,0),(3550,40,0),(3552,5,0),(3552,31,0),(3552,41,0),(3772,7,0),(3772,29,0),(3794,7,0),(3794,29,0),(3924,7,0),(3924,42,0),(4961,5,0),(4961,43,0),(4992,5,0),(4992,43,0),(4998,5,0),(4998,43,0),(5000,5,0),(5000,43,0),(5004,5,0),(5004,43,0),(5005,5,0),(5005,43,0),(5007,5,0),(5007,43,0),(5008,5,0),(5008,43,0),(5019,5,0),(5019,43,0),(5022,5,0),(5022,43,0),(5023,5,0),(5023,43,0),(5025,5,0),(5025,43,0),(5030,5,0),(5030,43,0),(5033,5,0),(5033,43,0),(5034,5,0),(5034,43,0),(5036,5,0),(5036,43,0),(5037,5,0),(5037,43,0),(5038,5,0),(5038,43,0),(5179,5,0),(5179,43,0),(5226,5,0),(5226,44,0),(5312,5,0),(5312,45,0),(5313,5,0),(5313,45,0),(5314,5,0),(5314,45,0),(5315,5,0),(5315,45,0),(5316,5,0),(5316,45,0),(5317,5,0),(5317,45,0),(5318,5,0),(5318,45,0),(5320,5,0),(5320,45,0),(5328,5,0),(5328,46,0),(5334,5,0),(5334,46,0),(5337,5,0),(5337,46,0),(5345,5,0),(5345,46,0),(5347,5,0),(5347,46,0),(5356,5,0),(5356,46,0),(5362,5,0),(5362,46,0),(5379,5,0),(5379,46,0),(5382,5,0),(5382,46,0),(5435,5,0),(5435,46,0),(5443,5,0),(5443,46,0),(5449,5,0),(5449,46,0),(5451,5,0),(5451,46,0),(5457,5,0),(5457,46,0),(5513,7,0),(5513,29,0),(5513,47,0),(5513,48,0),(5513,49,0),(5513,50,0),(5538,7,0),(5538,29,0),(5538,47,0),(5538,48,0),(5538,49,0),(5538,50,0),(5572,7,0),(5572,29,0),(5572,47,0),(5572,48,0),(5572,49,0),(5572,50,0),(5578,7,0),(5578,29,0),(5578,47,0),(5578,48,0),(5578,49,0),(5578,50,0),(5582,7,0),(5582,29,0),(5582,47,0),(5582,48,0),(5582,49,0),(5582,50,0),(5589,7,0),(5589,51,0),(5589,52,0),(5631,5,0),(5631,51,0),(5631,52,0),(5646,7,0),(5646,29,0),(5646,47,0),(5646,48,0),(5646,49,0),(5646,50,0),(5914,5,0),(5914,31,0),(5914,53,0),(5917,5,0),(5917,31,0),(5917,54,0),(5940,5,0),(5940,31,0),(5940,36,0),(5987,5,0),(5987,22,0),(6207,5,0),(6207,46,0),(6485,5,0),(6485,25,0),(6500,5,0),(6500,43,0),(6541,5,0),(6541,55,0),(6588,5,0),(6588,55,0),(6627,7,0),(6627,51,0),(6627,56,0),(6685,5,0),(6685,24,0),(6685,25,0),(6686,5,0),(6686,24,0),(6686,25,0);
/*!40000 ALTER TABLE `wptest_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_term_taxonomy`
--

DROP TABLE IF EXISTS `wptest_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_term_taxonomy`
--

LOCK TABLES `wptest_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wptest_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wptest_term_taxonomy` VALUES (1,1,'category','',0,3),(2,2,'seq_type','',0,1),(3,3,'e20r_email_type','Payment Warning Message types for PMPro Member notifications',0,3),(5,5,'product_type','',0,197),(6,6,'product_type','',0,0),(7,7,'product_type','',0,14),(8,8,'product_type','',0,0),(9,9,'product_visibility','',0,0),(10,10,'product_visibility','',0,0),(11,11,'product_visibility','',0,0),(12,12,'product_visibility','',0,0),(13,13,'product_visibility','',0,0),(14,14,'product_visibility','',0,0),(15,15,'product_visibility','',0,0),(16,16,'product_visibility','',0,0),(17,17,'product_visibility','',0,0),(18,18,'product_cat','',0,2),(19,19,'product_cat','',0,1),(20,20,'product_cat','',0,10),(21,21,'product_cat','',0,2),(22,22,'product_cat','',0,13),(23,23,'product_cat','',0,1),(24,24,'product_cat','',0,10),(25,25,'product_cat','',0,19),(26,26,'product_cat','',0,80),(27,27,'product_cat','',0,2),(28,28,'product_cat','',0,2),(29,29,'product_cat','',0,11),(30,30,'product_cat','',0,2),(31,31,'product_tag','',0,16),(32,32,'product_cat','',0,2),(33,33,'product_cat','',0,3),(34,34,'product_cat','',0,2),(35,35,'product_cat','',0,3),(36,36,'product_cat','',0,2),(37,37,'product_cat','',0,1),(38,38,'product_cat','',0,1),(39,39,'product_cat','',0,1),(40,40,'product_cat','',0,1),(41,41,'product_cat','',0,1),(42,42,'product_cat','',0,1),(43,43,'product_cat','',0,20),(44,44,'product_cat','',0,1),(45,45,'product_cat','',0,8),(46,46,'product_cat','',0,15),(47,47,'product_cat','',0,6),(48,48,'pa_ticket-type','',0,6),(49,49,'pa_ticket-type','',0,6),(50,50,'pa_ticket-type','',0,6),(51,51,'product_cat','',0,3),(52,52,'product_cat','',51,2),(53,53,'product_cat','',0,1),(54,54,'product_cat','',0,1),(55,55,'product_cat','',0,2),(56,56,'product_cat','',51,1),(57,57,'membership','',0,0),(58,58,'membership','Test Level #2',0,0),(59,59,'membership','Test Level #3',0,0),(60,60,'membership','Test Level #4',0,0),(61,61,'membership','Law Students 1-2',0,0),(62,62,'membership','Law Students 1-2',0,0),(63,63,'membership','Retired Legal Professionals',0,0),(64,64,'membership','Law Students 3-4',0,0),(65,65,'membership','Legal Support',0,0),(66,66,'membership','Complimentary',0,0),(67,67,'membership','Not Applicable (Deleted)',0,0),(68,68,'membership','Admin Support',0,0),(69,69,'membership','Purchaser',0,0),(70,70,'product_cat','PMPro Membership Products',0,0);
/*!40000 ALTER TABLE `wptest_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_termmeta`
--

DROP TABLE IF EXISTS `wptest_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_termmeta`
--

LOCK TABLES `wptest_termmeta` WRITE;
/*!40000 ALTER TABLE `wptest_termmeta` DISABLE KEYS */;
INSERT INTO `wptest_termmeta` VALUES (3,19,'order','0'),(4,19,'display_type',''),(5,19,'thumbnail_id','0'),(6,19,'product_count_product_cat','1'),(7,20,'order','0'),(8,21,'order','0'),(9,20,'product_count_product_cat','10'),(10,21,'product_count_product_cat','2'),(11,22,'order','0'),(12,23,'order','0'),(13,22,'product_count_product_cat','13'),(14,23,'product_count_product_cat','1'),(15,24,'order','0'),(16,25,'order','0'),(17,24,'product_count_product_cat','10'),(18,25,'product_count_product_cat','19'),(19,26,'order','0'),(20,26,'product_count_product_cat','80'),(21,27,'order','0'),(22,28,'order','0'),(23,27,'product_count_product_cat','2'),(24,28,'product_count_product_cat','2'),(25,29,'order','0'),(26,30,'order','0'),(27,32,'order','0'),(28,33,'order','0'),(29,29,'product_count_product_cat','11'),(30,30,'product_count_product_cat','2'),(31,32,'product_count_product_cat','2'),(32,33,'product_count_product_cat','3'),(33,31,'product_count_product_tag','16'),(34,34,'order','0'),(35,35,'order','0'),(36,34,'product_count_product_cat','2'),(37,35,'product_count_product_cat','3'),(38,36,'order','0'),(39,37,'order','0'),(40,38,'order','0'),(41,39,'order','0'),(42,40,'order','0'),(43,41,'order','0'),(44,42,'order','0'),(45,43,'order','0'),(46,36,'product_count_product_cat','2'),(47,37,'product_count_product_cat','1'),(48,38,'product_count_product_cat','1'),(49,39,'product_count_product_cat','1'),(50,40,'product_count_product_cat','1'),(51,41,'product_count_product_cat','1'),(52,42,'product_count_product_cat','1'),(53,43,'product_count_product_cat','20'),(54,44,'order','0'),(55,45,'order','0'),(56,44,'product_count_product_cat','1'),(57,45,'product_count_product_cat','8'),(58,46,'order','0'),(59,46,'product_count_product_cat','15'),(60,47,'order','0'),(61,51,'order','0'),(62,52,'order','0'),(63,53,'order','0'),(64,47,'product_count_product_cat','6'),(65,51,'product_count_product_cat','3'),(66,52,'product_count_product_cat','2'),(67,53,'product_count_product_cat','1'),(68,54,'order','0'),(69,55,'order','0'),(70,56,'order','0'),(71,54,'product_count_product_cat','1'),(72,55,'product_count_product_cat','2'),(73,56,'product_count_product_cat','1'),(74,18,'product_count_product_cat','2');
/*!40000 ALTER TABLE `wptest_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_terms`
--

DROP TABLE IF EXISTS `wptest_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `slug` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_terms`
--

LOCK TABLES `wptest_terms` WRITE;
/*!40000 ALTER TABLE `wptest_terms` DISABLE KEYS */;
INSERT INTO `wptest_terms` VALUES (1,'Uncategorized','uncategorized',0),(2,'Member Sequences','member-sequences',0),(3,'E20R Payment Warning Notices','e20r-pw-notices',0),(5,'simple','simple',0),(6,'grouped','grouped',0),(7,'variable','variable',0),(8,'external','external',0),(9,'exclude-from-search','exclude-from-search',0),(10,'exclude-from-catalog','exclude-from-catalog',0),(11,'featured','featured',0),(12,'outofstock','outofstock',0),(13,'rated-1','rated-1',0),(14,'rated-2','rated-2',0),(15,'rated-3','rated-3',0),(16,'rated-4','rated-4',0),(17,'rated-5','rated-5',0),(18,'Uncategorized','uncategorized',0),(19,'Test Category','test-category',0),(20,'Memberships','memberships',0),(21,'Students','students',0),(22,'Sections','sections',0),(23,'Barristers Club','barristers-club',0),(24,'Standing Committees','standing-committees',0),(25,'Committees','committees',0),(26,'Practice Areas','practice-areas',0),(27,'Hotlinks','hotlinks',0),(28,'Donations','donations',0),(29,'General Law','general-law',0),(30,'Civil (General) Law','civil-general-law',0),(31,'seminar publications','seminar-publications',0),(32,'Business Law','business-law',0),(33,'MCLE Online Courses','mcle-online-courses',0),(34,'Bankruptcy Law','bankruptcy-law',0),(35,'Cannabis Law','cannabis-law',0),(36,'Family Law','family-law',0),(37,'Fire Related Topics','fire-related-topics',0),(38,'Labor &amp; Employment','labor-employment',0),(39,'Land Use / Real Property','land-use-real-property',0),(40,'Landlord / Tenant','landlord-tenant',0),(41,'Trust &amp; Estates','trust-estates',0),(42,'Competence Issues','competence-issues',0),(43,'Fee Arb &amp; LRS Documents','fee-arb-lrs-documents',0),(44,'Registration Documents','registration-documents',0),(45,'Bar Journal &amp; Ad Rates','bar-journal-ad-rates',0),(46,'Resources','resources',0),(47,'MCLE Courses','mcle-courses',0),(48,'Law Student','law-student',0),(49,'Public','public',0),(50,'SCBA Member','scba-member',0),(51,'Events','events',0),(52,'SCBA Award Events','events-scba-award-events',0),(53,'General Interest','general-interest',0),(54,'Litigation','litigation',0),(55,'Downloads','downloads',0),(56,'SCBA Social Events','events-scba-social-events',0),(57,'Attorneys 6','attorneys-6',0),(58,'Attorneys 1-5','attorneys-1-5',0),(59,'Attorneys - New','attorneys-new',0),(60,'Attorneys - Govt','attorneys-govt',0),(61,'Associate','associate',0),(62,'Law Students 1-2','law-students-1-2',0),(63,'Retired Legal Professionals','retired-legal-professionals',0),(64,'Law Students 3-4','law-students-3-4',0),(65,'Legal Support','legal-support',0),(66,'Complimentary','complimentary',0),(67,'Not Applicable (Deleted)','not-applicable-deleted',0),(68,'Admin Support','admin-support',0),(69,'Purchaser','purchaser',0),(70,'Membership','membership',0);
/*!40000 ALTER TABLE `wptest_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_usermeta`
--

DROP TABLE IF EXISTS `wptest_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=41297 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_usermeta`
--

LOCK TABLES `wptest_usermeta` WRITE;
/*!40000 ALTER TABLE `wptest_usermeta` DISABLE KEYS */;
INSERT INTO `wptest_usermeta` VALUES (1,1,'nickname','admin'),(2,1,'first_name','Thomas'),(3,1,'last_name','Sjolshagen'),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wptest_capabilities','a:2:{s:13:\"administrator\";b:1;s:10:\"e20r_coach\";b:1;}'),(13,1,'wptest_user_level','10'),(14,1,'dismissed_wptest_pointers','wp496_privacy,plugin_editor_notice,pmpro_v2_menu_moved'),(15,1,'show_welcome_panel','0'),(16,1,'session_tokens','a:1:{s:64:\"f14a3a7a812544acbbb12aa7f5d925a15b147b6d1e1eeab0d102f0ba1237bc87\";a:4:{s:10:\"expiration\";i:1550597606;s:2:\"ip\";s:12:\"192.168.1.87\";s:2:\"ua\";s:120:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.81 Safari/537.36\";s:5:\"login\";i:1549388006;}}'),(17,1,'wptest_user-settings','libraryContent=browse&editor=tinymce'),(18,1,'wptest_user-settings-time','1547759236'),(19,1,'wptest_dashboard_quick_press_last_post_id','6714'),(20,1,'pmpro_visits','a:10:{s:4:\"last\";s:10:\"01/03/2019\";s:8:\"thisdate\";s:10:\"2019-03-01\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"1\";s:7:\"alltime\";i:9;s:5:\"today\";i:0;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"01\";s:3:\"ytd\";i:1;s:8:\"thisyear\";s:4:\"2019\";}'),(21,1,'pmpro_views','a:10:{s:4:\"last\";s:10:\"2019-02-06\";s:5:\"month\";i:6;s:7:\"alltime\";i:88;s:9:\"thismonth\";s:1:\"2\";s:5:\"today\";i:0;s:8:\"thisdate\";s:10:\"2019-06-02\";s:4:\"week\";i:5;s:8:\"thisweek\";s:2:\"06\";s:3:\"ytd\";i:29;s:8:\"thisyear\";s:4:\"2019\";}'),(22,1,'closedpostboxes_post','a:0:{}'),(23,1,'metaboxhidden_post','a:0:{}'),(24,1,'pmpro_logins','a:10:{s:4:\"last\";s:10:\"2019-02-05\";s:8:\"thisdate\";s:10:\"2019-05-02\";s:5:\"month\";i:1;s:9:\"thismonth\";s:1:\"2\";s:7:\"alltime\";i:20;s:5:\"today\";i:0;s:4:\"week\";i:1;s:8:\"thisweek\";s:2:\"06\";s:3:\"ytd\";i:5;s:8:\"thisyear\";s:4:\"2019\";}'),(25,1,'_e20r-tracker-last-login','1531257082'),(54,1,'gform_recent_forms','a:2:{i:0;s:1:\"1\";i:1;s:1:\"2\";}');
/*!40000 ALTER TABLE `wptest_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wptest_users`
--

DROP TABLE IF EXISTS `wptest_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wptest_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_pass` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_nicename` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_url` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wptest_users`
--

LOCK TABLES `wptest_users` WRITE;
/*!40000 ALTER TABLE `wptest_users` DISABLE KEYS */;
INSERT INTO `wptest_users` VALUES (1,'admin','','admin-2','thomas@localhost.local','','2018-07-07 14:52:49','',0,'admin');
/*!40000 ALTER TABLE `wptest_users` ENABLE KEYS */;
UNLOCK TABLES;

UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-02-10 12:24:35
