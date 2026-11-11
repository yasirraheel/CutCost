-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: cutcost
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
-- Table structure for table `kyc_submissions`
--

DROP TABLE IF EXISTS `kyc_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kyc_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `front_path` varchar(255) NOT NULL,
  `back_path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'under_review',
  `admin_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kyc_submissions_user_id_unique` (`user_id`),
  KEY `kyc_submissions_reviewed_by_foreign` (`reviewed_by`),
  KEY `kyc_submissions_status_index` (`status`),
  CONSTRAINT `kyc_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kyc_submissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_submissions`
--

LOCK TABLES `kyc_submissions` WRITE;
/*!40000 ALTER TABLE `kyc_submissions` DISABLE KEYS */;
INSERT INTO `kyc_submissions` VALUES (2,2,'kyc/2/UcnuqFieEJ2GYBZBhEfCDPV2PJ9LFT2Pm5eoH0jU.jpg','kyc/2/YrgOXq9hnVFkOMWYkIOcCzRKiz2Xgfub2wFbTKQv.jpg','under_review',NULL,NULL,NULL,'2026-11-10 13:07:26','2026-11-10 13:07:26','2026-11-10 13:07:26');
/*!40000 ALTER TABLE `kyc_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_10_000003_add_role_to_users_table',1),(5,'2026_11_10_000004_add_profile_fields_to_users_table',2),(6,'2026_11_10_000005_create_kyc_submissions_table',3),(7,'2026_11_10_000006_add_settings_fields_to_users_table',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `intl_phone` varchar(50) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `zip` varchar(30) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `referral_code` varchar(255) DEFAULT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `payout_low_amount` tinyint(1) NOT NULL DEFAULT 0,
  `payout_convert_currency` tinyint(1) NOT NULL DEFAULT 1,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo User','Demo','User','demo-user','1990-01-01','+44 7000000000','+44 7000000000','1 Demo Street',NULL,'London','SW1A 1AA','London','GB',NULL,'en',0,1,'demo@example.com',NULL,'$2y$12$VmkOREvpVgwblKZSZpa57uohSqjbNOAkhKRERtx9uJToVtK9KsKzu','user','Ow5nMQlZleWwKvW0738AbgbAWd4CXk9CI80RKTCba6FL90FC6yVJIkunflzf','2026-11-10 11:56:44','2026-11-10 13:15:32'),(2,'Admin Demo','Admin','Demo','admin','2000-01-01','03015636544','+923015636544','dfsdfg','fgdf','dfgdfg','SW1A 1AA','sdtsdg','GB',NULL,'en',0,1,'admin@example.com',NULL,'$2y$12$SAxvEc.Ivx8QpdUxJWq3hOCqkfqVuAJPE6MhZB06rBHhGRvCqYx1G','admin','Lntt4GKe9NMvbWhLOeIpJiZNevNYzh6zXc2nW48ApHkJGNRFM9Lx1YgN3m1v','2026-11-10 11:56:44','2026-11-10 12:53:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'cutcost'
--

--
-- Dumping routines for database 'cutcost'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-11  6:19:22
