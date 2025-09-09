-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: eticaret
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'admin','$2y$10$9HMR2VNvgbzfNI8Q1jlVjejzCyDw8xROCezwNaOF5MJrYQTfmxR9y','2025-09-08 11:20:53');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`student_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_type` enum('admin','student') NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','product','system','support') NOT NULL,
  `read_status` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'student',18,'Siparişiniz hazırlanıyor','#ORD20250908_68bf156080ba1 numaralı siparişiniz: Siparişiniz hazırlanıyor','order',0,'2025-09-08 17:53:57'),(2,'admin',1,'Yeni sipariş alındı','cevat2 tarafından #ORD20250908_68bf18a2511fe numaralı yeni sipariş oluşturuldu.','order',0,'2025-09-08 17:55:46'),(3,'student',18,'Siparişiniz onaylandı','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz onaylandı','order',0,'2025-09-08 18:10:07'),(4,'student',18,'Siparişiniz kargoya verildi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz kargoya verildi','order',0,'2025-09-08 18:10:22'),(5,'student',18,'Siparişiniz teslim edildi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz teslim edildi','order',0,'2025-09-08 18:10:26'),(6,'student',18,'Siparişiniz teslim edildi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz teslim edildi','order',0,'2025-09-08 18:10:27'),(7,'student',18,'Siparişiniz onaylandı','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz onaylandı','order',0,'2025-09-08 18:10:31'),(8,'student',18,'Sipariş durumu güncellendi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Sipariş durumu güncellendi','order',0,'2025-09-08 18:10:32'),(9,'student',18,'Siparişiniz kargoya verildi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz kargoya verildi','order',0,'2025-09-08 18:10:34'),(10,'student',18,'Siparişiniz iptal edildi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz iptal edildi','order',0,'2025-09-08 18:10:38'),(11,'student',18,'Sipariş durumu güncellendi','#ORD20250908_68bf156080ba1 numaralı siparişiniz: Sipariş durumu güncellendi','order',0,'2025-09-08 18:10:40'),(12,'student',18,'Sipariş durumu güncellendi','#ORD20250908_68bf140f61d06 numaralı siparişiniz: Sipariş durumu güncellendi','order',0,'2025-09-08 18:10:44'),(13,'student',18,'Sipariş durumu güncellendi','#ORD20250908_68bf13b40c6c4 numaralı siparişiniz: Sipariş durumu güncellendi','order',0,'2025-09-08 18:10:47'),(14,'student',18,'Siparişiniz kargoya verildi','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz kargoya verildi','order',0,'2025-09-08 18:10:53'),(15,'student',18,'Siparişiniz hazırlanıyor','#ORD20250908_68bf154ccd81d numaralı siparişiniz: Siparişiniz hazırlanıyor','order',0,'2025-09-08 18:10:56'),(16,'student',18,'Siparişiniz teslim edildi','#ORD20250908_68bf154ccd81d numaralı siparişiniz: Siparişiniz teslim edildi','order',0,'2025-09-08 18:10:59'),(17,'student',18,'Siparişiniz hazırlanıyor','#ORD20250908_68bf154ccd81d numaralı siparişiniz: Siparişiniz hazırlanıyor','order',0,'2025-09-08 18:11:13'),(18,'student',18,'Siparişiniz hazırlanıyor','#ORD20250908_68bf18a2511fe numaralı siparişiniz: Siparişiniz hazırlanıyor','order',0,'2025-09-08 18:11:27'),(19,'admin',1,'Yeni sipariş alındı','cevat1 tarafından #ORD20250909_68bfa8a0e19cf numaralı yeni sipariş oluşturuldu.','order',0,'2025-09-09 04:10:08'),(20,'admin',1,'Yeni sipariş alındı','cevat1 tarafından #ORD20250909_68bfa9d3ad979 numaralı yeni sipariş oluşturuldu.','order',0,'2025-09-09 04:15:15');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,45,'cevat2',100.00,1,100.00),(2,2,45,'cevat2',100.00,1,100.00),(3,3,45,'cevat2',100.00,1,100.00),(4,4,45,'cevat2',100.00,1,100.00),(5,5,45,'cevat2',100.00,2,200.00),(6,6,45,'cevat2',100.00,1,100.00),(7,7,45,'cevat2',100.00,1,100.00),(8,8,45,'cevat2',100.00,1,100.00),(9,9,44,'cevat1',100.00,1,100.00),(10,10,44,'cevat1',100.00,1,100.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('eft','havale','kredi_karti') NOT NULL,
  `status` enum('beklemede','onaylandi','hazirlaniyor','kargoda','teslim_edildi','iptal') DEFAULT 'beklemede',
  `student_name` varchar(100) NOT NULL,
  `student_class` varchar(50) NOT NULL,
  `student_phone` varchar(20) DEFAULT NULL,
  `student_address` text,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,18,'ORD20250908_68bf1302e4cd5',100.00,'kredi_karti','beklemede','cevat2','1.sınıf','123','123','2025-09-08 17:31:46'),(2,18,'ORD20250908_68bf131785439',100.00,'kredi_karti','beklemede','cevat2','1.sınıf','123','123','2025-09-08 17:32:07'),(3,18,'ORD20250908_68bf135f10271',100.00,'eft','onaylandi','cevat2','1.sınıf','123','123','2025-09-08 17:33:19'),(4,18,'ORD20250908_68bf13b40c6c4',100.00,'kredi_karti','beklemede','cevat2','1.sınıf','123','123','2025-09-08 17:34:44'),(5,18,'ORD20250908_68bf140f61d06',200.00,'eft','beklemede','cevat2','1.sınıf','123','123','2025-09-08 17:36:15'),(6,18,'ORD20250908_68bf154ccd81d',100.00,'eft','hazirlaniyor','cevat2','1.sınıf','123','123','2025-09-08 17:41:32'),(7,18,'ORD20250908_68bf156080ba1',100.00,'kredi_karti','beklemede','cevat2','1.sınıf','123','123','2025-09-08 17:41:52'),(8,18,'ORD20250908_68bf18a2511fe',100.00,'eft','hazirlaniyor','cevat2','1.sınıf','123','123','2025-09-08 17:55:46'),(9,17,'ORD20250909_68bfa8a0e19cf',100.00,'eft','beklemede','cevat1','Anaokulu - Kreş','123','123','2025-09-09 04:10:08'),(10,17,'ORD20250909_68bfa9d3ad979',100.00,'eft','beklemede','cevat1','Anaokulu - Kreş','123','123','2025-09-09 04:15:15');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `images` text,
  `target_classes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `classes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (44,'cevat1','100',100.00,98,'[\"1757349937_68bf0831ebac1_DSCF1713.JPG\",\"1757349937_68bf0831ed799_DSCF1714.JPG\"]',NULL,'2025-09-08 16:45:37','[\"Anaokulu - Kre\\u015f\"]'),(45,'cevat2','100',100.00,91,'[\"1757349948_68bf083c1be4a_DSCF1725.JPG\",\"1757349948_68bf083c1e01d_DSCF1731.JPG\"]',NULL,'2025-09-08 16:45:48','[\"1.s\\u0131n\\u0131f\"]');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `class` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (17,'cevat1','$2y$10$vNb/Y/49Tsm1zdoXpIBtpe2FwMkLoEribfuDeO8A.ut3FqQ5XN43C','ahmet@ahmet.com','cevat1','Anaokulu - Kreş','123','123',NULL,'2025-09-08 16:46:02'),(18,'cevat2','$2y$10$lpiHqrprmFxv9O8tMA.XweQ.N3B46coK2258gGmmjYiTRtxLPCDLi','ahmet@ahmet.com','cevat2','1.sınıf','123','123',NULL,'2025-09-08 16:46:12'),(19,'cevat3','$2y$10$rqGqMhh9Jp6m7WGWR/Eb3.j8B9aj09wzU77Ig.GOHXRud5.LvjcQa','123123@hotmail.com','cevat3','5.sınıf','123','123',NULL,'2025-09-08 16:47:05');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_type` enum('admin','student') NOT NULL,
  `sender_id` int NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `support_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_messages`
--

LOCK TABLES `support_messages` WRITE;
/*!40000 ALTER TABLE `support_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-09 16:37:31
