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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_submissions`
--

LOCK TABLES `kyc_submissions` WRITE;
/*!40000 ALTER TABLE `kyc_submissions` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_10_000003_add_role_to_users_table',1),(5,'2026_11_10_000004_add_profile_fields_to_users_table',2),(6,'2026_11_10_000005_create_kyc_submissions_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('0VL0XVbuJnHS4h8ydD2jVy6wMOdS1ZvEvldvwTvy',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIxWWl1anpHVDlYdDQybml6amNlOGpXZTQ0OUtZVE1Lak10VW5CU0d1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794332217),('1eWczhX0wONWehf48P9md1iby7Ykp3MkvfmfebQ8',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJhR29aalVxcU1KYzVkdm5uS1RZNEhtbzVRcVF1ZDQ2WHdsZWZxeE1HIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2VuXC9teS1jaGF0XC9zdWJzY3JpcHRpb25zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvZW5cL215LWNoYXRcL3N1YnNjcmlwdGlvbnMiLCJyb3V0ZSI6InVzZXIuY2hhdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794332217),('3coCGrYVHHqVrGWj5bRjjbhVIBCoSQOIlFETqYBD',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJiUXBlWVlrZXY3YXQxc0FHU0p2Wkx4ZFNWeG5IMm9RR1ByNkFLR1NLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794332225),('4pkQCiw6up4zw84JpOCyUvi9cX7caoTYCoJDfWRe',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJvanQxR01hdEZpS1lBZGRkQk84cksyT2ZvcVVNNTc0dXhWdG14U3hYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332780),('6xmZ2oyv5fGaB4JuLWwpKQlEfBwEA0ezHHhXocQG',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIzeG5sZ3dybVJWeFJhSGZTZzJZQVZiZ0RCVUF5a0NHWHJhNVJGeUt1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332820),('7fxaEnLUE6i6PjneJI0EY5MSq38aBXBMagJERiOC',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJDT3FxSU9UbDFGOVgyRkdtU0Q0dGJibUlYaHJLMk9TWHA1OE5NbzIzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332735),('bHMW1UpkRM3o4qMlJCpL0TxwBcUL5CNccbXNKylh',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJ3MVNZSGNPa1ZnQ3N3eTUzM1AyUjhDdUo2cEg1UWl2YjF1SDdrSE5KIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudFwvaWRlbnRpdHkiLCJyb3V0ZSI6InVzZXIuaWRlbnRpdHkifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiI0OWYzMWNmMDJjN2YzNjQ1OTBkMDhmYzNjNGY1NTYyOWE2Mjk1OTRjZDVmNWRiMzM0MzU5NTc5NmNiN2E1YTY2In0=',1794333837),('fSI6bM2iJSf44b36duGUiDj3wGhIfbuw73K4QWVl',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiI2bXBxQWNKVzNHeXM2cG41ZUZmWmpkamQ0TUpIQnpTMlZweFJRRmdTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudFwvaWRlbnRpdHkiLCJyb3V0ZSI6InVzZXIuaWRlbnRpdHkifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiI0OWYzMWNmMDJjN2YzNjQ1OTBkMDhmYzNjNGY1NTYyOWE2Mjk1OTRjZDVmNWRiMzM0MzU5NTc5NmNiN2E1YTY2In0=',1794333773),('fVvVzLVmkWkfP4FWXZR1ceBAyjff2uJlSS5vt4Vi',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJZdXpKSXRSSWhTeTBSYWQ4VFJIUm9nVG5QWmYzV3l2UjVNQlpwQmdTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794332249),('iRAWeTQYqD8UBIYbbi0FC693QwS2MU2wnsckW3jt',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJLSUIxVW1ldHJ3aUJ6cUZuTGNpR1hQazNpVXE4emZ5WFlOenFSSjdtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvYWRtaW5cL2t5YyIsInJvdXRlIjoiYWRtaW4ua3ljIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInBhc3N3b3JkX2hhc2hfd2ViIjoiNDlmMzFjZjAyYzdmMzY0NTkwZDA4ZmMzYzRmNTU2MjlhNjI5NTk0Y2Q1ZjVkYjMzNDM1OTU3OTZjYjdhNWE2NiJ9',1794333761),('j9FX46bpEMczJ6OANUor9rGlAYxekBLL7smAvjOX',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJmOWREU1RkSVBMREJpZTdNZkxxRjg4N2Zxb2RJQ29MT1RnOHRHTjN1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiI0OWYzMWNmMDJjN2YzNjQ1OTBkMDhmYzNjNGY1NTYyOWE2Mjk1OTRjZDVmNWRiMzM0MzU5NTc5NmNiN2E1YTY2In0=',1794333373),('jmCLjcS30Uzqmd19Infe3TmTQj4TmBSUhvFuLguQ',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJZMHZpR3dpRVRuaTd3Z0R6SGp0V21mUFUxalRkS2dqWXFjd1h5aGVsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794332217),('joNk4yGUST63ywLmHsT1Oa7WkhI1AvNcHPF8Nat9',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJCVm91a1RvWFlOc2U5aFFLWnZlY0o5Qk02OEdmeDY2V0Zlc0VLZktEIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2VuXC9icmFuZFwvYW1hem9uLmh0bWwiLCJyb3V0ZSI6bnVsbH0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoyLCJwYXNzd29yZF9oYXNoX3dlYiI6IjhmZjQxMzY5NjMwNDBiMDk4NWMwN2M5MjQzMzc1ODFlODgyNmY2NWE4Y2UwOTc0M2QwODBmMjk5NTdkYTIwNjMifQ==',1794333652),('keuOc50tOqfcW1UDpttryq2sBicOTUQzzkcLAVK3',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJZUFVnRnVPQjZpWXo5aExYdjNCR3dhUUFzTGdySHVJZEd1VW96TkhTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvYWRtaW5cL2t5YyIsInJvdXRlIjoiYWRtaW4ua3ljIn0sIl9mbGFzaCI6eyJvbGQiOlsic3RhdHVzIl0sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MiwicGFzc3dvcmRfaGFzaF93ZWIiOiI4ZmY0MTM2OTYzMDQwYjA5ODVjMDdjOTI0MzM3NTgxZTg4MjZmNjVhOGNlMDk3NDNkMDgwZjI5OTU3ZGEyMDYzIiwic3RhdHVzIjoiSWRlbnRpdHkgYXBwcm92ZWQgc3VjY2Vzc2Z1bGx5LiJ9',1794333828),('lU5WNe2kIlGX81lG2OtImY5wuCZNFku9X81q5hDv',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJ1YXV2dHVBWGN2RFVPS25aRUFHaEpPcldoSER4Y2l3ejhjUExIQk1oIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvYWRtaW5cL2t5YyIsInJvdXRlIjoiYWRtaW4ua3ljIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjIsInBhc3N3b3JkX2hhc2hfd2ViIjoiOGZmNDEzNjk2MzA0MGIwOTg1YzA3YzkyNDMzNzU4MWU4ODI2ZjY1YThjZTA5NzQzZDA4MGYyOTk1N2RhMjA2MyJ9',1794333803),('nJ6QDIoUb5uGTvOGyAXxZNkEC6a5LbbGSjf1Ws8K',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJnU1R6dVdqQ3lRTTFtNU53aXl6R2o3WUx4SFc0REg1Z0V0dG5TczRpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332686),('oah2UR7uLJNN7uvkTBXhbN0YkYQhCeh2dWbcewzA',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJndFA2SDZpbTNOZWhyY0l4Z2Y2R2dZbnNoY2tCVTRuZE5HOUpXU0V6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudFwvaWRlbnRpdHkiLCJyb3V0ZSI6InVzZXIuaWRlbnRpdHkifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiI0OWYzMWNmMDJjN2YzNjQ1OTBkMDhmYzNjNGY1NTYyOWE2Mjk1OTRjZDVmNWRiMzM0MzU5NTc5NmNiN2E1YTY2In0=',1794333787),('Ok77Aav0ZQi4mretwIkm4hdcarKbjI1kaMzn8p8H',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJWUmlWR0E1eUtyR2JyMWtyWlNwcTRlYkVqQTdyVXhEU2tTaDFKeTh5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332770),('PJT9iPkcTVGyB7g4o2m4MsAcPj7jfX7ZPUUB3n8m',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiI4a2E3Z3M5YVlOdklybGs0cGpadk9weWdvOTd0Wm5aRk15RHU2c3BzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sInVybCI6eyJpbnRlbmRlZCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvYWRtaW5cL2t5YyJ9fQ==',1794333787),('RE8akJeoTp2LgFxMjLD4sqNwalm7JCUgVmgZHFL6',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJwN1lRZU9EeFZYTGwzdEVTSXBRNzFnZExDcXc3cmp1blcyUDRyNGpkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiI0OWYzMWNmMDJjN2YzNjQ1OTBkMDhmYzNjNGY1NTYyOWE2Mjk1OTRjZDVmNWRiMzM0MzU5NTc5NmNiN2E1YTY2In0=',1794333365),('SUcyGhwu0PGlNLykn3AYT8NLBln4IGBfhbDhyNfS',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIyUk1aaERma1ZsV3M3VGd2VVhoUlVuTzRZV01yamluaWpZTnEzbGx3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sInVybCI6eyJpbnRlbmRlZCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvYWRtaW5cL2t5YyJ9fQ==',1794333742),('vfdYN815Idic0oe66nom6SHoNkuCM9VQ7YM69Vkp',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJNN29hTE1ZVWpoNTNobzlnREJoSVNVSnpIU2dxaGRRU3VBQ2JLb01OIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudFwvaWRlbnRpdHkiLCJyb3V0ZSI6InVzZXIuaWRlbnRpdHkifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiI0OWYzMWNmMDJjN2YzNjQ1OTBkMDhmYzNjNGY1NTYyOWE2Mjk1OTRjZDVmNWRiMzM0MzU5NTc5NmNiN2E1YTY2In0=',1794333734),('WPYamzLtqsTxswTcxDFe7DrZrtazIpqMvShI7Qd3',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJIbXhxRGlpTTNYTkFvY21WbEJqdFo0VEc0UkNCTUt6dnc2VVJQcG8zIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332788),('Xl6CpATZZblBAROQgDKL2jL8JcF0uJBgYXDjrBNY',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJzN1ZNY2M1dHVpR1N4bmppYW54aTRnTUd2MktiRE1WZWE4dXQwaWpVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332829),('xXcp04ht1OXQrVeIXfsxAyMy0IIrRLY09YEZYzuk',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJBbXlDY0FnYnNCRG1vM0FDajhuZVMwdmFXUk9vdWFlV3c5VUFRYVRKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332753),('YKW4XW6dWRcgPFwhvVaanJDaU82LnvQHNwaytTg8',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJUenlzVTMyQWptQ2xWS2Z3bEJ4WEJTSE9QRXpCMUltT2JDV3hBQTVlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332672),('yxO11iAyCMEP99y8SPYMwdRgq3qSsWkm0gYf3iuq',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIxd05kOUtnUWhFb2JlM3QxMlRDMUp4bDdJOU1xODMyMklvYXMzdnJKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sInVybCI6eyJpbnRlbmRlZCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIn19',1794332809),('z002zkHOxTg2FkUHcaijn3fVbStbtz5varxKrg3O',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJVcTQ5ODQ3a29DYmJmN0pEVlNKMmFiRDZyVG90aE42ekFJSkcwRzd5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvYWRtaW5cL2t5YyIsInJvdXRlIjoiYWRtaW4ua3ljIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjIsInBhc3N3b3JkX2hhc2hfd2ViIjoiOGZmNDEzNjk2MzA0MGIwOTg1YzA3YzkyNDMzNzU4MWU4ODI2ZjY1YThjZTA5NzQzZDA4MGYyOTk1N2RhMjA2MyJ9',1794333734),('ZBvitsbCJelh92fojN5TZvKILSZKANUZGaO3Z4PG',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJsNmdieXRBeGtwSWNoeEM3NFdzM01NSXgwbE5MS0U1SEpPV1FBV1FCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktYWNjb3VudC5odG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjQ5ZjMxY2YwMmM3ZjM2NDU5MGQwOGZjM2M0ZjU1NjI5YTYyOTU5NGNkNWY1ZGIzMzQzNTk1Nzk2Y2I3YTVhNjYifQ==',1794332744);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo User','Demo','User','demo-user','1990-01-01','+44 7000000000','+44 7000000000','1 Demo Street',NULL,'London','SW1A 1AA','London','GB','demo@example.com',NULL,'$2y$12$z/tvuOH.sBHZm6Qa9Yz3Eei/pMzVTZg6BB8x.GHowVhiFLU1IQwfu','user','Ow5nMQlZleWwKvW0738AbgbAWd4CXk9CI80RKTCba6FL90FC6yVJIkunflzf','2026-11-10 11:56:44','2026-11-10 12:44:32'),(2,'Admin Demo','Admin','Demo','admin','2000-01-01','03015636544','+923015636544','dfsdfg','fgdf','dfgdfg','SW1A 1AA','sdtsdg','GB','admin@example.com',NULL,'$2y$12$SAxvEc.Ivx8QpdUxJWq3hOCqkfqVuAJPE6MhZB06rBHhGRvCqYx1G','admin','Lntt4GKe9NMvbWhLOeIpJiZNevNYzh6zXc2nW48ApHkJGNRFM9Lx1YgN3m1v','2026-11-10 11:56:44','2026-11-10 12:53:45');
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

-- Dump completed on 2026-11-10 23:04:33
