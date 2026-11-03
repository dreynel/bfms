-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 03, 2026 at 05:36 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u534933225_bhwms`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `user_name`, `user_role`, `action`, `module`, `record_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Hon. Jose R. Maravilla', 'admin', 'SYSTEM_INITIALIZATION', 'System', '0', 'BHWMS System overhaul and module verification completed.', '127.0.0.1', '2026-10-25 00:23:21'),
(2, 1, 'Hon. Jose R. Maravilla', 'admin', 'UPDATE_SETTINGS', 'Settings', '1', 'Barangay name standardized to Brgy. Pasil, New Lucena, Iloilo.', '127.0.0.1', '2026-10-25 00:23:21'),
(3, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '::1', '2026-10-25 03:25:26'),
(4, 9, 'Engr. Electronics Custodian (ELX)', 'custodian', 'LOGOUT', 'Authentication', '9', 'User logged out', '::1', '2026-10-25 06:52:02'),
(5, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '::1', '2026-10-25 06:52:05'),
(6, 9, 'Engr. Electronics Custodian (ELX)', 'custodian', 'LOGOUT', 'Authentication', '9', 'User logged out', '::1', '2026-10-25 09:04:12'),
(7, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'Authentication', '15', 'User logged in successfully', '::1', '2026-10-25 09:04:49'),
(8, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGOUT', 'Authentication', '15', 'User logged out', '::1', '2026-10-25 09:09:31'),
(9, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '::1', '2026-10-25 11:28:55'),
(10, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '::1', '2026-10-25 11:28:55'),
(11, NULL, 'System / Anonymous', 'public', 'RESIDENT_SIGNUP', 'users', '21', 'Resident online signup: Rechelle ann Perez (annperez130@gmail.com) in Zone 3 - Sitio Takas. Resident #RES-PAS-0011 under Household HH-PAS-008 created; awaiting in-person clinical profiling by assigned BHW.', '143.44.197.149', '2026-10-26 02:59:51'),
(12, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'Authentication', '21', 'User logged in successfully', '143.44.197.149', '2026-10-26 02:59:51'),
(13, 21, 'Rechelle ann Perez', 'resident', 'LOGOUT', 'Authentication', '21', 'User logged out', '143.44.197.149', '2026-10-26 03:05:37'),
(14, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.149', '2026-10-26 03:06:32'),
(15, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.149', '2026-10-26 03:06:32'),
(16, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'Authentication', '4', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:08:36'),
(17, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'users', '4', 'User logged in with role: bhw', '192.168.1.40', '2026-10-26 03:08:36'),
(18, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '143.44.197.149', '2026-10-26 03:10:14'),
(19, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'Authentication', '21', 'User logged in successfully', '143.44.197.149', '2026-10-26 03:10:38'),
(20, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'users', '21', 'User logged in with role: resident', '143.44.197.149', '2026-10-26 03:10:38'),
(21, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGOUT', 'Authentication', '4', 'User logged out', '192.168.1.40', '2026-10-26 03:13:35'),
(22, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:13:45'),
(23, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '192.168.1.40', '2026-10-26 03:13:45'),
(24, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '192.168.1.40', '2026-10-26 03:14:37'),
(25, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:14:40'),
(26, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '192.168.1.40', '2026-10-26 03:14:40'),
(27, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGOUT', 'Authentication', '2', 'User logged out', '192.168.1.40', '2026-10-26 03:16:12'),
(28, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'Authentication', '21', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:16:29'),
(29, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'users', '21', 'User logged in with role: resident', '192.168.1.40', '2026-10-26 03:16:29'),
(30, 21, 'Rechelle ann Perez', 'resident', 'LOGOUT', 'Authentication', '21', 'User logged out', '192.168.1.40', '2026-10-26 03:16:56'),
(31, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:17:16'),
(32, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '192.168.1.40', '2026-10-26 03:17:16'),
(33, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:17:46'),
(34, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '192.168.1.40', '2026-10-26 03:17:46'),
(35, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '192.168.1.40', '2026-10-26 03:20:25'),
(36, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'Authentication', '4', 'User logged in successfully', '192.168.1.40', '2026-10-26 03:20:34'),
(37, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'users', '4', 'User logged in with role: bhw', '192.168.1.40', '2026-10-26 03:20:34'),
(38, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'Authentication', '21', 'User logged in successfully', '143.44.197.149', '2026-10-26 07:18:46'),
(39, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'users', '21', 'User logged in with role: resident', '143.44.197.149', '2026-10-26 07:18:46'),
(40, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.149', '2026-10-26 07:19:10'),
(41, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.149', '2026-10-26 07:19:10'),
(42, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'Authentication', '4', 'User logged in successfully', '143.44.197.149', '2026-10-26 07:19:32'),
(43, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'users', '4', 'User logged in with role: bhw', '143.44.197.149', '2026-10-26 07:19:32'),
(44, 21, 'Rechelle ann Perez', 'resident', 'LOGOUT', 'Authentication', '21', 'User logged out', '143.44.197.149', '2026-10-26 08:00:13'),
(45, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'Authentication', '21', 'User logged in successfully', '143.44.197.149', '2026-10-26 08:01:11'),
(46, 21, 'Rechelle ann Perez', 'resident', 'LOGIN', 'users', '21', 'User logged in with role: resident', '143.44.197.149', '2026-10-26 08:01:11'),
(47, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGOUT', 'Authentication', '4', 'User logged out', '143.44.197.149', '2026-10-26 08:07:56'),
(48, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'Authentication', '15', 'User logged in successfully', '143.44.197.149', '2026-10-26 08:08:03'),
(49, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'users', '15', 'User logged in with role: resident', '143.44.197.149', '2026-10-26 08:08:03'),
(50, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGOUT', 'Authentication', '15', 'User logged out', '143.44.197.149', '2026-10-26 08:11:19'),
(51, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'Authentication', '15', 'User logged in successfully', '143.44.197.149', '2026-10-26 08:11:25'),
(52, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'users', '15', 'User logged in with role: resident', '143.44.197.149', '2026-10-26 08:11:25'),
(53, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGOUT', 'Authentication', '15', 'User logged out', '143.44.197.149', '2026-10-26 08:11:29'),
(54, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '143.44.197.149', '2026-10-26 08:11:33'),
(55, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '143.44.197.149', '2026-10-26 08:11:33'),
(56, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'CREATE_HOUSEHOLD', 'households', '11', 'Registered household HH-PAS-009 in Zone 1 - Sitio Turil', '143.44.197.149', '2026-10-26 08:20:08'),
(57, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'CREATE_RESIDENT', 'residents', '15', 'Profiled resident Kim Lopines under household HH-PAS-009', '143.44.197.149', '2026-10-26 08:22:02'),
(58, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'SET_HOUSEHOLD_HEAD', 'households', '11', 'Assigned Kim Lopines as Head of Household HH-PAS-009', '143.44.197.149', '2026-10-26 08:22:02'),
(59, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'CREATE_RESIDENT', 'residents', '16', 'Profiled resident Rech Perez under household HH-PAS-009', '143.44.197.149', '2026-10-26 08:23:06'),
(60, NULL, 'System / Anonymous', 'public', 'RESIDENT_SIGNUP_OTP_VERIFIED', 'users', '22', 'Resident online signup with OTP: Rechelle ann Perez (lopinesbenje@gmail.com) in Zone 1 - Sitio Turil. Resident #RES-PAS-0015 under Household HH-PAS-011 created.', '143.44.197.149', '2026-10-26 09:20:45'),
(61, 22, 'Rechelle ann Perez', 'resident', 'LOGIN', 'Authentication', '22', 'User logged in successfully', '143.44.197.149', '2026-10-26 09:20:45'),
(62, 22, 'Rechelle ann Perez', 'resident', 'LOGOUT', 'Authentication', '22', 'User logged out', '143.44.197.149', '2026-10-26 09:20:54'),
(63, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.149', '2026-10-26 09:20:57'),
(64, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.149', '2026-10-26 09:20:57'),
(65, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'CREATE_HEALTH_SERVICE', 'services', '6', 'Created health service announcement: vhuvhu', '143.44.197.149', '2026-10-26 09:21:39'),
(66, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'UPDATE_SERVICE_REQUEST', 'services', '3', 'Updated request #3 status to completed', '143.44.197.149', '2026-10-26 09:25:23'),
(67, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '143.44.197.149', '2026-10-26 09:28:16'),
(68, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '143.44.197.149', '2026-10-26 09:28:20'),
(69, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '143.44.197.149', '2026-10-26 09:28:20'),
(70, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGOUT', 'Authentication', '3', 'User logged out', '143.44.197.149', '2026-10-26 09:32:27'),
(71, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.149', '2026-10-26 09:32:29'),
(72, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.149', '2026-10-26 09:32:29'),
(73, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 13:26:39'),
(74, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 13:26:39'),
(75, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '143.44.197.85', '2026-10-26 13:26:48'),
(76, NULL, 'System / Anonymous', 'public', 'RESIDENT_SIGNUP_OTP_VERIFIED', 'users', '23', 'Resident online signup with OTP: John Lenard Bocal (bjohnlenard@gmail.com) in Zone 3 - Sitio Takas. Resident #RES-PAS-0018 under Household HH-PAS-013 created.', '143.44.197.85', '2026-10-26 13:28:11'),
(77, 23, 'John Lenard Bocal', 'resident', 'LOGIN', 'Authentication', '23', 'User logged in successfully', '143.44.197.85', '2026-10-26 13:28:11'),
(78, 23, 'John Lenard Bocal', 'resident', 'LOGOUT', 'Authentication', '23', 'User logged out', '143.44.197.85', '2026-10-26 13:28:38'),
(79, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'Authentication', '4', 'User logged in successfully', '143.44.197.85', '2026-10-26 13:28:43'),
(80, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'users', '4', 'User logged in with role: bhw', '143.44.197.85', '2026-10-26 13:28:43'),
(81, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'RESIDENT_PROFILED', 'residents', '18', 'Completed in-person clinical health profiling for resident John Lenard Bocal by BHW Maria Clara (BHW Sitio Takas)', '143.44.197.85', '2026-10-26 13:29:12'),
(82, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGOUT', 'Authentication', '4', 'User logged out', '143.44.197.85', '2026-10-26 13:29:36'),
(83, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 13:29:38'),
(84, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 13:29:38'),
(85, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 13:55:04'),
(86, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 13:55:04'),
(87, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '::1', '2026-10-26 14:27:08'),
(88, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '::1', '2026-10-26 14:27:08'),
(89, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '::1', '2026-10-26 14:42:27'),
(90, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '::1', '2026-10-26 14:42:27'),
(91, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '::1', '2026-10-26 14:58:39'),
(92, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '::1', '2026-10-26 14:58:43'),
(93, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '::1', '2026-10-26 14:58:43'),
(94, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'CREATE_HEALTH_SERVICE', 'services', '7', 'Created health service announcement: chuchu blabla', '::1', '2026-10-26 15:26:07'),
(95, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'CREATE_HEALTH_SERVICE', 'services', '8', 'Created health service announcement: klasdl;ksdl;', '::1', '2026-10-26 15:28:51'),
(96, NULL, 'System / Anonymous', 'public', 'RESIDENT_SIGNUP_OTP_VERIFIED', 'users', '24', 'Resident online signup with OTP: ella flores (eljunetalaman3@gmail.com) in Zone 6 - Sitio Kigyan. Resident #RES-PAS-0019 under Household HH-PAS-014 created.', '120.28.169.241', '2026-10-26 15:48:53'),
(97, 24, 'ella flores', 'resident', 'LOGIN', 'Authentication', '24', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:48:53'),
(98, 24, 'ella flores', 'resident', 'LOGOUT', 'Authentication', '24', 'User logged out', '120.28.169.241', '2026-10-26 15:50:18'),
(99, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:50:28'),
(100, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '120.28.169.241', '2026-10-26 15:50:28'),
(101, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'UPDATE_SERVICE_REQUEST', 'services', '4', 'Updated request #4 status to pending', '120.28.169.241', '2026-10-26 15:51:09'),
(102, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '120.28.169.241', '2026-10-26 15:51:51'),
(103, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:52:45'),
(104, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '120.28.169.241', '2026-10-26 15:52:45'),
(105, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'UPDATE_SERVICE_REQUEST', 'services', '4', 'Updated request #4 status to scheduled', '120.28.169.241', '2026-10-26 15:53:18'),
(106, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGOUT', 'Authentication', '2', 'User logged out', '120.28.169.241', '2026-10-26 15:53:32'),
(107, 24, 'ella flores', 'resident', 'LOGIN', 'Authentication', '24', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:53:50'),
(108, 24, 'ella flores', 'resident', 'LOGIN', 'users', '24', 'User logged in with role: resident', '120.28.169.241', '2026-10-26 15:53:50'),
(109, 24, 'ella flores', 'resident', 'LOGOUT', 'Authentication', '24', 'User logged out', '120.28.169.241', '2026-10-26 15:54:07'),
(110, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:54:11'),
(111, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '120.28.169.241', '2026-10-26 15:54:11'),
(112, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGOUT', 'Authentication', '3', 'User logged out', '120.28.169.241', '2026-10-26 15:55:28'),
(113, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:55:31'),
(114, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '120.28.169.241', '2026-10-26 15:55:31'),
(115, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGOUT', 'Authentication', '2', 'User logged out', '120.28.169.241', '2026-10-26 15:55:57'),
(116, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'Authentication', '4', 'User logged in successfully', '120.28.169.241', '2026-10-26 15:56:02'),
(117, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGIN', 'users', '4', 'User logged in with role: bhw', '120.28.169.241', '2026-10-26 15:56:02'),
(118, 4, 'Maria Clara (BHW Sitio Takas)', 'bhw', 'LOGOUT', 'Authentication', '4', 'User logged out', '120.28.169.241', '2026-10-26 15:56:58'),
(119, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '143.44.197.85', '2026-10-26 16:01:50'),
(120, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '143.44.197.85', '2026-10-26 16:01:50'),
(121, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGOUT', 'Authentication', '3', 'User logged out', '143.44.197.85', '2026-10-26 16:02:00'),
(122, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '143.44.197.85', '2026-10-26 21:49:58'),
(123, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'Authentication', '15', 'User logged in successfully', '143.44.197.85', '2026-10-26 21:50:02'),
(124, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGIN', 'users', '15', 'User logged in with role: resident', '143.44.197.85', '2026-10-26 21:50:02'),
(125, 15, 'Roberto Gonzales (Resident)', 'resident', 'LOGOUT', 'Authentication', '15', 'User logged out', '143.44.197.85', '2026-10-26 22:02:16'),
(126, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 22:02:24'),
(127, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 22:02:24'),
(128, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 22:06:37'),
(129, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 22:06:37'),
(130, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '120.28.169.241', '2026-10-26 22:06:43'),
(131, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '120.28.169.241', '2026-10-26 22:06:43'),
(132, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'CREATE_HEALTH_SERVICE', 'services', '9', 'Created health service announcement: solo parent health and wellness program', '180.190.242.42', '2026-10-26 22:14:59'),
(133, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '180.190.242.42', '2026-10-26 22:17:29'),
(134, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '180.190.242.42', '2026-10-26 22:17:49'),
(135, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '180.190.242.42', '2026-10-26 22:17:49'),
(136, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGOUT', 'Authentication', '2', 'User logged out', '139.135.99.194', '2026-10-26 22:21:54'),
(137, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '139.135.99.194', '2026-10-26 22:21:56'),
(138, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '139.135.99.194', '2026-10-26 22:21:56'),
(139, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '138.84.66.242', '2026-10-26 22:30:14'),
(140, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '138.84.66.242', '2026-10-26 22:30:17'),
(141, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '138.84.66.242', '2026-10-26 22:30:17'),
(142, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGOUT', 'Authentication', '2', 'User logged out', '138.84.66.242', '2026-10-26 22:30:28'),
(143, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '138.84.66.242', '2026-10-26 22:30:32'),
(144, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '138.84.66.242', '2026-10-26 22:30:32'),
(145, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGOUT', 'Authentication', '3', 'User logged out', '180.191.98.214', '2026-10-26 22:35:38'),
(146, 21, 'Rechelle Ann Perez', 'resident', 'LOGIN', 'Authentication', '21', 'User logged in successfully', '180.191.98.214', '2026-10-26 22:35:55'),
(147, 21, 'Rechelle Ann Perez', 'resident', 'LOGIN', 'users', '21', 'User logged in with role: resident', '180.191.98.214', '2026-10-26 22:35:55'),
(148, 21, 'Rechelle Ann Perez', 'resident', 'LOGOUT', 'Authentication', '21', 'User logged out', '139.135.99.194', '2026-10-26 22:44:34'),
(149, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '139.135.99.194', '2026-10-26 22:44:39'),
(150, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '139.135.99.194', '2026-10-26 22:44:39'),
(151, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 22:49:35'),
(152, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 22:49:35'),
(153, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGOUT', 'Authentication', '3', 'User logged out', '139.135.99.194', '2026-10-26 22:52:06'),
(154, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '139.135.99.194', '2026-10-26 22:52:09'),
(155, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '139.135.99.194', '2026-10-26 22:52:09'),
(156, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'Authentication', '3', 'User logged in successfully', '143.44.197.85', '2026-10-26 22:52:41'),
(157, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGIN', 'users', '3', 'User logged in with role: bhw', '143.44.197.85', '2026-10-26 22:52:41'),
(158, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '143.44.197.85', '2026-10-26 23:07:24'),
(159, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '143.44.197.85', '2026-10-26 23:07:24'),
(160, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '139.135.99.194', '2026-10-26 23:26:27'),
(161, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '139.135.99.194', '2026-10-26 23:26:27'),
(162, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '139.135.99.194', '2026-10-26 23:26:36'),
(163, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'Authentication', '1', 'User logged in successfully', '139.135.99.194', '2026-10-26 23:26:38'),
(164, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGIN', 'users', '1', 'User logged in with role: admin', '139.135.99.194', '2026-10-26 23:26:38'),
(165, 1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin', 'LOGOUT', 'Authentication', '1', 'User logged out', '139.135.99.194', '2026-10-26 23:26:47'),
(166, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'Authentication', '2', 'User logged in successfully', '139.135.99.194', '2026-10-26 23:26:48'),
(167, 2, 'Elena Gomez (Barangay Secretary)', 'secretary', 'LOGIN', 'users', '2', 'User logged in with role: secretary', '139.135.99.194', '2026-10-26 23:26:48'),
(168, 3, 'Ana Garcia (BHW Sitio Turil)', 'bhw', 'LOGOUT', 'Authentication', '3', 'User logged out', '143.44.197.85', '2026-10-26 23:50:02'),
(169, NULL, 'System / Anonymous', 'public', 'RESIDENT_SIGNUP_OTP_VERIFIED', 'users', '25', 'Resident online signup with OTP: sonialyn D Bolivar (vincekiltonh@gmail.com) in Zone 3 - Sitio Takas. Resident #RES-PAS-0020 under Household HH-PAS-015 created.', '124.107.252.135', '2026-10-27 01:12:38'),
(170, 25, 'sonialyn D Bolivar', 'resident', 'LOGIN', 'Authentication', '25', 'User logged in successfully', '124.107.252.135', '2026-10-27 01:12:38'),
(171, 25, 'sonialyn D Bolivar', 'resident', 'LOGOUT', 'Authentication', '25', 'User logged out', '124.107.252.135', '2026-10-27 01:23:41');

-- --------------------------------------------------------

--
-- Table structure for table `barangay_settings`
--

CREATE TABLE `barangay_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `barangay_name` varchar(255) NOT NULL DEFAULT 'Pasil',
  `municipality` varchar(255) NOT NULL DEFAULT 'New Lucena',
  `province` varchar(255) NOT NULL DEFAULT 'Iloilo',
  `captain_name` varchar(255) NOT NULL DEFAULT 'Hon. Jose R. Maravilla',
  `health_officer_name` varchar(255) NOT NULL DEFAULT 'Dr. Maria Santos, MD',
  `contact_phone` varchar(255) NOT NULL DEFAULT '(033) 540-1234',
  `office_address` varchar(255) NOT NULL DEFAULT 'Barangay Health Station, Pasil, New Lucena, 5005 Iloilo',
  `purok_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `system_announcement` text DEFAULT NULL,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sms_provider` varchar(100) NOT NULL DEFAULT 'Semaphore',
  `sms_api_key` varchar(255) DEFAULT NULL,
  `sms_sender_name` varchar(50) NOT NULL DEFAULT 'BHWMS',
  `require_announcement_approval` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barangay_settings`
--

INSERT INTO `barangay_settings` (`id`, `barangay_name`, `municipality`, `province`, `captain_name`, `health_officer_name`, `contact_phone`, `office_address`, `purok_list`, `system_announcement`, `sms_enabled`, `sms_provider`, `sms_api_key`, `sms_sender_name`, `require_announcement_approval`, `created_at`, `updated_at`) VALUES
(1, 'Pasil', 'New Lucena', 'Iloilo', 'Hon. Jose R. Maravilla', 'Dr. Maria Santos, MD', '(033) 540-1234', 'Barangay Health Station, Pasil, New Lucena, 5005 Iloilo', '[\"Zone 1 - Sitio Turil\",\"Zone 2 - Sitio Ubos\",\"Zone 3 - Sitio Takas\",\"Zone 4 - Sitio Bangga\",\"Zone 5 - Sitio Kakugnan\",\"Zone 6 - Sitio Kigyan\",\"Zone 7 - Sitio Kanyugan\"]', 'BHW Health Caravan & Maternal Care Immunization scheduled this Thursday at Barangay Hall.', 0, 'Semaphore', NULL, 'BHWMS', 0, '2026-10-18 09:09:12', '2026-10-18 09:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `bhw_assignments`
--

CREATE TABLE `bhw_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `purok` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL DEFAULT 'Pasil',
  `assigned_date` date NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bhw_assignments`
--

INSERT INTO `bhw_assignments` (`id`, `user_id`, `purok`, `barangay`, `assigned_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'Zone 1 - Sitio Turil', 'Pasil', '2026-04-01', 'active', 'Primary care for Sitio Turil households', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(2, 3, 'Zone 2 - Sitio Ubos', 'Pasil', '2026-04-01', 'active', 'Coverage for Sitio Ubos residents', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(3, 4, 'Zone 3 - Sitio Takas', 'Pasil', '2026-04-01', 'active', 'Coverage for Sitio Takas riverside area', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(4, 4, 'Zone 4 - Sitio Bangga', 'Pasil', '2026-04-01', 'active', 'Highway junction community monitoring', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(5, 5, 'Zone 5 - Sitio Kakugnan', 'Pasil', '2026-04-01', 'active', 'Elementary school area coverage', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(6, 8, 'Zone 6 - Sitio Kigyan', 'Pasil', '2026-04-01', 'active', 'Agricultural zone households coverage', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(7, 9, 'Zone 7 - Sitio Kanyugan', 'Pasil', '2026-04-01', 'active', 'Coconut plantation sector coverage', '2026-10-18 09:09:12', '2026-10-18 09:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_type` varchar(50) DEFAULT 'resident_signup',
  `is_verified` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_otps`
--

INSERT INTO `email_otps` (`id`, `email`, `otp_code`, `otp_type`, `is_verified`, `expires_at`, `created_at`) VALUES
(1, 'test.resident.1793003164@example.com', '303549', 'resident_signup', 1, '2026-10-26 16:36:04', '2026-10-26 08:26:05'),
(2, 'lopinesbenje@gmail.com', '169433', 'resident_signup', 2, '2026-10-26 09:29:06', '2026-10-26 09:19:06'),
(3, 'lopinesbenje@gmail.com', '547956', 'resident_signup', 1, '2026-10-26 09:30:29', '2026-10-26 09:20:29'),
(4, 'bjohnlenard@gmail.com', '247598', 'resident_signup', 1, '2026-10-26 13:37:52', '2026-10-26 13:27:52'),
(5, 'eljunetalaman3@gmail.com', '879581', 'resident_signup', 1, '2026-10-26 15:58:04', '2026-10-26 15:48:04'),
(6, 'vincekiltonh@gmail.com', '566720', 'resident_signup', 1, '2026-10-27 01:21:58', '2026-10-27 01:11:58');

-- --------------------------------------------------------

--
-- Table structure for table `followup_tasks`
--

CREATE TABLE `followup_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resident_id` bigint(20) UNSIGNED DEFAULT NULL,
  `household_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bhw_user_id` bigint(20) UNSIGNED NOT NULL,
  `task_type` varchar(100) NOT NULL DEFAULT 'Routine Visit Follow-up',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('pending','scheduled','in_progress','completed','rescheduled','unable_to_contact','referred') NOT NULL DEFAULT 'pending',
  `outcome_notes` text DEFAULT NULL,
  `referral_facility` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `followup_tasks`
--

INSERT INTO `followup_tasks` (`id`, `resident_id`, `household_id`, `bhw_user_id`, `task_type`, `title`, `description`, `due_date`, `priority`, `status`, `outcome_notes`, `referral_facility`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 'Maternal Follow-up', '3rd Trimester Prenatal Checkup', 'Monitor blood pressure, fetal heart tone, and deliver ferrous sulfate booster.', '2026-10-27', 'high', 'scheduled', NULL, 'Barangay Health Station, Pasil', NULL, '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(2, 2, 1, 3, 'Immunization Recall', 'Measles, Mumps, Rubella (MMR) Dose 1', 'Child turned 9 months old; verify immunization record card and administer vaccine.', '2026-10-30', 'urgent', 'pending', NULL, 'New Lucena Rural Health Unit', NULL, '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(3, 4, 2, 3, 'Hypertension Monitoring', 'Repeat BP Check & Medication Refill', 'Check adherence to daily Amlodipine 5mg and monitor resting blood pressure.', '2026-10-26', 'medium', 'scheduled', NULL, 'Barangay Health Station, Pasil', NULL, '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(4, 5, 3, 4, 'PWD Physical Therapy Referral', 'Orthopedic Mobility Evaluation', 'Assistance for physical therapy schedule at Municipal Health Office.', '2026-11-01', 'medium', 'in_progress', NULL, 'Iloilo Provincial Hospital', NULL, '2026-10-25 00:23:21', '2026-10-25 00:23:21');

-- --------------------------------------------------------

--
-- Table structure for table `health_services`
--

CREATE TABLE `health_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'General Health',
  `description` text NOT NULL,
  `target_audience` varchar(255) NOT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `registration_instructions` text DEFAULT NULL,
  `venue` varchar(255) NOT NULL DEFAULT 'Barangay Health Station, Pasil',
  `event_date` date NOT NULL,
  `start_time` time DEFAULT '08:00:00',
  `end_time` time DEFAULT '12:00:00',
  `target_purok` varchar(100) NOT NULL DEFAULT 'All Zones',
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `poster_image` varchar(255) DEFAULT NULL,
  `reschedule_notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `slots_available` int(11) DEFAULT 100,
  `status` enum('draft','pending_approval','upcoming','active','rescheduled','cancelled','completed','archived') NOT NULL DEFAULT 'upcoming',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_services`
--

INSERT INTO `health_services` (`id`, `title`, `category`, `description`, `target_audience`, `eligibility_criteria`, `requirements`, `registration_instructions`, `venue`, `event_date`, `start_time`, `end_time`, `target_purok`, `contact_person`, `contact_phone`, `poster_image`, `reschedule_notes`, `cancellation_reason`, `is_featured`, `published_at`, `slots_available`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Maternal & Child Care Immunization Caravan', 'Immunization', 'Comprehensive DOH immunization for infants (Pentavalent, Oral Polio, MMR) and prenatal checkup with free ferrous sulfate and folic acid tablets.', 'Infants (0-12 months) and Pregnant Mothers', NULL, 'Child Baby Book / Mother and Child Immunization Record', NULL, 'Barangay Health Center, Pasil', '2026-10-27', '08:00:00', '12:00:00', 'All Zones', NULL, NULL, NULL, NULL, NULL, 0, NULL, 60, 'upcoming', 1, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(2, 'Senior Citizens BP & Blood Sugar Screening Day', 'Senior Wellness', 'Free blood pressure monitoring, blood glucose test (glucometer), nutritional counseling, and turn-over of monthly maintenance tablets (Amlodipine, Losartan, Metformin).', 'Senior Citizens (60 years old and above) & Hypertensive Residents', NULL, 'Senior Citizen OSCA ID & Doctor Prescription if refilling maintenance meds', NULL, 'Barangay Pasil Covered Gymnasium', '2026-10-30', '07:30:00', '11:30:00', 'All Zones', NULL, NULL, NULL, NULL, NULL, 0, NULL, 120, 'upcoming', 1, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(3, 'Operation Timbang Plus & Supplementary Feeding', 'Nutrition & Feeding', 'Annual community nutritional assessment for under-five children. Free fortified arroz caldo and micronutrient powder sachets will be distributed.', 'Children aged 0-59 months', NULL, 'Barangay Health Record', NULL, 'Sitio Turil Day Care Center', '2026-11-03', '09:00:00', '13:00:00', 'Zone 1 - Sitio Turil', NULL, NULL, NULL, NULL, NULL, 0, NULL, 50, 'upcoming', 1, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(4, 'Dengue Prevention & Mist Fogging Operation', 'Disease Prevention', 'Barangay-wide anti-dengue drive. Olyset netting distribution, larvicide water treatment inspection, and scheduled misting of suspected mosquito breeding sites.', 'All Household Heads and Sitio Residents', NULL, 'Ensure water drums are covered and clean surrounding yards', NULL, 'Sitio Ubos & Sitio Takas Riverside Sector', '2026-11-07', '06:00:00', '10:00:00', 'Zone 2 - Sitio Ubos', NULL, NULL, NULL, NULL, NULL, 0, NULL, 150, 'upcoming', 1, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(5, 'Free Dental Checkup & Tooth Extraction Mission', 'Dental Care', 'In partnership with Municipal Health Office New Lucena, free oral prophylaxis, tooth extraction, and fluoride treatment for school-age children.', 'Elementary school children and adults with dental concerns', NULL, 'Barangay Residency Clearance or School ID', NULL, 'Pasil Elementary School Clinic', '2026-11-13', '08:30:00', '15:00:00', 'All Zones', NULL, NULL, NULL, NULL, NULL, 0, NULL, 80, 'upcoming', 1, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(9, 'solo parent health and wellness program', 'Senior Wellness', 'pang hatag bolong', 'Solo Parents', '', 'solo parent ID', '', 'Barangay Pasil Covered Gymnasium', '2026-12-05', '08:00:00', '12:00:00', 'Zone 4 - Sitio Bangga', '', '', NULL, NULL, NULL, 0, '2026-10-26 22:14:59', 50, '', 1, '2026-10-26 22:14:59', '2026-10-26 22:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `household_number` varchar(100) NOT NULL,
  `head_name` varchar(255) NOT NULL,
  `purok` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL DEFAULT 'Pasil',
  `municipality` varchar(100) NOT NULL DEFAULT 'New Lucena',
  `province` varchar(100) NOT NULL DEFAULT 'Iloilo',
  `address_details` text DEFAULT NULL,
  `water_source` varchar(150) NOT NULL DEFAULT 'Level II - Communal Faucet',
  `sanitary_toilet` varchar(150) NOT NULL DEFAULT 'Water-sealed Sanitary Toilet',
  `income_bracket` varchar(150) NOT NULL DEFAULT 'Low-Income / NTHP',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `household_number`, `head_name`, `purok`, `barangay`, `municipality`, `province`, `address_details`, `water_source`, `sanitary_toilet`, `income_bracket`, `latitude`, `longitude`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'HH-PAS-001', 'Roberto Gonzales', 'Zone 1 - Sitio Turil', 'Pasil', 'New Lucena', 'Iloilo', 'Sitio Turil, Near Municipal Plaza, Pasil', 'Level III - Waterworks System', 'Water-sealed Sanitary Toilet', 'Low-Income / NTHP', 10.8875588, 122.6087341, 3, '2026-10-18 09:09:12', '2026-10-26 08:10:54'),
(2, 'HH-PAS-002', 'Elena Magbanua', 'Zone 2 - Sitio Ubos', 'Pasil', 'New Lucena', 'Iloilo', 'Sitio Ubos Highway, Pasil', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', '4Ps Beneficiary', 10.8881000, 122.6115000, 3, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(3, 'HH-PAS-003', 'Fernando Villanueva', 'Zone 3 - Sitio Takas', 'Pasil', 'New Lucena', 'Iloilo', 'Riverside Area, Sitio Takas, Pasil', 'Level I - Protected Well', 'Pour Flush Toilet', 'Low-Income', 10.8869000, 122.6098000, 4, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(4, 'HH-PAS-004', 'Lourdes Sonza', 'Zone 5 - Sitio Kakugnan', 'Pasil', 'New Lucena', 'Iloilo', 'Near Elementary School, Sitio Kakugnan', 'Level III - Waterworks System', 'Water-sealed Sanitary Toilet', 'Middle Class', 10.8892000, 122.6122000, 5, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(5, 'HH-PAS-005', 'Ramon Delgado', 'Zone 4 - Sitio Bangga', 'Pasil', 'New Lucena', 'Iloilo', 'Crossing Sitio Bangga near Chapel', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', '4Ps Beneficiary', 10.8865000, 122.6130000, 4, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(6, 'HH-PAS-006', 'Corazon Alcantara', 'Zone 6 - Sitio Kigyan', 'Pasil', 'New Lucena', 'Iloilo', 'Sitio Kigyan farm road', 'Level I - Protected Well', 'Water-sealed Sanitary Toilet', 'Low-Income', 10.8858000, 122.6085000, 3, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(7, 'HH-PAS-007', 'Danilo Pardo', 'Zone 7 - Sitio Kanyugan', 'Pasil', 'New Lucena', 'Iloilo', 'Sitio Kanyugan boundary road', 'Level II - Communal Faucet', 'Pour Flush Toilet', 'Low-Income / NTHP', 10.8899000, 122.6145000, 5, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(10, 'HH-PAS-008', 'Rechelle Ann Perez', 'Zone 3 - Sitio Takas', 'Pasil', 'New Lucena', 'Iloilo', 'likod simbahan', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', 'Low-Income / NTHP', 10.8875000, 122.6108000, 21, '2026-10-26 02:59:51', '2026-10-26 09:10:10'),
(12, 'HH-PAS-011', 'Rechelle ann Perez', 'Zone 1 - Sitio Turil', 'Pasil', 'New Lucena', 'Iloilo', 'likod simbahan', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', 'Low-Income / NTHP', 10.8598366, 122.7166610, 22, '2026-10-26 09:20:45', '2026-10-26 09:20:45'),
(13, 'HH-PAS-013', 'John Lenard Bocal', 'Zone 3 - Sitio Takas', 'Pasil', 'New Lucena', 'Iloilo', 'Zone 3 - Sitio Takas, Barangay Pasil', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', 'Low-Income / NTHP', 10.8875000, 122.6108000, NULL, '2026-10-26 13:28:11', '2026-10-26 13:29:12'),
(14, 'HH-PAS-014', 'ella flores', 'Zone 6 - Sitio Kigyan', 'Pasil', 'New Lucena', 'Iloilo', 'tubang gym', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', 'Low-Income / NTHP', 10.8816360, 122.7122500, 24, '2026-10-26 15:48:53', '2026-10-26 15:48:53'),
(15, 'HH-PAS-015', 'sonialyn D Bolivar', 'Zone 3 - Sitio Takas', 'Pasil', 'New Lucena', 'Iloilo', 'ilaya', 'Level II - Communal Faucet', 'Water-sealed Sanitary Toilet', 'Low-Income / NTHP', 10.8599936, 122.7168600, 25, '2026-10-27 01:12:38', '2026-10-27 01:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `category` enum('Medicine','Vaccine','Nutritional Supplement','Medical Supply','Diagnostic / Test Kit') NOT NULL DEFAULT 'Medicine',
  `dosage_strength` varchar(100) DEFAULT NULL,
  `unit_of_measure` varchar(50) NOT NULL DEFAULT 'tablets',
  `quantity_on_hand` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 20,
  `batch_lot_number` varchar(100) DEFAULT NULL,
  `expiration_date` date NOT NULL,
  `storage_location` varchar(150) NOT NULL DEFAULT 'Barangay Health Station Medicine Cabinet',
  `status` enum('available','low_stock','expired','depleted') NOT NULL DEFAULT 'available',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `item_name`, `generic_name`, `category`, `dosage_strength`, `unit_of_measure`, `quantity_on_hand`, `reorder_level`, `batch_lot_number`, `expiration_date`, `storage_location`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'Amlodipine Besylate', 'Amlodipine', 'Medicine', '5 mg', 'tablets', 450, 100, 'AMLO-2025-01', '2027-11-30', 'Cabinet A - Antihypertensive Shelf', 'available', 'DOH RHU Allocation for Senior Citizens', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(2, 'Metformin Hydrochloride', 'Metformin', 'Medicine', '500 mg', 'tablets', 320, 100, 'MET-2025-04', '2027-08-31', 'Cabinet A - Diabetes Maintenance', 'available', 'For Type 2 Diabetes management', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(3, 'Ferrous Sulfate + Folic Acid', 'Iron + Folic Acid', 'Nutritional Supplement', '60mg elemental iron / 400mcg folic acid', 'tablets', 600, 150, 'IRON-2026-02', '2028-03-31', 'Cabinet B - Maternal Care Shelf', 'available', 'Free prenatal micronutrient allocation', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(4, 'Paracetamol Pediatric Syrup', 'Paracetamol', 'Medicine', '120mg / 5ml (60ml bottle)', 'bottles', 85, 30, 'PARA-2026-09', '2027-06-30', 'Cabinet C - Pediatric Medicines', 'available', 'For fever and pain relief in infants and children', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(5, 'Pentavalent Vaccine (DTP-HepB-Hib)', 'DTP-HepB-Hib', 'Vaccine', 'Single dose vial (0.5ml)', 'vials', 40, 20, 'PENTA-2026-X1', '2026-12-31', 'Health Center Cold Chain Refrigerator (2°C - 8°C)', 'available', 'Stored under cold chain management', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(6, 'Blood Glucose Test Strips (Glucometer)', 'Glucose Strips', 'Diagnostic / Test Kit', 'Box of 50 strips', 'boxes', 12, 5, 'GLUC-2026-B8', '2027-05-31', 'Diagnostic Equipment Cabinet', 'available', 'Used during Senior Wellness clinics', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(7, 'Oral Rehydration Salts (ORS)', 'Oral Rehydration Salts', 'Medicine', 'Sachet for 1 Liter', 'boxes', 180, 50, 'ORS-2025-55', '2028-01-31', 'Cabinet C - Diarrhea & Hydration', 'available', 'Essential hydration therapy for infants and adults', '2026-10-25 00:23:21', '2026-10-25 00:23:21'),
(8, 'Losartan Potassium', 'Losartan', 'Medicine', '50 mg', 'tablets', 18, 50, 'LOS-2024-99', '2026-08-31', 'Cabinet A - Antihypertensive Shelf', 'low_stock', 'Low stock alert: reorder from Municipal Health Office needed', '2026-10-25 00:23:21', '2026-10-25 00:23:21');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `movement_type` enum('stock_in','dispensed_visit','dispensed_walkin','damaged','expired_disposed','inventory_adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `resident_id` bigint(20) UNSIGNED DEFAULT NULL,
  `visit_log_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED NOT NULL,
  `movement_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iso_evaluations`
--

CREATE TABLE `iso_evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `evaluator_name` varchar(255) NOT NULL,
  `evaluator_role` enum('bhw','supervisor','it_expert','resident') NOT NULL,
  `organization` varchar(255) NOT NULL DEFAULT 'Barangay Pasil Health Center',
  `score_functional_suitability` decimal(3,2) NOT NULL,
  `score_performance_efficiency` decimal(3,2) NOT NULL,
  `score_compatibility` decimal(3,2) NOT NULL,
  `score_usability` decimal(3,2) NOT NULL,
  `score_reliability` decimal(3,2) NOT NULL,
  `score_security` decimal(3,2) NOT NULL,
  `score_maintainability` decimal(3,2) NOT NULL,
  `score_portability` decimal(3,2) NOT NULL,
  `overall_mean` decimal(3,2) NOT NULL,
  `verbal_interpretation` varchar(100) NOT NULL,
  `feedback_comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iso_evaluations`
--

INSERT INTO `iso_evaluations` (`id`, `evaluator_name`, `evaluator_role`, `organization`, `score_functional_suitability`, `score_performance_efficiency`, `score_compatibility`, `score_usability`, `score_reliability`, `score_security`, `score_maintainability`, `score_portability`, `overall_mean`, `verbal_interpretation`, `feedback_comments`, `created_at`, `updated_at`) VALUES
(1, 'Dr. Maria Santos', 'supervisor', 'Municipal Health Office, New Lucena', 4.90, 4.80, 4.85, 4.95, 4.80, 4.90, 4.85, 4.90, 4.87, 'Excellent (Very High Quality)', 'The system accurately addresses all BHW record-keeping needs, visit scheduling, and GPS map verification.', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(2, 'Ana Garcia', 'bhw', 'Barangay Health Workers Association', 4.80, 4.75, 4.80, 4.90, 4.70, 4.85, 4.75, 4.80, 4.79, 'Excellent (Very High Quality)', 'Very easy to log visits using mobile phones with automatic GPS coordinate capture.', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(3, 'Engr. Mark Reyes, MIT', 'it_expert', 'Iloilo IT Systems Evaluators Guild', 5.00, 4.85, 4.90, 4.80, 4.85, 4.95, 4.90, 4.85, 4.89, 'Excellent (Very High Quality)', 'Robust architecture with ISO/IEC 25010 metrics calculation and clean DataTables print layout.', '2026-10-18 09:09:12', '2026-10-18 09:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_role` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('announcement','schedule_change','cancellation','appointment','reminder','task','general') NOT NULL DEFAULT 'general',
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `target_role`, `title`, `message`, `type`, `link_url`, `is_read`, `created_at`) VALUES
(1, NULL, 'all', 'Maternal & Child Care Caravan Announced', 'Free infant immunization and prenatal checkups scheduled for October 27 at Barangay Health Center.', 'announcement', 'index.php#services', 0, '2026-10-25 00:23:21'),
(2, NULL, 'all', 'Senior Citizens BP & Screening Day', 'Senior Citizens health screening and free maintenance medicine refills on October 30 at Brgy. Pasil Gymnasium.', 'announcement', 'index.php#services', 0, '2026-10-25 00:23:21'),
(3, 3, 'bhw', 'New Assigned Visit Schedule', 'Household visit assigned for Baby Lucas Gonzales (Pentavalent Vaccine) in Zone 1 - Sitio Turil.', 'task', 'visits/index.php', 0, '2026-10-25 00:23:21'),
(4, 15, 'resident', 'Appointment Request Received', 'Your request for a BHW Home Visit has been logged and assigned to BHW Ana Garcia.', 'appointment', 'portal_resident.php', 0, '2026-10-25 00:23:21'),
(5, 15, NULL, 'Notice: Barangay Pasil Maternal & Child Immunization Caravan', 'Scheduled on Oct 29, 2026 at Barangay Pasil Health Station. Target: Infants (0-12 months) and Pregnant Mothers', '', 'services/index.php', 0, '2026-10-26 08:55:37'),
(6, 16, NULL, 'Notice: Barangay Pasil Maternal & Child Immunization Caravan', 'Scheduled on Oct 29, 2026 at Barangay Pasil Health Station. Target: Infants (0-12 months) and Pregnant Mothers', '', 'services/index.php', 0, '2026-10-26 08:55:42'),
(7, 17, NULL, 'Notice: Barangay Pasil Maternal & Child Immunization Caravan', 'Scheduled on Oct 29, 2026 at Barangay Pasil Health Station. Target: Infants (0-12 months) and Pregnant Mothers', '', 'services/index.php', 0, '2026-10-26 08:55:46'),
(8, 18, NULL, 'Notice: Barangay Pasil Maternal & Child Immunization Caravan', 'Scheduled on Oct 29, 2026 at Barangay Pasil Health Station. Target: Infants (0-12 months) and Pregnant Mothers', '', 'services/index.php', 0, '2026-10-26 08:55:51'),
(9, 19, NULL, 'Notice: Barangay Pasil Maternal & Child Immunization Caravan', 'Scheduled on Oct 29, 2026 at Barangay Pasil Health Station. Target: Infants (0-12 months) and Pregnant Mothers', '', 'services/index.php', 0, '2026-10-26 08:55:56'),
(10, 21, NULL, 'Notice: Barangay Pasil Maternal & Child Immunization Caravan', 'Scheduled on Oct 29, 2026 at Barangay Pasil Health Station. Target: Infants (0-12 months) and Pregnant Mothers', '', 'services/index.php', 0, '2026-10-26 08:56:01'),
(11, 21, NULL, 'Health Request #3 Updated', 'Your request for \'BHW Home Visit\' is now Completed. Staff notes: Scheduled for November 1, 2026 prenatal checkup and maternal vitals check.', '', 'services/inquiries.php', 0, '2026-10-26 09:25:23'),
(12, 15, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:07'),
(13, 16, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:09'),
(14, 17, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:11'),
(15, 19, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:13'),
(16, 18, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:15'),
(17, 21, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:18'),
(18, 22, NULL, 'Notice: chuchu blabla', 'Scheduled on Oct 26, 2026 at asd. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:26:20'),
(20, 15, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:28:51'),
(21, 16, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:28:53'),
(22, 17, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:28:55'),
(23, 19, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:28:57'),
(24, 18, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:29:00'),
(25, 21, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:29:02'),
(26, 22, NULL, 'Notice: klasdl;ksdl;', 'Scheduled on Nov 26, 2026 at asdas. Target: All Residents', '', 'services/index.php', 0, '2026-10-26 15:29:04'),
(28, 24, NULL, 'Health Request #4 Updated', 'Your request for \'BHW Home Visit\' is now Pending. Staff notes: tommorow', '', 'services/inquiries.php', 0, '2026-10-26 15:51:09'),
(29, 24, NULL, 'Health Request #4 Updated', 'Your request for \'BHW Home Visit\' is now Scheduled for Home Visit. Staff notes: tommorow', '', 'services/inquiries.php', 0, '2026-10-26 15:53:18');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `in_app_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `notify_announcements` tinyint(1) NOT NULL DEFAULT 1,
  `notify_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `notify_schedule_changes` tinyint(1) NOT NULL DEFAULT 1,
  `consent_timestamp` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `household_id` bigint(20) UNSIGNED NOT NULL,
  `family_code` varchar(50) DEFAULT NULL,
  `resident_code` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `sex` varchar(20) NOT NULL,
  `civil_status` varchar(50) NOT NULL DEFAULT 'Single',
  `contact_number` varchar(50) DEFAULT NULL,
  `philhealth_number` varchar(50) DEFAULT NULL,
  `id_type` varchar(100) DEFAULT NULL,
  `valid_id_photo` varchar(255) DEFAULT NULL,
  `selfie_photo` varchar(255) DEFAULT NULL,
  `verification_status` varchar(50) NOT NULL DEFAULT 'Verified',
  `status` enum('active','transferred','deceased','inactive') NOT NULL DEFAULT 'active',
  `linked_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_head` tinyint(1) NOT NULL DEFAULT 0,
  `is_pregnant` tinyint(1) NOT NULL DEFAULT 0,
  `is_lactating` tinyint(1) NOT NULL DEFAULT 0,
  `is_infant` tinyint(1) NOT NULL DEFAULT 0,
  `is_senior` tinyint(1) NOT NULL DEFAULT 0,
  `is_pwd` tinyint(1) NOT NULL DEFAULT 0,
  `is_solo_parent` tinyint(1) NOT NULL DEFAULT 0,
  `is_bedridden` tinyint(1) NOT NULL DEFAULT 0,
  `has_hypertension` tinyint(1) NOT NULL DEFAULT 0,
  `has_diabetes` tinyint(1) NOT NULL DEFAULT 0,
  `has_malnutrition` tinyint(1) NOT NULL DEFAULT 0,
  `immunization_status` varchar(100) DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `household_id`, `family_code`, `resident_code`, `first_name`, `middle_name`, `last_name`, `suffix`, `date_of_birth`, `sex`, `civil_status`, `contact_number`, `philhealth_number`, `id_type`, `valid_id_photo`, `selfie_photo`, `verification_status`, `status`, `linked_user_id`, `is_head`, `is_pregnant`, `is_lactating`, `is_infant`, `is_senior`, `is_pwd`, `is_solo_parent`, `is_bedridden`, `has_hypertension`, `has_diabetes`, `has_malnutrition`, `immunization_status`, `medical_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'FAM-001', 'RES-PAS-0001', 'Teresa', 'Cruz', 'Gonzales', NULL, '1998-05-14', 'Female', 'Married', '09179998811', '12-054329871-9', NULL, NULL, NULL, 'Verified', 'active', NULL, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Prenatal 2nd Trimester, BP monitoring needed', '2026-10-18 09:09:12', '2026-10-25 00:23:21'),
(2, 1, 'FAM-001', 'RES-PAS-0002', 'Baby Lucas', 'Cruz', 'Gonzales', NULL, '2025-11-10', 'Male', 'Single', '09179998811', 'N/A', NULL, NULL, NULL, 'Verified', 'active', NULL, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 'Pentavalent 2 completed', 'Scheduled for MMR vaccine next month', '2026-10-18 09:09:12', '2026-10-25 00:23:21'),
(3, 1, 'FAM-001', 'RES-PAS-0003', 'Roberto', 'Perez', 'Gonzales', NULL, '1994-08-12', 'Male', 'Married', '09179998810', '12-054329871-1', NULL, NULL, NULL, 'Verified', 'active', 15, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Head of family, healthy', '2026-10-18 09:09:12', '2026-10-26 09:10:10'),
(4, 2, 'FAM-002', 'RES-PAS-0004', 'Elena', 'Rios', 'Magbanua', NULL, '1954-08-20', 'Female', 'Widowed', '09187776622', '18-987654321-0', NULL, NULL, NULL, 'Verified', 'active', 16, 1, 0, 0, 0, 1, 0, 0, 0, 1, 1, 0, NULL, 'Senior Wellness maintenance meds: Amlodipine 5mg & Metformin 500mg', '2026-10-18 09:09:12', '2026-10-26 09:10:10'),
(5, 3, 'FAM-003', 'RES-PAS-0005', 'Fernando Jr.', 'Bautista', 'Villanueva', NULL, '2010-03-15', 'Male', 'Single', '09194445566', 'N/A', NULL, NULL, NULL, 'Verified', 'active', NULL, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, NULL, 'Orthopedic disability, routine physical therapy follow-up', '2026-10-18 09:09:12', '2026-10-25 00:23:21'),
(6, 3, 'FAM-003', 'RES-PAS-0006', 'Fernando Sr.', 'Bautista', 'Villanueva', NULL, '1982-01-22', 'Male', 'Married', '09194445560', '14-887766554-2', NULL, NULL, NULL, 'Verified', 'active', 17, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Head of household', '2026-10-18 09:09:12', '2026-10-26 09:10:10'),
(7, 4, 'FAM-004', 'RES-PAS-0007', 'Lourdes', 'Sonza', 'Sonza', NULL, '1958-12-05', 'Female', 'Married', '09193339999', '15-112233445-6', NULL, NULL, NULL, 'Verified', 'active', 19, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, NULL, 'Senior maintenance checkup, Amlodipine 10mg daily', '2026-10-18 09:09:12', '2026-10-26 09:10:11'),
(8, 5, 'FAM-005', 'RES-PAS-0008', 'Ramon', 'Torres', 'Delgado', NULL, '1962-04-18', 'Male', 'Married', '09192223344', '11-223344556-7', NULL, NULL, NULL, 'Verified', 'active', 18, 1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, NULL, 'Maintenance for mild hypertension', '2026-10-18 09:09:12', '2026-10-26 09:10:11'),
(9, 6, 'FAM-006', 'RES-PAS-0009', 'Corazon', 'Santos', 'Alcantara', NULL, '1975-09-09', 'Female', 'Married', '09191112233', '10-998877665-4', NULL, NULL, NULL, 'Verified', 'active', NULL, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, NULL, 'Type 2 diabetes mellitus diet management', '2026-10-18 09:09:12', '2026-10-25 00:23:21'),
(10, 7, 'FAM-007', 'RES-PAS-0010', 'Danilo', 'Lopez', 'Pardo', NULL, '1988-11-25', 'Male', 'Married', '09190001122', '13-445566778-9', NULL, NULL, NULL, 'Verified', 'active', NULL, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'General wellness check', '2026-10-18 09:09:12', '2026-10-25 00:23:21'),
(14, 10, 'FAM-010', 'RES-PAS-0011', 'Rechelle Ann', NULL, 'Perez', NULL, '2004-11-05', 'Female', 'Single', '09638646222', NULL, 'PhilSys National ID', 'uploads/verifications/valid_id_20261026_025951_1729fd35.jpg', 'uploads/verifications/selfie_20261026_025951_160c5222.jpg', 'Verified', 'active', 21, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Self-registered online resident. Prenatal monitoring scheduled.', '2026-10-26 02:59:51', '2026-10-26 09:10:12'),
(17, 12, 'FAM-012', 'RES-PAS-0015', 'Rechelle ann', '', 'Perez', '', '2004-11-05', 'Female', 'Single', '09638646222', NULL, 'PhilSys National ID', 'uploads/verifications/valid_id_20261026_091906_44b5a948.jpg', 'uploads/verifications/selfie_20261026_091906_ecc11786.png', 'Verified', 'active', 22, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Self-registered online with Brevo email OTP verification. Pending in-person clinical profiling by assigned BHW.', '2026-10-26 09:20:45', '2026-10-26 09:20:45'),
(18, 13, 'FAM-013', 'RES-PAS-0018', 'John Lenard', '', 'Bocal', '', '1999-03-22', 'Male', 'Single', '09077888705', '', 'PhilSys National ID', 'uploads/verifications/valid_id_20261026_132752_2a87847e.jpg', 'uploads/verifications/selfie_20261026_132752_762818c1.jpg', 'Verified', 'active', 23, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, '', 'Self-registered online with Brevo email OTP verification. Pending in-person clinical profiling by assigned BHW.', '2026-10-26 13:28:11', '2026-10-26 13:29:12'),
(19, 14, 'FAM-014', 'RES-PAS-0019', 'ella', '', 'flores', '', '2001-05-02', 'Female', 'Married', '09275306801', NULL, '4Ps / DSWD ID', 'uploads/verifications/valid_id_20261026_154804_1cd07e2d.jpg', 'uploads/verifications/selfie_20261026_154804_9f3e9b73.jpg', 'Verified', 'active', 24, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Self-registered online with Brevo email OTP verification. Pending in-person clinical profiling by assigned BHW.', '2026-10-26 15:48:53', '2026-10-26 15:48:53'),
(20, 15, 'FAM-015', 'RES-PAS-0020', 'sonialyn', 'D', 'Bolivar', '', '2000-12-06', 'Female', 'Single', '096576454758', NULL, 'PhilSys National ID', 'uploads/verifications/valid_id_20261027_011158_2408e5c8.jpg', 'uploads/verifications/selfie_20261027_011158_7aef75dd.jpg', 'Verified', 'active', 25, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, 'Self-registered online with Brevo email OTP verification. Pending in-person clinical profiling by assigned BHW.', '2026-10-27 01:12:38', '2026-10-27 01:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resident_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resident_name` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `purok` varchar(100) NOT NULL,
  `request_type` varchar(100) NOT NULL DEFAULT 'BHW Home Visit',
  `description` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `assigned_bhw_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `resident_user_id`, `service_id`, `resident_name`, `contact_number`, `purok`, `request_type`, `description`, `preferred_date`, `appointment_date`, `appointment_time`, `status`, `assigned_bhw_user_id`, `response_notes`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 6, 1, 'Roberto Gonzales', '09179998810', 'Zone 1 - Sitio Turil', 'Maternal & Infant Checkup', 'Requesting home visit checkup for baby Lucas (Pentavalent booster question) and prenatal BP check for wife Teresa.', '2026-10-25', NULL, NULL, 'scheduled', 3, 'Test schedule note', NULL, '2026-10-23 23:52:07', '2026-10-26 08:55:37'),
(3, 21, NULL, 'Rechelle Ann Perez', '09638646222', 'Zone 3 - Sitio Takas', 'BHW Home Visit', 'prenatal', '2026-10-27', NULL, NULL, 'completed', 4, 'Scheduled for November 1, 2026 prenatal checkup and maternal vitals check.', NULL, '2026-10-26 03:02:03', '2026-10-26 09:25:23'),
(4, 24, NULL, 'ella flores', '09275306801', 'Zone 6 - Sitio Kigyan', 'BHW Home Visit', 'allergy', '2026-10-27', NULL, NULL, 'scheduled', 13, 'tommorow', NULL, '2026-10-26 15:50:08', '2026-10-26 15:53:18'),
(5, 25, NULL, 'sonialyn D Bolivar', '096576454758', 'Zone 3 - Sitio Takas', 'Prenatal & Maternal Care', 'Prenatal', '2026-10-28', NULL, NULL, 'pending', 4, NULL, NULL, '2026-10-27 01:18:11', '2026-10-27 01:18:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'bhw',
  `employment_status` varchar(100) NOT NULL DEFAULT 'Barangay Volunteer BHW',
  `date_appointed` date DEFAULT NULL,
  `training_records` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `id_type` varchar(100) DEFAULT NULL,
  `valid_id_photo` varchar(255) DEFAULT NULL,
  `selfie_photo` varchar(255) DEFAULT NULL,
  `verification_status` varchar(50) NOT NULL DEFAULT 'Verified',
  `assigned_barangay` varchar(100) NOT NULL DEFAULT 'Pasil',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `employment_status`, `date_appointed`, `training_records`, `qualifications`, `phone_number`, `purok`, `id_type`, `valid_id_photo`, `selfie_photo`, `verification_status`, `assigned_barangay`, `is_active`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Hon. Jose R. Maravilla (Barangay Captain)', 'admin@newlucena.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'admin', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09171234567', 'Zone 1 - Sitio Turil', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-23 23:52:05'),
(2, 'Elena Gomez (Barangay Secretary)', 'secretary@newlucena.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'secretary', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09185559988', 'Barangay Hall Desk', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-23 23:52:05'),
(3, 'Ana Garcia (BHW Sitio Turil)', 'bhw1@newlucena.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195551111', 'Zone 1 - Sitio Turil', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-23 23:52:05'),
(4, 'Maria Clara (BHW Sitio Takas)', 'bhw2@newlucena.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195552222', 'Zone 3 - Sitio Takas', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-23 23:52:05'),
(5, 'Juana Dela Cruz (BHW Sitio Kakugnan)', 'bhw3@newlucena.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195553333', 'Zone 5 - Sitio Kakugnan', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-23 23:52:05'),
(6, 'Hon. Jose R. Maravilla', 'admin@bhwms.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'admin', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09171234567', 'Zone 1 - Sitio Turil', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(8, 'BHW Sitio Turil', 'bhw.turil@bhwms.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195554444', 'Zone 1 - Sitio Turil', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(9, 'BHW Sitio Ubos', 'bhw.ubos@bhwms.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195555555', 'Zone 2 - Sitio Ubos', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(10, 'BHW Sitio Takas', 'bhw.takas@bhwms.gov.ph', NULL, '$2y$10$9Xz1/01R04JRNdn6YwZZCujGnjXkNzNQuEG1OJu34wUbDCwiYd1iW', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195556666', 'Zone 3 - Sitio Takas', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(11, 'Rosario Perez (BHW Sitio Ubos)', 'bhw4@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195554444', 'Zone 2 - Sitio Ubos', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(12, 'Elena Dalisay (BHW Sitio Bangga)', 'bhw5@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195555555', 'Zone 4 - Sitio Bangga', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(13, 'Carmen Lopez (BHW Sitio Kigyan)', 'bhw6@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195556666', 'Zone 6 - Sitio Kigyan', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(14, 'Luzviminda Santos (BHW Sitio Kanyugan)', 'bhw7@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'bhw', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09195557777', 'Zone 7 - Sitio Kanyugan', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(15, 'Roberto Gonzales (Resident)', 'resident1@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09179998810', 'Zone 1 - Sitio Turil', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(16, 'Elena Magbanua (Resident)', 'resident2@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09187776622', 'Zone 2 - Sitio Ubos', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(17, 'Fernando Villanueva (Resident)', 'resident3@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09194445560', 'Zone 3 - Sitio Takas', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:06', '2026-10-23 23:52:06'),
(18, 'Ramon Delgado (Resident)', 'resident4@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09192223344', 'Zone 4 - Sitio Bangga', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(19, 'Lourdes Sonza (Resident)', 'resident5@newlucena.gov.ph', NULL, '$2y$10$KTEmcjJQ8Ecb8ChouQlreO.dCPpNUn4tlsGYNPiGaPGRsy50pUYoC', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09193339999', 'Zone 5 - Sitio Kakugnan', NULL, NULL, NULL, 'Verified', 'Pasil', 1, NULL, '2026-10-23 23:52:07', '2026-10-23 23:52:07'),
(21, 'Rechelle Ann Perez', 'annperez130@gmail.com', NULL, '$2y$10$gwdJzD6AyPsPpDaXXB3lMe1lAGT/tikhFdv0R.E/9XNDZt91GBsJG', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09638646222', 'Zone 3 - Sitio Takas', 'PhilSys National ID', 'uploads/verifications/valid_id_20261026_025951_1729fd35.jpg', 'uploads/verifications/selfie_20261026_025951_160c5222.jpg', 'Verified', 'Pasil', 1, NULL, '2026-10-26 02:59:51', '2026-10-26 09:10:10'),
(22, 'Rechelle ann Perez', 'lopinesbenje@gmail.com', NULL, '$2y$10$6IKsKiE2mnhP1E8k5cHERe5BLhLDdLKhxQQUCSiNUJ4QYpee11lwS', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09638646222', 'Zone 1 - Sitio Turil', 'PhilSys National ID', 'uploads/verifications/valid_id_20261026_091906_44b5a948.jpg', 'uploads/verifications/selfie_20261026_091906_ecc11786.png', 'Verified', 'Pasil', 1, NULL, '2026-10-26 09:20:45', '2026-10-26 09:20:45'),
(24, 'ella flores', 'eljunetalaman3@gmail.com', NULL, '$2y$10$JSm0/Mi04tAaZYwGMQoH7Oju0njhHpAoUEADTMHyuQNpXkCUF/nxO', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '09275306801', 'Zone 6 - Sitio Kigyan', '4Ps / DSWD ID', 'uploads/verifications/valid_id_20261026_154804_1cd07e2d.jpg', 'uploads/verifications/selfie_20261026_154804_9f3e9b73.jpg', 'Verified', 'Pasil', 1, NULL, '2026-10-26 15:48:53', '2026-10-26 15:48:53'),
(25, 'sonialyn D Bolivar', 'vincekiltonh@gmail.com', NULL, '$2y$10$nbYA4.eVHr/X7IrYJPX74u/tAOYkI7eTISsS/cPYk7I9FPOVIKLNe', 'resident', 'Barangay Volunteer BHW', NULL, NULL, NULL, '096576454758', 'Zone 3 - Sitio Takas', 'PhilSys National ID', 'uploads/verifications/valid_id_20261027_011158_2408e5c8.jpg', 'uploads/verifications/selfie_20261027_011158_7aef75dd.jpg', 'Verified', 'Pasil', 1, NULL, '2026-10-27 01:12:38', '2026-10-27 01:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `visit_logs`
--

CREATE TABLE `visit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_schedule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resident_id` bigint(20) UNSIGNED NOT NULL,
  `bhw_user_id` bigint(20) UNSIGNED NOT NULL,
  `vitals_bp` varchar(50) DEFAULT NULL,
  `vitals_weight_kg` decimal(5,2) DEFAULT NULL,
  `vitals_temp_c` decimal(4,1) DEFAULT NULL,
  `vitals_blood_sugar` varchar(50) DEFAULT NULL,
  `health_notes` text NOT NULL,
  `services_rendered` text NOT NULL,
  `follow_up_needed` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `follow_up_reason` text DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `accuracy_meters` decimal(8,2) DEFAULT NULL,
  `geo_permission_granted` tinyint(1) NOT NULL DEFAULT 1,
  `geo_verified` tinyint(1) NOT NULL DEFAULT 1,
  `captured_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visit_logs`
--

INSERT INTO `visit_logs` (`id`, `visit_schedule_id`, `resident_id`, `bhw_user_id`, `vitals_bp`, `vitals_weight_kg`, `vitals_temp_c`, `vitals_blood_sugar`, `health_notes`, `services_rendered`, `follow_up_needed`, `follow_up_date`, `follow_up_reason`, `latitude`, `longitude`, `accuracy_meters`, `geo_permission_granted`, `geo_verified`, `captured_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, '118/78', 62.50, 36.6, '95 mg/dL', 'Fetal heart tone normal. Mother feeling well. Given Ferrous Sulfate + Folic Acid 30 tabs.', 'Prenatal Vitals, Tetanus Toxoid counseling, Nutrition advice', 1, '2026-11-01', 'Routine 3rd Trimester Prenatal Checkup', 10.8875200, 122.6108100, 4.20, 1, 1, '2026-10-16 09:09:12', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(2, 3, 4, 3, '138/88', 58.00, 36.5, '130 mg/dL', 'Slightly elevated BP. Reminded to lessen salt intake and continue daily Amlodipine.', 'BP Screening, Blood Sugar test, Free Maintenance Meds turn-over', 1, '2026-10-25', 'Repeat BP check after 1 week', 10.8881200, 122.6115300, 3.80, 1, 1, '2026-10-13 09:09:12', '2026-10-18 09:09:12', '2026-10-18 09:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `visit_schedules`
--

CREATE TABLE `visit_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `resident_id` bigint(20) UNSIGNED NOT NULL,
  `bhw_user_id` bigint(20) UNSIGNED NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `visit_type` varchar(100) NOT NULL DEFAULT 'routine_monitoring',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','rescheduled','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visit_schedules`
--

INSERT INTO `visit_schedules` (`id`, `resident_id`, `bhw_user_id`, `scheduled_date`, `scheduled_time`, `visit_type`, `priority`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '2026-10-16', '09:00:00', 'maternal_checkup', 'high', 'Maternal prenatal checkup & iron supplements distribution', 'completed', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(2, 2, 3, '2026-10-19', '10:30:00', 'child_immunization', 'urgent', 'Child weight monitoring & Vitamin A drops', 'scheduled', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(3, 4, 3, '2026-10-13', '14:00:00', 'senior_wellness', 'medium', 'Blood pressure check & glucometer testing', 'completed', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(4, 7, 5, '2026-10-21', '09:30:00', 'senior_wellness', 'medium', 'Maintenance medicine verification', 'scheduled', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(5, 5, 4, '2026-10-22', '11:00:00', 'routine_monitoring', 'medium', 'PWD mobility assistance assessment', 'scheduled', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(6, 9, 3, '2026-10-23', '08:30:00', 'routine_monitoring', 'low', 'Blood sugar screening and lifestyle check', 'scheduled', '2026-10-18 09:09:12', '2026-10-18 09:09:12'),
(7, 9, 8, '2026-10-26', '09:00:00', 'senior_wellness', 'medium', 'senior citizine card', 'scheduled', '2026-10-26 22:21:16', '2026-10-26 22:21:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_module` (`module`),
  ADD KEY `idx_audit_action` (`action`);

--
-- Indexes for table `barangay_settings`
--
ALTER TABLE `barangay_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bhw_assignments`
--
ALTER TABLE `bhw_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bhw_assignments_user` (`user_id`);

--
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_otp` (`email`,`otp_code`);

--
-- Indexes for table `followup_tasks`
--
ALTER TABLE `followup_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tasks_resident` (`resident_id`),
  ADD KEY `fk_tasks_household` (`household_id`),
  ADD KEY `fk_tasks_bhw` (`bhw_user_id`);

--
-- Indexes for table `health_services`
--
ALTER TABLE `health_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_services_created_by` (`created_by`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `household_number` (`household_number`),
  ADD KEY `fk_households_created_by` (`created_by`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_status` (`status`),
  ADD KEY `idx_inventory_expiry` (`expiration_date`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movements_item` (`item_id`),
  ADD KEY `fk_movements_user` (`recorded_by`);

--
-- Indexes for table `iso_evaluations`
--
ALTER TABLE `iso_evaluations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`is_read`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_residents_household` (`household_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_requests_user` (`resident_user_id`),
  ADD KEY `fk_requests_service` (`service_id`),
  ADD KEY `fk_requests_bhw` (`assigned_bhw_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visit_logs`
--
ALTER TABLE `visit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visit_logs_schedule` (`visit_schedule_id`),
  ADD KEY `fk_visit_logs_resident` (`resident_id`),
  ADD KEY `fk_visit_logs_bhw` (`bhw_user_id`);

--
-- Indexes for table `visit_schedules`
--
ALTER TABLE `visit_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visit_schedules_resident` (`resident_id`),
  ADD KEY `fk_visit_schedules_bhw` (`bhw_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT for table `barangay_settings`
--
ALTER TABLE `barangay_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bhw_assignments`
--
ALTER TABLE `bhw_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `followup_tasks`
--
ALTER TABLE `followup_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `health_services`
--
ALTER TABLE `health_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iso_evaluations`
--
ALTER TABLE `iso_evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `visit_logs`
--
ALTER TABLE `visit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visit_schedules`
--
ALTER TABLE `visit_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bhw_assignments`
--
ALTER TABLE `bhw_assignments`
  ADD CONSTRAINT `fk_bhw_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `followup_tasks`
--
ALTER TABLE `followup_tasks`
  ADD CONSTRAINT `fk_tasks_bhw` FOREIGN KEY (`bhw_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tasks_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tasks_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `fk_households_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_movements_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_movements_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `fk_notif_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `residents`
--
ALTER TABLE `residents`
  ADD CONSTRAINT `fk_residents_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visit_logs`
--
ALTER TABLE `visit_logs`
  ADD CONSTRAINT `fk_visit_logs_bhw` FOREIGN KEY (`bhw_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_visit_logs_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_visit_logs_schedule` FOREIGN KEY (`visit_schedule_id`) REFERENCES `visit_schedules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visit_schedules`
--
ALTER TABLE `visit_schedules`
  ADD CONSTRAINT `fk_visit_schedules_bhw` FOREIGN KEY (`bhw_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_visit_schedules_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
