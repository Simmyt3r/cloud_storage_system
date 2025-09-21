-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2025 at 01:02 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cloud_storage`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `organization_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `folder_id`, `organization_id`, `name`, `file_path`, `file_size`, `mime_type`, `uploaded_by`, `uploaded_at`) VALUES
(4, NULL, 1, 'SWE 422- Software Engineering Professional Practice Note 1 21-08-2025.docx', 'C:\\xampp\\htdocs\\cloud_storage_system-1\\includes/../uploads/1/68cefddbcae011.86165041.docx', 1624578, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3, '2025-09-20 19:17:47'),
(5, 5, 1, 'EMBEDDED SYSTEMS.docx', 'C:\\xampp\\htdocs\\cloud_storage_system-1\\includes/../uploads/1/68ceff1643e4a7.19698811.docx', 33962, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 3, '2025-09-20 19:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `file_download_logs`
--

CREATE TABLE `file_download_logs` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `downloaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_download_logs`
--

INSERT INTO `file_download_logs` (`id`, `file_id`, `user_id`, `downloaded_at`, `ip_address`) VALUES
(3, 4, 3, '2025-09-20 19:17:57', '::1'),
(4, 5, 3, '2025-09-20 19:27:32', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `parent_folder_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `organization_id`, `parent_folder_id`, `name`, `description`, `created_by`, `created_at`, `updated_at`, `password_hash`) VALUES
(1, 1, NULL, 'HOD', '', 2, '2025-09-19 00:28:31', '2025-09-19 07:28:15', '$2y$10$g5IozfH8fAJ/7HTf/sKY0.7X9pYcFHAS7duAEwf653NQSSuqLul/K'),
(2, 1, 1, 'sss', '', 2, '2025-09-19 00:28:56', '2025-09-19 00:28:56', '$2y$10$jaTak6e62pQk69RpYKRwGORd6UKkp7Pw.MvtDbuheDPusQbmnjVM2'),
(3, 1, NULL, 'NAUB', 'NAUB DIR', 3, '2025-09-20 18:29:39', '2025-09-20 18:29:39', '$2y$10$/9OpaxX3fjvR6ibvKt0Xv.3kuOuZ1y0IbyzBtdLL8bJM04J2dnvLi'),
(4, 1, NULL, 'hello0', '', 3, '2025-09-20 18:48:30', '2025-09-20 19:14:37', '$2y$10$9.pF5b/bVHyvGHSiA0vD4e7NZSulRWJczHzf2B05SUJNsfn2g5ad2'),
(5, 1, 4, 'Locked', '', 3, '2025-09-20 19:22:41', '2025-09-20 19:22:41', '$2y$10$V.VA146.c6BPsD7ZGRNvN.5xVcDHmqUjwstq3LVqSS1Hlr.ode/32');

-- --------------------------------------------------------

--
-- Table structure for table `folder_access_logs`
--

CREATE TABLE `folder_access_logs` (
  `id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folder_access_logs`
--

INSERT INTO `folder_access_logs` (`id`, `folder_id`, `user_id`, `accessed_at`, `ip_address`) VALUES
(1, 1, 2, '2025-09-19 00:28:50', '::1'),
(2, 3, 3, '2025-09-20 18:30:01', '::1'),
(3, 3, 3, '2025-09-20 18:30:29', '::1'),
(4, 3, 3, '2025-09-20 18:30:41', '::1'),
(5, 3, 3, '2025-09-20 18:30:49', '::1'),
(6, 3, 3, '2025-09-20 18:31:28', '::1'),
(7, 3, 3, '2025-09-20 18:31:34', '::1'),
(8, 4, 3, '2025-09-20 18:48:44', '::1'),
(9, 4, 3, '2025-09-20 18:52:13', '::1'),
(10, 4, 3, '2025-09-20 18:54:30', '::1'),
(11, 4, 3, '2025-09-20 18:56:21', '::1'),
(12, 4, 3, '2025-09-20 19:04:04', '::1'),
(13, 4, 3, '2025-09-20 19:08:48', '::1'),
(14, 4, 3, '2025-09-20 19:14:37', '::1'),
(15, 4, 3, '2025-09-20 19:14:50', '::1'),
(16, 4, 3, '2025-09-20 19:22:24', '::1'),
(17, 4, 3, '2025-09-20 19:22:46', '::1'),
(18, 5, 3, '2025-09-20 19:22:51', '::1'),
(19, 4, 3, '2025-09-20 19:23:04', '::1'),
(20, 5, 3, '2025-09-20 19:23:05', '::1'),
(21, 4, 3, '2025-09-20 19:23:45', '::1'),
(22, 5, 3, '2025-09-20 19:23:46', '::1'),
(23, 4, 3, '2025-09-20 19:24:12', '::1'),
(24, 5, 3, '2025-09-20 19:24:14', '::1'),
(25, 5, 3, '2025-09-20 19:27:18', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `description`, `created_at`, `approved`, `approved_by`, `approved_at`, `requested_by`) VALUES
(1, 'System Admin', 'System administration organization', '2024-07-22 13:21:40', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) NOT NULL,
  `permission_level` enum('read','write','admin') NOT NULL,
  `granted_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('user','admin','super_admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `organization_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`, `last_login`, `is_active`, `password_reset_token`, `password_reset_expires`) VALUES
(1, 1, 'superadmin', 'superadmin@example.com', '$2y$10$wODe5RkeAG0s0b2vj.9WbuGv3.gI23U24.y1yI.48PoH836a2vVvS', 'Super', 'Admin', 'super_admin', '2024-07-22 13:21:40', NULL, 1, NULL, NULL),
(2, 1, 'sss', 'sss@gmail.com', '$2y$10$7iNwOrA/Ys9qt25pBpy4weBJ04p9mQtm6JodcpAtNLXOyRBLmmbR.', 's', 's', 'admin', '2025-09-19 00:26:41', '2025-09-19 00:31:08', 1, NULL, NULL),
(3, 1, 'Simmyt3r', 'simmyt3r@gmail.com', '$2y$10$Wyyk0ROrhnGRUQzcPTsZYu8U8HcpzCSl9ApUJUY81v9FRLI1ptGLe', 'Sarverun', 'Simeon Tertese', 'user', '2025-09-19 00:31:01', '2025-09-20 18:32:48', 1, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder_id` (`folder_id`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `file_download_logs`
--
ALTER TABLE `file_download_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `parent_folder_id` (`parent_folder_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `folder_access_logs`
--
ALTER TABLE `folder_access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder_id` (`folder_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `fk_requested_by` (`requested_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_folder_id` (`user_id`,`folder_id`),
  ADD KEY `folder_id` (`folder_id`),
  ADD KEY `granted_by` (`granted_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `organization_id` (`organization_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `file_download_logs`
--
ALTER TABLE `file_download_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `folder_access_logs`
--
ALTER TABLE `folder_access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_download_logs`
--
ALTER TABLE `file_download_logs`
  ADD CONSTRAINT `file_download_logs_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_download_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folders_ibfk_2` FOREIGN KEY (`parent_folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `folder_access_logs`
--
ALTER TABLE `folder_access_logs`
  ADD CONSTRAINT `folder_access_logs_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folder_access_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organizations`
--
ALTER TABLE `organizations`
  ADD CONSTRAINT `fk_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permissions_ibfk_2` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permissions_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
