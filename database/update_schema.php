<?php

require_once __DIR__ . '/../config/database.php';

echo "Running ticket workflow schema update...\n";

$database = new Database();
$conn = $database->connect();

function columnExists($conn, $table, $column)
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function tableExists($conn, $table)
{
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

$ticketColumns = [
    'estimated_cost' => "ADD COLUMN `estimated_cost` DECIMAL(12,2) DEFAULT NULL AFTER `due_date`",
    'estimated_delivery_date' => "ADD COLUMN `estimated_delivery_date` DATE DEFAULT NULL AFTER `estimated_cost`",
    'proposal_sent_at' => "ADD COLUMN `proposal_sent_at` DATETIME DEFAULT NULL AFTER `estimated_delivery_date`",
    'payment_confirmed_at' => "ADD COLUMN `payment_confirmed_at` DATETIME DEFAULT NULL AFTER `proposal_sent_at`",
    'is_team_visible' => "ADD COLUMN `is_team_visible` TINYINT(1) NOT NULL DEFAULT 1 AFTER `payment_confirmed_at`",
    'commercial_review_requested' => "ADD COLUMN `commercial_review_requested` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_team_visible`",
];

foreach ($ticketColumns as $column => $alter) {
    if (!columnExists($conn, 'tickets', $column)) {
        if ($conn->query("ALTER TABLE `tickets` $alter")) {
            echo "Added tickets.$column\n";
        } else {
            echo "Error adding tickets.$column: " . $conn->error . "\n";
        }
    }
}

if (!columnExists($conn, 'ticket_attachments', 'mime_type')) {
    if ($conn->query("ALTER TABLE `ticket_attachments` ADD COLUMN `mime_type` VARCHAR(100) DEFAULT NULL AFTER `file_size`")) {
        echo "Added ticket_attachments.mime_type\n";
    } else {
        echo "Error adding mime_type: " . $conn->error . "\n";
    }
}

$statusAlter = "ALTER TABLE `tickets` MODIFY COLUMN `status` ENUM(
    'Open',
    'Awaiting Admin Approval',
    'Awaiting Client Review',
    'Awaiting Payment',
    'Payment Confirmed',
    'Approved',
    'In Development',
    'Resolved',
    'Reopened',
    'Closed',
    'Rejected',
    'On Hold'
) NOT NULL DEFAULT 'Open'";

if ($conn->query($statusAlter)) {
    echo "Updated tickets.status enum\n";
} else {
    echo "Error updating status enum: " . $conn->error . "\n";
}

if (!tableExists($conn, 'ticket_discussions')) {
    $discussionSql = "CREATE TABLE `ticket_discussions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ticket_id` INT NOT NULL,
      `message` TEXT NOT NULL,
      `created_by` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      INDEX `idx_discussion_ticket` (`ticket_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($discussionSql)) {
        echo "Created ticket_discussions table\n";
    } else {
        echo "Error creating ticket_discussions: " . $conn->error . "\n";
    }
}

echo "\nTicket workflow schema update complete.\n";
