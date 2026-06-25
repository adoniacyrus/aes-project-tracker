-- MySQL Schema for AES Project Tracker & Management System

DROP TABLE IF EXISTS `user_activity_logs`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
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

INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role`, `designation`, `organization`, `status`)
VALUES
('System Admin', 'admin@aes.com', '1234567890', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'admin', 'Administrator', 'AES', 'active'),
('John Developer', 'dev@aes.com', '1234567891', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'developer', 'Senior PHP Engineer', 'AES', 'active'),
('Sarah Intern', 'intern@aes.com', '1234567892', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'intern', 'Junior Web Intern', 'AES', 'active'),
('Mark Client', 'client@aes.com', '1234567893', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'client', 'Project Sponsor', 'Acme Corp', 'active'),
('Inactive User', 'inactive@aes.com', '1234567894', '$2y$10$CxxJiyMkJDl2QgLxGfeAoOoJ6klnjN4aX9xMZwgRtLJSbm3jmSeiq', 'developer', 'Tester', 'AES', 'inactive');

-- 5. Projects Table
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_name` VARCHAR(150) NOT NULL,
  `project_code` VARCHAR(20) NOT NULL UNIQUE,
  `client_name` VARCHAR(100) DEFAULT NULL,
  `organization_name` VARCHAR(100) DEFAULT NULL,
  `project_description` TEXT DEFAULT NULL,
  `project_type` VARCHAR(50) DEFAULT NULL,
  `technology_stack` VARCHAR(255) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `expected_end_date` DATE DEFAULT NULL,
  `project_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Proposal Received', 'In Progress', 'Maintenance', 'On Hold', 'Cancelled', 'Completed') DEFAULT 'Proposal Received',
  `is_archived` TINYINT DEFAULT 0,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_project_status` (`status`),
  INDEX `idx_project_archived` (`is_archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Project Members Table
CREATE TABLE IF NOT EXISTS `project_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_project_user` (`project_id`, `user_id`),
  INDEX `idx_member_project` (`project_id`),
  INDEX `idx_member_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tickets Table
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category` ENUM('Bug Fix', 'New Feature Request', 'Enhancement Request', 'Technical Support') NOT NULL,
  `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
  `created_by` INT NOT NULL,
  `assigned_to` INT DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `estimated_cost` DECIMAL(12,2) DEFAULT NULL,
  `estimated_delivery_date` DATE DEFAULT NULL,
  `proposal_sent_at` DATETIME DEFAULT NULL,
  `payment_confirmed_at` DATETIME DEFAULT NULL,
  `is_team_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `commercial_review_requested` TINYINT(1) NOT NULL DEFAULT 0,
  `pending_admin_review` TINYINT(1) NOT NULL DEFAULT 0,
  `resolution_submitted_by` INT DEFAULT NULL,
  `resolution_submitted_at` DATETIME DEFAULT NULL,
  `resolution_comment` TEXT DEFAULT NULL,
  `latest_review_comment` TEXT DEFAULT NULL,
  `latest_review_by` INT DEFAULT NULL,
  `latest_review_at` DATETIME DEFAULT NULL,
  `status` ENUM('Initiated', 'Processing', 'Completed', 'Open', 'Awaiting Admin Approval', 'Awaiting Client Review', 'Awaiting Payment', 'Payment Confirmed', 'Approved', 'In Development', 'Resolved', 'Reopened', 'Closed', 'Rejected', 'On Hold') DEFAULT 'Open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`resolution_submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`latest_review_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_ticket_project` (`project_id`),
  INDEX `idx_ticket_status` (`status`),
  INDEX `idx_ticket_assigned` (`assigned_to`),
  INDEX `idx_ticket_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Ticket Comments Table
CREATE TABLE IF NOT EXISTS `ticket_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `channel` ENUM('team', 'client', 'admin_dev') NOT NULL DEFAULT 'team',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_comment_ticket` (`ticket_id`),
  INDEX `idx_comment_channel` (`ticket_id`, `channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket cost estimation audit log
CREATE TABLE IF NOT EXISTS `ticket_cost_estimation_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `previous_cost` DECIMAL(12,2) DEFAULT NULL,
  `new_cost` DECIMAL(12,2) DEFAULT NULL,
  `previous_delivery_date` DATE DEFAULT NULL,
  `new_delivery_date` DATE DEFAULT NULL,
  `reason` TEXT DEFAULT NULL,
  `updated_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_cost_log_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket team member assignments (multi-assign)
CREATE TABLE IF NOT EXISTS `ticket_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `assigned_by` INT NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uniq_ticket_assignment` (`ticket_id`, `user_id`),
  INDEX `idx_ticket_assignment_ticket` (`ticket_id`),
  INDEX `idx_ticket_assignment_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Ticket Attachments Table
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_attachment_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Ticket Discussions Table (Client-Admin only)
CREATE TABLE IF NOT EXISTS `ticket_discussions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_discussion_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Tasks Table
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `task_name` VARCHAR(255) NOT NULL,
  `assigned_member` INT DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed', 'Blocked') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_member`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_task_ticket` (`ticket_id`),
  INDEX `idx_task_assigned` (`assigned_member`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Ticket Internal Discussions Table (Admin-Team only)
CREATE TABLE IF NOT EXISTS `ticket_internal_discussions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_internal_discussion_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Team Chat Attachments (linked to ticket comments / team chat messages)
CREATE TABLE IF NOT EXISTS `team_chat_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `comment_id` INT NOT NULL,
  `uploaded_by` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_size` INT NOT NULL DEFAULT 0,
  `file_type` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`comment_id`) REFERENCES `ticket_comments` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_team_chat_attachment_comment` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

