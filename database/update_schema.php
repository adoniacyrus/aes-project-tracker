<?php

require_once __DIR__ . '/../config/database.php';

echo "Running safe DB update for project, team, ticket, and task tables...\n";

$database = new Database();
$conn = $database->connect();

// Disable foreign key checks while creating tables
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");

$queries = [
    "CREATE TABLE IF NOT EXISTS `projects` (
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
      `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
      `status` ENUM('Proposal Received', 'In Progress', 'Maintenance', 'On Hold', 'Cancelled', 'Completed') DEFAULT 'Proposal Received',
      `is_archived` TINYINT DEFAULT 0,
      `created_by` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
      INDEX `idx_project_status` (`status`),
      INDEX `idx_project_archived` (`is_archived`),
      INDEX `idx_project_priority` (`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `project_members` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `project_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      UNIQUE KEY `uq_project_user` (`project_id`, `user_id`),
      INDEX `idx_member_project` (`project_id`),
      INDEX `idx_member_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `tickets` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `project_id` INT NOT NULL,
      `title` VARCHAR(200) NOT NULL,
      `description` TEXT NOT NULL,
      `category` ENUM('Bug Fix', 'New Feature Request', 'Enhancement Request', 'Technical Support') NOT NULL,
      `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
      `created_by` INT NOT NULL,
      `assigned_to` INT DEFAULT NULL,
      `due_date` DATE DEFAULT NULL,
      `status` ENUM('Open', 'Awaiting Admin Approval', 'Awaiting Payment', 'Approved', 'In Development', 'Resolved', 'Reopened', 'Closed', 'Rejected', 'On Hold') DEFAULT 'Open',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
      FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
      INDEX `idx_ticket_project` (`project_id`),
      INDEX `idx_ticket_status` (`status`),
      INDEX `idx_ticket_assigned` (`assigned_to`),
      INDEX `idx_ticket_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `ticket_comments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ticket_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `comment` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      INDEX `idx_comment_ticket` (`ticket_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `ticket_attachments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ticket_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `file_name` VARCHAR(255) NOT NULL,
      `file_path` VARCHAR(255) NOT NULL,
      `file_size` INT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      INDEX `idx_attachment_ticket` (`ticket_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS `tasks` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    if ($conn->query($query)) {
        $successCount++;
    } else {
        echo "Error executing query: " . $conn->error . "\n";
        $errorCount++;
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\nUpdate Finished. Successfully executed: $successCount, Errors: $errorCount\n";
if ($errorCount > 0) {
    exit(1);
} else {
    exit(0);
}
