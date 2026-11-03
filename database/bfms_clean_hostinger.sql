-- =====================================================================
-- Barangay Facility & Management System (BFMS) - Clean Production Dump
-- Barangay Lublub (P.D. Monfort North), Municipality of Dumangas, Iloilo
-- Generated: 2026-11-01 20:09:57
-- Optimized for Hostinger cPanel / hPanel MySQL & phpMyAdmin Import
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+08:00';

-- -----------------------------------------------------
-- Table structure for `sitios`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `sitios`;
CREATE TABLE `sitios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `population_cache` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Dumping data for table `sitios`
-- -----------------------------------------------------
INSERT INTO `sitios` (`id`, `name`, `code`, `description`, `population_cache`, `created_at`) VALUES
('1', 'Sitio Proper', 'PROPER', 'Central administration and heritage proper of Barangay Lublub', '0', '2026-10-22 10:42:09'),
('2', 'Sitio Centro', 'CENTRO', 'Main community and commercial center of Barangay Lublub', '0', '2026-10-22 10:42:09'),
('3', 'Sitio Weapons', 'WEAPONS', 'Upper agricultural and weapon craft district', '0', '2026-10-22 10:42:09'),
('4', 'Sitio Ilaya Facoma', 'FACOMA', 'Farmers cooperative (Facoma) and grains trade district', '0', '2026-10-22 10:42:09'),
('5', 'Sitio Sabang', 'SABANG', 'River junction and coastal water route settlement', '0', '2026-10-22 10:42:09'),
('6', 'Sitio Subdivision', 'SUBD', 'Organized residential neighborhood subdivision', '0', '2026-10-22 10:42:09'),
('7', 'Sitio Lanag', 'LANAG', 'Plain agricultural and wetland farmstead area', '0', '2026-10-22 10:42:09');

-- -----------------------------------------------------
-- Table structure for `system_settings`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Dumping data for table `system_settings`
-- -----------------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('barangay_name', 'Barangay Lublub (P.D. Monfort North)'),
('captain_name', 'Hon. Eliseo Rodriguez'),
('contact_number', '(033) 361-2045 / 0917-889-1234'),
('email', 'brgy.lublub.dumangas@gmail.com'),
('municipality', 'Dumangas'),
('office_hours', 'Monday - Friday, 8:00 AM - 5:00 PM'),
('province', 'Iloilo'),
('region', 'Region VI - Western Visayas'),
('secretary_name', 'Ms. Annielor Gulbique'),
('sk_chairperson', 'Janine Pearl D. Morales'),
('system_title', 'Smart Barangay Socio Economic Profiling and Social Services Allocation System'),
('treasurer_name', 'Ms. Joy Valiente'),
('zip_code', '5006');

-- -----------------------------------------------------
-- Table structure for `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('captain','secretary','treasurer','bhw','resident') NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `status` enum('active','pending','rejected','suspended') DEFAULT 'active',
  `assigned_sitio_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_user_sitio` (`assigned_sitio_id`),
  CONSTRAINT `fk_user_sitio` FOREIGN KEY (`assigned_sitio_id`) REFERENCES `sitios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Dumping data for table `users`
-- -----------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `full_name`, `contact_number`, `status`, `assigned_sitio_id`, `created_at`, `updated_at`) VALUES
('1', 'captain', 'captain.lublub@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'captain', 'Hon. Eliseo Rodriguez', '0917-123-4501', 'active', NULL, '2026-10-22 10:42:09', '2026-10-22 10:42:09'),
('2', 'secretary', 'secretary.lublub@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'secretary', 'Ms. Annielor Gulbique', '0918-234-5602', 'active', NULL, '2026-10-22 10:42:09', '2026-10-22 10:42:09'),
('10', 'bhw_proper', 'bhw.proper@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Maria Elena Santos', '0926-111-2201', 'active', '1', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('11', 'bhw_centro', 'bhw.centro@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Jocelyn Ramirez', '0926-111-2202', 'active', '2', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('12', 'bhw_weapons', 'bhw.weapons@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Teresa Villanueva', '0926-111-2203', 'active', '3', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('13', 'bhw_facoma', 'bhw.facoma@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Rosanna Dela Cruz', '0926-111-2204', 'active', '4', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('14', 'bhw_sabang', 'bhw.sabang@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Carmela Gonzales', '0926-111-2205', 'active', '5', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('15', 'bhw_subdivision', 'bhw.subdivision@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Liezl Fernandez', '0926-111-2206', 'active', '6', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('16', 'bhw_lanag', 'bhw.lanag@gmail.com', '$2y$10$iNKJQ29EJQeAXDlC8InQquuHf9er0qMqwAJQJOUtNrxVmRTHetNFe', 'bhw', 'BHW Marites Castillo', '0926-111-2207', 'active', '7', '2026-11-01 00:07:29', '2026-11-01 00:07:29'),
('21', 'treasurer', 'treasurer.lublub@gmail.com', '$2y$10$.vDnhCBb7pzx2oFt6GiTl.GOXj1lk/IflfTCi3hlTqegNQ/XRzNIK', 'treasurer', 'Ms. Joy Valiente', '0926-012-3410', 'active', NULL, '2026-11-01 11:42:45', '2026-11-01 11:42:45');

-- -----------------------------------------------------
-- Table structure for `officials`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `officials`;
CREATE TABLE `officials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `official_name` varchar(150) DEFAULT NULL,
  `position` varchar(80) NOT NULL,
  `committee` varchar(120) NOT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sitio_id` int(11) DEFAULT NULL,
  `term_start` date DEFAULT NULL,
  `term_end` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_official_sitio` (`sitio_id`),
  CONSTRAINT `fk_official_sitio` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Dumping data for table `officials`
-- -----------------------------------------------------
INSERT INTO `officials` (`id`, `user_id`, `official_name`, `position`, `committee`, `contact_number`, `email`, `sitio_id`, `term_start`, `term_end`, `photo`, `created_at`) VALUES
('1', '1', 'Hon. Eliseo Rodriguez', 'Punong Barangay', 'Executive Governance & Peace and Order', '0917-123-4501', 'captain.lublub@gmail.com', NULL, '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('2', '2', 'Ms. Annielor Gulbique', 'Barangay Secretary', 'Records & Administration', '0918-234-5602', 'secretary.lublub@gmail.com', NULL, '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('3', NULL, 'Hon. Lucky Alarcon', 'Barangay Kagawad', 'Committee on Peace and Order', '0919-345-6703', 'kagawad.sabang@gmail.com', '5', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('4', NULL, 'Hon. Marianita Cervas', 'Barangay Kagawad', 'Committee on Agriculture and Fisheries', '0920-456-7804', 'kagawad.facoma@gmail.com', '4', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('5', NULL, 'Hon. Edgar Decastillo', 'Barangay Kagawad', 'Committee on Environment & Disaster Preparedness', '0921-567-8905', 'kagawad.lanag@gmail.com', '7', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('6', NULL, 'Hon. Renier Dilag', 'Barangay Kagawad', 'Committee on Infrastructure and Public Works', '0922-678-9006', 'kagawad.subdivision@gmail.com', '6', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('7', NULL, 'Hon. Aladino Deblois', 'Barangay Kagawad', 'Committee on Education and Culture', '0923-789-0107', 'kagawad.weapons@gmail.com', '3', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('8', NULL, 'Hon. Mark Lester Dolendo', 'Barangay Kagawad', 'Committee on Health and Social Services', '0924-890-1208', 'kagawad.centro@gmail.com', '2', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('9', NULL, 'Hon. Arnold Do-oma', 'Barangay Kagawad', 'Committee on Appropriations and Finance', '0925-901-2309', 'kagawad.proper@gmail.com', '1', '2023-11-01', '2026-11-30', NULL, '2026-10-22 10:42:09'),
('10', '21', 'Ms. Joy Valiente', 'Barangay Treasurer', 'Finance, Budget & Ayuda Disbursements', '0926-012-3410', 'treasurer.lublub@gmail.com', NULL, '2023-11-01', '2026-11-30', NULL, '2026-11-01 11:42:45');

-- -----------------------------------------------------
-- Table structure for `approvers`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `approvers`;
CREATE TABLE `approvers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) NOT NULL,
  `approve_order` int(11) NOT NULL,
  `approver_role` enum('captain','secretary','treasurer','bhw') NOT NULL,
  `approver_title` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_doc_order` (`document_type`,`approve_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Dumping data for table `approvers`
-- -----------------------------------------------------
INSERT INTO `approvers` (`id`, `document_type`, `approve_order`, `approver_role`, `approver_title`, `description`, `created_at`) VALUES
('1', 'resident_signup', '1', 'secretary', 'Barangay Secretary Verification & Household Assignment', 'Verifies resident identity, credentials, civil/vulnerability profiling, and assigns household number', '2026-11-01 11:53:12'),
('2', 'assistance_program', '1', 'captain', 'Punong Barangay Review & Authorization', 'Executive validation and authorization of social welfare aid drive, budget, and targeting criteria', '2026-11-01 11:53:12');

-- -----------------------------------------------------
-- Table structure for `approvals`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `approvals`;
CREATE TABLE `approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) NOT NULL,
  `document_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `approve_order` int(11) NOT NULL DEFAULT 1 COMMENT '0: Draft, 1..: Pending hierarchy, 777: Approved, 555: Rejected',
  `current_approver_role` varchar(50) DEFAULT NULL,
  `submitted_by_user_id` int(11) DEFAULT NULL,
  `action_by_user_id` int(11) DEFAULT NULL,
  `action_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`document_type`,`document_id`),
  KEY `idx_order` (`approve_order`),
  KEY `idx_role` (`current_approver_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `approval_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `approval_logs`;
CREATE TABLE `approval_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_id` int(11) NOT NULL,
  `from_order` int(11) NOT NULL,
  `to_order` int(11) NOT NULL,
  `action` enum('submit','approve','reject','return_draft') NOT NULL,
  `action_by_user_id` int(11) NOT NULL,
  `action_by_name` varchar(150) NOT NULL,
  `action_by_role` varchar(50) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_app_log` (`approval_id`),
  KEY `idx_doc_log` (`document_type`,`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `households`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `households`;
CREATE TABLE `households` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `household_no` varchar(50) NOT NULL,
  `sitio_id` int(11) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `head_resident_name` varchar(150) DEFAULT NULL,
  `total_members` int(11) DEFAULT 1,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  `archived_by_user_id` int(11) DEFAULT NULL,
  `house_structure` enum('Concrete','Semi-Concrete','Light Materials / Wood','Makeshift') DEFAULT 'Concrete',
  `sanitation_facility` enum('Water-sealed Toilet','Pit Latrine','Shared / Communal','No Facility') DEFAULT 'Water-sealed Toilet',
  `water_source` enum('Piped Water / Tap','Deep Well','Protected Spring','Communal Faucet','Mineral/Bottled') DEFAULT 'Piped Water / Tap',
  `electricity_access` tinyint(1) DEFAULT 1,
  `waste_management` enum('Barangay Garbage Collection','Composting','Segregated Disposal','Burning / Open') DEFAULT 'Barangay Garbage Collection',
  `house_photo_path` varchar(255) DEFAULT NULL,
  `bhw_documentation_photo` varchar(255) DEFAULT NULL,
  `bhw_notes` text DEFAULT NULL,
  `bhw_assessed_by_user_id` int(11) DEFAULT NULL,
  `bhw_assessment_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `household_no` (`household_no`),
  KEY `fk_hh_sitio` (`sitio_id`),
  KEY `fk_hh_bhw` (`bhw_assessed_by_user_id`),
  CONSTRAINT `fk_hh_bhw` FOREIGN KEY (`bhw_assessed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_hh_sitio` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `residents`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `residents`;
CREATE TABLE `residents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `resident_code` varchar(30) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `suffix` varchar(15) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Separated','Solo Parent') NOT NULL DEFAULT 'Single',
  `birth_date` date NOT NULL,
  `age` int(11) NOT NULL,
  `contact_no` varchar(25) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sitio_id` int(11) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `household_no` varchar(50) DEFAULT NULL,
  `is_head_of_family` tinyint(1) DEFAULT 0,
  `family_role` varchar(50) DEFAULT 'Member',
  `family_members_count` int(11) DEFAULT 1,
  `is_pwd` tinyint(1) DEFAULT 0,
  `pwd_type` varchar(100) DEFAULT NULL,
  `pwd_id_no` varchar(50) DEFAULT NULL,
  `pwd_id_photo` varchar(255) DEFAULT NULL,
  `is_solo_parent` tinyint(1) DEFAULT 0,
  `solo_parent_id_no` varchar(50) DEFAULT NULL,
  `solo_parent_id_photo` varchar(255) DEFAULT NULL,
  `dependent_children_count` int(11) DEFAULT 0,
  `is_indigent` tinyint(1) DEFAULT 1,
  `monthly_income` decimal(10,2) DEFAULT 0.00,
  `income_source` varchar(100) DEFAULT NULL,
  `is_senior` tinyint(1) DEFAULT 0,
  `senior_id_no` varchar(50) DEFAULT NULL,
  `senior_id_photo` varchar(255) DEFAULT NULL,
  `is_4ps` tinyint(1) DEFAULT 0,
  `four_ps_id_no` varchar(50) DEFAULT NULL,
  `four_ps_id_photo` varchar(255) DEFAULT NULL,
  `is_youth` tinyint(1) DEFAULT 0,
  `is_student` tinyint(1) DEFAULT 0,
  `school_name` varchar(150) DEFAULT NULL,
  `student_id_no` varchar(50) DEFAULT NULL,
  `school_id_photo` varchar(255) DEFAULT NULL,
  `is_voter` tinyint(1) DEFAULT 1,
  `precinct_no` varchar(30) DEFAULT NULL,
  `voter_id_photo` varchar(255) DEFAULT NULL,
  `employment_status` enum('Employed','Self-Employed','Unemployed','Student','Retired') DEFAULT 'Unemployed',
  `occupation` varchar(100) DEFAULT NULL,
  `education_level` varchar(80) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `philhealth_no` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_no` varchar(30) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  `archived_by_user_id` int(11) DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `id_card_path` varchar(255) DEFAULT NULL,
  `house_photo_path` varchar(255) DEFAULT NULL,
  `bhw_documentation_photo` varchar(255) DEFAULT NULL,
  `bhw_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resident_code` (`resident_code`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_resident_sitio` (`sitio_id`),
  KEY `fk_resident_approver` (`approved_by_user_id`),
  CONSTRAINT `fk_resident_approver` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resident_sitio` FOREIGN KEY (`sitio_id`) REFERENCES `sitios` (`id`),
  CONSTRAINT `fk_resident_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `assistance_programs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assistance_programs`;
CREATE TABLE `assistance_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `program_code` varchar(50) NOT NULL,
  `category` enum('Financial Assistance','Food Assistance','Medical/Health Aid','Educational Assistance','PWD Welfare','Solo Parent Subsidy','Senior Citizen Aid','Calamity Relief') NOT NULL,
  `description` text NOT NULL,
  `fund_source` varchar(100) DEFAULT 'Barangay Calamity / Social Fund',
  `amount_per_beneficiary` decimal(10,2) DEFAULT 0.00,
  `in_kind_details` varchar(255) DEFAULT NULL,
  `target_sitio_id` int(11) DEFAULT NULL,
  `target_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_criteria`)),
  `distribution_date` date NOT NULL,
  `status` enum('Draft','Pending Approval','Ongoing','Completed','Rejected','Archived') DEFAULT 'Pending Approval',
  `created_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_code` (`program_code`),
  KEY `fk_program_sitio` (`target_sitio_id`),
  KEY `fk_program_creator` (`created_by_user_id`),
  CONSTRAINT `fk_program_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_program_sitio` FOREIGN KEY (`target_sitio_id`) REFERENCES `sitios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `assistance_beneficiaries`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `assistance_beneficiaries`;
CREATE TABLE `assistance_beneficiaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `status` enum('Enrolled','Claimed','Unclaimed') DEFAULT 'Enrolled',
  `claimed_at` datetime DEFAULT NULL,
  `released_by_user_id` int(11) DEFAULT NULL,
  `received_amount` decimal(10,2) DEFAULT 0.00,
  `received_items` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_program_resident` (`program_id`,`resident_id`),
  KEY `fk_beneficiary_resident` (`resident_id`),
  KEY `fk_beneficiary_releaser` (`released_by_user_id`),
  CONSTRAINT `fk_beneficiary_program` FOREIGN KEY (`program_id`) REFERENCES `assistance_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_beneficiary_releaser` FOREIGN KEY (`released_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_beneficiary_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `activity_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_log_user` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for `barangay_certificates`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `barangay_certificates`;
CREATE TABLE `barangay_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cert_no` varchar(50) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `cert_type` enum('Barangay Clearance','Certificate of Indigency','Certificate of Residency','First Time Jobseeker') NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `issued_by_user_id` int(11) NOT NULL,
  `signed_by_official` varchar(120) NOT NULL,
  `issue_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cert_no` (`cert_no`),
  KEY `fk_cert_resident` (`resident_id`),
  KEY `fk_cert_issuer` (`issued_by_user_id`),
  CONSTRAINT `fk_cert_issuer` FOREIGN KEY (`issued_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_cert_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
