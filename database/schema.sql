-- =====================================================================
-- Barangay Facility & Management System (BFMS)
-- Barangay Lublub (P.D. Monfort North), Municipality of Dumangas, Iloilo
-- Generated: 2026-11-01 20:09:55
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
('17', 'resident_test', 'testres@lublub.gov.ph', '$2y$10$HIXU0jXBQHt6B1RfWJ.msOwPVoRsvISmX5m62HxXjATqfQDBOy00G', 'resident', 'Test Resident Citizen', NULL, 'active', '1', '2026-11-01 03:50:43', '2026-11-01 03:50:43'),
('20', 'Kenneth', 'Kenneth@lublub.portal', '$2y$10$sqSYDbHKEKu8YwWPhtXuK.Esf2txEYwM4Kd2m1OOSzEiDo47KZ/Zq', 'resident', 'Kenneth Jade Atonducan Bares', '09234234322', 'active', '2', '2026-11-01 06:36:01', '2026-11-01 06:37:40'),
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
-- Dumping data for table `approvals`
-- -----------------------------------------------------
INSERT INTO `approvals` (`id`, `document_type`, `document_id`, `title`, `approve_order`, `current_approver_role`, `submitted_by_user_id`, `action_by_user_id`, `action_at`, `remarks`, `created_at`, `updated_at`) VALUES
('1', 'assistance_program', '1', 'Assistance Drive: Eskwelahagi', '777', NULL, '2', NULL, NULL, NULL, '2026-11-01 09:28:11', '2026-11-01 11:53:13'),
('2', 'assistance_program', '2', 'Assistance Drive: nabahaan', '777', NULL, '2', NULL, NULL, NULL, '2026-11-01 06:42:36', '2026-11-01 11:53:13'),
('3', 'assistance_program', '3', 'Assistance Drive: School Supplies', '777', NULL, '2', NULL, NULL, NULL, '2026-11-01 06:45:15', '2026-11-01 11:53:13'),
('4', 'assistance_program', '4', 'Assistance Drive: Sch Supp', '777', NULL, '2', NULL, NULL, NULL, '2026-11-01 07:07:35', '2026-11-01 11:53:13'),
('5', 'assistance_program', '5', 'Assistance Drive: Hatagay', '777', NULL, '1', NULL, NULL, NULL, '2026-11-01 07:14:59', '2026-11-01 11:53:13');

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
-- Dumping data for table `households`
-- -----------------------------------------------------
INSERT INTO `households` (`id`, `household_no`, `sitio_id`, `address_line`, `head_resident_name`, `total_members`, `is_archived`, `archived_at`, `archived_reason`, `archived_by_user_id`, `house_structure`, `sanitation_facility`, `water_source`, `electricity_access`, `waste_management`, `house_photo_path`, `bhw_documentation_photo`, `bhw_notes`, `bhw_assessed_by_user_id`, `bhw_assessment_date`, `created_at`, `updated_at`) VALUES
('3', 'HH-LUB-2026-0001', '1', 'asdasd', 'Juan Dela Cruz', '4', '0', NULL, NULL, NULL, 'Concrete', 'Water-sealed Toilet', 'Piped Water / Tap', '1', 'Barangay Garbage Collection', 'uploads/households/house_6ae6750c48165_1793488140.png', 'uploads/bhw_docs/bhwdoc_6ae6750c487c2_1793488140.png', '[PWD Member in House] [Infant / Newborn Present]', '10', '2026-11-01 07:09:00', '2026-11-01 07:09:00', '2026-11-01 10:12:09'),
('8', 'HH-LUB-2026-0002', '5', 'Purok Riverside, Near Jalaur River Dike', 'Rodrigo P. Alcantara', '6', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Shared / Communal', 'Deep Well', '1', 'Segregated Disposal', NULL, NULL, 'Low-lying riverbank area frequently submerged during flash floods and monsoons. High priority for flood calamity relief and evacuation monitoring.', '10', '2026-11-01 08:52:34', '2026-11-01 08:52:34', '2026-11-01 10:12:09'),
('9', 'HH-LUB-2026-0003', '3', 'Purok 3, Barangay Road Near Irrigation Canal', 'Marites G. Salcedo', '3', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Water-sealed Toilet', 'Piped Water / Tap', '1', 'Composting', NULL, NULL, 'Single earner household with 3 schooling children and 1 infant. Agricultural tenancy family prone to canal overflow during typhoon season.', '10', '2026-11-01 08:52:34', '2026-11-01 08:52:34', '2026-11-01 08:58:51'),
('10', 'HH-LUB-2026-0004', '7', 'Purok Bagong Silang, Hillside Boundary', 'Danilo B. Fernandez', '4', '0', NULL, NULL, NULL, 'Makeshift', 'Pit Latrine', 'Communal Faucet', '0', 'Composting', NULL, NULL, 'Dwelling constructed of bamboo slats and salvaged materials. No direct power connection (uses solar lamp). Priority candidate for barangay sanitation project.', '10', '2026-11-01 08:52:34', '2026-11-01 08:52:34', '2026-11-01 10:12:09'),
('11', 'HH-LUB-2026-0005', '6', 'Block 4, Lot 12, Sunrise Village', 'Carmela V. Bautista', '3', '0', NULL, NULL, NULL, 'Concrete', 'Water-sealed Toilet', 'Piped Water / Tap', '1', 'Barangay Garbage Collection', NULL, NULL, 'Two-story reinforced concrete dwelling. Elevated foundation with complete sanitation and permanent ILECO power connection.', '10', '2026-11-01 08:52:34', '2026-11-01 08:52:34', '2026-11-01 08:52:34'),
('12', 'HH-LUB-2026-0006', '4', 'Purok 1, Mangrove Border Near Fishpond Canal', 'Ernesto S. Villalobos', '4', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Water-sealed Toilet', 'Mineral/Bottled', '1', 'Barangay Garbage Collection', NULL, NULL, 'Low-lying coastal fisherman family dwelling affected by high spring tides, storm surges, and riverback flow. Extended family of 7.', '10', '2026-11-01 08:52:34', '2026-11-01 08:52:34', '2026-11-01 08:58:51'),
('13', 'HH-LUB-2026-0007', '1', 'Purok 1, Sitio Proper', 'Noel Gutierrez', '4', '0', NULL, NULL, NULL, 'Concrete', 'No Facility', 'Protected Spring', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Proper. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('14', 'HH-LUB-2026-0008', '1', 'Purok 2, Sitio Proper', 'Angelo Legaspi', '3', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Pit Latrine', 'Communal Faucet', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Proper. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('15', 'HH-LUB-2026-0009', '1', 'Purok 3, Sitio Proper', 'Reynaldo Santillan', '3', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'No Facility', 'Mineral/Bottled', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Proper. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('16', 'HH-LUB-2026-0010', '1', 'Purok 4, Sitio Proper', 'Nestor Reyes', '3', '0', NULL, NULL, NULL, 'Makeshift', 'Pit Latrine', 'Piped Water / Tap', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Proper. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('17', 'HH-LUB-2026-0011', '2', 'Purok 1, Sitio Centro', 'Efren Reyes', '4', '0', NULL, NULL, NULL, 'Semi-Concrete', 'No Facility', 'Deep Well', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Centro. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('18', 'HH-LUB-2026-0012', '2', 'Purok 2, Sitio Centro', 'Ramon Torres', '4', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Pit Latrine', 'Protected Spring', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Centro. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('19', 'HH-LUB-2026-0013', '2', 'Purok 3, Sitio Centro', 'Fernando Valdez', '3', '0', NULL, NULL, NULL, 'Makeshift', 'No Facility', 'Communal Faucet', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Centro. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('20', 'HH-LUB-2026-0014', '2', 'Purok 4, Sitio Centro', 'Roberto Cordero', '3', '0', NULL, NULL, NULL, 'Concrete', 'Pit Latrine', 'Mineral/Bottled', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Centro. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('21', 'HH-LUB-2026-0015', '2', 'Purok 1, Sitio Centro', 'Gilbert Cabrera', '3', '0', NULL, NULL, NULL, 'Semi-Concrete', 'No Facility', 'Piped Water / Tap', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Centro. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('22', 'HH-LUB-2026-0016', '3', 'Purok 1, Sitio Weapons', 'Jaime Ocampo', '5', '0', NULL, NULL, NULL, 'Makeshift', 'Water-sealed Toilet', 'Deep Well', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Weapons. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('23', 'HH-LUB-2026-0017', '3', 'Purok 2, Sitio Weapons', 'Allan Pascual', '4', '0', NULL, NULL, NULL, 'Concrete', 'Shared / Communal', 'Protected Spring', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Weapons. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('24', 'HH-LUB-2026-0018', '3', 'Purok 3, Sitio Weapons', 'Noel Fajardo', '4', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Water-sealed Toilet', 'Communal Faucet', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Weapons. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('25', 'HH-LUB-2026-0019', '3', 'Purok 4, Sitio Weapons', 'Angelo Perez', '4', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Shared / Communal', 'Mineral/Bottled', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Weapons. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('26', 'HH-LUB-2026-0020', '4', 'Purok 1, Sitio Ilaya Facoma', 'Christian Perez', '5', '0', NULL, NULL, NULL, 'Concrete', 'Water-sealed Toilet', 'Piped Water / Tap', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Ilaya Facoma. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('27', 'HH-LUB-2026-0021', '4', 'Purok 2, Sitio Ilaya Facoma', 'Gabriel Dela Cruz', '4', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Shared / Communal', 'Deep Well', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Ilaya Facoma. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('28', 'HH-LUB-2026-0022', '4', 'Purok 3, Sitio Ilaya Facoma', 'Efren Castillo', '4', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Water-sealed Toilet', 'Protected Spring', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Ilaya Facoma. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('29', 'HH-LUB-2026-0023', '4', 'Purok 4, Sitio Ilaya Facoma', 'Ramon Mercado', '4', '0', NULL, NULL, NULL, 'Makeshift', 'Shared / Communal', 'Communal Faucet', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Ilaya Facoma. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('30', 'HH-LUB-2026-0024', '5', 'Purok 1, Sitio Sabang', 'Arnel Mercado', '5', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Water-sealed Toilet', 'Mineral/Bottled', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Sabang. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('31', 'HH-LUB-2026-0025', '5', 'Purok 2, Sitio Sabang', 'Manuel Soriano', '4', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Shared / Communal', 'Piped Water / Tap', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Sabang. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('32', 'HH-LUB-2026-0026', '5', 'Purok 3, Sitio Sabang', 'Danilo Ocampo', '4', '0', NULL, NULL, NULL, 'Makeshift', 'Water-sealed Toilet', 'Deep Well', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Sabang. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('33', 'HH-LUB-2026-0027', '5', 'Purok 4, Sitio Sabang', 'Rolando Pascual', '4', '0', NULL, NULL, NULL, 'Concrete', 'Shared / Communal', 'Protected Spring', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Sabang. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('34', 'HH-LUB-2026-0028', '6', 'Purok 1, Sitio Subdivision', 'Jaime Pascual', '5', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Water-sealed Toilet', 'Communal Faucet', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Subdivision. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('35', 'HH-LUB-2026-0029', '6', 'Purok 2, Sitio Subdivision', 'Allan Fajardo', '4', '0', NULL, NULL, NULL, 'Makeshift', 'Shared / Communal', 'Mineral/Bottled', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Subdivision. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('36', 'HH-LUB-2026-0030', '6', 'Purok 3, Sitio Subdivision', 'Noel Perez', '4', '0', NULL, NULL, NULL, 'Concrete', 'Water-sealed Toilet', 'Piped Water / Tap', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Subdivision. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('37', 'HH-LUB-2026-0031', '6', 'Purok 4, Sitio Subdivision', 'Angelo Dela Cruz', '4', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Shared / Communal', 'Deep Well', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Subdivision. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('38', 'HH-LUB-2026-0032', '7', 'Purok 1, Sitio Lanag', 'Christian Dela Cruz', '5', '0', NULL, NULL, NULL, 'Makeshift', 'Water-sealed Toilet', 'Protected Spring', '1', 'Barangay Garbage Collection', NULL, NULL, 'Field verification completed for Sitio Lanag. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 10:12:09'),
('39', 'HH-LUB-2026-0033', '7', 'Purok 2, Sitio Lanag', 'Gabriel Castillo', '4', '0', NULL, NULL, NULL, 'Concrete', 'Shared / Communal', 'Communal Faucet', '0', 'Composting', NULL, NULL, 'Field verification completed for Sitio Lanag. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('40', 'HH-LUB-2026-0034', '7', 'Purok 3, Sitio Lanag', 'Efren Mercado', '4', '0', NULL, NULL, NULL, 'Semi-Concrete', 'Water-sealed Toilet', 'Mineral/Bottled', '1', 'Segregated Disposal', NULL, NULL, 'Field verification completed for Sitio Lanag. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52'),
('41', 'HH-LUB-2026-0035', '7', 'Purok 4, Sitio Lanag', 'Ramon Soriano', '4', '0', NULL, NULL, NULL, 'Light Materials / Wood', 'Shared / Communal', 'Piped Water / Tap', '1', 'Burning / Open', NULL, NULL, 'Field verification completed for Sitio Lanag. Assessment confirmed dwelling status and residency.', NULL, '2026-10-15 00:00:00', '2026-11-01 09:10:52', '2026-11-01 09:10:52');

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
-- Dumping data for table `residents`
-- -----------------------------------------------------
INSERT INTO `residents` (`id`, `user_id`, `resident_code`, `first_name`, `middle_name`, `last_name`, `suffix`, `nickname`, `gender`, `civil_status`, `birth_date`, `age`, `contact_no`, `email`, `sitio_id`, `address_line`, `household_no`, `is_head_of_family`, `family_role`, `family_members_count`, `is_pwd`, `pwd_type`, `pwd_id_no`, `pwd_id_photo`, `is_solo_parent`, `solo_parent_id_no`, `solo_parent_id_photo`, `dependent_children_count`, `is_indigent`, `monthly_income`, `income_source`, `is_senior`, `senior_id_no`, `senior_id_photo`, `is_4ps`, `four_ps_id_no`, `four_ps_id_photo`, `is_youth`, `is_student`, `school_name`, `student_id_no`, `school_id_photo`, `is_voter`, `precinct_no`, `voter_id_photo`, `employment_status`, `occupation`, `education_level`, `blood_type`, `medical_conditions`, `philhealth_no`, `emergency_contact_name`, `emergency_contact_no`, `status`, `is_archived`, `archived_at`, `archived_reason`, `archived_by_user_id`, `registered_at`, `approved_at`, `approved_by_user_id`, `rejection_reason`, `photo_path`, `id_card_path`, `house_photo_path`, `bhw_documentation_photo`, `bhw_notes`) VALUES
('3', NULL, 'LUB-2026-0001', 'Juan Dela', NULL, 'Cruz', NULL, NULL, 'Male', 'Married', '2012-10-31', '14', '09896565', NULL, '1', 'asdasd', 'HH-LUB-2026-0001', '1', 'Head', '5', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', NULL, '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Unemployed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 07:09:00', '2026-11-01 07:09:00', '10', NULL, NULL, NULL, 'uploads/households/house_6ae6750c48165_1793488140.png', NULL, NULL),
('5', NULL, 'LUB-2026-0004', 'Maria', '', 'Poblacion', '', '', 'Female', 'Married', '2002-12-01', '23', '2998989', '', '1', 'asdasd', 'HH-LUB-2026-0001', '0', 'Spouse', '1', '1', 'Orthopedic', '1295012', 'uploads/documents/pwd_6ae68977a6a9c_1793493367.jpg', '0', '', NULL, '1', '1', '4000.00', '', '0', '', NULL, '0', '', NULL, '1', '0', '', '', NULL, '0', '', NULL, 'Unemployed', '', '', '', 'Pregnant / Lactating', '1261261', '', '', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:36:07', '2026-11-01 08:36:07', '10', NULL, 'uploads/residents/res_6ae68977a62a9_1793493367.jpg', 'uploads/ids/id_6ae68977a6512_1793493367.jpg', 'uploads/households/house_6ae68977a66f2_1793493367.jpg', NULL, NULL),
('6', NULL, 'LUB-2026-0005', 'Rodrigo', 'Perez', 'Alcantara', '', 'Roding', 'Male', 'Married', '1978-04-12', '48', '09171234561', '', '5', 'Purok Riverside, Near Jalaur River Dike', 'HH-LUB-2026-0002', '1', 'Head', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '7500.00', 'River fishing & labor', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0012A', NULL, 'Self-Employed', 'Fisherman', 'High School Level', 'O+', '', '12-003492819-1', 'Elena Alcantara', '09181234562', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('7', NULL, 'LUB-2026-0006', 'Elena', 'Magbanua', 'Alcantara', '', 'Len', 'Female', 'Married', '1981-08-20', '45', '09181234562', '', '5', 'Purok Riverside, Near Jalaur River Dike', 'HH-LUB-2026-0002', '0', 'Spouse', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', 'None', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0012A', NULL, 'Unemployed', 'Housekeeper', 'High School Graduate', 'A+', 'Hypertension', '', 'Rodrigo Alcantara', '09171234561', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('8', NULL, 'LUB-2026-0007', 'Christian', 'Magbanua', 'Alcantara', '', 'Ian', 'Male', 'Single', '2009-02-14', '17', '09191234563', '', '5', 'Purok Riverside, Near Jalaur River Dike', 'HH-LUB-2026-0002', '0', 'Son', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', '', '0', '', NULL, '0', '', NULL, '1', '1', 'Dumangas National High School', 'DNHS-2024-0891', NULL, '0', '', NULL, 'Student', 'Grade 11 Student', 'High School Level', 'O+', '', '', 'Elena Alcantara', '09181234562', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('9', NULL, 'LUB-2026-0008', 'Princess Joy', 'Magbanua', 'Alcantara', '', 'Joy', 'Female', 'Single', '2014-06-18', '12', '', '', '5', 'Purok Riverside, Near Jalaur River Dike', 'HH-LUB-2026-0002', '0', 'Daughter', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', '', '0', '', NULL, '0', '', NULL, '0', '1', 'Lublub Elementary School', 'LES-2023-4102', NULL, '0', '', NULL, 'Student', 'Grade 6 Pupil', 'Elementary Level', 'A+', '', '', 'Rodrigo Alcantara', '09171234561', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('10', NULL, 'LUB-2026-0009', 'Teodora', 'Palma', 'Alcantara', '', 'Lola Doring', 'Female', 'Widowed', '1954-11-05', '72', '', '', '5', 'Purok Riverside, Near Jalaur River Dike', 'HH-LUB-2026-0002', '0', 'Mother', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '1000.00', 'Social Pension for Indigent Seniors', '1', 'OSCA-DUM-2014-0491', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0012A', NULL, 'Retired', 'Senior Citizen', 'Elementary Graduate', 'O+', 'Arthritis, Mild Visual Impairment', '12-008471928-3', 'Rodrigo Alcantara', '09171234561', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('11', NULL, 'LUB-2026-0010', 'Marites', 'Gomez', 'Salcedo', '', 'Tess', 'Female', 'Solo Parent', '1985-03-24', '41', '09204567891', '', '3', 'Purok 3, Barangay Road Near Irrigation Canal', 'HH-LUB-2026-0003', '1', 'Head', '4', '0', '', '', NULL, '1', 'SP-LUB-2021-0029', NULL, '3', '1', '6000.00', 'Farm tenancy & vegetable vending', '0', '', NULL, '1', '4PS-ILO-DUM-881920', NULL, '0', '0', '', '', NULL, '1', '0008B', NULL, 'Self-Employed', 'Vendor / Agricultural Worker', 'High School Level', 'B+', '', '12-004928172-9', 'Joshua Salcedo', '09204567892', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('12', NULL, 'LUB-2026-0011', 'Joshua', 'Gomez', 'Salcedo', '', 'Josh', 'Male', 'Single', '2010-09-15', '16', '09204567892', '', '3', 'Purok 3, Barangay Road Near Irrigation Canal', 'HH-LUB-2026-0003', '0', 'Son', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', '', '0', '', NULL, '1', '4PS-ILO-DUM-881920', NULL, '1', '1', 'Dumangas National High School', 'DNHS-2025-1102', NULL, '0', '', NULL, 'Student', 'Grade 10 Student', 'Junior High School', 'B+', '', '', 'Marites Salcedo', '09204567891', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('13', NULL, 'LUB-2026-0012', 'Angelica', 'Gomez', 'Salcedo', '', 'Angie', 'Female', 'Single', '2012-11-28', '14', '', '', '3', 'Purok 3, Barangay Road Near Irrigation Canal', 'HH-LUB-2026-0003', '0', 'Daughter', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', '', '0', '', NULL, '1', '4PS-ILO-DUM-881920', NULL, '0', '1', 'Lublub National High School', 'LNHS-2024-0319', NULL, '0', '', NULL, 'Student', 'Grade 8 Student', 'Junior High School', 'O+', '', '', 'Marites Salcedo', '09204567891', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('14', NULL, 'LUB-2026-0013', 'Danilo', 'Barredo', 'Fernandez', '', 'Danny', 'Male', 'Married', '1974-05-19', '52', '09213456781', '', '7', 'Purok Bagong Silang, Hillside Boundary', 'HH-LUB-2026-0004', '1', 'Head', '4', '1', 'Physical / Orthopedic Disability', 'PWD-DUM-2018-0114', NULL, '0', '', NULL, '0', '1', '5500.00', 'Bamboo craft & repair', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0015A', NULL, 'Self-Employed', 'Handicraft Maker', 'Elementary Graduate', 'AB+', 'Lower limb mobility impairment (crutches)', '12-005829103-2', 'Leticia Fernandez', '09213456782', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('15', NULL, 'LUB-2026-0014', 'Leticia', 'Santillan', 'Fernandez', '', 'Letty', 'Female', 'Married', '1976-12-03', '50', '09213456782', '', '7', 'Purok Bagong Silang, Hillside Boundary', 'HH-LUB-2026-0004', '0', 'Spouse', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '4000.00', 'Food vending', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0015A', NULL, 'Self-Employed', 'Street Vendor', 'High School Level', 'B+', '', '', 'Danilo Fernandez', '09213456781', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('16', NULL, 'LUB-2026-0015', 'John Paul', 'Santillan', 'Fernandez', '', 'JP', 'Male', 'Single', '2007-04-10', '19', '09213456783', 'jp.fernandez@gmail.com', '7', 'Purok Bagong Silang, Hillside Boundary', 'HH-LUB-2026-0004', '0', 'Son', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', '', '0', '', NULL, '0', '', NULL, '1', '1', 'ISAT University Dumangas Campus', 'ISATU-2024-04192', NULL, '1', '0015A', NULL, 'Student', 'College Student (BSIT 2nd Year)', 'College Level', 'O+', '', '', 'Leticia Fernandez', '09213456782', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('17', NULL, 'LUB-2026-0016', 'Carmela', 'Valdez', 'Bautista', '', 'Mel', 'Female', 'Married', '1971-09-08', '55', '09228901234', 'carmela.bautista@gmail.com', '6', 'Block 4, Lot 12, Sunrise Village', 'HH-LUB-2026-0005', '1', 'Head', '4', '0', '', '', NULL, '0', '', NULL, '0', '0', '38000.00', 'Private accounting employment', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0018C', NULL, 'Employed', 'Accountant', 'College Graduate', 'A+', '', '12-009182736-4', 'Vicente Bautista', '09228901235', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('18', NULL, 'LUB-2026-0017', 'Vicente', 'Reyes', 'Bautista', '', 'Vic', 'Male', 'Married', '1968-01-22', '58', '09228901235', '', '6', 'Block 4, Lot 12, Sunrise Village', 'HH-LUB-2026-0005', '0', 'Spouse', '4', '0', '', '', NULL, '0', '', NULL, '0', '0', '25000.00', 'Transport operations', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0018C', NULL, 'Self-Employed', 'Transport Business', 'College Graduate', 'O+', '', '12-009182736-5', 'Carmela Bautista', '09228901234', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('19', NULL, 'LUB-2026-0018', 'Bea Nicole', 'Valdez', 'Bautista', '', 'Bea', 'Female', 'Single', '2005-07-30', '21', '09228901236', 'bea.bautista@wvsu.edu.ph', '6', 'Block 4, Lot 12, Sunrise Village', 'HH-LUB-2026-0005', '0', 'Daughter', '4', '0', '', '', NULL, '0', '', NULL, '0', '0', '0.00', '', '0', '', NULL, '0', '', NULL, '1', '1', 'West Visayas State University', 'WVSU-2022-0941', NULL, '1', '0018C', NULL, 'Student', 'Nursing Student (BSN 3rd Year)', 'College Level', 'A+', '', '', 'Carmela Bautista', '09228901234', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', '', NULL, NULL, NULL, NULL, NULL),
('20', NULL, 'LUB-2026-0019', 'Ernesto', 'Salazar', 'Villalobos', '', 'Erning', 'Male', 'Married', '1963-02-17', '63', '09235678901', '', '4', 'Purok 1, Mangrove Border Near Fishpond Canal', 'HH-LUB-2026-0006', '1', 'Head', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '8000.00', 'Municipal fishing', '1', 'OSCA-DUM-2023-0182', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0010B', NULL, 'Self-Employed', 'Fisherman', 'Elementary Graduate', 'O+', 'Hypertension', '12-007281920-5', 'Rosario Villalobos', '09235678902', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('21', NULL, 'LUB-2026-0020', 'Rosario', 'Diaz', 'Villalobos', '', 'Charing', 'Female', 'Married', '1965-10-11', '61', '09235678902', '', '4', 'Purok 1, Mangrove Border Near Fishpond Canal', 'HH-LUB-2026-0006', '0', 'Spouse', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '3500.00', 'Fish drying and vending', '1', 'OSCA-DUM-2025-0041', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0010B', NULL, 'Self-Employed', 'Fish Vendor', 'Elementary Graduate', 'O+', '', '12-007281920-6', 'Ernesto Villalobos', '09235678901', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('22', NULL, 'LUB-2026-0021', 'Dante', 'Diaz', 'Villalobos', '', 'Dan', 'Male', 'Single', '1994-03-05', '32', '09235678903', '', '4', 'Purok 1, Mangrove Border Near Fishpond Canal', 'HH-LUB-2026-0006', '0', 'Son', '4', '1', 'Visual Impairment (Low Vision)', 'PWD-DUM-2020-0083', NULL, '0', '', NULL, '0', '1', '3000.00', 'Fishnet mending', '0', '', NULL, '0', '', NULL, '0', '0', '', '', NULL, '1', '0010B', NULL, 'Self-Employed', 'Net Repairer', 'High School Graduate', 'B+', 'Severe myopia and partial corneal opacity', '', 'Ernesto Villalobos', '09235678901', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('23', NULL, 'LUB-2026-0022', 'Althea Mae', 'Diaz', 'Villalobos', '', 'Thea', 'Female', 'Single', '2011-08-12', '15', '', '', '4', 'Purok 1, Mangrove Border Near Fishpond Canal', 'HH-LUB-2026-0006', '0', 'Granddaughter', '4', '0', '', '', NULL, '0', '', NULL, '0', '1', '0.00', '', '0', '', NULL, '0', '', NULL, '1', '1', 'Dumangas National High School', 'DNHS-2024-1920', NULL, '0', '', NULL, 'Student', 'Grade 9 Student', 'Junior High School', 'O+', '', '', 'Ernesto Villalobos', '09235678901', 'approved', '0', NULL, NULL, NULL, '2026-11-01 08:58:51', '2026-11-01 08:58:51', '1', NULL, NULL, NULL, NULL, NULL, NULL),
('24', NULL, 'LUB-2026-0023', 'Noel', 'P.', 'Gutierrez', NULL, NULL, 'Male', 'Married', '1974-08-22', '52', '09486133906', NULL, '1', 'Purok 1, Sitio Proper', 'HH-LUB-2026-0007', '1', 'Head', '3', '1', 'Hearing Impairment', 'PWD-4892', NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Electrician', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Electrician', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('25', NULL, 'LUB-2026-0024', 'Dianne', 'M.', 'Gutierrez', NULL, NULL, 'Female', 'Married', '1977-01-02', '49', '09526370227', NULL, '1', 'Purok 1, Sitio Proper', 'HH-LUB-2026-0007', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('26', NULL, 'LUB-2026-0025', 'Maricel', 'G.', 'Gutierrez', NULL, NULL, 'Female', 'Single', '2007-03-09', '19', '09176981092', NULL, '1', 'Purok 1, Sitio Proper', 'HH-LUB-2026-0007', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Capiz State University - Burias Campus', 'STU-622017', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('27', NULL, 'LUB-2026-0026', 'Angelo', 'P.', 'Legaspi', NULL, NULL, 'Male', 'Married', '1967-09-25', '59', '09852857376', NULL, '1', 'Purok 2, Sitio Proper', 'HH-LUB-2026-0008', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Tailor / Seamstress', '0', NULL, NULL, '1', '4PS-6237529', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Tailor / Seamstress', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('28', NULL, 'LUB-2026-0027', 'Elena', 'M.', 'Legaspi', NULL, NULL, 'Female', 'Married', '1972-02-06', '54', '09685010889', NULL, '1', 'Purok 2, Sitio Proper', 'HH-LUB-2026-0008', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-1490633', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('29', NULL, 'LUB-2026-0028', 'Angelo', 'L.', 'Legaspi', NULL, NULL, 'Male', 'Single', '2014-04-14', '12', '09766638359', NULL, '1', 'Purok 2, Sitio Proper', 'HH-LUB-2026-0008', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-2124538', NULL, '0', '1', 'Dumarao Vocational Technical School', 'STU-916125', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('30', NULL, 'LUB-2026-0029', 'Reynaldo', 'P.', 'Santillan', NULL, NULL, 'Male', 'Married', '1960-01-01', '66', '09946388798', NULL, '1', 'Purok 3, Sitio Proper', 'HH-LUB-2026-0009', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Fisherman', '1', 'OSCA-84249', NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Fisherman', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('31', NULL, 'LUB-2026-0030', 'Rosario', 'M.', 'Santillan', NULL, NULL, 'Female', 'Married', '1961-03-10', '65', '09634809104', NULL, '1', 'Purok 3, Sitio Proper', 'HH-LUB-2026-0009', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '1', 'OSCA-50485', NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('32', NULL, 'LUB-2026-0031', 'Giselle', 'S.', 'Santillan', NULL, NULL, 'Female', 'Single', '2012-05-19', '14', '09886006870', NULL, '1', 'Purok 3, Sitio Proper', 'HH-LUB-2026-0009', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub National High School', 'STU-758209', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('33', NULL, 'LUB-2026-0032', 'Nestor', 'P.', 'Reyes', NULL, NULL, 'Male', 'Married', '1985-02-04', '41', '09464815307', NULL, '1', 'Purok 4, Sitio Proper', 'HH-LUB-2026-0010', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Construction Worker', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Construction Worker', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('34', NULL, 'LUB-2026-0033', 'Rowena', 'M.', 'Reyes', NULL, NULL, 'Female', 'Married', '1989-04-14', '37', '09203209038', NULL, '1', 'Purok 4, Sitio Proper', 'HH-LUB-2026-0010', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('35', NULL, 'LUB-2026-0034', 'Manuel', 'R.', 'Reyes', NULL, NULL, 'Male', 'Single', '2010-06-24', '16', '09933465608', NULL, '1', 'Purok 4, Sitio Proper', 'HH-LUB-2026-0010', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Filamer Christian University', 'STU-527069', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('36', NULL, 'LUB-2026-0035', 'Efren', 'P.', 'Reyes', NULL, NULL, 'Male', 'Married', '1978-03-07', '48', '09539176878', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0011', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Barangay Tanod', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Barangay Tanod', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('37', NULL, 'LUB-2026-0036', 'Rowena', 'M.', 'Reyes', NULL, NULL, 'Female', 'Married', '1981-05-18', '45', '09345989986', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0011', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('38', NULL, 'LUB-2026-0037', 'Analyn', 'R.', 'Reyes', NULL, NULL, 'Female', 'Single', '2012-07-02', '14', '09919630109', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0011', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub National High School', 'STU-149657', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('39', NULL, 'LUB-2026-0038', 'Ramon', 'P.', 'Torres', NULL, NULL, 'Male', 'Married', '1971-04-10', '55', '09382392237', NULL, '2', 'Purok 2, Sitio Centro', 'HH-LUB-2026-0012', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Daycare Worker', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Daycare Worker', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('40', NULL, 'LUB-2026-0039', 'Cristina', 'M.', 'Torres', NULL, NULL, 'Female', 'Married', '1972-06-22', '54', '09816677014', NULL, '2', 'Purok 2, Sitio Centro', 'HH-LUB-2026-0012', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('41', NULL, 'LUB-2026-0040', 'Joel', 'T.', 'Torres', NULL, NULL, 'Male', 'Single', '2010-08-07', '16', '09504031811', NULL, '2', 'Purok 2, Sitio Centro', 'HH-LUB-2026-0012', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Filamer Christian University', 'STU-726006', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('42', NULL, 'LUB-2026-0041', 'Fernando', 'P.', 'Valdez', NULL, NULL, 'Male', 'Married', '1964-05-13', '62', '09816626753', NULL, '2', 'Purok 3, Sitio Centro', 'HH-LUB-2026-0013', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Farmer', '1', 'OSCA-56021', NULL, '1', '4PS-7567207', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Farmer', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('43', NULL, 'LUB-2026-0042', 'Princess', 'M.', 'Valdez', NULL, NULL, 'Female', 'Married', '1965-07-26', '61', '09380344339', NULL, '2', 'Purok 3, Sitio Centro', 'HH-LUB-2026-0013', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '1', 'OSCA-69662', NULL, '1', '4PS-3372186', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('44', NULL, 'LUB-2026-0043', 'Angelica', 'V.', 'Valdez', NULL, NULL, 'Female', 'Single', '2008-09-12', '18', '09304421952', NULL, '2', 'Purok 3, Sitio Centro', 'HH-LUB-2026-0013', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-7928972', NULL, '0', '1', 'Lublub Elementary School', 'STU-452754', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('45', NULL, 'LUB-2026-0044', 'Roberto', 'P.', 'Cordero', NULL, NULL, 'Male', 'Married', '1989-06-16', '37', '09182570109', NULL, '2', 'Purok 4, Sitio Centro', 'HH-LUB-2026-0014', '1', 'Head', '3', '1', 'Learning Disability', 'PWD-3405', NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Carpenter', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Carpenter', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('46', NULL, 'LUB-2026-0045', 'Dianne', 'M.', 'Cordero', NULL, NULL, 'Female', 'Married', '1994-08-03', '32', '09653870900', NULL, '2', 'Purok 4, Sitio Centro', 'HH-LUB-2026-0014', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('47', NULL, 'LUB-2026-0046', 'Nestor', 'C.', 'Cordero', NULL, NULL, 'Male', 'Single', '2006-01-17', '20', '09108925606', NULL, '2', 'Purok 4, Sitio Centro', 'HH-LUB-2026-0014', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Capiz State University - Burias Campus', 'STU-493678', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('48', NULL, 'LUB-2026-0047', 'Gilbert', 'P.', 'Cabrera', NULL, NULL, 'Male', 'Married', '1982-07-19', '44', '09445927913', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0015', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Vendor', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Vendor', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('49', NULL, 'LUB-2026-0048', 'Elena', 'M.', 'Cabrera', NULL, NULL, 'Female', 'Married', '1986-09-07', '40', '09222787352', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0015', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('50', NULL, 'LUB-2026-0049', 'Carmela', 'C.', 'Cabrera', NULL, NULL, 'Female', 'Single', '2013-02-22', '13', '09827246836', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0015', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Dumarao Vocational Technical School', 'STU-761788', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('51', NULL, 'LUB-2026-0050', 'Jaime', 'P.', 'Ocampo', NULL, NULL, 'Male', 'Married', '1975-08-22', '51', '09690941381', NULL, '3', 'Purok 1, Sitio Weapons', 'HH-LUB-2026-0016', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Security Guard', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Security Guard', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('52', NULL, 'LUB-2026-0051', 'Maria', 'M.', 'Ocampo', NULL, NULL, 'Female', 'Married', '1978-01-11', '48', '09712588902', NULL, '3', 'Purok 1, Sitio Weapons', 'HH-LUB-2026-0016', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('53', NULL, 'LUB-2026-0052', 'Rolando', 'O.', 'Ocampo', NULL, NULL, 'Male', 'Single', '2007-03-27', '19', '09977015218', NULL, '3', 'Purok 1, Sitio Weapons', 'HH-LUB-2026-0016', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub National High School', 'STU-771277', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('54', NULL, 'LUB-2026-0053', 'Angelica', 'O.', 'Ocampo', NULL, NULL, 'Female', 'Single', '2015-05-16', '11', NULL, NULL, '3', 'Purok 1, Sitio Weapons', 'HH-LUB-2026-0016', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-369971', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('55', NULL, 'LUB-2026-0054', 'Allan', 'P.', 'Pascual', NULL, NULL, 'Male', 'Married', '1968-09-25', '58', '09443354801', NULL, '3', 'Purok 2, Sitio Weapons', 'HH-LUB-2026-0017', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Agricultural Laborer', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Agricultural Laborer', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('56', NULL, 'LUB-2026-0055', 'Carmela', 'M.', 'Pascual', NULL, NULL, 'Female', 'Married', '1972-02-15', '54', '09739286744', NULL, '3', 'Purok 2, Sitio Weapons', 'HH-LUB-2026-0017', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('57', NULL, 'LUB-2026-0056', 'Liezel', 'P.', 'Pascual', NULL, NULL, 'Female', 'Single', '2014-04-05', '12', '09877686185', NULL, '3', 'Purok 2, Sitio Weapons', 'HH-LUB-2026-0017', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Filamer Christian University', 'STU-164389', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('58', NULL, 'LUB-2026-0057', 'Ramon', 'P.', 'Pascual', NULL, NULL, 'Male', 'Single', '2014-06-22', '12', NULL, NULL, '3', 'Purok 2, Sitio Weapons', 'HH-LUB-2026-0017', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-735124', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('59', NULL, 'LUB-2026-0058', 'Noel', 'P.', 'Fajardo', NULL, NULL, 'Male', 'Married', '1961-01-01', '65', '09927260487', NULL, '3', 'Purok 3, Sitio Weapons', 'HH-LUB-2026-0018', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Tricycle Driver', '1', 'OSCA-93597', NULL, '1', '4PS-1683805', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Tricycle Driver', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('60', NULL, 'LUB-2026-0059', 'Analyn', 'M.', 'Fajardo', NULL, NULL, 'Female', 'Married', '1966-03-19', '60', '09424681979', NULL, '3', 'Purok 3, Sitio Weapons', 'HH-LUB-2026-0018', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '1', 'OSCA-94018', NULL, '1', '4PS-6074738', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('61', NULL, 'LUB-2026-0060', 'Gabriel', 'F.', 'Fajardo', NULL, NULL, 'Male', 'Single', '2012-05-10', '14', '09196350031', NULL, '3', 'Purok 3, Sitio Weapons', 'HH-LUB-2026-0018', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-2528994', NULL, '0', '1', 'Lublub Elementary School', 'STU-527328', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('62', NULL, 'LUB-2026-0061', 'Analyn', 'F.', 'Fajardo', NULL, NULL, 'Female', 'Single', '2019-07-01', '7', NULL, NULL, '3', 'Purok 3, Sitio Weapons', 'HH-LUB-2026-0018', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-8094922', NULL, '0', '1', 'Lublub Elementary School', 'STU-277408', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('63', NULL, 'LUB-2026-0062', 'Angelo', 'P.', 'Perez', NULL, NULL, 'Male', 'Married', '1986-02-04', '40', '09352479725', NULL, '3', 'Purok 4, Sitio Weapons', 'HH-LUB-2026-0019', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Sari-sari Store Owner', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Sari-sari Store Owner', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('64', NULL, 'LUB-2026-0063', 'Maricel', 'M.', 'Perez', NULL, NULL, 'Female', 'Married', '1991-04-23', '35', '09627284791', NULL, '3', 'Purok 4, Sitio Weapons', 'HH-LUB-2026-0019', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('65', NULL, 'LUB-2026-0064', 'Maria', 'P.', 'Perez', NULL, NULL, 'Female', 'Single', '2010-06-15', '16', '09607626026', NULL, '3', 'Purok 4, Sitio Weapons', 'HH-LUB-2026-0019', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Capiz State University - Burias Campus', 'STU-295543', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('66', NULL, 'LUB-2026-0065', 'Christopher', 'P.', 'Perez', NULL, NULL, 'Male', 'Single', '2018-08-07', '8', NULL, NULL, '3', 'Purok 4, Sitio Weapons', 'HH-LUB-2026-0019', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-971366', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('67', NULL, 'LUB-2026-0066', 'Christian', 'P.', 'Perez', NULL, NULL, 'Male', 'Married', '1979-03-07', '47', '09259786089', NULL, '4', 'Purok 1, Sitio Ilaya Facoma', 'HH-LUB-2026-0020', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Electrician', '0', NULL, NULL, '1', '4PS-8676739', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Electrician', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('68', NULL, 'LUB-2026-0067', 'Maricel', 'M.', 'Perez', NULL, NULL, 'Female', 'Married', '1983-05-27', '43', '09586953465', NULL, '4', 'Purok 1, Sitio Ilaya Facoma', 'HH-LUB-2026-0020', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-5266523', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('69', NULL, 'LUB-2026-0068', 'Renato', 'P.', 'Perez', NULL, NULL, 'Male', 'Single', '2012-07-20', '14', '09453998547', NULL, '4', 'Purok 1, Sitio Ilaya Facoma', 'HH-LUB-2026-0020', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-1529683', NULL, '0', '1', 'Lublub Elementary School', 'STU-519112', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('70', NULL, 'LUB-2026-0069', 'Giselle', 'P.', 'Perez', NULL, NULL, 'Female', 'Single', '2017-09-13', '9', NULL, NULL, '4', 'Purok 1, Sitio Ilaya Facoma', 'HH-LUB-2026-0020', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-7926409', NULL, '0', '1', 'Lublub Elementary School', 'STU-415773', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('71', NULL, 'LUB-2026-0070', 'Gabriel', 'P.', 'Dela Cruz', NULL, NULL, 'Male', 'Married', '1972-04-10', '54', '09249719845', NULL, '4', 'Purok 2, Sitio Ilaya Facoma', 'HH-LUB-2026-0021', '1', 'Head', '3', '1', 'Orthopedic / Physical Disability', 'PWD-4006', NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Tailor / Seamstress', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Tailor / Seamstress', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('72', NULL, 'LUB-2026-0071', 'Rhea', 'M.', 'Dela Cruz', NULL, NULL, 'Female', 'Married', '1974-06-04', '52', '09220671323', NULL, '4', 'Purok 2, Sitio Ilaya Facoma', 'HH-LUB-2026-0021', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('73', NULL, 'LUB-2026-0072', 'Marilou', 'D.', 'Dela Cruz', NULL, NULL, 'Female', 'Single', '2010-08-25', '16', '09621133952', NULL, '4', 'Purok 2, Sitio Ilaya Facoma', 'HH-LUB-2026-0021', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Capiz State University - Burias Campus', 'STU-891734', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('74', NULL, 'LUB-2026-0073', 'Renato', 'D.', 'Dela Cruz', NULL, NULL, 'Male', 'Single', '2016-01-19', '10', NULL, NULL, '4', 'Purok 2, Sitio Ilaya Facoma', 'HH-LUB-2026-0021', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-138739', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('75', NULL, 'LUB-2026-0074', 'Efren', 'P.', 'Castillo', NULL, NULL, 'Male', 'Married', '1965-05-13', '61', '09896028775', NULL, '4', 'Purok 3, Sitio Ilaya Facoma', 'HH-LUB-2026-0022', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Fisherman', '1', 'OSCA-58592', NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Fisherman', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('76', NULL, 'LUB-2026-0075', 'Clarisse', 'M.', 'Castillo', NULL, NULL, 'Female', 'Married', '1969-07-08', '57', '09800375754', NULL, '4', 'Purok 3, Sitio Ilaya Facoma', 'HH-LUB-2026-0022', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('77', NULL, 'LUB-2026-0076', 'Christopher', 'C.', 'Castillo', NULL, NULL, 'Male', 'Single', '2008-09-03', '18', '09303479910', NULL, '4', 'Purok 3, Sitio Ilaya Facoma', 'HH-LUB-2026-0022', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Dumarao Vocational Technical School', 'STU-994724', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('78', NULL, 'LUB-2026-0077', 'Maricel', 'C.', 'Castillo', NULL, NULL, 'Female', 'Single', '2015-02-25', '11', NULL, NULL, '4', 'Purok 3, Sitio Ilaya Facoma', 'HH-LUB-2026-0022', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-454189', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('79', NULL, 'LUB-2026-0078', 'Ramon', 'P.', 'Mercado', NULL, NULL, 'Male', 'Married', '1990-06-16', '36', '09160292515', NULL, '4', 'Purok 4, Sitio Ilaya Facoma', 'HH-LUB-2026-0023', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Construction Worker', '0', NULL, NULL, '1', '4PS-5124273', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Construction Worker', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('80', NULL, 'LUB-2026-0079', 'Maria', 'M.', 'Mercado', NULL, NULL, 'Female', 'Married', '1995-08-12', '31', '09978359158', NULL, '4', 'Purok 4, Sitio Ilaya Facoma', 'HH-LUB-2026-0023', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-1039666', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('81', NULL, 'LUB-2026-0080', 'Clarisse', 'M.', 'Mercado', NULL, NULL, 'Female', 'Single', '2006-01-08', '20', '09605017996', NULL, '4', 'Purok 4, Sitio Ilaya Facoma', 'HH-LUB-2026-0023', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-7093905', NULL, '0', '1', 'Lublub National High School', 'STU-266018', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('82', NULL, 'LUB-2026-0081', 'Gabriel', 'M.', 'Mercado', NULL, NULL, 'Male', 'Single', '2014-03-04', '12', NULL, NULL, '4', 'Purok 4, Sitio Ilaya Facoma', 'HH-LUB-2026-0023', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-4873916', NULL, '0', '1', 'Lublub Elementary School', 'STU-980214', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('83', NULL, 'LUB-2026-0082', 'Arnel', 'P.', 'Mercado', NULL, NULL, 'Male', 'Married', '1983-07-19', '43', '09232733170', NULL, '5', 'Purok 1, Sitio Sabang', 'HH-LUB-2026-0024', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Barangay Tanod', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Barangay Tanod', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('84', NULL, 'LUB-2026-0083', 'Maria', 'M.', 'Mercado', NULL, NULL, 'Female', 'Married', '1986-09-16', '40', '09404540964', NULL, '5', 'Purok 1, Sitio Sabang', 'HH-LUB-2026-0024', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('85', NULL, 'LUB-2026-0084', 'Ramon', 'M.', 'Mercado', NULL, NULL, 'Male', 'Single', '2008-02-13', '18', '09399417670', NULL, '5', 'Purok 1, Sitio Sabang', 'HH-LUB-2026-0024', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Dumarao Vocational Technical School', 'STU-269122', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('86', NULL, 'LUB-2026-0085', 'Teresa', 'M.', 'Mercado', NULL, NULL, 'Female', 'Single', '2019-04-10', '7', NULL, NULL, '5', 'Purok 1, Sitio Sabang', 'HH-LUB-2026-0024', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-972852', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('87', NULL, 'LUB-2026-0086', 'Manuel', 'P.', 'Soriano', NULL, NULL, 'Male', 'Married', '1976-08-22', '50', '09557534749', NULL, '5', 'Purok 2, Sitio Sabang', 'HH-LUB-2026-0025', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Daycare Worker', '0', NULL, NULL, '1', '4PS-1468927', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Daycare Worker', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('88', NULL, 'LUB-2026-0087', 'Carmela', 'M.', 'Soriano', NULL, NULL, 'Female', 'Married', '1978-01-20', '48', '09504995848', NULL, '5', 'Purok 2, Sitio Sabang', 'HH-LUB-2026-0025', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-6242821', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('89', NULL, 'LUB-2026-0088', 'Corazon', 'S.', 'Soriano', NULL, NULL, 'Female', 'Single', '2006-03-18', '20', '09687124172', NULL, '5', 'Purok 2, Sitio Sabang', 'HH-LUB-2026-0025', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-4392087', NULL, '0', '1', 'Lublub National High School', 'STU-829099', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('90', NULL, 'LUB-2026-0089', 'Rolando', 'S.', 'Soriano', NULL, NULL, 'Male', 'Single', '2018-05-16', '8', NULL, NULL, '5', 'Purok 2, Sitio Sabang', 'HH-LUB-2026-0025', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-2315687', NULL, '0', '1', 'Lublub Elementary School', 'STU-720257', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('91', NULL, 'LUB-2026-0090', 'Danilo', 'P.', 'Ocampo', NULL, NULL, 'Male', 'Married', '1969-09-25', '57', '09555019374', NULL, '5', 'Purok 3, Sitio Sabang', 'HH-LUB-2026-0026', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Farmer', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Farmer', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('92', NULL, 'LUB-2026-0091', 'Analyn', 'M.', 'Ocampo', NULL, NULL, 'Female', 'Married', '1972-02-24', '54', '09263469154', NULL, '5', 'Purok 3, Sitio Sabang', 'HH-LUB-2026-0026', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('93', NULL, 'LUB-2026-0092', 'Allan', 'O.', 'Ocampo', NULL, NULL, 'Male', 'Single', '2013-04-23', '13', '09137944904', NULL, '5', 'Purok 3, Sitio Sabang', 'HH-LUB-2026-0026', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Filamer Christian University', 'STU-788005', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('94', NULL, 'LUB-2026-0093', 'Rhea', 'O.', 'Ocampo', NULL, NULL, 'Female', 'Single', '2017-06-22', '9', NULL, NULL, '5', 'Purok 3, Sitio Sabang', 'HH-LUB-2026-0026', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-768538', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('95', NULL, 'LUB-2026-0094', 'Rolando', 'P.', 'Pascual', NULL, NULL, 'Male', 'Married', '1962-01-01', '64', '09654990773', NULL, '5', 'Purok 4, Sitio Sabang', 'HH-LUB-2026-0027', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Carpenter', '1', 'OSCA-90539', NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Carpenter', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('96', NULL, 'LUB-2026-0095', 'Maricel', 'M.', 'Pascual', NULL, NULL, 'Female', 'Married', '1963-03-01', '63', '09126968267', NULL, '5', 'Purok 4, Sitio Sabang', 'HH-LUB-2026-0027', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '1', 'OSCA-50367', NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('97', NULL, 'LUB-2026-0096', 'Rhea', 'P.', 'Pascual', NULL, NULL, 'Female', 'Single', '2011-05-01', '15', '09586459380', NULL, '5', 'Purok 4, Sitio Sabang', 'HH-LUB-2026-0027', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-940225', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('98', NULL, 'LUB-2026-0097', 'Nestor', 'P.', 'Pascual', NULL, NULL, 'Male', 'Single', '2016-07-01', '10', NULL, NULL, '5', 'Purok 4, Sitio Sabang', 'HH-LUB-2026-0027', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-777314', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('99', NULL, 'LUB-2026-0098', 'Jaime', 'P.', 'Pascual', NULL, NULL, 'Male', 'Married', '1987-02-04', '39', '09618165943', NULL, '6', 'Purok 1, Sitio Subdivision', 'HH-LUB-2026-0028', '1', 'Head', '3', '1', 'Speech Impairment', 'PWD-7921', NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Vendor', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Vendor', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('100', NULL, 'LUB-2026-0099', 'Maricel', 'M.', 'Pascual', NULL, NULL, 'Female', 'Married', '1991-04-05', '35', '09454183152', NULL, '6', 'Purok 1, Sitio Subdivision', 'HH-LUB-2026-0028', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('101', NULL, 'LUB-2026-0100', 'Rommel', 'P.', 'Pascual', NULL, NULL, 'Male', 'Single', '2013-06-06', '13', '09803592548', NULL, '6', 'Purok 1, Sitio Subdivision', 'HH-LUB-2026-0028', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Filamer Christian University', 'STU-567202', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('102', NULL, 'LUB-2026-0101', 'Corazon', 'P.', 'Pascual', NULL, NULL, 'Female', 'Single', '2015-08-07', '11', NULL, NULL, '6', 'Purok 1, Sitio Subdivision', 'HH-LUB-2026-0028', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-804097', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('103', NULL, 'LUB-2026-0102', 'Allan', 'P.', 'Fajardo', NULL, NULL, 'Male', 'Married', '1980-03-07', '46', '09535762344', NULL, '6', 'Purok 2, Sitio Subdivision', 'HH-LUB-2026-0029', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Security Guard', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Security Guard', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('104', NULL, 'LUB-2026-0103', 'Rhea', 'M.', 'Fajardo', NULL, NULL, 'Female', 'Married', '1983-05-09', '43', '09369085982', NULL, '6', 'Purok 2, Sitio Subdivision', 'HH-LUB-2026-0029', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('105', NULL, 'LUB-2026-0104', 'Teresa', 'F.', 'Fajardo', NULL, NULL, 'Female', 'Single', '2011-07-11', '15', '09840904360', NULL, '6', 'Purok 2, Sitio Subdivision', 'HH-LUB-2026-0029', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-829042', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('106', NULL, 'LUB-2026-0105', 'Joel', 'F.', 'Fajardo', NULL, NULL, 'Male', 'Single', '2014-09-13', '12', NULL, NULL, '6', 'Purok 2, Sitio Subdivision', 'HH-LUB-2026-0029', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-685192', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('107', NULL, 'LUB-2026-0106', 'Noel', 'P.', 'Perez', NULL, NULL, 'Male', 'Married', '1973-04-10', '53', '09569719918', NULL, '6', 'Purok 3, Sitio Subdivision', 'HH-LUB-2026-0030', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Agricultural Laborer', '0', NULL, NULL, '1', '4PS-1184166', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Agricultural Laborer', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('108', NULL, 'LUB-2026-0107', 'Clarisse', 'M.', 'Perez', NULL, NULL, 'Female', 'Married', '1974-06-13', '52', '09812420775', NULL, '6', 'Purok 3, Sitio Subdivision', 'HH-LUB-2026-0030', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-3380949', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('109', NULL, 'LUB-2026-0108', 'Roberto', 'P.', 'Perez', NULL, NULL, 'Male', 'Single', '2009-08-16', '17', '09275445762', NULL, '6', 'Purok 3, Sitio Subdivision', 'HH-LUB-2026-0030', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-1121300', NULL, '0', '1', 'Capiz State University - Burias Campus', 'STU-912416', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('110', NULL, 'LUB-2026-0109', 'Clarisse', 'P.', 'Perez', NULL, NULL, 'Female', 'Single', '2019-01-19', '7', NULL, NULL, '6', 'Purok 3, Sitio Subdivision', 'HH-LUB-2026-0030', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-9293676', NULL, '0', '1', 'Lublub Elementary School', 'STU-697002', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('111', NULL, 'LUB-2026-0110', 'Angelo', 'P.', 'Dela Cruz', NULL, NULL, 'Male', 'Married', '1966-05-13', '60', '09473680450', NULL, '6', 'Purok 4, Sitio Subdivision', 'HH-LUB-2026-0031', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Tricycle Driver', '1', 'OSCA-33436', NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Tricycle Driver', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('112', NULL, 'LUB-2026-0111', 'Maria', 'M.', 'Dela Cruz', NULL, NULL, 'Female', 'Married', '1971-07-17', '55', '09945957871', NULL, '6', 'Purok 4, Sitio Subdivision', 'HH-LUB-2026-0031', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('113', NULL, 'LUB-2026-0112', 'Maricel', 'D.', 'Dela Cruz', NULL, NULL, 'Female', 'Single', '2007-09-21', '19', '09526034485', NULL, '6', 'Purok 4, Sitio Subdivision', 'HH-LUB-2026-0031', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Dumarao Vocational Technical School', 'STU-405763', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('114', NULL, 'LUB-2026-0113', 'Manuel', 'D.', 'Dela Cruz', NULL, NULL, 'Male', 'Single', '2018-02-25', '8', NULL, NULL, '6', 'Purok 4, Sitio Subdivision', 'HH-LUB-2026-0031', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-997740', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('115', NULL, 'LUB-2026-0114', 'Christian', 'P.', 'Dela Cruz', NULL, NULL, 'Male', 'Married', '1991-06-16', '35', '09442001362', NULL, '7', 'Purok 1, Sitio Lanag', 'HH-LUB-2026-0032', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Sari-sari Store Owner', '0', NULL, NULL, '1', '4PS-1683880', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Sari-sari Store Owner', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('116', NULL, 'LUB-2026-0115', 'Maria', 'M.', 'Dela Cruz', NULL, NULL, 'Female', 'Married', '1992-08-21', '34', '09174436467', NULL, '7', 'Purok 1, Sitio Lanag', 'HH-LUB-2026-0032', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-6927708', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('117', NULL, 'LUB-2026-0116', 'Angelo', 'D.', 'Dela Cruz', NULL, NULL, 'Male', 'Single', '2009-01-26', '17', '09224092681', NULL, '7', 'Purok 1, Sitio Lanag', 'HH-LUB-2026-0032', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-6248276', NULL, '0', '1', 'Capiz State University - Burias Campus', 'STU-286172', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('118', NULL, 'LUB-2026-0117', 'Marilou', 'D.', 'Dela Cruz', NULL, NULL, 'Female', 'Single', '2017-03-04', '9', NULL, NULL, '7', 'Purok 1, Sitio Lanag', 'HH-LUB-2026-0032', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-3966009', NULL, '0', '1', 'Lublub Elementary School', 'STU-859064', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('119', NULL, 'LUB-2026-0118', 'Gabriel', 'P.', 'Castillo', NULL, NULL, 'Male', 'Married', '1984-07-19', '42', '09535669564', NULL, '7', 'Purok 2, Sitio Lanag', 'HH-LUB-2026-0033', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '12000.00', 'Electrician', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Electrician', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('120', NULL, 'LUB-2026-0119', 'Carmela', 'M.', 'Castillo', NULL, NULL, 'Female', 'Married', '1987-09-25', '39', '09839598374', NULL, '7', 'Purok 2, Sitio Lanag', 'HH-LUB-2026-0033', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('121', NULL, 'LUB-2026-0120', 'Giselle', 'C.', 'Castillo', NULL, NULL, 'Female', 'Single', '2007-02-04', '19', '09200203847', NULL, '7', 'Purok 2, Sitio Lanag', 'HH-LUB-2026-0033', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Dumarao Vocational Technical School', 'STU-779536', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('122', NULL, 'LUB-2026-0121', 'Angelo', 'C.', 'Castillo', NULL, NULL, 'Male', 'Single', '2016-04-10', '10', NULL, NULL, '7', 'Purok 2, Sitio Lanag', 'HH-LUB-2026-0033', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-616911', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('123', NULL, 'LUB-2026-0122', 'Efren', 'P.', 'Mercado', NULL, NULL, 'Male', 'Married', '1977-08-22', '49', '09413371968', NULL, '7', 'Purok 3, Sitio Lanag', 'HH-LUB-2026-0034', '1', 'Head', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Tailor / Seamstress', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Tailor / Seamstress', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('124', NULL, 'LUB-2026-0123', 'Analyn', 'M.', 'Mercado', NULL, NULL, 'Female', 'Married', '1981-01-02', '45', '09520168403', NULL, '7', 'Purok 3, Sitio Lanag', 'HH-LUB-2026-0034', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '0', NULL, NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('125', NULL, 'LUB-2026-0124', 'Manuel', 'M.', 'Mercado', NULL, NULL, 'Male', 'Single', '2014-03-09', '12', '09405292979', NULL, '7', 'Purok 3, Sitio Lanag', 'HH-LUB-2026-0034', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub National High School', 'STU-977790', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('126', NULL, 'LUB-2026-0125', 'Maria', 'M.', 'Mercado', NULL, NULL, 'Female', 'Single', '2015-05-16', '11', NULL, NULL, '7', 'Purok 3, Sitio Lanag', 'HH-LUB-2026-0034', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-783331', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('127', NULL, 'LUB-2026-0126', 'Ramon', 'P.', 'Soriano', NULL, NULL, 'Male', 'Married', '1970-09-25', '56', '09368433860', NULL, '7', 'Purok 4, Sitio Lanag', 'HH-LUB-2026-0035', '1', 'Head', '3', '1', 'Visual Impairment', 'PWD-3390', NULL, '0', NULL, NULL, '0', '1', '4500.00', 'Fisherman', '0', NULL, NULL, '1', '4PS-7412108', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Fisherman', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('128', NULL, 'LUB-2026-0127', 'Maricel', 'M.', 'Soriano', NULL, NULL, 'Female', 'Married', '1971-02-06', '55', '09896368722', NULL, '7', 'Purok 4, Sitio Lanag', 'HH-LUB-2026-0035', '0', 'Spouse', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Housekeeper', '0', NULL, NULL, '1', '4PS-1104616', NULL, '0', '0', NULL, NULL, NULL, '1', NULL, NULL, 'Employed', 'Housewife', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('129', NULL, 'LUB-2026-0128', 'Analyn', 'S.', 'Soriano', NULL, NULL, 'Female', 'Single', '2012-04-14', '14', '09999946911', NULL, '7', 'Purok 4, Sitio Lanag', 'HH-LUB-2026-0035', '0', 'Daughter', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-7041142', NULL, '0', '1', 'Filamer Christian University', 'STU-856573', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('130', NULL, 'LUB-2026-0129', 'Roberto', 'S.', 'Soriano', NULL, NULL, 'Male', 'Single', '2014-06-22', '12', NULL, NULL, '7', 'Purok 4, Sitio Lanag', 'HH-LUB-2026-0035', '0', 'Son', '3', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-5666881', NULL, '0', '1', 'Lublub Elementary School', 'STU-838002', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 09:10:52', '2026-11-01 09:10:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('131', NULL, 'LUB-2026-0130', 'John Mark', 'R.', 'Dela Cruz', NULL, NULL, 'Male', 'Single', '2011-04-18', '15', '09274649308', NULL, '1', 'asdasd', 'HH-LUB-2026-0001', '0', 'Son', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '1', '1', 'Lublub National High School', 'STU-2026-0130', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('132', NULL, 'LUB-2026-0131', 'Princess Angela', 'R.', 'Dela Cruz', NULL, NULL, 'Female', 'Single', '2015-08-24', '11', '09115258000', NULL, '1', 'asdasd', 'HH-LUB-2026-0001', '0', 'Daughter', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-2026-0131', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('133', NULL, 'LUB-2026-0132', 'Karlo', 'M.', 'Gutierrez', NULL, NULL, 'Male', 'Single', '2007-01-12', '19', '09391509062', NULL, '1', 'Purok 1, Sitio Proper', 'HH-LUB-2026-0007', '0', 'Son', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '1', '1', 'Capiz State University - Burias Campus', 'STU-2026-0132', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('134', NULL, 'LUB-2026-0133', 'Hannah Mae', 'P.', 'Reyes', NULL, NULL, 'Female', 'Single', '2009-09-05', '17', '09209034443', NULL, '2', 'Purok 1, Sitio Centro', 'HH-LUB-2026-0011', '0', 'Daughter', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-5035860', NULL, '1', '1', 'Lublub Senior High School', 'STU-2026-0133', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('135', NULL, 'LUB-2026-0134', 'Christian Jay', 'B.', 'Torres', NULL, NULL, 'Male', 'Single', '2013-11-20', '13', '09381577141', NULL, '2', 'Purok 2, Sitio Centro', 'HH-LUB-2026-0012', '0', 'Son', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub National High School', 'STU-2026-0134', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('136', NULL, 'LUB-2026-0135', 'Stephanie Joy', 'C.', 'Ocampo', NULL, NULL, 'Female', 'Single', '2006-03-15', '20', '09559282497', NULL, '3', 'Purok 1, Sitio Weapons', 'HH-LUB-2026-0016', '0', 'Daughter', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-2774811', NULL, '1', '1', 'Filamer Christian University', 'STU-2026-0135', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('137', NULL, 'LUB-2026-0136', 'Gabriel Dave', 'S.', 'Perez', NULL, NULL, 'Male', 'Single', '2012-06-30', '14', '09374844379', NULL, '4', 'Purok 1, Sitio Ilaya Facoma', 'HH-LUB-2026-0020', '0', 'Son', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub National High School', 'STU-2026-0136', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('138', NULL, 'LUB-2026-0137', 'Alyssa Nicole', 'V.', 'Mercado', NULL, NULL, 'Female', 'Single', '2008-10-10', '18', '09567014105', NULL, '5', 'Purok 1, Sitio Sabang', 'HH-LUB-2026-0024', '0', 'Daughter', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-6025495', NULL, '1', '1', 'Capiz State University - Burias Campus', 'STU-2026-0137', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('139', NULL, 'LUB-2026-0138', 'Justin Paul', 'T.', 'Pascual', NULL, NULL, 'Male', 'Single', '2010-02-14', '16', '09472786290', NULL, '6', 'Purok 1, Sitio Subdivision', 'HH-LUB-2026-0028', '0', 'Son', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '0', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '1', '1', 'Lublub Senior High School', 'STU-2026-0138', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('140', NULL, 'LUB-2026-0139', 'Camille Andrea', 'D.', 'Dela Cruz', NULL, NULL, 'Female', 'Single', '2014-07-22', '12', '09288131935', NULL, '7', 'Purok 1, Sitio Lanag', 'HH-LUB-2026-0032', '0', 'Daughter', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '1', '4PS-6812930', NULL, '0', '1', 'Lublub Elementary School', 'STU-2026-0139', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('141', NULL, 'LUB-2026-0140', 'Mark Jason', 'M.', 'Alcantara', NULL, NULL, 'Male', 'Single', '2016-05-19', '10', '09293035582', NULL, '5', 'Purok Riverside, Near Jalaur River Dike', 'HH-LUB-2026-0002', '0', 'Son', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '0', '1', 'Lublub Elementary School', 'STU-2026-0140', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('142', NULL, 'LUB-2026-0141', 'Erica Mae', 'R.', 'Fernandez', NULL, NULL, 'Female', 'Single', '2011-12-08', '15', '09733697771', NULL, '7', 'Purok Bagong Silang, Hillside Boundary', 'HH-LUB-2026-0004', '0', 'Daughter', '4', '0', NULL, NULL, NULL, '0', NULL, NULL, '0', '1', '0.00', 'Student Dependent', '0', NULL, NULL, '0', NULL, NULL, '1', '1', 'Lublub National High School', 'STU-2026-0141', NULL, '1', NULL, NULL, 'Student', 'Student', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '0', NULL, NULL, NULL, '2026-11-01 10:12:09', '2026-11-01 10:12:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('148', '20', 'LUB-2026-0142', 'Kenneth Jade', 'Atonducan', 'Bares', '', '', 'Male', 'Single', '2004-11-11', '21', '', '', '2', 'Near in sugarcane', 'HH-LUB-2026-0014', '0', 'Son', '1', '0', '', '', NULL, '0', '', NULL, '0', '0', '0.00', '', '0', '', NULL, '0', '', NULL, '1', '1', 'ISAT U', '2023-2091-C', 'uploads/documents/school_id_6ae6ddd1c5874_1793514961.png', '0', '', NULL, 'Unemployed', '', '', '', '', '', '', '', 'approved', '0', NULL, NULL, NULL, '2026-11-01 06:36:01', '2026-11-01 06:37:40', '2', NULL, 'uploads/residents/selfie_6ae6ddd1c545a_1793514961.jpg', 'uploads/ids/id_6ae6ddd1c5624_1793514961.png', NULL, NULL, NULL);

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
-- Dumping data for table `assistance_programs`
-- -----------------------------------------------------
INSERT INTO `assistance_programs` (`id`, `title`, `program_code`, `category`, `description`, `fund_source`, `amount_per_beneficiary`, `in_kind_details`, `target_sitio_id`, `target_criteria`, `distribution_date`, `status`, `created_by_user_id`, `created_at`) VALUES
('1', 'Eskwelahagi', 'AID-2026-712', 'Educational Assistance', 'Eskwelahan pamahagi', '-', '1500.00', '', NULL, '{\"sectors\": {\"4ps\": 0, \"pwd\": 0, \"senior\": 0, \"student\": 1, \"indigent\": 0, \"solo_parent\": 0}, \"household_scope\": \"heads_only\", \"target_sitio_id\": null, \"target_by_household\": 0, \"selected_households_count\": 0}', '2026-11-07', 'Ongoing', '2', '2026-11-01 09:28:11'),
('2', 'nabahaan', 'AID-2026-660', 'Food Assistance', 'hehe', 'Barangay Lublub', '0.00', '10kg rice', NULL, '{\"target_sitio_id\":null,\"sectors\":{\"pwd\":0,\"solo_parent\":0,\"indigent\":0,\"senior\":0,\"4ps\":0,\"student\":0},\"target_by_household\":1,\"household_scope\":\"heads_only\",\"selected_households_count\":6,\"selected_households\":[\"HH-LUB-2026-0001\",\"HH-LUB-2026-0002\",\"HH-LUB-2026-0003\",\"HH-LUB-2026-0004\",\"HH-LUB-2026-0005\",\"HH-LUB-2026-0006\"]}', '2020-11-11', 'Ongoing', '2', '2026-11-01 06:42:36'),
('3', 'School Supplies', 'AID-2026-625', 'Educational Assistance', 'Balik Skwela', 'Barangay Lublub', '0.00', 'Papers, Books, Pencil', NULL, '{\"target_sitio_id\":null,\"sectors\":{\"pwd\":0,\"solo_parent\":0,\"indigent\":0,\"senior\":0,\"4ps\":0,\"student\":1},\"target_by_household\":0,\"household_scope\":\"heads_only\",\"selected_households_count\":0}', '2027-11-11', 'Ongoing', '2', '2026-11-01 06:45:15'),
('4', 'Sch Supp', 'AID-2026-445', 'Educational Assistance', 'scholl supplies', 'lublubfund', '0.00', '12 Notebook', NULL, '{\"target_sitio_id\":null,\"sectors\":{\"pwd\":0,\"solo_parent\":0,\"indigent\":0,\"senior\":0,\"4ps\":0,\"student\":1},\"target_by_household\":0,\"household_scope\":\"heads_only\",\"selected_households\":[],\"selected_households_count\":0}', '2026-11-29', 'Ongoing', '2', '2026-11-01 07:07:35'),
('5', 'Hatagay', 'AID-2026-913', 'Financial Assistance', 'kwarta na', 'Barangay Lublub', '10000.00', '', '2', '{\"target_sitio_id\":2,\"sectors\":{\"pwd\":0,\"solo_parent\":0,\"indigent\":0,\"senior\":0,\"4ps\":0,\"student\":1},\"target_by_household\":0,\"household_scope\":\"heads_only\",\"selected_households\":[],\"selected_households_count\":0}', '2002-11-11', 'Ongoing', '1', '2026-11-01 07:14:59');

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
-- Dumping data for table `assistance_beneficiaries`
-- -----------------------------------------------------
INSERT INTO `assistance_beneficiaries` (`id`, `program_id`, `resident_id`, `status`, `claimed_at`, `released_by_user_id`, `received_amount`, `received_items`, `remarks`, `created_at`) VALUES
('1', '1', '8', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('2', '1', '9', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('3', '1', '12', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('4', '1', '13', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('5', '1', '16', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('6', '1', '19', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('7', '1', '23', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('8', '1', '26', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('9', '1', '29', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('10', '1', '32', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('11', '1', '35', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('12', '1', '38', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('13', '1', '41', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('14', '1', '44', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('15', '1', '47', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('16', '1', '50', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('17', '1', '53', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('18', '1', '54', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('19', '1', '57', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('20', '1', '58', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('21', '1', '61', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('22', '1', '62', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('23', '1', '65', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('24', '1', '66', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('25', '1', '69', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('26', '1', '70', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('27', '1', '73', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('28', '1', '74', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('29', '1', '77', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('30', '1', '78', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('31', '1', '81', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('32', '1', '82', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('33', '1', '85', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('34', '1', '86', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('35', '1', '89', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('36', '1', '90', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('37', '1', '93', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('38', '1', '94', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('39', '1', '97', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('40', '1', '98', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('41', '1', '101', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('42', '1', '102', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('43', '1', '105', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('44', '1', '106', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('45', '1', '109', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('46', '1', '110', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('47', '1', '113', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('48', '1', '114', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('49', '1', '117', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('50', '1', '118', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('51', '1', '121', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('52', '1', '122', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('53', '1', '125', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('54', '1', '126', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('55', '1', '129', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('56', '1', '130', 'Enrolled', NULL, NULL, '1500.00', '', NULL, '2026-11-01 09:28:11'),
('57', '1', '148', 'Claimed', '2026-11-01 06:39:55', '11', '1500.00', '', '--', '2026-11-01 06:39:43'),
('58', '2', '3', 'Enrolled', NULL, NULL, '0.00', 'Relief Pack', NULL, '2026-11-01 06:56:54'),
('59', '2', '6', 'Enrolled', NULL, NULL, '0.00', 'Relief Pack', NULL, '2026-11-01 06:56:54'),
('60', '2', '11', 'Enrolled', NULL, NULL, '0.00', 'Relief Pack', NULL, '2026-11-01 06:56:55'),
('61', '2', '14', 'Enrolled', NULL, NULL, '0.00', 'Relief Pack', NULL, '2026-11-01 06:56:55'),
('62', '2', '17', 'Enrolled', NULL, NULL, '0.00', 'Relief Pack', NULL, '2026-11-01 06:56:55'),
('63', '2', '20', 'Enrolled', NULL, NULL, '0.00', 'Relief Pack', NULL, '2026-11-01 06:56:55'),
('64', '4', '8', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('65', '4', '9', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('66', '4', '12', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('67', '4', '13', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('68', '4', '16', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('69', '4', '19', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('70', '4', '23', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('71', '4', '26', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('72', '4', '29', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('73', '4', '32', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('74', '4', '35', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('75', '4', '38', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('76', '4', '41', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('77', '4', '44', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('78', '4', '47', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('79', '4', '50', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('80', '4', '53', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('81', '4', '54', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('82', '4', '57', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('83', '4', '58', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('84', '4', '61', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('85', '4', '62', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('86', '4', '65', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('87', '4', '66', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('88', '4', '69', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('89', '4', '70', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('90', '4', '73', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('91', '4', '74', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('92', '4', '77', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('93', '4', '78', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('94', '4', '81', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('95', '4', '82', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('96', '4', '85', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('97', '4', '86', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('98', '4', '89', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('99', '4', '90', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('100', '4', '93', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('101', '4', '94', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('102', '4', '97', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('103', '4', '98', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('104', '4', '101', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('105', '4', '102', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('106', '4', '105', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('107', '4', '106', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('108', '4', '109', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('109', '4', '110', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('110', '4', '113', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('111', '4', '114', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('112', '4', '117', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('113', '4', '118', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('114', '4', '121', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('115', '4', '122', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('116', '4', '125', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('117', '4', '126', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('118', '4', '129', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('119', '4', '130', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('120', '4', '131', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('121', '4', '132', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('122', '4', '133', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('123', '4', '134', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('124', '4', '135', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('125', '4', '136', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('126', '4', '137', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('127', '4', '138', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('128', '4', '139', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('129', '4', '140', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('130', '4', '141', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('131', '4', '142', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('132', '4', '148', 'Enrolled', NULL, NULL, '0.00', '12 Notebook', NULL, '2026-11-01 07:07:35'),
('133', '5', '38', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('134', '5', '41', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('135', '5', '44', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('136', '5', '47', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('137', '5', '50', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('138', '5', '134', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('139', '5', '135', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59'),
('140', '5', '148', 'Enrolled', NULL, NULL, '10000.00', '', NULL, '2026-11-01 07:14:59');

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
-- Dumping data for table `activity_logs`
-- -----------------------------------------------------
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
('1', '1', 'User Login', 'User captain logged in via demo switcher', '::1', '2026-11-01 00:03:32'),
('2', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '127.0.0.1', '2026-11-01 03:45:23'),
('3', '1', 'User Login', 'User captain logged in via demo switcher', '::1', '2026-11-01 03:46:35'),
('4', '2', 'User Login', 'User kagawad_proper logged in via demo switcher', '::1', '2026-11-01 03:46:50'),
('5', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '::1', '2026-11-01 03:47:15'),
('6', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '::1', '2026-11-01 03:48:10'),
('7', '1', 'User Login', 'User captain logged in successfully', '::1', '2026-11-01 03:49:40'),
('8', '2', 'User Login', 'User secretary logged in successfully', '::1', '2026-11-01 03:49:40'),
('9', '2', 'User Login', 'User kagawad_proper logged in successfully', '::1', '2026-11-01 03:49:41'),
('10', '10', 'User Login', 'User bhw_proper logged in successfully', '::1', '2026-11-01 03:49:41'),
('11', '1', 'User Login', 'User captain logged in successfully', '::1', '2026-11-01 03:51:22'),
('12', '2', 'User Login', 'User secretary logged in successfully', '::1', '2026-11-01 03:51:23'),
('13', '2', 'User Login', 'User kagawad_proper logged in successfully', '::1', '2026-11-01 03:51:23'),
('14', '10', 'User Login', 'User bhw_proper logged in successfully', '::1', '2026-11-01 03:51:24'),
('15', '17', 'User Login', 'User resident_test logged in successfully', '::1', '2026-11-01 03:51:24'),
('16', '10', 'User Login', 'User bhw_proper logged in successfully', '::1', '2026-11-01 06:20:17'),
('17', '10', 'Register Household', 'Household HH-TEST-1793485218 registered in Sitio 1 by BHW Maria Elena Santos', '::1', '2026-11-01 06:20:18'),
('18', '10', 'User Login', 'User bhw_proper logged in successfully', '::1', '2026-11-01 06:20:32'),
('19', '10', 'Register Household', 'Household HH-TEST-1793485233 registered in Sitio 1 by BHW Maria Elena Santos', '::1', '2026-11-01 06:20:33'),
('20', '10', 'User Logout', 'User BHW Maria Elena Santos signed out', '127.0.0.1', '2026-11-01 07:05:32'),
('21', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '127.0.0.1', '2026-11-01 07:05:35'),
('22', '10', 'Register Household', 'Household HH-LUB-2026-0001 registered in Sitio 1 by BHW Maria Elena Santos', '127.0.0.1', '2026-11-01 07:09:00'),
('23', '10', 'User Login', 'User bhw_proper logged in successfully', '::1', '2026-11-01 07:13:33'),
('24', '10', 'Register Household', 'Household HH-TEST-5745 registered in Sitio 1 by BHW Maria Elena Santos', '::1', '2026-11-01 07:13:33'),
('25', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '::1', '2026-11-01 08:17:13'),
('26', '10', 'Encode Resident', 'Resident Maria Poblacion (LUB-2026-0004) encoded and approved by BHW Maria Elena Santos', '::1', '2026-11-01 08:36:07'),
('27', '10', 'User Logout', 'User BHW Maria Elena Santos signed out', '::1', '2026-11-01 08:37:00'),
('28', '2', 'User Login', 'User secretary logged in via demo switcher', '::1', '2026-11-01 08:37:02'),
('29', '2', 'Launch Aid Program', 'Program \'Eskwelahagi\' (AID-2026-712) created with 56 auto-enrolled beneficiaries', '::1', '2026-11-01 09:28:11'),
('30', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '175.176.77.177', '2026-11-01 06:06:37'),
('31', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '143.44.197.133', '2026-11-01 06:07:00'),
('32', '10', 'User Logout', 'User BHW Maria Elena Santos signed out', '175.176.77.177', '2026-11-01 06:12:48'),
('33', '1', 'User Login', 'User captain logged in via demo switcher', '175.176.77.177', '2026-11-01 06:12:52'),
('34', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '175.176.77.177', '2026-11-01 06:14:34'),
('35', '1', 'User Login', 'User captain logged in via demo switcher', '175.176.77.177', '2026-11-01 06:14:52'),
('36', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '175.176.77.177', '2026-11-01 06:16:55'),
('37', '10', 'User Logout', 'User BHW Maria Elena Santos signed out', '143.44.197.133', '2026-11-01 06:30:09'),
('38', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '143.44.197.133', '2026-11-01 06:30:30'),
('39', NULL, 'Resident Registration', 'Resident Kenneth Jade Atonducan Bares submitted registration for Sitio Sitio Centro', '175.176.77.177', '2026-11-01 06:36:01'),
('40', '10', 'User Logout', 'User BHW Maria Elena Santos signed out', '143.44.197.133', '2026-11-01 06:36:30'),
('41', '2', 'User Login', 'User kagawad_centro logged in via demo switcher', '143.44.197.133', '2026-11-01 06:36:34'),
('42', '2', 'User Logout', 'User Hon. Mark Lester Dolendo signed out', '143.44.197.133', '2026-11-01 06:37:07'),
('43', '11', 'User Login', 'User bhw_centro logged in via demo switcher', '143.44.197.133', '2026-11-01 06:37:14'),
('44', '11', 'User Logout', 'User BHW Jocelyn Ramirez signed out', '143.44.197.133', '2026-11-01 06:37:31'),
('45', '2', 'User Login', 'User kagawad_centro logged in via demo switcher', '143.44.197.133', '2026-11-01 06:37:34'),
('46', '2', 'Approve Resident', 'Resident Kenneth Jade Bares approved by Hon. Mark Lester Dolendo for Sitio Sitio Centro', '143.44.197.133', '2026-11-01 06:37:40'),
('47', '2', 'User Logout', 'User Hon. Mark Lester Dolendo signed out', '143.44.197.133', '2026-11-01 06:37:43'),
('48', '11', 'User Login', 'User bhw_centro logged in via demo switcher', '143.44.197.133', '2026-11-01 06:37:46'),
('49', '11', 'Update Resident', 'Updated resident profile Kenneth Jade Bares by BHW Jocelyn Ramirez', '143.44.197.133', '2026-11-01 06:38:58'),
('50', '20', 'User Login', 'User Kenneth logged in successfully', '175.176.77.177', '2026-11-01 06:39:05'),
('51', '11', 'Enroll Beneficiary', 'Enrolled resident Kenneth Jade Bares (ID: 148) into program ID 1 with benefit amount of ₱1,500.00', '143.44.197.133', '2026-11-01 06:39:43'),
('52', '11', 'Release Aid', 'Ayuda released to beneficiary ID 57 by BHW Jocelyn Ramirez', '143.44.197.133', '2026-11-01 06:39:55'),
('53', '11', 'User Logout', 'User BHW Jocelyn Ramirez signed out', '143.44.197.133', '2026-11-01 06:40:38'),
('54', '2', 'User Login', 'User secretary logged in via demo switcher', '143.44.197.133', '2026-11-01 06:40:41'),
('55', '20', 'User Logout', 'User Kenneth Jade Atonducan Bares signed out', '175.176.77.177', '2026-11-01 06:40:41'),
('56', '2', 'User Login', 'User secretary logged in via demo switcher', '175.176.77.177', '2026-11-01 06:40:45'),
('57', '10', 'User Login', 'User bhw_proper logged in via demo switcher', '143.44.197.133', '2026-11-01 06:42:22'),
('58', '2', 'Launch Aid Program', 'Program \'nabahaan\' (AID-2026-660) created with 0 auto-enrolled beneficiaries', '175.176.77.177', '2026-11-01 06:42:36'),
('59', '10', 'User Logout', 'User BHW Maria Elena Santos signed out', '143.44.197.133', '2026-11-01 06:43:07'),
('60', '2', 'User Login', 'User secretary logged in via demo switcher', '143.44.197.133', '2026-11-01 06:43:10'),
('61', '2', 'Launch Aid Program', 'Program \'School Supplies\' (AID-2026-625) created with 0 auto-enrolled beneficiaries', '175.176.77.177', '2026-11-01 06:45:15'),
('62', '2', 'User Logout', 'User Ms. Annielor Gulbique signed out', '175.176.77.177', '2026-11-01 06:51:02'),
('63', '20', 'User Login', 'User Kenneth logged in successfully', '175.176.77.177', '2026-11-01 06:53:28'),
('64', '20', 'User Logout', 'User Kenneth Jade Atonducan Bares signed out', '175.176.77.177', '2026-11-01 06:53:33'),
('65', '1', 'User Login', 'User captain logged in via demo switcher', '175.176.77.177', '2026-11-01 06:53:38'),
('66', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '175.176.77.177', '2026-11-01 06:54:59'),
('67', '1', 'User Login', 'User captain logged in via demo switcher', '175.176.77.177', '2026-11-01 06:56:02'),
('68', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '175.176.77.177', '2026-11-01 06:56:51'),
('69', '11', 'User Login', 'User bhw_centro logged in via demo switcher', '175.176.77.177', '2026-11-01 06:57:45'),
('70', '1', 'User Login', 'User captain logged in via demo switcher', '175.176.77.177', '2026-11-01 07:04:21'),
('71', '2', 'Launch Aid Program', 'Program \'Sch Supp\' (AID-2026-445) created with 69 auto-enrolled beneficiaries', '143.44.197.133', '2026-11-01 07:07:35'),
('72', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '175.176.77.177', '2026-11-01 07:12:16'),
('73', '11', 'User Login', 'User bhw_centro logged in via demo switcher', '175.176.77.177', '2026-11-01 07:12:26'),
('74', '11', 'User Logout', 'User BHW Jocelyn Ramirez signed out', '175.176.77.177', '2026-11-01 07:13:47'),
('75', '1', 'User Login', 'User captain logged in via demo switcher', '175.176.77.177', '2026-11-01 07:13:50'),
('76', '1', 'Launch Aid Program', 'Program \'Hatagay\' (AID-2026-913) created with 8 auto-enrolled beneficiaries', '175.176.77.177', '2026-11-01 07:14:59'),
('77', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '175.176.77.177', '2026-11-01 07:17:07'),
('78', '11', 'User Login', 'User bhw_centro logged in via demo switcher', '175.176.77.177', '2026-11-01 07:25:30'),
('79', '11', 'User Logout', 'User BHW Jocelyn Ramirez signed out', '175.176.77.177', '2026-11-01 07:32:32'),
('80', '1', 'User Login', 'User captain logged in via demo switcher', '210.23.168.152', '2026-11-01 08:12:26'),
('81', '1', 'User Logout', 'User Hon. Eliseo Rodriguez signed out', '2001:4454:311:e900:c5cd:b93d:48d1:8fd7', '2026-11-01 08:20:11'),
('82', '11', 'User Login', 'User bhw_centro logged in via demo switcher', '2001:4454:311:e900:c5cd:b93d:48d1:8fd7', '2026-11-01 08:20:15'),
('83', '11', 'User Logout', 'User BHW Jocelyn Ramirez signed out', '2001:4454:311:e900:c5cd:b93d:48d1:8fd7', '2026-11-01 08:28:57'),
('84', '1', 'User Login', 'User captain logged in via demo switcher', '2001:4454:311:e900:c5cd:b93d:48d1:8fd7', '2026-11-01 08:28:59'),
('85', '2', 'User Login', 'User secretary logged in via demo switcher', '::1', '2026-11-01 11:06:38'),
('86', '2', 'User Login', 'User secretary logged in via demo switcher', '::1', '2026-11-01 11:52:35');

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
