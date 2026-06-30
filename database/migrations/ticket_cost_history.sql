-- Ticket cost revision audit history for financial reporting
CREATE TABLE IF NOT EXISTS `ticket_cost_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `project_id` INT NOT NULL,
  `old_cost` DECIMAL(12,2) DEFAULT NULL,
  `new_cost` DECIMAL(12,2) NOT NULL,
  `difference` DECIMAL(12,2) NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `changed_by` INT NOT NULL,
  `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `revision_number` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_cost_history_project` (`project_id`),
  INDEX `idx_cost_history_ticket` (`ticket_id`),
  INDEX `idx_cost_history_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
