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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_10_000003_add_role_to_users_table',1);
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
INSERT INTO `sessions` VALUES ('5ZggZmSAO52sXzRY1Dv0foJ7HQGYN8v8teYLqVVB',1,'127.0.0.1','curl/8.21.0','eyJfdG9rZW4iOiJLMGNEMDY5QkZJUWJNa2U1R3R4R25kdVdnNjhuMHFJZ2E2V25BbGRBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjBjNWYzNmI3ODlkYWJkNTc2NGUzNmNhYWI4YTJiYzBhNTBlNzYzMTQyODdjYTRiNzRlYTI3NTkwZTk1MWU5MDEifQ==',1794330345),('aM2xzuXwp9hVKUfO2zd5v6138FmWl96oLIWDIk03',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJZb0szdFlxekpWVkgyeFNzSEdoNDhyVlJrV0xHTTE5TXhrSWlzUElKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvbXktY2hhdFwvc3Vic2NyaXB0aW9ucyIsInJvdXRlIjoidXNlci5jaGF0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjEsInBhc3N3b3JkX2hhc2hfd2ViIjoiMGM1ZjM2Yjc4OWRhYmQ1NzY0ZTM2Y2FhYjhhMmJjMGE1MGU3NjMxNDI4N2NhNGI3NGVhMjc1OTBlOTUxZTkwMSJ9',1794329841),('bdUxopqFJw2uFDyFVXc4FAdlGNASHBcTLuAJcLXR',NULL,'127.0.0.1','curl/8.21.0','eyJfdG9rZW4iOiI0ejlhZ1BaSjR6SlkzTVZHRTNrYVNBR0E2NHVFSFZyWGp4RHRRUVh3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794330620),('FPHgbnGecJPmglp1IltQIO5tnX9KCpK6GBwyuAbZ',NULL,'127.0.0.1','curl/8.21.0','eyJfdG9rZW4iOiI4QXcybVpza3N0ZWRnZE1iNWVUa1JqZTJrTXRtajlISFhSMGRwRHN4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794330607),('Ipp2dCFS0Ta1larulNobw71cCIbIYA566hsbJm4f',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJLTlhkbVdySklyQ1NlNjdQRWdtb0I2NnRVcExnelpsVGY5bm9WRmk1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794331982),('jOdfUBdz1NxpxqmVEEuwbTe1tZFMxy7BsqjpzfXq',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiI0emdFMFpWQmdVQVJRWVhIVFJjSnJlY0ZuUjFYemVQVWROdE9GMnVmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvZGFzaGJvYXJkIiwicm91dGUiOiJ1c2VyLmRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sInVybCI6eyJpbnRlbmRlZCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvZGFzaGJvYXJkIn19',1794330285),('KtjrZv26kOXp5PJsiyBtXkd4spzXamd1jVR7TJGI',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJodlJXdml1dUdLUXNLZUIwRTdPeTVWWVNleTQxT3c1YmpFMUJxZElvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvam9pbmVkLXN1YnNjcmlwdGlvbnMiLCJyb3V0ZSI6InVzZXIuam9pbmVkLXN1YnNjcmlwdGlvbnMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MywicGFzc3dvcmRfaGFzaF93ZWIiOiIwODRjMmE4NjQwZTI4ZWY4NWVhNzAyYWM2MzQ1ZjVmMzdmNWM2NmRlOGJjMWQ2NTExYjY3ZDFiNWU1ZDZhZTU3In0=',1794329884),('mIhz4WkH0paFMnyCJvU6i8jIKlg500lxUUvtreln',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJwY0VKMmpDWTE1UWNKRlpRYUhOeHVmZ0RqNHdMMnBwSmxJY3BkT1FZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbIl9vbGRfaW5wdXQiLCJlcnJvcnMiXSwibmV3IjpbXX0sIl9vbGRfaW5wdXQiOnsiZW1haWwiOiJ3cm9uZ0BleGFtcGxlLmNvbSJ9LCJlcnJvcnMiOnsiZGVmYXVsdCI6eyJmb3JtYXQiOiI6bWVzc2FnZSIsIm1lc3NhZ2VzIjp7ImVtYWlsIjpbIlRoZSBwcm92aWRlZCBjcmVkZW50aWFscyBhcmUgaW5jb3JyZWN0LiJdfX19fQ==',1794330216),('NttxO5lO3NcrkPDNCZmxCZzMGP6mpEKQz96AW7Ua',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJlYTVNWXZldkM4SllOWVVtbVdick9ZU1F3dmJRVEkyd0k3M3pVWWdQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luLmh0bWwiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1794330154),('oRODEoVUVReOjoeYmh9fTgY7r0PMf6SLVAaqQOLu',NULL,'127.0.0.1','curl/8.21.0','eyJfdG9rZW4iOiJwaEhCdFN4S1FIQ29SMnk3djlXN01ERWZzb2JrY3JYU0UwRTBKT0llIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2VuIiwicm91dGUiOm51bGx9fQ==',1794330676),('orZ49jyH1i7MRIJBpmhu2raqS9SekMk6cqXe7A6L',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJpd3JXZURzb1dnTVBJNm81UjlvNXE2TXhiVHBZN0JzOTlIR25wd0t1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794330286),('ox5YTjs8RmgQTFIMVClOdEfTGGsRmgFhlfib9Fn5',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI4bDVIekIyMHRnVFlUTVpyaE1tYUU3QjNCVUh0aFBHM09ueWIwRDQ2IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL25ocGUiLCJyb3V0ZSI6bnVsbH0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjBjNWYzNmI3ODlkYWJkNTc2NGUzNmNhYWI4YTJiYzBhNTBlNzYzMTQyODdjYTRiNzRlYTI3NTkwZTk1MWU5MDEifQ==',1794330788),('pp2t7urWVWMyY5Glreal4KKH6BbqhliYTOf1cjcq',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIwYTBkdTJEUk81c3hOekJ5SmZ5Y0FkRnMxZm5VV2E1SURMVlNLa0xhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794331981),('qlBWFIEHI51BwW0uQzm2CPfd3vQeLKnODQa9RvJN',NULL,'127.0.0.1','curl/8.21.0','eyJfdG9rZW4iOiJqdFBsV3BnejFLVkxwTkt5U1FxcEJ3OWROcWdPVmh2SUprVHlYS3lXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794330620),('rKLVy6GcTgFGaYGssq7QABHxRddOngh6Lj3Ka81w',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJrSjFLYTYya2FtTGtRcno5d1h4S1lRWXdSRHZTdnlKZWVqZlJnbGJUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvcmVnaXN0ZXIuaHRtbCIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794329864),('S6Rx45GBpXl6jvSbwf4DH7pIVq2aJ2yc4lRrLqO9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJhcXZpSTM4OFFiV003TVZlcTRwSzFsakNScEFoeE5UY1dBNW5iN2k0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794330242),('v5APJfGbLn94tHt7H54QQ8fmA9UfJncbIgFQQtvS',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJRanhHN2ZhSEZZTEhiR1V1ZVdJeXRtUnB5azQ1dEZ3YXM1c01uN2J5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794330263),('x7UGZkzdS2pKX30Pv0ndP1Ec2aJJxZBTGr3jtlDp',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJqZFRycjJubkxPQ2VmMUVVMFFmUXpZM25YaW9Vc2ZsRlczcjU5Wm82IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luP3JldHVybj0lMkZlbiUyRm15LWNoYXQlMkZzdWJzY3JpcHRpb25zIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794329926),('Yc5YmQebdZbohMpNvl6QeiGuXIyUnp5wjUj11uPJ',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiI1aDlOd25xa1JzQjBvbndrQlBHTnVrMFZldUhFaWZTaktXU2pGakE2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvam9pbmVkLXN1YnNjcmlwdGlvbnMiLCJyb3V0ZSI6InVzZXIuam9pbmVkLXN1YnNjcmlwdGlvbnMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvZW5cL2pvaW5lZC1zdWJzY3JpcHRpb25zIn19',1794329856),('Ye3OPFYMy1OCiG3ec42hLyivU2Dgme2UhNy4QOyX',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIyYWRsbHBhTzNPTncxTjVKMXFjd0dZZ2k2Z2xQbzJJSm5JRDRwNml4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1794331989),('Yf6PXQo58hMQxYHeaxSu55kKvDvR0Oj5rsHIhdep',1,'127.0.0.1','curl/8.21.0','eyJfdG9rZW4iOiJockpQdXBsalcwMHRZcmNJdjNwZ0RZNUR6anhVNnFBY1ZqWXhxS1FTIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lbiIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiIwYzVmMzZiNzg5ZGFiZDU3NjRlMzZjYWFiOGEyYmMwYTUwZTc2MzE0Mjg3Y2E0Yjc0ZWEyNzU5MGU5NTFlOTAxIn0=',1794330666),('YIFm7tEjWyW2ID4kPvk0c0i3U50BrgbFO0zNaCUz',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJsekliYVdQems3SjJJOVE3QWZMZGk1bndjUm9hZ2g4M0Uwa0NOaTdqIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2VuXC9teS1jaGF0XC9zdWJzY3JpcHRpb25zIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvZW5cL215LWNoYXRcL3N1YnNjcmlwdGlvbnMiLCJyb3V0ZSI6InVzZXIuY2hhdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794331982),('YNOfkk9SsHuBpib8ExkBhH8F56hLCUBGfxrnXw6Y',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJtUE5qZm1KOGhOQXcwUHV2WGNHV0w1S2lhUWRzaWlSeTczU3JqZllWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794330286),('ZJvYEnzwFARwqWgGNRHhBX1Uz6APnv48IKnq8eID',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiIwT0ljY2dIQzgwRGttRnpJbEk2Vjd6UVZtcU5pWTNhMGxzaVUwdUh2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794330200),('zmWuPvNCwm92Lo5QyugpfESOKdV5W5UwrfDuSS66',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJsVFhmcFcyV1NUMGJnM2pKTDhNN0s5aXRZVTRLRkFabDRkdkttZ3ZJIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL2VuXC9kYXNoYm9hcmQifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvZGFzaGJvYXJkIiwicm91dGUiOiJ1c2VyLmRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794329840),('ZodeGxlDZW74AgFHUb6NTP4B0Oq9wIfRh6r8ODQ0',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Microsoft Windows 10.0.26300; en-US) PowerShell/7.6.9','eyJfdG9rZW4iOiJyRGpxZXdqM1dqSW9hTURaMklITTZQNXlUd216dWRxT2syaXNUd2VaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9lblwvdXNlcmxvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1794330224);
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
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo User','demo@example.com',NULL,'$2y$12$z/tvuOH.sBHZm6Qa9Yz3Eei/pMzVTZg6BB8x.GHowVhiFLU1IQwfu','user','wmy8INZ9sQshGLlGjAkQNFsO6fmMPd1tyDXZnw4YoVZasy9WyLTzIGKI8FYX','2026-11-10 11:56:44','2026-11-10 11:56:44'),(2,'Demo Admin','admin@example.com',NULL,'$2y$12$SAxvEc.Ivx8QpdUxJWq3hOCqkfqVuAJPE6MhZB06rBHhGRvCqYx1G','admin',NULL,'2026-11-10 11:56:44','2026-11-10 11:56:44');
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

-- Dump completed on 2026-11-10 22:33:16
