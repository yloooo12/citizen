-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 05:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_services`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_alerts`
--

CREATE TABLE `academic_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `grade` varchar(20) DEFAULT NULL,
  `program_section` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `intervention` text DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL,
  `alert_type` varchar(50) DEFAULT NULL,
  `is_resolved` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
  
--
-- Table structure for table `admission_interviews`
--

CREATE TABLE `admission_interviews` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','scheduled','completed','cancelled') DEFAULT 'pending',
  `scheduled_date` datetime DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crediting_history`
--

CREATE TABLE `crediting_history` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `performed_by` int(11) NOT NULL,
  `performed_by_type` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crediting_requests`
--

CREATE TABLE `crediting_requests` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_title` varchar(255) DEFAULT NULL,
  `units` int(11) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `school_taken` varchar(255) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL,
  `documents` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `program_head_approved` tinyint(4) DEFAULT 0,
  `dean_approved` tinyint(4) DEFAULT 0,
  `registrar_approved` tinyint(4) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `date_submitted` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `credited_subjects` text DEFAULT NULL,
  `evaluation_remarks` text DEFAULT NULL,
  `date_evaluated` datetime DEFAULT NULL,
  `secretary_prepared` tinyint(1) DEFAULT 0,
  `date_prepared` datetime DEFAULT NULL,
  `program_head_signature` text DEFAULT NULL,
  `secretary_signature` text DEFAULT NULL,
  `dean_signature` text DEFAULT NULL,
  `date_dean_approved` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dean_inc_requests`
--

CREATE TABLE `dean_inc_requests` (
  `id` int(11) NOT NULL,
  `inc_request_id` int(11) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `professor` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `inc_reason` text DEFAULT NULL,
  `inc_semester` varchar(50) DEFAULT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dean_approved` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'pending',
  `signature` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dean_notifications`
--

CREATE TABLE `dean_notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `column_name` varchar(100) NOT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `units` int(11) DEFAULT 3,
  `remarks` varchar(20) DEFAULT NULL,
  `equivalent` varchar(10) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_columns`
--

CREATE TABLE `grade_columns` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) DEFAULT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `period` varchar(20) DEFAULT NULL,
  `columns` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inc_requests`
--

CREATE TABLE `inc_requests` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_email` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `professor` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `inc_reason` text NOT NULL,
  `inc_semester` varchar(50) NOT NULL,
  `date_submitted` timestamp NOT NULL DEFAULT current_timestamp(),
  `dean_approved` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'pending',
  `signature` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(43, '246', 'New student registered: Jaylo Ludovice, (0122-1132)', 'registration', 0, '2025-11-03 08:31:36');

-- --------------------------------------------------------

--
-- Table structure for table `program_head_notifications`
--

CREATE TABLE `program_head_notifications` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secretary_notifications`
--

CREATE TABLE `secretary_notifications` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invitations`
--

CREATE TABLE `student_invitations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_invitations`
--

INSERT INTO `student_invitations` (`id`, `id_number`, `first_name`, `last_name`, `email`, `token`, `expires_at`, `used`, `created_at`, `year_level`, `semester`, `school_year`) VALUES
(42, '0122-0353', 'Jhon Rey', 'Albino,', 'jhonrey.sample@gmail.com', '157974b6d7a8c39079768e2178ddbf4ea1080083186784154d3c707204f85b9a', '2025-11-10 09:26:20', 0, '2025-11-03 08:26:20', NULL, NULL, NULL),
(43, '0122-0417', 'Maria', 'A?onuevo,', 'maria.user@gmail.com', '9a80b7e5ad62a5631753a71fe1a09f28b94625090cb72f89d5f8cdfd751aba82', '2025-11-10 09:26:25', 0, '2025-11-03 08:26:25', NULL, NULL, NULL),
(44, '0122-0348', 'Philip', 'Birador,', 'peejbirador@gmail.com', 'b17c6323b164401a23fb8a41839653ccaf4000f83cb7ea36b3651dcbaeab0139', '2025-11-10 09:26:29', 0, '2025-11-03 08:26:29', NULL, NULL, NULL),
(45, '0122-0415', 'Camille', 'Canoy,', 'camille.sample@gmail.com', 'cbe054a7fa4967b7cbe0724ebecdaeb35039b208dd0b53ddad5274a7ca0d0ed4', '2025-11-10 09:26:48', 0, '2025-11-03 08:26:48', NULL, NULL, NULL),
(46, '0122-0414', 'Marc', 'Casuno,', 'marc.user@gmail.com', 'c784f3c12566a605d98e5f3d92168615452da61ce90dfc0048b8f3ea0a798f8c', '2025-11-10 09:26:51', 0, '2025-11-03 08:26:51', NULL, NULL, NULL),
(47, '0122-1975', 'Bernadeth', 'Cortez,', 'bernadeth.sample@gmail.com', '815f4ad3695082f2e0ed66a57fc896dc4edba5e540a49d2518601cbbb8ae06b2', '2025-11-10 09:26:56', 0, '2025-11-03 08:26:56', NULL, NULL, NULL),
(48, '0122-0457', 'Kim', 'Dausin,', 'kim.user@gmail.com', '014e0a54248fba161a3de2e3548178973d0fa7f59d53f4d60241dbc95a63a0c0', '2025-11-10 09:26:59', 0, '2025-11-03 08:26:59', NULL, NULL, NULL),
(49, '0122-3402', 'Axel', 'Dionisio,', 'axel.sample@gmail.com', '489789036297b19f0232edd0b0ad84bb305f93b903da3bcff5a9e7e74e06f5d8', '2025-11-10 09:27:03', 0, '2025-11-03 08:27:03', NULL, NULL, NULL),
(50, 'CCSBSIT15-0047', 'Krisantha', 'Elca,', 'krisantha.user@gmail.com', 'a8febe140257398181ac1fb93f1222177caeaba075c4a91b15a9ab231ba3c15d', '2025-11-10 09:27:07', 0, '2025-11-03 08:27:07', NULL, NULL, NULL),
(51, '0122-1775', 'Jhon', 'Felipe,', 'jhon.sample@gmail.com', '934451a98abc1e0c957c0c55ce92c2722ea251456f3fe9297f946ca435272aed', '2025-11-10 09:27:10', 0, '2025-11-03 08:27:10', NULL, NULL, NULL),
(52, '0122-1628', 'John', 'Gallano,', 'john.user@gmail.com', '69a9d8021844f30372f250ffda5b3070287e188d70750e4ab700fcf86423642c', '2025-11-10 09:27:14', 0, '2025-11-03 08:27:14', NULL, NULL, NULL),
(53, '0122-2441', 'Renz', 'Guerrero,', 'renz.sample@gmail.com', '1e08ad6d2c613d240faa6b0d10a53e991e3e6a628b201d49c4907bfcaae3ce4f', '2025-11-10 09:27:18', 0, '2025-11-03 08:27:18', NULL, NULL, NULL),
(54, '0122-3268', 'Claud', 'Jimenez,', 'claud.user@gmail.com', '7c9490a73245a0111c6bc65762f55574fe564af20f54678b01865be3e2b2c5b5', '2025-11-10 09:27:21', 0, '2025-11-03 08:27:21', NULL, NULL, NULL),
(55, '0122-0584', 'Maria', 'Joya,', 'maria.sample@gmail.com', '7c73a297272f4fdaf517311123705474f10016750b17802ce4d04108248a7c36', '2025-11-10 09:27:24', 0, '2025-11-03 08:27:24', NULL, NULL, NULL),
(56, '0122-1132', 'Jaylo', 'Ludovice,', 'ludoviceylo26@gmail.com', '3594018fd6e0faf604b3897b63fa78add6a624cab58d93fbdb1178aaca43a207', '2025-11-10 09:27:28', 1, '2025-11-03 08:27:28', NULL, NULL, NULL),
(57, '0122-3632', 'Anne', 'Maceda,', 'anne.sample@gmail.com', 'ce8d0f680715b0fd87483a8c3f7eb85317b7f40bf70fdf84f8616b96526224cb', '2025-11-10 09:27:32', 0, '2025-11-03 08:27:32', NULL, NULL, NULL),
(58, 'CCSACT15-0036', 'Rhea', 'Mogro,', 'rhea.user@gmail.com', '1f9bfa9af025788e9c5748d6a8dc5cd88620af898959be723a4a547a510d53ed', '2025-11-10 09:27:36', 0, '2025-11-03 08:27:36', NULL, NULL, NULL),
(59, '0122-0783', 'Nicole', 'Nericua,', 'nicole.sample@gmail.com', '9df0d058e2895d697c5725e37e934868734f02aaddeb40f14a440c6119a7454b', '2025-11-10 09:27:39', 0, '2025-11-03 08:27:39', NULL, NULL, NULL),
(60, '0122-0784', 'John', 'Oracion,', 'john.sample2@gmail.com', '513baa675cbb7d08b68a6160b5847dff8ffd4d2c130fbcc2627f191ecda3494f', '2025-11-10 09:27:43', 0, '2025-11-03 08:27:43', NULL, NULL, NULL),
(61, '0122-0647', 'Julianne', 'Pabale,', 'julianne.user@gmail.com', '5f0d4192004b0c8047929060730f26e02a582027722c491e1cea1483200912fa', '2025-11-10 09:27:47', 0, '2025-11-03 08:27:47', NULL, NULL, NULL),
(62, '0122-3886', 'Lyndon', 'Pablo,', 'lyndon.sample@gmail.com', '1fe6abb38e64d3711d98a981a7acc2c3e3b28d78997a60ac6db6548a0843ed5c', '2025-11-10 09:27:50', 0, '2025-11-03 08:27:50', NULL, NULL, NULL),
(63, '0122-0702', 'Justine', 'Pe?aloza,', 'justine.user@gmail.com', '19786fa628ef824f275726c1a7260986c9aa396302adedfc255e45295a7641f2', '2025-11-10 09:27:54', 0, '2025-11-03 08:27:54', NULL, NULL, NULL),
(64, '0122-0643', 'Vincent', 'Ponce,', 'vincent.sample@gmail.com', '619a66b45c67ee5efe60bc30421511b9580b5df3b026798248e55d674cb64d0d', '2025-11-10 09:28:20', 0, '2025-11-03 08:28:20', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_title` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `teacher_id` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `descriptive_title` varchar(255) DEFAULT NULL,
  `units` int(11) NOT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `descriptive_title`, `units`, `year_level`, `semester`, `created_at`) VALUES
(53, 'GEC101', 'Understanding the Self', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(54, 'GEC102', 'Readings in Philippine History', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(55, 'GEC104', 'Mathematics in the Modern World', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(56, 'GEC103', 'The Contemporary World', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(57, 'KOMFIL', 'Kontekstwalisadong Komunikasyon sa Filipino', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(58, 'PE1', 'Physical Fitness & Self-Testing Activities', NULL, 2, NULL, NULL, '2025-10-12 08:35:21'),
(59, 'NSTP1', 'CWTS 1/ROTC1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(60, 'ITEC102', 'Fundamentals of Programming', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(61, 'ITEC101', 'Introduction to Information Technology Computing', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(62, 'GE106', 'Art Appreciation', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(63, 'GEC105', 'Purposive Communication', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(64, 'FILDIS', 'Filipino sa Ibat Ibang Disiplina', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(65, 'PE2', 'Rhythmic Activities', NULL, 2, NULL, NULL, '2025-10-12 08:35:21'),
(66, 'NSTP2', 'ROTC 2 /CWTS 2 /LTS 2', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(67, 'ITEC103', 'Intermediate Programming', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(68, 'ITEP101', 'Human Computer Interaction 1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(69, 'ITEP102', 'Discrete Mathematics', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(70, 'PI100', 'Life, Works and Writings of Rizal', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(71, 'GEC107', 'Science, Technology and Society', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(72, 'PE3', 'Fundamentals of Games and Sports', NULL, 2, NULL, NULL, '2025-10-12 08:35:21'),
(73, 'ITEC204', 'Data Structure and Algorithm', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(74, 'ITEC205', 'Information Management', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(75, 'ITEP203', 'Quantitative Methods including Modeling and Simulation', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(76, 'ITEL201', 'Object-Oriented Programming', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(77, 'ITEL202', 'Platform Technologies', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(78, 'SOSLIT', 'Sosyedad at Literatura / Panitikang Panlipunan', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(79, 'GEC108', 'Ethics', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(80, 'ITEP204', 'Advance Database Systems', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(81, 'ITEP205', 'Multimedia Systems', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(82, 'ITEP206', 'Integrative Programming Technologies 1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(83, 'ITEP207', 'Networking 1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(84, 'ITEL203', 'Web Systems and Technologies', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(85, 'PE4', 'Recreational Activities/Team Sports', NULL, 2, NULL, NULL, '2025-10-12 08:35:21'),
(86, 'ITEP308', 'System Integration and Architecture 1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(87, 'ITEP309', 'Networking 2', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(88, 'ITEP310', 'Social and Professional Issues', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(89, 'ITEL304', 'Integrative Programming Technologies 2', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(90, 'ITST301', 'Principles of Web Design (WMA 301)', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(91, 'ITST302', 'Client-Server Technologies (WMA 302)', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(92, 'ITEC306', 'Applications Development & Emerging Technologies', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(93, 'ITEP312', 'Capstone Project 1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(94, 'ITEP311', 'Information Assurance and Security 1', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(95, 'ITEL305', 'System Integration and Architecture 2', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(96, 'ITST303', 'Web and Database Integration (WMA 303)', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(97, 'ITST304', 'Mobile Computing (WMA 304)', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(98, 'ITST305', 'Cloud Computing (WMA 305)', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(99, 'ITEP415', 'Capstone Project 2', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(100, 'ITEP413', 'Information Assurance and Security 2', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(101, 'ITEP414', 'System Administration and Maintenance', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(102, 'ITST306', 'UX/UI and Cross Platform Applications (WMA 306)', NULL, 3, NULL, NULL, '2025-10-12 08:35:21'),
(103, 'ITEP503', 'On-the-Job Training (600 Hours)', NULL, 6, '4th Year', '2nd', '2025-10-12 08:35:21');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_invitations`
--

CREATE TABLE `teacher_invitations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `assigned_course` varchar(10) DEFAULT NULL,
  `assigned_section` varchar(10) DEFAULT NULL,
  `assigned_subject` varchar(100) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_lecture` varchar(100) DEFAULT NULL,
  `assigned_lab` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_invitations`
--

INSERT INTO `teacher_invitations` (`id`, `id_number`, `first_name`, `last_name`, `email`, `assigned_course`, `assigned_section`, `assigned_subject`, `token`, `expires_at`, `used`, `created_at`, `assigned_lecture`, `assigned_lab`, `year_level`, `semester`, `school_year`) VALUES
(20, '111', 'Maria', 'Santos', 'peejzxc32@gmail.com', NULL, NULL, NULL, '83809735be89ea38af92d8eb8b63b1d38522a7c9af6dd5abef7c686387e9e5a6', '2025-11-11 05:53:03', 1, '2025-11-04 04:53:03', NULL, NULL, NULL, NULL, NULL),
(21, '333', 'Ana', 'Garcia', 'jaylo.ludovice@lspu.edu.ph', NULL, NULL, NULL, '4e09ee8af6ef218489794790263bdf553796e9c0763181d234f9f610af08644f', '2025-11-11 05:53:06', 1, '2025-11-04 04:53:06', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_notifications`
--

CREATE TABLE `teacher_notifications` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unscheduled_requests`
--

CREATE TABLE `unscheduled_requests` (
  `id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `request_letter` text DEFAULT NULL,
  `eval_grades` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `date_submitted` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `course` varchar(10) DEFAULT NULL,
  `section` varchar(5) DEFAULT NULL,
  `student_type` varchar(20) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `contact_number` varchar(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_type` varchar(20) DEFAULT 'student',
  `assigned_section` varchar(10) DEFAULT NULL,
  `assigned_course` varchar(10) DEFAULT NULL,
  `assigned_subject` varchar(100) DEFAULT NULL,
  `assigned_lecture` varchar(100) DEFAULT NULL,
  `assigned_lab` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `first_name`, `middle_name`, `last_name`, `email`, `course`, `section`, `student_type`, `sex`, `contact_number`, `password`, `status`, `created_at`, `user_type`, `assigned_section`, `assigned_course`, `assigned_subject`, `assigned_lecture`, `assigned_lab`, `year_level`, `semester`, `school_year`, `profile_picture`) VALUES
(1, '246', 'Dean/Super User', NULL, 'Dean/Super User', 'admin@ccs.edu', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-10 16:53:14', 'dean', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(235, '001', 'Mark', 'P.', 'Bernardino', '1mark.bernardino@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(236, '002', 'Edward', 'S.', 'Flores', '1edward.flores@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL),
(237, '003', 'Reynalen', 'C.', 'Justo', '1reynalen.justo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(238, '004', 'Maria Laureen', 'B.', 'Miranda', '1marialaureen.miranda@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(239, '005', 'Gener', 'F.', 'Mosico', '1gener.mosico@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(240, '006', 'Reymart Joseph', 'P.', 'Pielago', '1reymartjoseph.pielago@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(241, '007', 'Rachiel', 'R.', 'Rivano', '1rachiel.rivano@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(242, '008', 'Margarita', '', 'Villanueva', '1margarita.villanueva@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(243, '009', 'Mia', 'V.', 'Villarica', '1mia.villarica@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(244, '010', 'Micah Joy', '', 'Formaran', '1micahjoy.formaran@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(245, '011', 'Roxanne', 'Rivera', 'Garbo', '1roxanne.garbo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(246, '012', 'Margielyn', 'A', 'Guico', '1margielyn.guico@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(247, '013', 'Francisco Kaleb', 'C.', 'Marquez', '1franciscokaleb.marquez@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(248, '014', 'Harlene Gabrielle', 'E.', 'Origines', '1harlenegabrielle.origines@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(249, '015', 'John Randolf', '', 'Penaredondo', '1johnrandolf.penaredondo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(250, '016', 'Jeremy', '', 'Reyes', '1jeremy.reyes@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(251, '017', 'Edison', 'V.', 'Templo', '1edison.templo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(252, '018', 'Zion Krehl', '', 'Astronomo', '1zionkrehl.astronomo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(253, '019', 'Kristian Carlo', '', 'Garcia', '1kristiancarlo.garcia@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(254, '020', 'Kayecie', 'O.', 'Dorado', '1kayecie.dorado@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(255, '021', 'Cristian Jay', 'B.', 'Pollarca', '1cristianjay.pollarca@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(256, '022', 'Annie Belle', 'M.', 'Santiago', '1anniebelle.santiago@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(257, '023', 'Khrisna Cara', 'O.', 'Solde', '1khrisnacara.solde@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(258, '2025', 'Secretary', NULL, 'Sha', 'secretary@lspu.edu.ph', NULL, NULL, NULL, NULL, '09123456789', '123', 'approved', '2025-10-12 07:49:48', 'secretary', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(299, '0011', 'Program', NULL, 'Head', 'programhead@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-26 18:02:57', 'program_head', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(308, '0122-1132', 'Jaylo', NULL, 'Ludovice,', 'ludoviceylo26@gmail.com', 'IT', 'A', 'Transferee', 'Male', '12312312331', '123', 'approved', '2025-11-03 08:31:36', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(357, '111', 'Maria', NULL, 'Santos', 'peejzxc32@gmail.com', NULL, NULL, NULL, NULL, '90091238123', '$2y$10$zPZzdaQlwzcu2jQG/DtDxuba9XjAWXurkPScElVu495Sk3C.i16wy', 'approved', '2025-11-04 04:53:27', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(358, '333', 'Ana', NULL, 'Garcia', 'jaylo.ludovice@lspu.edu.ph', NULL, NULL, NULL, NULL, '23131233123', '$2y$10$zsiEEgyEnb4Un0RNK1iZ1.8qRWtfTe5nOkyTdV8iL6EVYBKF5dPm.', 'approved', '2025-11-04 04:54:50', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_alerts`
--
ALTER TABLE `academic_alerts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_alert` (`user_id`,`course`,`alert_type`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`,`user_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admission_interviews`
--
ALTER TABLE `admission_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `crediting_history`
--
ALTER TABLE `crediting_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `crediting_requests`
--
ALTER TABLE `crediting_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dean_inc_requests`
--
ALTER TABLE `dean_inc_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dean_notifications`
--
ALTER TABLE `dean_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_grade` (`student_id`,`teacher_id`,`subject_code`,`column_name`);

--
-- Indexes for table `grade_columns`
--
ALTER TABLE `grade_columns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_code` (`subject_code`,`teacher_id`,`period`);

--
-- Indexes for table `inc_requests`
--
ALTER TABLE `inc_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `program_head_notifications`
--
ALTER TABLE `program_head_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `secretary_notifications`
--
ALTER TABLE `secretary_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_invitations`
--
ALTER TABLE `student_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`subject_code`,`teacher_id`,`section`,`semester`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `teacher_invitations`
--
ALTER TABLE `teacher_invitations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_notifications`
--
ALTER TABLE `teacher_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unscheduled_requests`
--
ALTER TABLE `unscheduled_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_alerts`
--
ALTER TABLE `academic_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admission_interviews`
--
ALTER TABLE `admission_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crediting_history`
--
ALTER TABLE `crediting_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crediting_requests`
--
ALTER TABLE `crediting_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dean_inc_requests`
--
ALTER TABLE `dean_inc_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `dean_notifications`
--
ALTER TABLE `dean_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1371;

--
-- AUTO_INCREMENT for table `grade_columns`
--
ALTER TABLE `grade_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inc_requests`
--
ALTER TABLE `inc_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `program_head_notifications`
--
ALTER TABLE `program_head_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `secretary_notifications`
--
ALTER TABLE `secretary_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_invitations`
--
ALTER TABLE `student_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=362;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `teacher_invitations`
--
ALTER TABLE `teacher_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `teacher_notifications`
--
ALTER TABLE `teacher_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `unscheduled_requests`
--
ALTER TABLE `unscheduled_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=359;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
