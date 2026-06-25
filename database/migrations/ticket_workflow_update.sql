-- Ticket workflow & commercial features migration
-- Run via: php database/update_schema.php

ALTER TABLE `tickets`
  ADD COLUMN IF NOT EXISTS `estimated_cost` DECIMAL(12,2) DEFAULT NULL AFTER `due_date`,
  ADD COLUMN IF NOT EXISTS `estimated_delivery_date` DATE DEFAULT NULL AFTER `estimated_cost`,
  ADD COLUMN IF NOT EXISTS `proposal_sent_at` DATETIME DEFAULT NULL AFTER `estimated_delivery_date`,
  ADD COLUMN IF NOT EXISTS `payment_confirmed_at` DATETIME DEFAULT NULL AFTER `proposal_sent_at`,
  ADD COLUMN IF NOT EXISTS `is_team_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `payment_confirmed_at`,
  ADD COLUMN IF NOT EXISTS `commercial_review_requested` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_team_visible`;

-- Extend status enum for commercial workflow
ALTER TABLE `tickets`
  MODIFY COLUMN `status` ENUM(
    'Open',
    'Awaiting Admin Approval',
    'Awaiting Client Review',
    'Awaiting Payment',
    'Payment Confirmed',
    'In Development',
    'Resolved',
    'Reopened',
    'Closed',
    'Rejected',
    'On Hold'
  ) NOT NULL DEFAULT 'Open';

ALTER TABLE `ticket_attachments`
  ADD COLUMN IF NOT EXISTS `mime_type` VARCHAR(100) DEFAULT NULL AFTER `file_size`;

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
