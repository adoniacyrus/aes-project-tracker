-- MySQL Schema for AES Project Tracker & Management System

DROP TABLE IF EXISTS `user_activity_logs`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'developer', 'intern', 'client') NOT NULL,
  `designation` VARCHAR(100) DEFAULT NULL,
  `organization` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Password Resets Table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reset_email` (`email`),
  INDEX `idx_reset_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Activity Logs Table
CREATE TABLE IF NOT EXISTS `user_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_log_user` (`user_id`),
  INDEX `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Sample Seed Data
-- Default passwords are 'admin123' for admin and other seeded users.
-- admin123 bcrypt hash: $2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq

INSERT INTO `users` (`first_name`, `last_name`, `email`, `phone`, `password`, `role`, `designation`, `organization`, `status`)
VALUES
('System', 'Admin', 'admin@aes.com', '1234567890', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'admin', 'Administrator', 'AES', 'active'),
('John', 'Developer', 'dev@aes.com', '1234567891', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'developer', 'Senior PHP Engineer', 'AES', 'active'),
('Sarah', 'Intern', 'intern@aes.com', '1234567892', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'intern', 'Junior Web Intern', 'AES', 'active'),
('Mark', 'Client', 'client@aes.com', '1234567893', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'client', 'Project Sponsor', 'Acme Corp', 'active'),
('Inactive', 'User', 'inactive@aes.com', '1234567894', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'developer', 'Tester', 'AES', 'inactive');
