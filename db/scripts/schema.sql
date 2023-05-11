-- MySQL dump 10.13  Distrib 8.0.29, for Win64 (x86_64)
--
-- Host: localhost    Database: beapilot
-- ------------------------------------------------------
-- Server version	5.7.24

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
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answers` (
  `id_answer` int(11) NOT NULL AUTO_INCREMENT,
  `id_question_answer` int(11) DEFAULT NULL,
  `istrue_answer` tinyint(1) DEFAULT NULL,
  `string_answer` varchar(1000) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id_answer`),
  KEY `questions_in_answer_idx` (`id_question_answer`),
  CONSTRAINT `questions_in_answer` FOREIGN KEY (`id_question_answer`) REFERENCES `questions` (`id_question`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2150 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id_category` int(2) NOT NULL AUTO_INCREMENT,
  `name_category` varchar(45) CHARACTER SET utf8 NOT NULL,
  `numberquestions_category` int(11) DEFAULT NULL,
  `code_category` varchar(45) CHARACTER SET utf8 NOT NULL,
  `testtime_category` time NOT NULL,
  PRIMARY KEY (`id_category`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `questionintests`
--

DROP TABLE IF EXISTS `questionintests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questionintests` (
  `id_questionintest` int(11) NOT NULL AUTO_INCREMENT,
  `id_test_questionintest` int(11) NOT NULL,
  `studentanswer_questionintest` tinyint(4) DEFAULT NULL,
  `id_question_questionintest` int(11) NOT NULL,
  `id_user_questionintest` int(11) NOT NULL,
  PRIMARY KEY (`id_questionintest`),
  KEY `id_test_in_questionintest_idx` (`id_test_questionintest`),
  KEY `id-question_in_questionintest_idx` (`id_question_questionintest`),
  KEY `id_user_in_questionintest_idx` (`id_user_questionintest`),
  CONSTRAINT `id_question_in_questionintest` FOREIGN KEY (`id_question_questionintest`) REFERENCES `questions` (`id_question`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `id_test_in_questionintest` FOREIGN KEY (`id_test_questionintest`) REFERENCES `test` (`id_test`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `id_user_in_questionintest` FOREIGN KEY (`id_user_questionintest`) REFERENCES `users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id_question` int(11) NOT NULL AUTO_INCREMENT,
  `id_category_question` int(2) NOT NULL,
  `string_question` varchar(1000) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id_question`),
  KEY `category_in_question_idx` (`id_category_question`),
  CONSTRAINT `category_in_question` FOREIGN KEY (`id_category_question`) REFERENCES `categories` (`id_category`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=538 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_answers`
--

DROP TABLE IF EXISTS `student_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_answers` (
  `id_student_answer` int(11) NOT NULL AUTO_INCREMENT,
  `id_user_student_answer` int(11) NOT NULL,
  `id_answer_student_answer` int(11) DEFAULT NULL,
  `id_question_student_answer` int(11) DEFAULT NULL,
  `id_test_student_answer` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_student_answer`),
  KEY `user_in_student_answer_idx` (`id_user_student_answer`),
  KEY `answer_in_student_answer_idx` (`id_answer_student_answer`),
  KEY `question_in_student_answer_idx` (`id_question_student_answer`),
  KEY `test_in_student_answer_idx` (`id_test_student_answer`),
  CONSTRAINT `answer_in_student_answer` FOREIGN KEY (`id_answer_student_answer`) REFERENCES `answers` (`id_answer`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `question_in_student_answer` FOREIGN KEY (`id_question_student_answer`) REFERENCES `questions` (`id_question`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `test_in_student_answer` FOREIGN KEY (`id_test_student_answer`) REFERENCES `test` (`id_test`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `user_in_student_answer` FOREIGN KEY (`id_user_student_answer`) REFERENCES `users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test`
--

DROP TABLE IF EXISTS `test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `test` (
  `id_test` int(11) NOT NULL AUTO_INCREMENT,
  `id_category_test` int(11) NOT NULL,
  `rightanswers_test` int(11) DEFAULT NULL,
  `updatedate_test` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `creationdate_test` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_user_test` int(11) DEFAULT NULL,
  `finished_test` tinyint(4) NOT NULL DEFAULT '0',
  `currentanswer_test` int(3) NOT NULL,
  PRIMARY KEY (`id_test`),
  KEY `category_in_test_idx` (`id_category_test`),
  KEY `iduser_in_test_idx` (`id_user_test`),
  CONSTRAINT `category_in_test` FOREIGN KEY (`id_category_test`) REFERENCES `categories` (`id_category`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `iduser_in_test` FOREIGN KEY (`id_user_test`) REFERENCES `users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `name_user` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `email_user` varchar(45) CHARACTER SET utf8 NOT NULL,
  `pass_user` varchar(105) CHARACTER SET utf8 NOT NULL,
  `token_user` varchar(105) CHARACTER SET utf8 DEFAULT NULL,
  `tokenexp_user` varchar(105) CHARACTER SET utf8 DEFAULT NULL,
  `datecreated_user` date NOT NULL,
  `dateupdated_user` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-05-11 13:20:34
