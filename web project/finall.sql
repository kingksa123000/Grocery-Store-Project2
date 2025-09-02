-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: grocerystoredb
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (98,17,38,1,'2025-05-04 14:19:09'),(99,17,37,1,'2025-05-04 14:19:10'),(101,17,39,1,'2025-05-04 14:19:13'),(102,17,33,1,'2025-05-04 14:19:16'),(103,17,32,1,'2025-05-04 14:19:18');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_id` (`user_id`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (11,'','','hi','asdasfjmikasfjnioksajfniaskf','2025-04-09 14:43:27',20),(14,'layan','layan@gmail.com','ss','ss','2025-05-04 13:14:44',25),(15,'omar','omar@gmail.com','ss','sfff','2025-05-04 13:17:48',25),(16,'khalid','khalid@gmail.com','hhhhh','asdsadasdsada','2025-05-04 13:20:41',25),(17,'Turki','user12@gmail.com','sad','sss','2025-05-04 13:36:52',17);
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,4,36,NULL,1,5.00),(2,4,35,NULL,3,5.00),(3,4,34,NULL,5,3.00),(4,4,33,NULL,3,5.00),(5,4,32,NULL,4,7.00),(6,5,34,NULL,1,3.00),(7,6,33,NULL,1,5.00),(8,7,34,NULL,1,3.00),(9,8,35,NULL,1,5.00),(10,9,35,NULL,1,5.00),(11,10,34,NULL,1,3.00),(12,11,36,NULL,1,5.00),(13,11,35,NULL,1,5.00),(14,11,34,NULL,1,3.00),(15,11,33,NULL,1,5.00),(16,12,36,NULL,20,5.00),(17,13,34,NULL,6,3.00),(18,13,38,NULL,3,5.00),(19,13,37,NULL,6,3.50),(20,14,37,NULL,2,3.50),(21,15,37,NULL,1,3.50),(22,16,37,NULL,1000,3.50),(23,17,37,NULL,1,3.50),(24,18,38,NULL,1,5.00),(25,18,37,NULL,1,3.50),(26,19,38,NULL,95,5.00),(27,19,37,NULL,1000,3.50),(28,20,35,NULL,2,5.00),(29,21,34,NULL,5,3.00),(30,21,35,NULL,2,5.00),(31,21,38,NULL,5,5.00),(32,21,37,NULL,1,3.50),(33,22,38,NULL,3,5.00),(34,23,38,NULL,3,5.00),(35,24,38,NULL,3,5.00),(36,25,38,NULL,3,5.00),(37,26,38,NULL,10,5.00),(38,27,38,NULL,5,5.00),(39,27,37,NULL,5,3.50),(40,27,35,NULL,5,5.00),(41,27,34,NULL,5,3.00),(42,27,33,NULL,5,5.00),(43,27,32,NULL,5,7.00),(44,28,38,NULL,3,5.00),(45,28,37,NULL,2,3.50),(46,28,35,NULL,2,5.00),(47,28,34,NULL,1,3.00),(48,28,33,NULL,1,5.00),(49,28,32,NULL,1,7.00),(50,29,35,NULL,1,5.00),(51,29,37,NULL,4,3.50),(52,29,32,NULL,4,7.00),(53,29,38,NULL,5,5.00),(54,30,38,NULL,1,5.00),(55,30,37,NULL,1,3.50),(56,31,38,NULL,1,5.00),(57,32,38,NULL,1,5.00),(58,33,37,NULL,1112,3.50),(59,34,37,NULL,3,3.50),(60,34,35,NULL,5,5.00),(61,34,32,NULL,3,7.00),(62,34,33,NULL,10,5.00),(63,35,38,NULL,71,5.00),(64,35,39,NULL,100,10.00),(65,36,37,NULL,83,3.50),(66,37,32,NULL,83,7.00),(67,38,32,NULL,3,7.00),(68,39,39,NULL,1,10.00),(69,39,38,NULL,1,5.00),(70,39,37,NULL,1,3.50),(71,40,39,NULL,1,10.00),(72,40,38,NULL,1,5.00),(73,40,37,NULL,1,3.50),(74,40,35,NULL,1,5.00),(75,40,33,NULL,1,5.00),(76,40,32,NULL,1,7.00),(77,41,39,NULL,1,10.00),(78,42,38,NULL,1,5.00),(79,43,39,NULL,1,10.00),(80,44,38,NULL,1,5.00),(81,45,39,'meat',1,10.00),(82,45,38,'strawberies (5 per kg)',1,5.00),(83,45,37,'Banana perKG',1,3.50),(84,45,35,'orange (5 per kg)',1,5.00),(85,45,33,'chichen breast',1,5.00),(86,45,32,'chichen',1,7.00),(87,46,38,'strawberies (5 per kg)',1,5.00),(88,46,39,'meat',2,10.00),(89,46,37,'Banana perKG',1,3.50),(90,46,35,'orange (5 per kg)',1,5.00),(91,46,33,'chichen breast',1,5.00),(92,46,32,'chichen',5,7.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `OrderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('Pending','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `name` varchar(45) DEFAULT NULL,
  `number` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `city` varchar(45) DEFAULT NULL,
  `address` varchar(45) DEFAULT NULL,
  `total_price` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `CustomerID` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,'2025-03-21 12:27:01','Shipped',NULL,NULL,NULL,NULL,NULL,NULL),(2,2,'2025-03-21 12:28:38','Cancelled',NULL,NULL,NULL,NULL,NULL,NULL),(3,18,'2025-03-21 12:28:38','Shipped','khalid','12345678','khalid@gmail.com',NULL,NULL,'50'),(4,17,'2025-04-09 11:40:34','Cancelled',NULL,NULL,NULL,NULL,NULL,'78'),(5,17,'2025-04-09 11:44:30','Cancelled',NULL,NULL,NULL,NULL,NULL,'3'),(6,17,'2025-04-09 11:44:48','Cancelled',NULL,NULL,NULL,NULL,NULL,'5'),(7,17,'2025-04-09 11:50:36','Cancelled',NULL,NULL,NULL,NULL,NULL,'3'),(8,17,'2025-04-09 11:54:00','Cancelled',NULL,NULL,NULL,NULL,NULL,'5'),(9,17,'2025-04-09 11:54:54','Cancelled',NULL,NULL,NULL,NULL,NULL,'5'),(10,17,'2025-04-09 11:57:36','Cancelled',NULL,NULL,NULL,NULL,NULL,'3'),(11,20,'2025-04-09 12:08:59','Shipped',NULL,NULL,NULL,NULL,NULL,'18'),(12,20,'2025-04-09 12:43:25','Shipped',NULL,NULL,NULL,NULL,NULL,'100'),(13,20,'2025-04-09 13:14:07','Shipped',NULL,NULL,NULL,NULL,NULL,'54'),(14,20,'2025-04-09 13:42:00','Shipped',NULL,NULL,NULL,NULL,NULL,'7'),(15,20,'2025-04-09 13:49:04','Cancelled',NULL,NULL,NULL,NULL,NULL,'3.5'),(16,20,'2025-04-09 13:49:25','Cancelled',NULL,NULL,NULL,NULL,NULL,'3500'),(17,20,'2025-04-09 13:52:18','Shipped',NULL,NULL,NULL,NULL,NULL,'3.5'),(18,20,'2025-04-09 13:58:50','Shipped',NULL,NULL,NULL,NULL,NULL,'8.5'),(19,20,'2025-04-09 14:06:40','Shipped',NULL,NULL,NULL,NULL,NULL,'3975'),(20,20,'2025-04-09 14:08:14','Shipped',NULL,NULL,NULL,NULL,NULL,'10'),(21,20,'2025-04-09 14:19:03','Cancelled',NULL,NULL,NULL,NULL,NULL,'53.5'),(22,20,'2025-04-09 14:22:03','Cancelled',NULL,NULL,NULL,NULL,NULL,'15'),(23,20,'2025-04-09 14:22:47','Cancelled',NULL,NULL,NULL,NULL,NULL,'15'),(24,20,'2025-04-09 14:24:57','Shipped',NULL,NULL,NULL,NULL,NULL,'15'),(25,20,'2025-04-09 14:29:06','Shipped',NULL,NULL,NULL,NULL,NULL,'15'),(26,20,'2025-04-09 14:31:54','Shipped',NULL,NULL,NULL,NULL,NULL,'50'),(27,20,'2025-04-11 10:13:09','Shipped',NULL,NULL,NULL,NULL,NULL,'142.5'),(28,20,'2025-04-12 18:24:56','Shipped',NULL,NULL,NULL,NULL,NULL,'47'),(29,17,'2025-05-02 00:01:30','Shipped',NULL,NULL,NULL,NULL,NULL,'72'),(30,17,'2025-05-02 03:04:25','Cancelled',NULL,NULL,NULL,NULL,NULL,'8.5'),(31,17,'2025-05-02 03:09:34','Cancelled',NULL,NULL,NULL,NULL,NULL,'5'),(32,17,'2025-05-02 03:45:44','Cancelled',NULL,NULL,NULL,NULL,NULL,'5'),(33,17,'2025-05-02 03:58:08','Cancelled',NULL,NULL,NULL,NULL,NULL,'3892'),(34,17,'2025-05-02 04:56:33','Shipped',NULL,NULL,NULL,NULL,NULL,'106.5'),(35,17,'2025-05-03 00:21:44','Cancelled',NULL,NULL,NULL,NULL,NULL,'1355'),(36,17,'2025-05-03 00:31:04','Cancelled',NULL,NULL,NULL,NULL,NULL,'290.5'),(37,17,'2025-05-03 01:41:47','Cancelled',NULL,NULL,NULL,NULL,NULL,'581'),(38,17,'2025-05-03 01:42:07','Cancelled',NULL,NULL,NULL,NULL,NULL,'21'),(39,17,'2025-05-04 11:10:08','Shipped',NULL,NULL,NULL,NULL,NULL,'18.5'),(40,25,'2025-05-04 11:29:40','Shipped',NULL,NULL,NULL,NULL,NULL,'35.5'),(41,25,'2025-05-04 12:01:13','Shipped',NULL,NULL,NULL,NULL,NULL,'10'),(42,25,'2025-05-04 12:10:08','Shipped','layan','1234567890','layan@gmail.com','dammam','ohud 71d','5'),(43,26,'2025-05-04 12:15:27','','omar','0560926213','omar1@gmail.com','khobar','khobar alrakah','10'),(44,17,'2025-05-04 12:43:23','','Turki','0560926615','user12@gmail.com','dammam','ahud 71d','5'),(45,17,'2025-05-04 13:44:05','','Turki','0560926615','user12@gmail.com','dammam','ahud 71d','35.5'),(46,17,'2025-05-04 14:19:06','Shipped','Turki','0560926615','user12@gmail.com','dammam','ahud 71d','73.5');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `CategoryID` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (32,'chichen','chicken','',7.00,69.00,'uploads/products/67e5ac691935b_raw chicken.jpg','2025-03-27 19:52:09'),(33,'chichen breast','chicken','',5.00,67.00,'uploads/products/67e5accda715a_chicken.jpg','2025-03-27 19:53:49'),(37,'Banana perKG','fruits','',3.50,91.00,'uploads/products/67f6721a9feee_Banana.jpg','2025-04-09 13:11:54'),(38,'strawberies (5 per kg)','fruits','',5.00,87.00,'uploads/products/67f6725f497eb_strawberries.jpg','2025-04-09 13:13:03'),(39,'meat','meat','fresh meat',10.00,85.00,'uploads/products/681451d723fd6_meat.jpg','2025-05-02 05:02:15'),(40,'dragon fruit','fruits','',5.00,100.00,'uploads/products/681777aa384b5_dragon fruit.jpg','2025-05-04 14:20:26'),(42,'Carrots','vegetables','',3.00,100.00,'uploads/products/681778511d02e_Carrots.jpg','2025-05-04 14:23:13'),(43,'orange','fruits','',2.00,100.00,'uploads/products/68177b1e120c2_orange.jpg','2025-05-04 14:35:10');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `city` varchar(45) NOT NULL,
  `Address` text NOT NULL,
  `Phone` varchar(15) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_type` varchar(45) DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'Turki mohammed','user1@gmail.com','Turki123456','','','','2025-03-26 00:02:09','admin'),(7,'Turki','user7@gmail.com','Turki123456','','','','2025-03-26 00:11:23','user'),(8,'Turki','user8@gmail.com','t123','','','','2025-03-26 00:38:22','user'),(9,'Turki','user9@gmail.com','t123','','','','2025-03-26 00:49:16','user'),(10,'Turki mohammed','user10@gmail.com','123','','','','2025-03-26 00:52:43','user'),(11,'Turki','user11@gmail.com','$2y$10$52XTdmSWgWsaMPZ3OiI/ZOolMjqrDLu8ISH18drB3pZRJC6rwYW6C','','','','2025-03-26 00:58:58','user'),(13,'khalid','newuser1@gmail.com','$2y$10$8meO52cDPBUR8aeqy6YCU.sSGKBmUp1A0Zs8Bwbd2gJSUrHfybvWG','','','','2025-03-26 01:26:43','admin'),(14,'Turki mohammed','newuser2@gmail.com','$2y$10$Dj1vpGilxem4oDBJ2DGQv.E6pO.kdCYqhtwAsncbJxZD4x5cd2Poy','','','','2025-03-26 01:42:43','user'),(15,'Turki','newuser3@gmail.com','$2y$10$x76ZVuiHbD39g.DSD.1U5OBLscZM0u0I2uAoRuQQEMS7HJUEsSvQC','dammam','ahud 71j','0560926611','2025-03-26 19:45:07','admin'),(16,'Turki mohammed','newuser4@gmail.com','$2y$10$6vylTDgUOnEu5Z5ELLZPcO0F3lU1L3FM7W/ottXJaVk04IkrLBXNu','','','','2025-03-27 03:57:34','user'),(17,'Turki','user12@gmail.com','$2y$10$x3VEvPFzk.hXHLLf23CKgu/td9SzncX1DmZOmoZ6URrcIVDJMXp5.','dammam','ahud 71d','0560926615','2025-03-27 04:01:11','user'),(18,'khalid','khalid@gmail.com','$2y$10$vJVcbDTqrTnOBPyuV5BzC.JJ2fwjV1lStrIWoHEwxjnWCBdxzEejm','','','','2025-04-09 04:52:10','user'),(19,'aziz','aziz@gmail.com','$2y$10$OiWjcXOb3dlWznPmF4OgTuVoo.OfzbIAoM00VpE/GfKAlAqflehKe','','','','2025-04-09 08:14:51','admin'),(20,'omar','omar@gmail.com','$2y$10$qmqRe2eJDphCEMwMcWmk9eRB2dKBAgXSp8Hp1Myyq2wP5okCGLYV6','','','','2025-04-09 12:08:24','user'),(21,'Turki','Admin@gmail.com','$2y$10$wkAc6c8G8pogTUXut4LWE.H4gKamL3ogwxFfJ73fbzfRgJzvdUKKi','','','','2025-04-11 10:55:41','admin'),(22,'fahad','fahad@gmail.com','$2y$10$j7pSTSUupD6TkMBTLjFHZ.5.eUGqO7gPcWV2jNzZCFPf.dny2tM0K','','dammam','1234567890','2025-05-03 02:06:03','user'),(23,'abdullah','abdullah@gmail.com','$2y$10$Ylqv4O9NhQnJN5ERyM/ggOAbSyBMbztzwg2EpO5hotlHNWmUg0NKq','','alrakah','1234509876','2025-05-03 02:17:31','user'),(24,'turki','turki@gmail.com','$2y$10$oDOgbEegYecBlrx/xFZZWO2sJINLEz91z1rf5wpavweyqKPoZgLOu','dammam','ahud 71d','1234567890','2025-05-03 02:21:51','user'),(25,'layan','layan@gmail.com','$2y$10$KOZ5GV.1wdASnvmot7E2Key7Zszh9tPN088bMfgXKEWLoL5q9vaxS','dammam','ohud 71d','1234567891','2025-05-04 11:28:20','user'),(26,'omar','omar1@gmail.com','$2y$10$/gTYW6CNPqz57bRkpnhPyuRvo.Bcp6aw6mmdxSlYoUMOdI/eqXbwu','khobar','khobar alrakah','0560926213','2025-05-04 12:12:33','user');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-04 17:41:36
