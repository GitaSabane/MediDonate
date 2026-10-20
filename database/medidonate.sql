-- phpMyAdmin SQL Dump
-- Database: medidonate

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `medidonate` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medidonate`;

-- --------------------------------------------------------

-- Table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('donor','organization','verifier','admin') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `organizations`
CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `org_name` varchar(150) NOT NULL,
  `org_type` enum('NGO','Charity','Hospital','Clinic','Trust','Other') NOT NULL,
  `registration_number` varchar(100) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `verification_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','suspended') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `medicines`
CREATE TABLE `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('Tablet','Capsule','Syrup','Cream','Drops','Injection','Other') NOT NULL,
  `strength` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL CHECK (quantity >= 0),
  `unit` enum('Strip','Bottle','Pack','Vial','Tube','Other') NOT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(150) DEFAULT NULL,
  `packaging_condition` text DEFAULT NULL,
  `storage_condition` text DEFAULT NULL,
  `images` text DEFAULT NULL, -- Comma-separated filenames
  `verification_status` enum('pending','verified','rejected','more_info') NOT NULL DEFAULT 'pending',
  `verification_remarks` text DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expiry_status` enum('valid','expiring_soon','expired') GENERATED ALWAYS AS (
    CASE
      WHEN expiry_date < CURDATE() THEN 'expired'
      WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
      ELSE 'valid'
    END
  ) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `donor_id` (`donor_id`),
  CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `donations`
-- This table stores the donation record, linking donor and medicine submission. 
-- It is essentially the same as medicines, but we separate for clarity. 
-- Actually, we can treat medicines as donation items. 
-- We'll keep the medicines table as the main donation records.

-- --------------------------------------------------------

-- Table `verifications`
CREATE TABLE `verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) NOT NULL,
  `verifier_id` int(11) NOT NULL,
  `status` enum('verified','rejected','more_info') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `verifier_id` (`verifier_id`),
  CONSTRAINT `verifications_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verifications_ibfk_2` FOREIGN KEY (`verifier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `medicine_requests`
CREATE TABLE `medicine_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `requested_quantity` int(11) NOT NULL CHECK (requested_quantity > 0),
  `purpose` text DEFAULT NULL,
  `urgency` enum('Normal','Urgent','Very Urgent') DEFAULT 'Normal',
  `additional_info` text DEFAULT NULL,
  `status` enum('pending','approved','partially_approved','rejected','cancelled') DEFAULT 'pending',
  `approved_quantity` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL, -- verifier or admin
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `medicine_requests_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medicine_requests_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medicine_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `inventory`
-- This table tracks available/reserved/distributed quantities per medicine.
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) NOT NULL,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `distributed_quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_id` (`medicine_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `distributions`
CREATE TABLE `distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('awaiting_distribution','picked_up','delivered','received','completed') DEFAULT 'awaiting_distribution',
  `distribution_date` datetime DEFAULT NULL,
  `distribution_method` varchar(100) DEFAULT NULL,
  `confirmation_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `organization_id` (`organization_id`),
  CONSTRAINT `distributions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `medicine_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `distributions_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `distributions_ibfk_3` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `audit_logs`
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `password_resets`
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table `system_settings`
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'MediDonate'),
('site_email', 'umdsystem01@gmail.com'),
('expiry_warning_days', '30'),
('default_verifier_id', '');

-- --------------------------------------------------------

-- Sample Data

-- Insert Admin (password: Admin@123)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `address`, `city`) VALUES
('Admin User', 'admin@medidonate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'Admin Office', 'Metro');

-- Insert Verifier (password: Verifier@123)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `address`, `city`) VALUES
('Verifier User', 'verifier@medidonate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'verifier', 'active', 'Verification Center', 'Metro');

-- Insert Donor (password: Donor@123)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `address`, `city`) VALUES
('John Donor', 'donor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'active', '123 Donor St', 'Cityville');

-- Insert Organization (pending approval) (password: Org@123)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `address`, `city`) VALUES
('Org Manager', 'org@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organization', 'active', 'Org Address', 'Townsville');
INSERT INTO `organizations` (`user_id`, `org_name`, `org_type`, `registration_number`, `contact_person`, `address`, `city`, `verification_doc`, `status`) VALUES
((SELECT id FROM users WHERE email='org@example.com'), 'Health Aid NGO', 'NGO', 'NGO123456', 'Org Manager', 'Org Address', 'Townsville', 'org_doc.pdf', 'approved');

-- Insert some sample medicines (donated by donor)
INSERT INTO `medicines` (`donor_id`, `name`, `type`, `strength`, `quantity`, `unit`, `manufacturing_date`, `expiry_date`, `batch_number`, `manufacturer`, `packaging_condition`, `storage_condition`, `images`, `verification_status`, `verification_remarks`) VALUES
((SELECT id FROM users WHERE email='donor@example.com'), 'Paracetamol 500mg', 'Tablet', '500mg', 100, 'Strip', '2025-01-01', '2026-06-30', 'BATCH001', 'PharmaCo', 'Sealed', 'Cool dry place', 'paracetamol.jpg', 'verified', 'Approved by verifier'),
((SELECT id FROM users WHERE email='donor@example.com'), 'Amoxicillin 250mg', 'Capsule', '250mg', 50, 'Strip', '2024-11-01', '2025-08-15', 'BATCH002', 'MediPharm', 'Good', 'Room temp', 'amoxicillin.jpg', 'pending', NULL);

-- Insert inventory for verified medicine
INSERT INTO `inventory` (`medicine_id`, `total_quantity`, `available_quantity`, `reserved_quantity`, `distributed_quantity`)
SELECT id, quantity, quantity, 0, 0 FROM medicines WHERE verification_status = 'verified';

-- Insert a sample request (for the approved organization)
INSERT INTO `medicine_requests` (`organization_id`, `medicine_id`, `requested_quantity`, `purpose`, `urgency`, `status`) VALUES
((SELECT id FROM organizations WHERE user_id = (SELECT id FROM users WHERE email='org@example.com')), (SELECT id FROM medicines WHERE name='Paracetamol 500mg'), 20, 'Free health camp', 'Urgent', 'pending');

-- Insert notifications for admin
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`) VALUES
((SELECT id FROM users WHERE email='admin@medidonate.com'), 'info', 'New Donation', 'A new medicine donation has been submitted.', 'verifier/pending-medicines.php', 0);

COMMIT;