-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 05:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_mao_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `assistance_records`
--

CREATE TABLE `assistance_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `assistance_no` varchar(20) NOT NULL,
  `program_beneficiary_id` int(10) UNSIGNED NOT NULL,
  `assistance_type_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_value` decimal(12,2) DEFAULT NULL,
  `total_value` decimal(14,2) DEFAULT NULL,
  `date_distributed` date NOT NULL,
  `ack_receipt_no` varchar(40) DEFAULT NULL,
  `released_by` varchar(120) DEFAULT NULL,
  `duplicate_flag` tinyint(1) NOT NULL DEFAULT 0,
  `override_reason` varchar(255) DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assistance_types`
--

CREATE TABLE `assistance_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `category` enum('seeds','fertilizer','machinery','livestock','boat','fishing_gear','cash','insurance','livelihood','training','other') NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `duplicate_window_months` smallint(5) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assistance_types`
--

INSERT INTO `assistance_types` (`id`, `name`, `category`, `unit`, `duplicate_window_months`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Certified Rice Seeds', 'seeds', 'bag', 6, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(2, 'Vegetable Seeds', 'seeds', 'pack', 6, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(3, 'Complete Fertilizer', 'fertilizer', 'bag', 6, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(4, 'Urea Fertilizer', 'fertilizer', 'bag', 6, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(5, 'Farm Machinery', 'machinery', 'unit', NULL, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(6, 'Livestock (Swine/Goat)', 'livestock', 'head', 12, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(7, 'Fishing Boat', 'boat', 'unit', NULL, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(8, 'Fishing Gear', 'fishing_gear', 'set', 12, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(9, 'Cash Assistance', 'cash', 'peso', 12, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(10, 'Crop Insurance', 'insurance', 'policy', 12, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25'),
(11, 'Livelihood Project', 'livelihood', 'project', 12, 1, '2026-11-22 15:21:25', '2026-11-22 15:21:25');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` enum('create','update','archive','override') NOT NULL,
  `table_name` varchar(60) NOT NULL,
  `record_id` int(10) UNSIGNED NOT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `note`, `created_at`) VALUES
(1, 1, 'create', 'profiles', 2, NULL, '{\"last_name\":\"Sanquina\",\"first_name\":\"Lindsay\",\"middle_name\":\"Diaz\",\"suffix\":null,\"sex\":\"Female\",\"birthdate\":\"1980-02-22\",\"contact_no\":\"09212444915\",\"barangay_id\":\"10\",\"address_detail\":\"Prk. N\",\"status\":\"active\",\"name_key\":\"sanquinalindsay\"}', NULL, '2026-11-21 10:50:41'),
(2, 1, 'update', 'profiles', 2, '{\"first_name\":\"Lindsay\"}', '{\"first_name\":\"Lindsey\"}', NULL, '2026-11-21 12:40:33'),
(3, 1, 'update', 'profiles', 2, '{\"first_name\":\"Lindsey\"}', '{\"first_name\":\"Lindsay\"}', NULL, '2026-11-21 19:47:42'),
(4, 1, 'update', 'program_beneficiaries', 4, '{\"status\":\"approved\"}', '{\"status\":\"identified\"}', NULL, '2026-11-22 15:23:48'),
(5, 1, 'update', 'program_beneficiaries', 4, '{\"status\":\"identified\"}', '{\"status\":\"validated\"}', NULL, '2026-11-22 15:27:07'),
(6, 1, 'archive', 'program_beneficiaries', 4, '{\"status\":\"validated\"}', '{\"status\":\"removed\"}', NULL, '2026-11-22 15:30:37'),
(7, 1, 'archive', 'program_beneficiaries', 2, '{\"status\":\"approved\"}', '{\"status\":\"removed\"}', NULL, '2026-11-22 15:37:52'),
(8, 1, 'archive', 'program_beneficiaries', 3, '{\"status\":\"approved\"}', '{\"status\":\"removed\"}', NULL, '2026-11-22 15:39:48'),
(9, 1, 'create', 'program_beneficiaries', 2, NULL, '{\"program_id\":1,\"profile_id\":3}', NULL, '2026-11-22 15:40:01'),
(10, 1, 'create', 'program_beneficiaries', 3, NULL, '{\"program_id\":1,\"profile_id\":4}', NULL, '2026-11-22 15:40:08'),
(11, 1, 'create', 'assistance_records', 2, NULL, '{\"program_beneficiary_id\":2,\"assistance_type_id\":1,\"quantity\":\"10\",\"unit\":\"bag\",\"unit_value\":null,\"total_value\":null,\"date_distributed\":\"2026-11-22\",\"ack_receipt_no\":null,\"released_by\":\"MAO Agriculturist\",\"duplicate_flag\":0,\"override_reason\":null,\"recorded_by\":1,\"assistance_no\":\"AST-2026-00002\"}', NULL, '2026-11-22 15:43:14'),
(12, 1, 'update', 'programs', 2, '{\"allocation_amount\":null}', '{\"allocation_amount\":\"10000\"}', NULL, '2026-11-22 16:25:59'),
(13, 1, 'update', 'programs', 2, '{\"allocation_amount\":\"10000.00\"}', '{\"allocation_amount\":\"1000.00\"}', NULL, '2026-11-22 18:42:05'),
(14, 1, 'archive', 'program_beneficiaries', 3, '{\"status\":\"identified\"}', '{\"status\":\"removed\"}', NULL, '2026-11-22 18:47:21'),
(15, 1, 'archive', 'profiles', 5, NULL, '{\"name\":\"Demo, User\"}', 'Profile deleted by user', '2026-11-23 00:31:02');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `psgc_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`id`, `name`, `psgc_code`) VALUES
(1, 'Barangay Zone 1', NULL),
(2, 'Barangay Zone 1-A', NULL),
(3, 'Barangay Zone 2', NULL),
(4, 'Barangay Zone 3', NULL),
(5, 'Barangay Zone 4', NULL),
(6, 'Barangay Zone 4-A', NULL),
(7, 'Barangay Zone 5', NULL),
(8, 'Barangay Zone 6', NULL),
(9, 'Barangay Zone 7', NULL),
(10, 'Canjusa', NULL),
(11, 'Crossing Pulupandan', NULL),
(12, 'Culo', NULL),
(13, 'Mabini', NULL),
(14, 'Pag-ayon', NULL),
(15, 'Palaka Norte', NULL),
(16, 'Palaka Sur', NULL),
(17, 'Patic', NULL),
(18, 'Tapong', NULL),
(19, 'Ubay', NULL),
(20, 'Utod', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `farming_profiles`
--

CREATE TABLE `farming_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `rsbsa_no` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive','transferred','deceased') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farming_profiles`
--

INSERT INTO `farming_profiles` (`id`, `rsbsa_no`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, NULL, 'active', NULL, NULL, NULL, NULL),
(4, '06-45-00-TEST01', 'active', NULL, NULL, NULL, NULL),
(5, 'FRM-2026-00003', 'active', NULL, NULL, NULL, '2026-11-23 00:31:02');

-- --------------------------------------------------------

--
-- Table structure for table `fishery_profiles`
--

CREATE TABLE `fishery_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `rsbsa_no` varchar(30) DEFAULT NULL,
  `fishr_no` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive','transferred','deceased') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fishery_profiles`
--

INSERT INTO `fishery_profiles` (`id`, `rsbsa_no`, `fishr_no`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, '06-45-00-TEST02', 'FR-TEST001', 'active', NULL, NULL, NULL, NULL),
(5, 'FSH-2026-00003', 'FIR-2026-00003', 'active', NULL, NULL, NULL, '2026-11-23 00:31:02');

-- --------------------------------------------------------

--
-- Table structure for table `id_sequences`
--

CREATE TABLE `id_sequences` (
  `prefix` varchar(10) NOT NULL,
  `year` smallint(6) NOT NULL,
  `last_number` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `id_sequences`
--

INSERT INTO `id_sequences` (`prefix`, `year`, `last_number`) VALUES
('AST', 2026, 2),
('FIR', 2026, 3),
('FRM', 2026, 3),
('FSH', 2026, 3),
('PRG', 2026, 2);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `birthdate` date DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `barangay_id` int(10) UNSIGNED NOT NULL,
  `address_detail` varchar(150) DEFAULT NULL,
  `status` enum('active','inactive','transferred','deceased') NOT NULL DEFAULT 'active',
  `name_key` varchar(140) NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `last_name`, `first_name`, `middle_name`, `suffix`, `sex`, `birthdate`, `contact_no`, `barangay_id`, `address_detail`, `status`, `name_key`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Sanquina', 'Lindsay', 'Diaz', NULL, 'Female', '1980-02-22', '09212444915', 10, 'Prk. N', 'active', 'sanquinalindsay', 1, '2026-11-21 10:50:41', '2026-11-21 19:47:42', NULL),
(3, 'Test', 'Profile', NULL, NULL, 'Male', '1980-01-01', NULL, 1, NULL, 'active', 'testprofile', NULL, '2026-11-21 10:52:06', '2026-11-21 10:52:06', NULL),
(4, 'Test', 'Profile', NULL, NULL, 'Male', '1980-01-01', NULL, 1, NULL, 'active', 'testprofile', NULL, '2026-11-21 10:52:51', '2026-11-21 10:52:51', NULL),
(5, 'Demo', 'User', NULL, NULL, 'Male', '1990-01-15', NULL, 1, NULL, 'active', 'demouser', NULL, '2026-11-21 10:53:49', '2026-11-21 10:53:49', '2026-11-23 00:31:02');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(10) UNSIGNED NOT NULL,
  `program_no` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `source_agency` varchar(60) NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `allocation_amount` decimal(14,2) DEFAULT NULL,
  `target_beneficiaries` int(10) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','ongoing','completed') NOT NULL DEFAULT 'planned',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_no`, `name`, `description`, `objectives`, `source_agency`, `year`, `allocation_amount`, `target_beneficiaries`, `start_date`, `end_date`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'PRG-2026-001', 'Rice Seed Distribution 2026', 'Certified rice seeds for registered rice farmers.', NULL, 'Department of Agriculture', 2026, 150000.00, 20, '2026-03-01', '2026-12-31', 'ongoing', NULL, '2026-11-22 15:22:25', '2026-11-22 15:22:25', NULL),
(2, 'PRG-2026-002', 'Fertilizer Subsidy 2026', 'Subsidized fertilizer for the wet season.', NULL, 'LGU', 2026, 1000.00, 10, '2026-06-01', '2026-09-30', 'planned', NULL, '2026-11-22 15:22:25', '2026-11-22 18:42:05', NULL),
(3, 'TST-20261122', 'TEST Allocation Program', NULL, NULL, 'Other', 2026, NULL, NULL, NULL, NULL, 'planned', NULL, '2026-11-22 16:13:02', '2026-11-22 16:13:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `program_beneficiaries`
--

CREATE TABLE `program_beneficiaries` (
  `id` int(10) UNSIGNED NOT NULL,
  `program_id` int(10) UNSIGNED NOT NULL,
  `profile_id` int(10) UNSIGNED NOT NULL,
  `status` enum('identified','validated','approved','served','removed') NOT NULL DEFAULT 'identified',
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_beneficiaries`
--

INSERT INTO `program_beneficiaries` (`id`, `program_id`, `profile_id`, `status`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'served', NULL, NULL, '2026-11-22 15:22:25', '2026-11-22 15:22:25'),
(2, 1, 3, 'served', NULL, NULL, '2026-11-22 15:22:25', '2026-11-22 15:43:14'),
(3, 1, 4, 'removed', NULL, NULL, '2026-11-22 15:22:25', '2026-11-22 18:47:21'),
(4, 1, 5, 'removed', NULL, NULL, '2026-11-22 15:22:25', '2026-11-22 15:30:37');

-- --------------------------------------------------------

--
-- Table structure for table `program_unit_allocations`
--

CREATE TABLE `program_unit_allocations` (
  `id` int(11) NOT NULL,
  `program_id` int(10) UNSIGNED NOT NULL,
  `unit` varchar(100) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL,
  `unit_value` decimal(14,2) DEFAULT NULL,
  `total_allocated_value` decimal(14,2) GENERATED ALWAYS AS (`allocated_amount` * coalesce(`unit_value`,0)) STORED,
  `distributed_amount` decimal(14,2) DEFAULT 0.00,
  `distributed_value` decimal(14,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_unit_allocations`
--

INSERT INTO `program_unit_allocations` (`id`, `program_id`, `unit`, `allocated_amount`, `unit_value`, `distributed_amount`, `distributed_value`, `created_at`, `updated_at`) VALUES
(3, 2, 'bag', 10.00, 100.00, 0.00, 0.00, '2026-11-22 18:42:05', '2026-11-22 18:42:05'),
(4, 3, 'bag', 100.00, 150.00, 0.00, 0.00, '2026-11-22 18:43:41', '2026-11-22 18:43:41'),
(5, 3, 'peso', 50000.00, 1.00, 0.00, 0.00, '2026-11-22 18:43:41', '2026-11-22 18:43:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('technician','agriculturist') NOT NULL DEFAULT 'technician',
  `sector` enum('crops','livestock_poultry','fisheries','all') NOT NULL DEFAULT 'all',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `password_hash`, `role`, `sector`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'MAO Agriculturist', 'admin', '$2y$10$Jc.x1CmENNMnkBSGUSq3qOcIC6LVMYyJRACwi3PFpuqdqivmuUjJO', 'agriculturist', 'all', 1, '2026-11-22 23:46:06', '2026-11-19 02:35:39', '2026-11-19 02:35:39'),
(2, 'Crop Technician', 'tech1', '$2y$10$0Hardx7wGYSPe6N.uqiQAOrRzxau7Ctt9v7QuDY6LPy2o5aaWi2h2', 'technician', 'crops', 1, '2026-11-22 23:38:08', '2026-11-19 02:36:32', '2026-11-19 02:36:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assistance_records`
--
ALTER TABLE `assistance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `assistance_no` (`assistance_no`),
  ADD KEY `idx_ar_beneficiary_type` (`program_beneficiary_id`,`assistance_type_id`,`date_distributed`),
  ADD KEY `idx_ar_date` (`date_distributed`),
  ADD KEY `idx_ar_flag` (`duplicate_flag`),
  ADD KEY `fk_ar_type` (`assistance_type_id`),
  ADD KEY `fk_ar_recorded_by` (`recorded_by`);

--
-- Indexes for table `assistance_types`
--
ALTER TABLE `assistance_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_record` (`table_name`,`record_id`),
  ADD KEY `idx_audit_user` (`user_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `farming_profiles`
--
ALTER TABLE `farming_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rsbsa_no` (`rsbsa_no`),
  ADD KEY `idx_farming_status` (`status`),
  ADD KEY `fk_farming_profiles_created_by` (`created_by`);

--
-- Indexes for table `fishery_profiles`
--
ALTER TABLE `fishery_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rsbsa_no` (`rsbsa_no`),
  ADD UNIQUE KEY `fishr_no` (`fishr_no`),
  ADD KEY `idx_fishery_status` (`status`),
  ADD KEY `fk_fishery_profiles_created_by` (`created_by`);

--
-- Indexes for table `id_sequences`
--
ALTER TABLE `id_sequences`
  ADD PRIMARY KEY (`prefix`,`year`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profiles_name` (`last_name`,`first_name`,`birthdate`),
  ADD KEY `idx_profiles_name_key` (`name_key`),
  ADD KEY `idx_profiles_barangay` (`barangay_id`),
  ADD KEY `idx_profiles_status` (`status`),
  ADD KEY `fk_profiles_created_by` (`created_by`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_no` (`program_no`),
  ADD KEY `idx_programs_status` (`status`),
  ADD KEY `idx_programs_year` (`year`),
  ADD KEY `fk_programs_created_by` (`created_by`);

--
-- Indexes for table `program_beneficiaries`
--
ALTER TABLE `program_beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_program_profile` (`program_id`,`profile_id`),
  ADD KEY `idx_pb_profile` (`profile_id`),
  ADD KEY `fk_pb_created_by` (`created_by`);

--
-- Indexes for table `program_unit_allocations`
--
ALTER TABLE `program_unit_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_program_unit` (`program_id`,`unit`),
  ADD KEY `idx_program_id` (`program_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assistance_records`
--
ALTER TABLE `assistance_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assistance_types`
--
ALTER TABLE `assistance_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `program_beneficiaries`
--
ALTER TABLE `program_beneficiaries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `program_unit_allocations`
--
ALTER TABLE `program_unit_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assistance_records`
--
ALTER TABLE `assistance_records`
  ADD CONSTRAINT `fk_ar_beneficiary` FOREIGN KEY (`program_beneficiary_id`) REFERENCES `program_beneficiaries` (`id`),
  ADD CONSTRAINT `fk_ar_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ar_type` FOREIGN KEY (`assistance_type_id`) REFERENCES `assistance_types` (`id`);

--
-- Constraints for table `farming_profiles`
--
ALTER TABLE `farming_profiles`
  ADD CONSTRAINT `fk_farming_profiles_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_farming_profiles_profile` FOREIGN KEY (`id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fishery_profiles`
--
ALTER TABLE `fishery_profiles`
  ADD CONSTRAINT `fk_fishery_profiles_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_fishery_profiles_profile` FOREIGN KEY (`id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `fk_profiles_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  ADD CONSTRAINT `fk_profiles_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `fk_programs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `program_beneficiaries`
--
ALTER TABLE `program_beneficiaries`
  ADD CONSTRAINT `fk_pb_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_pb_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`),
  ADD CONSTRAINT `fk_pb_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`);

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` tinyint(1) unsigned NOT NULL,
  `office_name` varchar(120) NOT NULL,
  `municipality` varchar(120) NOT NULL,
  `report_header` varchar(200) NOT NULL,
  `reporting_start` date NOT NULL,
  `reporting_end` date NOT NULL,
  `wet_season_label` varchar(60) NOT NULL DEFAULT 'Wet season',
  `dry_season_label` varchar(60) NOT NULL DEFAULT 'Dry season',
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `system_settings`
  (`id`, `office_name`, `municipality`, `report_header`, `reporting_start`, `reporting_end`, `wet_season_label`, `dry_season_label`, `updated_at`)
VALUES
  (1, 'Municipal Agriculture Office', 'Pulupandan', 'Pulupandan, Negros Occidental',
   CONCAT(YEAR(CURRENT_DATE), '-01-01'), CONCAT(YEAR(CURRENT_DATE), '-12-31'),
   'Wet season', 'Dry season', CURRENT_TIMESTAMP);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
