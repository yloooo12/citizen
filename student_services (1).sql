-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 10:28 PM
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
  `exam_date` date DEFAULT NULL,
  `exam_time` varchar(50) DEFAULT NULL,
  `exam_venue` varchar(255) DEFAULT NULL,
  `exam_details_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_alerts`
--

INSERT INTO `academic_alerts` (`id`, `user_id`, `student_id`, `course`, `grade`, `program_section`, `reason`, `intervention`, `instructor`, `semester`, `school_year`, `alert_type`, `is_resolved`, `exam_date`, `exam_time`, `exam_venue`, `exam_details_sent`, `created_at`, `updated_at`) VALUES
(427, NULL, '0122-0348', 'Subject Crediting', 'APPROVED', 'BSIT', 'Crediting approved by Dean', 'Download your crediting document from the dashboard', 'Registrar Office', '', '', 'CREDITING', 1, NULL, NULL, NULL, 0, '2025-12-01 19:11:49', '2025-12-01 20:49:10'),
(428, NULL, '0122-1132', 'Unscheduled Subject Request', 'REQUIRED', 'B.S. Information Technology', 'Request for unscheduled subject offering', 'Submit request through the portal with required documents', 'Registrar Office', '', '', 'UNSCHEDULED', 1, NULL, NULL, NULL, 0, '2025-12-01 19:25:27', '2025-12-01 20:04:09'),
(438, NULL, '0122-1111', 'Admission Interview', 'REQUIRED', 'B.S. Information Technology', 'Admission interview required for freshmen students', 'Submit interview request through the portal', 'Admission Office', '', '', 'INTERVIEW', 1, NULL, NULL, NULL, 0, '2025-12-01 19:59:18', '2025-12-01 20:00:11'),
(439, NULL, '0122-1111', 'Admission Interview Request', 'SCHEDULED', 'B.S. Information Technology', 'Interview scheduled', 'Attend interview on 2025-12-05 at 05:01 via Face-to-Face at CCS 123', 'Admission Office', NULL, NULL, 'INTERVIEW', 0, NULL, NULL, NULL, 0, '2025-12-01 20:00:00', '2025-12-01 20:00:54'),
(440, NULL, '0122-1132', 'Unscheduled Subject Request', 'REQUIRED', 'B.S. Information Technology', 'Request for unscheduled subject offering', 'Submit request through the portal with required documents', 'Registrar Office', '', '', 'UNSCHEDULED', 0, NULL, NULL, NULL, 0, '2025-12-01 20:04:33', '2025-12-01 20:04:33'),
(441, NULL, '0122-0348', 'Subject Crediting', 'PENDING', 'B. S. Information Technology', 'Automatic crediting for Transferee', 'Submit required documents for crediting evaluation', 'Registrar Office', '1st', '2025 - 2026', 'CREDITING', 0, NULL, NULL, NULL, 0, '2025-12-01 20:50:17', '2025-12-01 20:50:17'),
(447, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-11 at 05:11 in ComLab 1. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 1, NULL, NULL, NULL, 0, '2025-12-01 21:08:02', '2025-12-01 21:09:02'),
(448, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-11 at 05:11 in ComLab 1. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:09:02', '2025-12-01 21:09:02'),
(449, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-04 at 05:11 in MacLab 1. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:09:25', '2025-12-01 21:09:25'),
(450, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-11 at 05:15 in ComLab 1. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:12:29', '2025-12-01 21:12:29'),
(451, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-12 at 05:14 in ComLab 1. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:13:41', '2025-12-01 21:13:41'),
(452, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-10 at 05:17 in MacLab 2. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:15:22', '2025-12-01 21:15:22'),
(453, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-18 at 05:19 in ComLab 1. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:17:12', '2025-12-01 21:17:12'),
(454, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-03 at 05:19 in MacLab 2. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:17:45', '2025-12-01 21:17:45'),
(455, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-10 at 05:20 in Room 101. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:20:04', '2025-12-01 21:20:04'),
(456, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-19 at 05:23 in MacLab 2. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, '2025-12-11', '05:22', 'rara', 1, '2025-12-01 21:20:43', '2025-12-01 21:21:40'),
(457, NULL, '0122-1132', 'ITEC103 (Intermediate Programming)', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-04 at 05:25 in MacLab 2. Check your email for details.', 'Bernardino, Mark', '2nd', '2025 - 2026', 'EXAM', 0, NULL, NULL, NULL, 0, '2025-12-01 21:23:10', '2025-12-01 21:23:10');

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

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `user_type`, `action`, `details`, `ip_address`, `created_at`) VALUES
(3, 299, 'program_head', 'Approved Crediting Request', 'Approved crediting request for student: Philip Jullan Birador (ID: 0122-0348)', NULL, '2025-12-01 20:05:56');

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
  `room` varchar(100) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_interviews`
--

INSERT INTO `admission_interviews` (`id`, `student_id`, `student_name`, `email`, `phone`, `program`, `preferred_date`, `preferred_time`, `message`, `status`, `scheduled_date`, `room`, `meeting_link`, `interview_notes`, `created_at`, `updated_at`) VALUES
(6, '0122-1111', 'Jonard Wilson', 'peejman92@gmail.com', '09817529936', NULL, NULL, NULL, NULL, 'scheduled', '2025-12-05 05:01:00', 'CCS 123', '', NULL, '2025-12-01 20:00:00', '2025-12-01 20:00:54');

-- --------------------------------------------------------

--
-- Table structure for table `alert_status_sent`
--

CREATE TABLE `alert_status_sent` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `status_key` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crediting_alerts`
--

CREATE TABLE `crediting_alerts` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_type` varchar(50) NOT NULL,
  `program` varchar(100) DEFAULT 'BSIT',
  `status` varchar(50) DEFAULT 'pending',
  `reason` text DEFAULT 'Automatic crediting for student type',
  `intervention` text DEFAULT 'Submit required documents for crediting evaluation',
  `semester` varchar(50) DEFAULT '2nd',
  `school_year` varchar(50) DEFAULT '2025 - 2026',
  `is_resolved` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crediting_alerts`
--

INSERT INTO `crediting_alerts` (`id`, `student_id`, `student_type`, `program`, `status`, `reason`, `intervention`, `semester`, `school_year`, `is_resolved`, `created_at`, `updated_at`) VALUES
(18, '0122-0348', 'Transferee', 'BSIT', 'warning', 'Automatic crediting for Transferee', 'Submit required documents for crediting evaluation', '2nd', '2025 - 2026', 1, '2025-12-01 19:11:46', '2025-12-01 19:35:47');

-- --------------------------------------------------------

--
-- Table structure for table `dean_crediting`
--

CREATE TABLE `dean_crediting` (
  `id` int(11) NOT NULL,
  `secretary_request_id` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `credited_subjects` text DEFAULT NULL,
  `evaluation_remarks` text DEFAULT NULL,
  `signature_file` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dean_approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dean_crediting`
--

INSERT INTO `dean_crediting` (`id`, `secretary_request_id`, `student_id`, `student_name`, `credited_subjects`, `evaluation_remarks`, `signature_file`, `status`, `created_at`, `dean_approved_at`) VALUES
(7, 8, '0122-0348', 'Philip Jullan Birador', 'hahahha', 'hahaha', 'signature_13_1764619556.png', 'pending', '2025-12-01 20:06:50', NULL);

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

--
-- Dumping data for table `dean_inc_requests`
--

INSERT INTO `dean_inc_requests` (`id`, `inc_request_id`, `student_name`, `student_id`, `student_email`, `professor`, `subject`, `inc_reason`, `inc_semester`, `date_submitted`, `dean_approved`, `status`, `signature`, `created_at`) VALUES
(21, 17, 'Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 'Bernardino, Mark', 'ITEC103 (Intermediate Programming)', 'haha', '2nd 2025 - 2026', '2025-12-01 21:01:46', 1, 'pending', 'data:image/png;base64,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', '2025-12-01 21:01:17'),
(22, 18, 'Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 'Bernardino, Mark', 'ITEC103 (Intermediate Programming)', '123', '2nd 2025 - 2026', '2025-12-01 21:08:47', 1, 'pending', 'data:image/png;base64,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', '2025-12-01 21:08:38');

-- --------------------------------------------------------

--
-- Table structure for table `dean_notifications`
--

CREATE TABLE `dean_notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` varchar(50) DEFAULT 'general',
  `request_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dean_notifications`
--

INSERT INTO `dean_notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`, `type`, `request_id`, `student_id`, `student_name`) VALUES
(29, NULL, 'New student registered: Jonard Wilson (0122-1111)', 1, '2025-12-01 19:02:28', 'general', NULL, NULL, NULL),
(30, NULL, 'New INC request from Philip Jullan Birador (0122-0348) for ITEC103 (Intermediate Programming) requires your approval.', 0, '2025-12-01 19:34:28', 'general', NULL, NULL, NULL),
(31, NULL, 'ðŸ“… New unscheduled subject request from Jaylo Ludovice (0122-1132) for ITEP 101 - Fundamentals', 0, '2025-12-01 20:02:59', 'unscheduled_request', 24, '0122-1132', 'Jaylo Ludovice'),
(32, NULL, 'âœ… You approved unscheduled subject request from Jaylo Ludovice (ITEP 101 - Fundamentals)', 0, '2025-12-01 20:04:09', 'unscheduled_approved', NULL, NULL, NULL),
(33, NULL, 'New INC request from Jaylo Ludovice (0122-1132) for ITEC103 (Intermediate Programming) requires your approval.', 0, '2025-12-01 21:01:17', 'general', NULL, NULL, NULL),
(34, NULL, 'New INC request from Jaylo Ludovice (0122-1132) for ITEC103 (Intermediate Programming) requires your approval.', 0, '2025-12-01 21:08:38', 'general', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient_email`, `recipient_name`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'jaylo.ludovice@lspu.edu.ph', 'Philip Jullan', 'Academic Alert: Incomplete Grade', 'Dear Philip Jullan,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 19:52:24'),
(2, 'jaylo.ludovice@lspu.edu.ph', 'Philip Jullan', 'Academic Alert: Incomplete Grade', 'Dear Philip Jullan,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 19:54:10'),
(3, 'jaylo.ludovice@lspu.edu.ph', 'Philip Jullan', 'Academic Alert: Incomplete Grade', 'Dear Philip Jullan,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 19:55:58'),
(4, 'jaylo.ludovice@lspu.edu.ph', 'Philip Jullan', 'Academic Alert: Incomplete Grade', 'Dear Philip Jullan,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 19:58:19'),
(5, 'jaylo.ludovice@lspu.edu.ph', 'Philip Jullan', 'Academic Alert: Incomplete Grade', 'Dear Philip Jullan,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 20:56:01'),
(6, 'ludoviceylo26@gmail.com', 'Jaylo', 'Academic Alert: Incomplete Grade', 'Dear Jaylo,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 20:56:02'),
(7, 'ludoviceylo26@gmail.com', 'Jaylo', 'Academic Alert: Incomplete Grade', 'Dear Jaylo,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'failed', '2025-12-01 20:58:08'),
(8, 'ludoviceylo26@gmail.com', 'Jaylo', 'Academic Alert: Incomplete Grade', 'Dear Jaylo,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 21:00:16'),
(9, 'ludoviceylo26@gmail.com', 'Jaylo', 'Academic Alert: Incomplete Grade', 'Dear Jaylo,\n\nYou have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming) due to missing final exam (score: 0).\n\nPlease contact your instructor: Bernardino, Mark to complete the requirements.\n\nProgram: B.S. Information Technology\nSemester: 2nd 2025-2026\n\nThank you.\nLSPU-CCS', 'sent', '2025-12-01 21:08:05'),
(10, 'ludoviceylo26@gmail.com', 'Jaylo', 'Exam Schedule: ITEC103 (Intermediate Programming)', 'Dear Jaylo,\n\nYour exam schedule for ITEC103 (Intermediate Programming) has been set:\n\nDate: 2025-12-11\nTime: 05:22\nVenue: rara\n\nInstructor: Bernardino, Mark\n\nPlease be on time.\n\nLSPU-CCS', 'sent', '2025-12-01 21:21:44');

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

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `teacher_id`, `subject_code`, `column_name`, `grade`, `created_at`, `updated_at`, `units`, `remarks`, `equivalent`, `notified`) VALUES
(2258, '0122-0348', '001', 'ITEC103', 'midterm_ATT', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2259, '0122-0348', '001', 'ITEC103', 'midterm_Behavior', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2260, '0122-0348', '001', 'ITEC103', 'midterm_RecPar', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2261, '0122-0348', '001', 'ITEC103', 'midterm_Act1', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2262, '0122-0348', '001', 'ITEC103', 'midterm_Act2', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2263, '0122-0348', '001', 'ITEC103', 'midterm_Act3', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2264, '0122-0348', '001', 'ITEC103', 'midterm_Act4', '89', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2265, '0122-0348', '001', 'ITEC103', 'midterm_Act5', '70', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2266, '0122-0348', '001', 'ITEC103', 'midterm_Q1', '70', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2267, '0122-0348', '001', 'ITEC103', 'midterm_Q2', '70', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2268, '0122-0348', '001', 'ITEC103', 'midterm_Q3', '70', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2269, '0122-0348', '001', 'ITEC103', 'midterm_Q4', '70', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2270, '0122-0348', '001', 'ITEC103', 'midterm_Exam', '5', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, NULL, 0),
(2271, '0122-0348', '001', 'ITEC103', 'midterm_total', '72.90', '2025-12-01 19:20:25', '2025-12-01 19:21:43', 3, NULL, '4.00', 0),
(2286, '0122-1132', '001', 'ITEC103', 'midterm_ATT', '90', '2025-12-01 19:21:09', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2287, '0122-1132', '001', 'ITEC103', 'midterm_Behavior', '90', '2025-12-01 19:21:09', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2288, '0122-1132', '001', 'ITEC103', 'midterm_RecPar', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2289, '0122-1132', '001', 'ITEC103', 'midterm_Act1', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2290, '0122-1132', '001', 'ITEC103', 'midterm_Act2', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2291, '0122-1132', '001', 'ITEC103', 'midterm_Act3', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2292, '0122-1132', '001', 'ITEC103', 'midterm_Act4', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2293, '0122-1132', '001', 'ITEC103', 'midterm_Act5', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2294, '0122-1132', '001', 'ITEC103', 'midterm_Q1', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2295, '0122-1132', '001', 'ITEC103', 'midterm_Q2', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2296, '0122-1132', '001', 'ITEC103', 'midterm_Q3', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2297, '0122-1132', '001', 'ITEC103', 'midterm_Q4', '90', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2298, '0122-1132', '001', 'ITEC103', 'midterm_total', '90.00', '2025-12-01 19:21:10', '2025-12-01 19:21:45', 3, NULL, '1.75', 0),
(2348, '0122-1132', '001', 'ITEC103', 'midterm_Act6', '90', '2025-12-01 19:21:45', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2353, '0122-1132', '001', 'ITEC103', 'midterm_Exam', '70', '2025-12-01 19:21:45', '2025-12-01 19:21:45', 3, NULL, NULL, 0),
(2355, '0122-0348', '001', 'ITEC103', 'finals_ATT', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2356, '0122-0348', '001', 'ITEC103', 'finals_Behavior', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2357, '0122-0348', '001', 'ITEC103', 'finals_Act1', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2358, '0122-0348', '001', 'ITEC103', 'finals_RecPar', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2359, '0122-0348', '001', 'ITEC103', 'finals_Act2', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2360, '0122-0348', '001', 'ITEC103', 'finals_Act3', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2361, '0122-0348', '001', 'ITEC103', 'finals_Act4', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2362, '0122-0348', '001', 'ITEC103', 'finals_Act5', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2363, '0122-0348', '001', 'ITEC103', 'finals_Act6', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2364, '0122-0348', '001', 'ITEC103', 'finals_Q1', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2365, '0122-0348', '001', 'ITEC103', 'finals_Q2', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2366, '0122-0348', '001', 'ITEC103', 'finals_Q3', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2367, '0122-0348', '001', 'ITEC103', 'finals_Q4', '70', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2368, '0122-0348', '001', 'ITEC103', 'finals_total', '68.00', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, '5.00', 0),
(2369, '0122-1132', '001', 'ITEC103', 'finals_ATT', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2370, '0122-1132', '001', 'ITEC103', 'finals_Behavior', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2371, '0122-1132', '001', 'ITEC103', 'finals_RecPar', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2372, '0122-1132', '001', 'ITEC103', 'finals_Act1', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2373, '0122-1132', '001', 'ITEC103', 'finals_Act2', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2374, '0122-1132', '001', 'ITEC103', 'finals_Act3', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2375, '0122-1132', '001', 'ITEC103', 'finals_Act4', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2376, '0122-1132', '001', 'ITEC103', 'finals_Act5', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2377, '0122-1132', '001', 'ITEC103', 'finals_Act6', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2378, '0122-1132', '001', 'ITEC103', 'finals_Q1', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:02', 3, NULL, NULL, 0),
(2379, '0122-1132', '001', 'ITEC103', 'finals_Q2', '90', '2025-12-01 19:26:50', '2025-12-01 20:56:02', 3, NULL, NULL, 0),
(2380, '0122-1132', '001', 'ITEC103', 'finals_Q3', '90', '2025-12-01 19:26:51', '2025-12-01 20:56:02', 3, NULL, NULL, 0),
(2381, '0122-1132', '001', 'ITEC103', 'finals_Q4', '90', '2025-12-01 19:26:51', '2025-12-01 20:56:02', 3, NULL, NULL, 0),
(2382, '0122-1132', '001', 'ITEC103', 'finals_Exam', '0', '2025-12-01 19:26:51', '2025-12-01 20:56:02', 3, NULL, NULL, 0),
(2383, '0122-1132', '001', 'ITEC103', 'finals_total', '76.00', '2025-12-01 19:26:51', '2025-12-01 20:56:02', 3, NULL, '3.00', 0),
(2384, '0122-0348', '001', 'ITEC103', 'final_grade', '0', '2025-12-01 19:26:51', '2025-12-01 20:56:02', 3, NULL, 'INC', 0),
(2398, '0122-0348', '001', 'ITEC103', 'finals_Exam', '0', '2025-12-01 19:31:37', '2025-12-01 20:56:01', 3, NULL, NULL, 0),
(2493, '0122-1132', '001', 'ITEC103', 'final_grade', '0', '2025-12-01 20:56:02', '2025-12-01 20:56:02', 3, NULL, 'INC', 0);

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

--
-- Dumping data for table `grade_columns`
--

INSERT INTO `grade_columns` (`id`, `subject_code`, `teacher_id`, `period`, `columns`, `created_at`, `updated_at`) VALUES
(3, 'ITEC103', '001', 'midterm', 'Act 6', '2025-12-01 19:21:20', '2025-12-01 19:21:20');

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

--
-- Dumping data for table `inc_requests`
--

INSERT INTO `inc_requests` (`id`, `student_name`, `student_id`, `student_email`, `user_id`, `professor`, `subject`, `inc_reason`, `inc_semester`, `date_submitted`, `dean_approved`, `status`, `signature`, `updated_at`) VALUES
(18, 'Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 308, 'Bernardino, Mark', 'ITEC103 (Intermediate Programming)', '123', '2nd 2025 - 2026', '2025-12-01 21:08:19', 1, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `interview_requests`
--

CREATE TABLE `interview_requests` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_email` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_time` time DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(240, '0122-0348', 'Crediting: As a Transferee, your crediting request has been submitted. Please provide required documents.', 'crediting_alert', 0, '2025-12-01 19:11:49'),
(241, '0122-0348', 'Your midterm grade in ITEC103 (Intermediate Programming) is 72.90. You need to improve to pass this subject.', 'warning', 0, '2025-12-01 19:21:43'),
(242, '0122-0348', 'Academic Warning: Your midterm grade for ITEC103 is 72.9. You need at least 76.4 in finals to pass.', 'academic_warning', 0, '2025-12-01 19:24:37'),
(243, '0122-1132', 'Action Required: As an Irregular student, you can request unscheduled subject offerings. Please submit your request through the portal.', 'unscheduled_required', 0, '2025-12-01 19:25:27'),
(264, '0122-1111', 'Action Required: As a Freshmen student, you need to request an admission interview. Please submit your request through the Admission Interview page.', 'interview_required', 0, '2025-12-01 19:59:18'),
(265, '2025', 'ðŸ”” New interview request from Jonard Wilson (0122-1111)', 'interview_request', 0, '2025-12-01 20:00:04'),
(266, '0122-1111', 'ðŸ“‹ Interview request submitted successfully! Secretary will send you the schedule soon.', 'interview_submitted', 0, '2025-12-01 20:00:07'),
(267, '0122-1111', 'âš ï¸ Academic Alert: Admission Interview Request - PENDING. Waiting for secretary to send interview schedule.', 'academic_alert', 0, '2025-12-01 20:00:07'),
(268, '0122-1111', 'âœ… Your admission interview has been scheduled for 2025-12-05 at 05:01 via Face-to-Face at CCS 123', 'interview_scheduled', 0, '2025-12-01 20:00:54'),
(269, '0122-1132', 'âœ… Your unscheduled subject request has been approved by the Dean. Document is ready for download.', 'unscheduled_approved', 0, '2025-12-01 20:04:09'),
(270, '0122-0348', 'Your crediting request has been approved by the Program Head and sent to Secretary for final processing.', 'crediting_approved', 0, '2025-12-01 20:05:56'),
(271, '0122-0348', 'âœ… Your crediting request has been approved by the Dean. Document is ready for download.', 'crediting_approved', 0, '2025-12-01 20:07:14'),
(277, '0122-1132', 'Academic Alert: You have an INCOMPLETE (INC) grade for ITEC103 (Intermediate Programming). Contact instructor: Bernardino, Mark to complete requirements.', 'academic_alert', 0, '2025-12-01 21:08:05'),
(288, '0122-1132', 'ðŸ“… Exam Schedule: ITEC103 (Intermediate Programming) on 2025-12-04 at 05:25 in MacLab 2. Check your email for details.', 'exam_schedule', 0, '2025-12-01 21:23:10');

-- --------------------------------------------------------

--
-- Table structure for table `ojt_requests`
--

CREATE TABLE `ojt_requests` (
  `id` int(11) NOT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `requirements_complete` tinyint(4) DEFAULT 0,
  `resume_file` varchar(255) DEFAULT NULL,
  `parent_consent` varchar(255) DEFAULT NULL,
  `enrollment_form` varchar(255) DEFAULT NULL,
  `medical_cert` varchar(255) DEFAULT NULL,
  `letter_inquiry` varchar(255) DEFAULT NULL,
  `letter_response` varchar(255) DEFAULT NULL,
  `application_letter` varchar(255) DEFAULT NULL,
  `recommendation_letter` varchar(255) DEFAULT NULL,
  `acceptance_letter` varchar(255) DEFAULT NULL,
  `internship_plan` varchar(255) DEFAULT NULL,
  `internship_contract_lspu` varchar(255) DEFAULT NULL,
  `internship_contract_company` varchar(255) DEFAULT NULL,
  `moa_draft` varchar(255) DEFAULT NULL,
  `certificate_employment` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ojt_requests`
--

INSERT INTO `ojt_requests` (`id`, `student_name`, `student_id`, `student_email`, `requirements_complete`, `resume_file`, `parent_consent`, `enrollment_form`, `medical_cert`, `letter_inquiry`, `letter_response`, `application_letter`, `recommendation_letter`, `acceptance_letter`, `internship_plan`, `internship_contract_lspu`, `internship_contract_company`, `moa_draft`, `certificate_employment`, `status`, `created_at`) VALUES
(1, 'Philip Jullan Birador', '0122-0348', 'jaylo.ludovice@lspu.edu.ph', 0, 'uploads/ojt_requirements/0122-0348_resume_file_1764620526.pdf', '', '', '', '', '', '', '', '', '', '', '', '', '', 'pending', '2025-12-01 20:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `program_head_crediting`
--

CREATE TABLE `program_head_crediting` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `student_type` varchar(20) DEFAULT NULL,
  `subjects_to_credit` text DEFAULT NULL,
  `transcript_info` text DEFAULT NULL,
  `transcript_file` varchar(255) DEFAULT NULL,
  `credited_subjects` text DEFAULT NULL,
  `evaluation_remarks` text DEFAULT NULL,
  `signature_file` varchar(255) DEFAULT NULL,
  `program_head_approved` tinyint(4) DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dean_remarks` text DEFAULT NULL,
  `dean_signature_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_head_crediting`
--

INSERT INTO `program_head_crediting` (`id`, `student_id`, `student_name`, `student_type`, `subjects_to_credit`, `transcript_info`, `transcript_file`, `credited_subjects`, `evaluation_remarks`, `signature_file`, `program_head_approved`, `status`, `created_at`, `updated_at`, `dean_remarks`, `dean_signature_file`) VALUES
(13, '0122-0348', 'Philip Jullan Birador', 'Transferee', 'haha', 'hahah', 'transcript_0122-0348_1764617747.pdf', 'hahahha', 'hahaha', 'signature_13_1764619556.png', 1, 'dean_approved', '2025-12-01 19:35:47', '2025-12-01 20:07:14', '', 'dean_signature_13_1764619634.png');

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
-- Table structure for table `secretary_crediting`
--

CREATE TABLE `secretary_crediting` (
  `id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `credited_subjects` text DEFAULT NULL,
  `evaluation_remarks` text DEFAULT NULL,
  `signature_file` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_to_dean_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `secretary_crediting`
--

INSERT INTO `secretary_crediting` (`id`, `request_id`, `student_id`, `student_name`, `credited_subjects`, `evaluation_remarks`, `signature_file`, `status`, `created_at`, `sent_to_dean_at`) VALUES
(8, 13, '0122-0348', 'Philip Jullan Birador', 'hahahha', 'hahaha', 'signature_13_1764619556.png', 'sent_to_dean', '2025-12-01 20:05:56', '2025-12-01 20:06:50');

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
(95, '0122-0353', 'Jhon Rey', 'Albino,', 'jhonrey.sample@gmail.com', 'e32513de5639317c909c31f7f7b532bc8ea9d5e3f08dbaa46e911540a86a99b3', '2025-11-11 06:11:10', 0, '2025-11-04 05:11:10', NULL, NULL, NULL),
(96, '0122-0417', 'Maria', 'Anonuevo,', 'maria.user@gmail.com', '0b77c83d84e016311274aa3ed99addc805454fcaf6134d0d2a6a39abd73a62b3', '2025-11-11 06:11:14', 0, '2025-11-04 05:11:14', NULL, NULL, NULL),
(97, '0122-0348', 'Philip', 'Birador,', 'jaylo.ludovice@lspu.edu.ph', '94576c6925bba4cf3999bb6c5da38c8702df93a85a3c023a0e256510caea0d17', '2025-11-11 06:11:17', 1, '2025-11-04 05:11:17', NULL, NULL, NULL),
(98, '0122-0415', 'Camille', 'Canoy,', 'camille.sample@gmail.com', '20f10865dff05b8e64cef7727d2792407887daffa0663c955e48d29ebaf5dbd7', '2025-11-11 06:11:21', 0, '2025-11-04 05:11:21', NULL, NULL, NULL),
(99, '0122-0414', 'Marc', 'Casuno,', 'marc.user@gmail.com', '9a7576a681344fd12eeeebe76cd6f7b63a786ab520f3fa5c8c3ef3495a33cd24', '2025-11-11 06:11:25', 0, '2025-11-04 05:11:25', NULL, NULL, NULL),
(100, '0122-1975', 'Bernadeth', 'Cortez,', 'bernadeth.sample@gmail.com', '19a4f0040838cb5bf1efe1cb0b4ca87ad54068f450d607744a6e43802e7eeb65', '2025-11-11 06:11:28', 0, '2025-11-04 05:11:28', NULL, NULL, NULL),
(101, '0122-0457', 'Kim', 'Dausin,', 'kim.user@gmail.com', '535da0d066237e4f357b5aba8ff3030fad306c535361623582118f46305132a4', '2025-11-11 06:11:31', 0, '2025-11-04 05:11:31', NULL, NULL, NULL),
(102, '0122-3402', 'Axel', 'Dionisio,', 'axel.sample@gmail.com', 'd257311d3e9d47ec03fe74ad1334be94d8ccf035c20d3c30f9637c2a084366fc', '2025-11-11 06:11:35', 0, '2025-11-04 05:11:35', NULL, NULL, NULL),
(103, 'CCSBSIT15-0047', 'Krisantha', 'Elca,', 'krisantha.user@gmail.com', '559deb1291fa42811d54fb40dede29297f46c19820743090b20b4274cdaac76e', '2025-11-11 06:11:38', 0, '2025-11-04 05:11:38', NULL, NULL, NULL),
(104, '0122-1775', 'Jhon', 'Felipe,', 'jhon.sample@gmail.com', 'f2d6e25ca1797c5e70acd79a8947ed02c986a480f24e3f068e3bfae5f2f2e9ec', '2025-11-11 06:11:42', 0, '2025-11-04 05:11:42', NULL, NULL, NULL),
(105, '0122-1628', 'John', 'Gallano,', 'john.user@gmail.com', 'b983076257885becd1dae06f2021b23bb6ce76e566d59886d58e53a93db79bd6', '2025-11-11 06:11:47', 0, '2025-11-04 05:11:47', NULL, NULL, NULL),
(106, '0122-2441', 'Renz', 'Guerrero,', 'renz.sample@gmail.com', 'f0400212ecf504bc66dc49b0b0a453850f2f56cceb88eb7b29ad4c3eb1c58018', '2025-11-11 06:11:57', 0, '2025-11-04 05:11:57', NULL, NULL, NULL),
(107, '0122-3268', 'Claud', 'Jimenez,', 'claud.user@gmail.com', 'fdb25b80fe12fd0a2d52a84cced083fb33e53ebbd97f89573427626a27812d10', '2025-11-11 06:12:01', 0, '2025-11-04 05:12:01', NULL, NULL, NULL),
(108, '0122-0584', 'Maria', 'Joya,', 'maria.sample@gmail.com', 'ac2691ff66dc14a7247f02b30aefa7ec99e60d1a11ce8663f4872a8d528a535c', '2025-11-11 06:12:05', 0, '2025-11-04 05:12:05', NULL, NULL, NULL),
(109, '0122-3632', 'Anne', 'Maceda,', 'anne.sample@gmail.com', '21e9ae93ec51ffc3af9df4b16c594f83f69c3802fa7fafb52a9df008114c1d4c', '2025-11-11 06:12:08', 0, '2025-11-04 05:12:08', NULL, NULL, NULL),
(110, 'CCSACT15-0036', 'Rhea', 'Mogro,', 'rhea.user@gmail.com', 'ab6082a73073ed334d997c678ebe978e28db6c8f4b611bcb0a8b2e96ca9fd405', '2025-11-11 06:12:12', 0, '2025-11-04 05:12:12', NULL, NULL, NULL),
(111, '0122-0783', 'Nicole', 'Nericua,', 'nicole.sample@gmail.com', '7f96233756c6430fdf616c4d6641ac4fb1e7e4d9c490ed6cd5b63f55e967a79c', '2025-11-11 06:12:15', 0, '2025-11-04 05:12:15', NULL, NULL, NULL),
(112, '0122-0784', 'John', 'Oracion,', 'john.sample2@gmail.com', 'fafc6d87cbb6aba3525a550328772bd32ddf67f4a61efde62bc806bb0ce2c912', '2025-11-11 06:12:19', 0, '2025-11-04 05:12:19', NULL, NULL, NULL),
(113, '0122-0647', 'Julianne', 'Pabale,', 'julianne.user@gmail.com', '424b5e05e060abefbbc1c7b7abecfb364c0aed175a1b31db28c8e353580e37b5', '2025-11-11 06:12:23', 0, '2025-11-04 05:12:23', NULL, NULL, NULL),
(114, '0122-3886', 'Lyndon', 'Pablo,', 'lyndon.sample@gmail.com', 'cc21f84adb4b1333d571097fea879ed3c7e53e9f723040e294bf115a8bb11327', '2025-11-11 06:12:27', 0, '2025-11-04 05:12:27', NULL, NULL, NULL),
(115, '0122-0702', 'Justine', 'Penaloza,', 'justine.user@gmail.com', 'eefa8ed4ef2307bf1614125ebc3df2d8d66bd665d9338606fea8b7cdb3e1ec39', '2025-11-11 06:12:30', 0, '2025-11-04 05:12:30', NULL, NULL, NULL),
(116, '0122-0643', 'Vincent', 'Ponce,', 'vincent.sample@gmail.com', 'ac2a87c541fc1d6f023555249319e72fde4a5792d890226ccdfbc3220cbf4bba', '2025-11-11 06:12:34', 0, '2025-11-04 05:12:34', NULL, NULL, NULL),
(117, '0122-3625', 'Ivan', 'Ramiro,', 'ivan.user@gmail.com', '2cc3c11eec53fe943c616388b41d22dda0288ddc27d6fef4c32358c1a290960a', '2025-11-11 06:12:38', 0, '2025-11-04 05:12:38', NULL, NULL, NULL),
(118, '0122-0876', 'Aljen', 'Roxas,', 'aljen.sample@gmail.com', '3ce070f729a6db28bf87ab3ca046480bbdd5ea56ca7aa658b665c28bd1b63e59', '2025-11-11 06:12:41', 0, '2025-11-04 05:12:41', NULL, NULL, NULL),
(119, '0122-3597', 'Jobert', 'Salvador,', 'jobert.user@gmail.com', 'baac36cc09e3b8476f31dfc838647d300f764d6a05978aef4642c8c68eeab0e1', '2025-11-11 06:12:45', 0, '2025-11-04 05:12:45', NULL, NULL, NULL),
(120, '0122-1154', 'Juan,', 'San', 'juan.sample@gmail.com', '5ac86a94703d735d4b3326086d74dcdbad3d1475e7892722d064d1a8aeb9b0e9', '2025-11-11 06:12:49', 0, '2025-11-04 05:12:49', NULL, NULL, NULL),
(121, '0122-2087', 'Sofia', 'Sy,', 'syshobe.0417@gmail.com', 'e2adaa6ec88f14dd10927236f5124641296d1f1dc1a4d2aa665a69643c60b6fb', '2025-12-08 14:04:43', 1, '2025-11-04 05:12:53', NULL, NULL, NULL),
(122, '0122-1390', 'Sasha', 'Tolentino,', 'sasha.sample@gmail.com', 'a284396fbdd7eca58a1e5c28ed28d645708a271acad94f00eb14af6e1d8d1cd5', '2025-11-11 06:13:00', 0, '2025-11-04 05:13:00', NULL, NULL, NULL),
(123, '0122-1064', 'Mark', 'Villarosa,', 'mark.user@gmail.com', '9e0d767ec0a50950baaed217007aede8d322a5f90d2cd4a6a170f371fb02bd99', '2025-11-11 06:13:03', 0, '2025-11-04 05:13:03', NULL, NULL, NULL),
(124, '0122-1135', 'Diane', 'Zotomayor,', 'diane.sample@gmail.com', '28f9b2b791c1786ef60fe7cbfe548ee2a29e00f402b9e0d54b94d42e9dbb7e1f', '2025-11-11 06:13:07', 0, '2025-11-04 05:13:07', NULL, NULL, NULL),
(125, '0122-1111', 'Jonard', 'Wilson', 'peejman92@gmail.com', 'b1cd595bb5e30f58ee6a18ac6ad8918b9863f241de122f6564536ba049147e9c', '2025-12-08 20:00:31', 1, '2025-12-01 19:00:31', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_notifications`
--

INSERT INTO `student_notifications` (`id`, `student_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, '0122-0348', 'OJT Deployment Now Available', 'Congratulations! You are now eligible to submit your OJT deployment requirements. As a 4th Year 1st Semester student, you can now prepare and submit your OJT documents through the portal.', 'ojt_eligible', 0, '2025-12-01 20:13:47'),
(2, '0122-1132', 'OJT Deployment Now Available', 'Congratulations! You are now eligible to submit your OJT deployment requirements. As a 4th Year 1st Semester student, you can now prepare and submit your OJT documents through the portal.', 'ojt_eligible', 0, '2025-12-01 20:56:09');

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

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_code`, `subject_title`, `program`, `year_level`, `section`, `teacher_id`, `school_year`, `semester`, `created_at`, `archived`) VALUES
(372, '0122-0348', 'ITEC103', 'Intermediate Programming', 'B. S. Information Technology', '1st Year', '1A', '001', '2025 - 2026', '1st', '2025-12-01 19:18:06', 0),
(373, '0122-1132', 'ITEC103', 'Intermediate Programming', 'B. S. Information Technology', '1st Year', '1A', '001', '2025 - 2026', '1st', '2025-12-01 19:18:06', 0),
(374, '0122-0348', 'ITEC104', 'Programming', 'B. S. Information Technology', '4th Year', '4A', '003', '2025 - 2026', '1st', '2025-12-01 20:13:30', 0),
(375, '0122-1132', 'ITEC104', 'Programming', 'B. S. Information Technology', '4th Year', '4A', '003', '2025 - 2026', '1st', '2025-12-01 20:13:30', 0);

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
(24, '0122-0000', 'Jonard', 'Tsu', 'geezyugo@gmail.com', NULL, NULL, NULL, '887988fe908d1f76ad52d7057decc16219cd8d75e29b958f750960ab2d82092a', '2025-12-08 20:09:58', 0, '2025-12-01 19:09:58', NULL, NULL, NULL, NULL, NULL);

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

--
-- Dumping data for table `teacher_notifications`
--

INSERT INTO `teacher_notifications` (`id`, `teacher_id`, `message`, `is_read`, `created_at`) VALUES
(13, '001', 'New INC request from Jaylo Ludovice (0122-1132) for ITEC103 (Intermediate Programming) - requires your approval', 0, '2025-12-01 21:08:19');

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
  `subject_code` varchar(50) DEFAULT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `eval_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `dean_signature` varchar(255) DEFAULT NULL,
  `dean_remarks` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unscheduled_requests`
--

INSERT INTO `unscheduled_requests` (`id`, `student_name`, `student_id`, `student_email`, `user_id`, `subject_code`, `subject_name`, `reason`, `eval_file`, `status`, `dean_signature`, `dean_remarks`, `approved_at`, `date_submitted`) VALUES
(24, 'Jaylo Ludovice', '0122-1132', 'ludoviceylo26@gmail.com', 308, 'ITEP 101', 'Fundamentals', 'I, Jaylo Ludovice, a student of Laguna State Polytechnic University, College of Computer Studies, would like to formally request for an unscheduled subject offering.\r\n\r\nI am currently enrolled as an Irregular student and need to take this subject to complete my academic requirements. The subject is not included in the regular schedule for this semester, which is why I am requesting for a special class arrangement.\r\n\r\nI understand that this request is subject to approval by the Dean and availability of faculty members. I am willing to comply with all requirements and schedules that will be set for this unscheduled subject.\r\n\r\nThank you for your consideration.\r\n\r\nRespectfully yours,\r\nJaylo Ludovice0122-1132', 'eval_0122-1132_1764619379.pdf', 'approved', 'dean_unscheduled_24_1764619449.png', '', '2025-12-01 20:04:09', '2025-12-02 04:02:59');

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
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `is_admin` tinyint(4) DEFAULT 0,
  `program` varchar(100) DEFAULT 'N/A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `first_name`, `middle_name`, `last_name`, `email`, `course`, `section`, `student_type`, `sex`, `contact_number`, `password`, `status`, `created_at`, `user_type`, `assigned_section`, `assigned_course`, `assigned_subject`, `assigned_lecture`, `assigned_lab`, `year_level`, `semester`, `school_year`, `profile_picture`, `is_active`, `is_admin`, `program`) VALUES
(1, '246', 'Dean/Super User', NULL, 'Dean/Super User', 'admin@ccs.edu', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-10 16:53:14', 'dean', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(235, '001', 'Mark', 'P.', 'Bernardino', '1mark.bernardino@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(236, '002', 'Edward', 'S.', 'Flores', '1edward.flores@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(237, '003', 'Reynalen', 'C.', 'Justo', '1reynalen.justo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(238, '004', 'Maria Laureen', 'B.', 'Miranda', '1marialaureen.miranda@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(239, '005', 'Gener', 'F.', 'Mosico', '1gener.mosico@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(240, '006', 'Reymart Joseph', 'P.', 'Pielago', '1reymartjoseph.pielago@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(241, '007', 'Rachiel', 'R.', 'Rivano', '1rachiel.rivano@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(242, '008', 'Margarita', '', 'Villanueva', '1margarita.villanueva@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(243, '009', 'Mia', 'V.', 'Villarica', '1mia.villarica@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(244, '010', 'Micah Joy', '', 'Formaran', '1micahjoy.formaran@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(245, '011', 'Roxanne', 'Rivera', 'Garbo', '1roxanne.garbo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(246, '012', 'Margielyn', 'A', 'Guico', '1margielyn.guico@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(247, '013', 'Francisco Kaleb', 'C.', 'Marquez', '1franciscokaleb.marquez@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(248, '014', 'Harlene Gabrielle', 'E.', 'Origines', '1harlenegabrielle.origines@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(249, '015', 'John Randolf', '', 'Penaredondo', '1johnrandolf.penaredondo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(250, '016', 'Jeremy', '', 'Reyes', '1jeremy.reyes@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(251, '017', 'Edison', 'V.', 'Templo', '1edison.templo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(252, '018', 'Zion Krehl', '', 'Astronomo', '1zionkrehl.astronomo@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(253, '019', 'Kristian Carlo', '', 'Garcia', '1kristiancarlo.garcia@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(254, '020', 'Kayecie', 'O.', 'Dorado', '1kayecie.dorado@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(255, '021', 'Cristian Jay', 'B.', 'Pollarca', '1cristianjay.pollarca@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(256, '022', 'Annie Belle', 'M.', 'Santiago', '1anniebelle.santiago@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(257, '023', 'Khrisna Cara', 'O.', 'Solde', '1khrisnacara.solde@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-12 04:23:00', 'teacher', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(258, '2025', 'Secretary', NULL, 'Sha', 'secretary@lspu.edu.ph', NULL, NULL, NULL, NULL, '09123456789', '123', 'approved', '2025-10-12 07:49:48', 'secretary', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(299, '0011', 'Program', NULL, 'Head', 'programhead@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-10-26 18:02:57', 'program_head', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(308, '0122-1132', 'Jaylo', 'Reyes', 'Ludovice', 'ludoviceylo26@gmail.com', '', '4A', 'Irregular', 'Male', '12312312331', '123', 'approved', '2025-11-03 08:31:36', 'student', NULL, NULL, NULL, NULL, NULL, '4th Year', '1st', NULL, 'uploads/profile_1762233673_IMG_5875.jpeg', 1, 0, 'N/A'),
(360, '0122-0348', 'Philip Jullan', 'Bandillo', 'Birador', 'jaylo.ludovice@lspu.edu.ph', '', '4A', 'Transferee', 'Male', '98765433210', '1234', 'approved', '2025-11-04 05:18:58', 'student', NULL, NULL, NULL, NULL, NULL, '4th Year', '1st', NULL, NULL, 1, 0, 'N/A'),
(361, '000', 'System', NULL, 'Administrator', 'sysadmin@lspu.edu.ph', NULL, NULL, NULL, NULL, NULL, '123', 'approved', '2025-11-30 16:17:31', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 4, 'N/A'),
(362, '0122-2087', 'Sofia', NULL, 'Sy,', 'syshobe.0417@gmail.com', 'IT', 'A', 'Freshmen', 'Male', '92457249249', '123', 'approved', '2025-12-01 13:08:10', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A'),
(363, '0122-1111', 'Jonard', NULL, 'Wilson', 'peejman92@gmail.com', 'IT', 'A', 'Freshmen', 'Male', '09921312312', '1234', 'approved', '2025-12-01 19:02:28', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 'N/A');

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
-- Indexes for table `alert_status_sent`
--
ALTER TABLE `alert_status_sent`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crediting_alerts`
--
ALTER TABLE `crediting_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `dean_crediting`
--
ALTER TABLE `dean_crediting`
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
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
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
-- Indexes for table `interview_requests`
--
ALTER TABLE `interview_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ojt_requests`
--
ALTER TABLE `ojt_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_head_crediting`
--
ALTER TABLE `program_head_crediting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_head_notifications`
--
ALTER TABLE `program_head_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `secretary_crediting`
--
ALTER TABLE `secretary_crediting`
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
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=458;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admission_interviews`
--
ALTER TABLE `admission_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `alert_status_sent`
--
ALTER TABLE `alert_status_sent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `crediting_alerts`
--
ALTER TABLE `crediting_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `dean_crediting`
--
ALTER TABLE `dean_crediting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `dean_inc_requests`
--
ALTER TABLE `dean_inc_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `dean_notifications`
--
ALTER TABLE `dean_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2494;

--
-- AUTO_INCREMENT for table `grade_columns`
--
ALTER TABLE `grade_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inc_requests`
--
ALTER TABLE `inc_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `interview_requests`
--
ALTER TABLE `interview_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- AUTO_INCREMENT for table `ojt_requests`
--
ALTER TABLE `ojt_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `program_head_crediting`
--
ALTER TABLE `program_head_crediting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `program_head_notifications`
--
ALTER TABLE `program_head_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `secretary_crediting`
--
ALTER TABLE `secretary_crediting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `secretary_notifications`
--
ALTER TABLE `secretary_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_invitations`
--
ALTER TABLE `student_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=376;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `teacher_invitations`
--
ALTER TABLE `teacher_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `teacher_notifications`
--
ALTER TABLE `teacher_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `unscheduled_requests`
--
ALTER TABLE `unscheduled_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=364;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
