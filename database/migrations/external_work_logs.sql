-- External Work Logs: document off-platform client requests and related work.
-- Isolated table. Does not alter tickets, projects, or tasks.

CREATE TABLE IF NOT EXISTS `external_work_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `created_by` INT NOT NULL,
  `assigned_to` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `communication_source` ENUM(
    'Email',
    'Phone Call',
    'WhatsApp',
    'Meeting',
    'Zoom',
    'Teams',
    'Client Visit',
    'Other'
  ) NOT NULL DEFAULT 'Email',
  `requested_by` VARCHAR(255) DEFAULT NULL,
  `work_date` DATE NOT NULL,
  `estimated_hours` DECIMAL(8,2) DEFAULT NULL,
  `actual_hours` DECIMAL(8,2) DEFAULT NULL,
  `status` ENUM(
    'Pending',
    'In Progress',
    'Completed',
    'Cancelled'
  ) NOT NULL DEFAULT 'Pending',
  `client_reference` LONGTEXT DEFAULT NULL,
  `completion_notes` LONGTEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_ewl_project` (`project_id`),
  INDEX `idx_ewl_assigned` (`assigned_to`),
  INDEX `idx_ewl_created_by` (`created_by`),
  INDEX `idx_ewl_status` (`status`),
  INDEX `idx_ewl_work_date` (`work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
