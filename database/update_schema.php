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

// Migrate legacy Approved status to Open before enum changes
if ($conn->query("UPDATE tickets SET status = 'Open' WHERE status = 'Approved'")) {
    echo "Migrated Approved tickets to Open (" . $conn->affected_rows . " rows)\n";
} else {
    echo "Error migrating Approved tickets: " . $conn->error . "\n";
}

$statusAlter = "ALTER TABLE `tickets` MODIFY COLUMN `status` ENUM(
    'Initiated',
    'Processing',
    'Completed',
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

if (!tableExists($conn, 'ticket_internal_discussions')) {
    $internalDiscussionSql = "CREATE TABLE `ticket_internal_discussions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ticket_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `message` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      INDEX `idx_internal_discussion_ticket` (`ticket_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($internalDiscussionSql)) {
        echo "Created ticket_internal_discussions table\n";
    } else {
        echo "Error creating ticket_internal_discussions: " . $conn->error . "\n";
    }
}

echo "\nTicket workflow schema update complete.\n";

// ---- User full_name migration ----
echo "\nRunning user full_name migration...\n";

if (!columnExists($conn, 'users', 'full_name')) {
    if ($conn->query("ALTER TABLE `users` ADD COLUMN `full_name` VARCHAR(120) NULL AFTER `id`")) {
        echo "Added users.full_name column\n";
    } else {
        echo "Error adding full_name: " . $conn->error . "\n";
    }
}

if (columnExists($conn, 'users', 'first_name') && columnExists($conn, 'users', 'full_name')) {
    if ($conn->query("UPDATE `users` SET `full_name` = TRIM(CONCAT(`first_name`, ' ', `last_name`)) WHERE `full_name` IS NULL OR `full_name` = ''")) {
        echo "Populated full_name from first_name + last_name\n";
    } else {
        echo "Error populating full_name: " . $conn->error . "\n";
    }
}

if (columnExists($conn, 'users', 'full_name')) {
    if ($conn->query("ALTER TABLE `users` MODIFY COLUMN `full_name` VARCHAR(120) NOT NULL")) {
        echo "Set full_name NOT NULL\n";
    } else {
        echo "Error setting full_name NOT NULL: " . $conn->error . "\n";
    }
}

if (columnExists($conn, 'users', 'first_name')) {
    if ($conn->query("ALTER TABLE `users` DROP COLUMN `first_name`")) {
        echo "Dropped users.first_name\n";
    } else {
        echo "Error dropping first_name: " . $conn->error . "\n";
    }
}

if (columnExists($conn, 'users', 'last_name')) {
    if ($conn->query("ALTER TABLE `users` DROP COLUMN `last_name`")) {
        echo "Dropped users.last_name\n";
    } else {
        echo "Error dropping last_name: " . $conn->error . "\n";
    }
}

echo "User full_name migration complete.\n";

// ---- Remove project priority (tickets retain priority) ----
echo "\nRemoving projects.priority column...\n";

if (columnExists($conn, 'projects', 'priority')) {
    $indexResult = $conn->query("SHOW INDEX FROM `projects` WHERE Key_name = 'idx_project_priority'");
    if ($indexResult && $indexResult->num_rows > 0) {
        if ($conn->query("ALTER TABLE `projects` DROP INDEX `idx_project_priority`")) {
            echo "Dropped idx_project_priority index\n";
        } else {
            echo "Error dropping idx_project_priority: " . $conn->error . "\n";
        }
    }

    if ($conn->query("ALTER TABLE `projects` DROP COLUMN `priority`")) {
        echo "Dropped projects.priority column\n";
    } else {
        echo "Error dropping projects.priority: " . $conn->error . "\n";
    }
} else {
    echo "projects.priority already removed\n";
}

echo "Project priority removal complete.\n";

// ---- Add project_cost column ----
echo "\nAdding projects.project_cost column...\n";

if (!columnExists($conn, 'projects', 'project_cost')) {
    if ($conn->query("ALTER TABLE `projects` ADD COLUMN `project_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `expected_end_date`")) {
        echo "Added projects.project_cost column\n";
    } else {
        echo "Error adding projects.project_cost: " . $conn->error . "\n";
    }
} else {
    echo "projects.project_cost already exists\n";
}

echo "Project cost migration complete.\n";

// ---- Sync ticket team visibility for pre-approval / pre-payment statuses ----
echo "\nSyncing ticket team visibility flags...\n";

$hiddenStatuses = "'Initiated', 'Awaiting Admin Approval', 'Awaiting Client Review', 'Awaiting Payment'";
if ($conn->query("UPDATE tickets SET is_team_visible = 0 WHERE status IN ($hiddenStatuses) AND is_team_visible = 1")) {
    echo "Set is_team_visible = 0 for pre-approval/pre-payment tickets (" . $conn->affected_rows . " rows)\n";
} else {
    echo "Error syncing ticket visibility: " . $conn->error . "\n";
}

$visibleStatuses = "'Open', 'Payment Confirmed', 'Processing', 'Completed', 'In Development', 'Resolved', 'Reopened', 'Closed', 'Rejected', 'On Hold'";
if ($conn->query("UPDATE tickets SET is_team_visible = 1 WHERE status IN ($visibleStatuses) AND is_team_visible = 0")) {
    echo "Repaired is_team_visible = 1 for approved/active tickets (" . $conn->affected_rows . " rows)\n";
} else {
    echo "Error repairing ticket visibility flags: " . $conn->error . "\n";
}

echo "Ticket team visibility sync complete.\n";

// ---- Team Chat Attachments table ----
echo "\nCreating team_chat_attachments table if needed...\n";

if (!tableExists($conn, 'team_chat_attachments')) {
    $teamChatAttachmentSql = "CREATE TABLE `team_chat_attachments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($teamChatAttachmentSql)) {
        echo "Created team_chat_attachments table\n";
    } else {
        echo "Error creating team_chat_attachments: " . $conn->error . "\n";
    }
} else {
    echo "team_chat_attachments table already exists\n";
}

echo "Team chat attachments migration complete.\n";

// ---- Ticket comment channels (team vs admin-client) ----
echo "\nAdding ticket_comments.channel column...\n";

if (!columnExists($conn, 'ticket_comments', 'channel')) {
    if ($conn->query("ALTER TABLE `ticket_comments` ADD COLUMN `channel` ENUM('team', 'client') NOT NULL DEFAULT 'team' AFTER `comment`")) {
        echo "Added ticket_comments.channel\n";
    } else {
        echo "Error adding ticket_comments.channel: " . $conn->error . "\n";
    }
} else {
    echo "ticket_comments.channel already exists\n";
}

echo "Migrating legacy client discussions into ticket_comments...\n";
$migrateDiscussionsSql = "INSERT INTO ticket_comments (ticket_id, user_id, comment, channel, created_at)
    SELECT td.ticket_id, td.created_by, td.message, 'client', td.created_at
    FROM ticket_discussions td
    WHERE NOT EXISTS (
        SELECT 1 FROM ticket_comments tc
        WHERE tc.ticket_id = td.ticket_id
          AND tc.user_id = td.created_by
          AND tc.comment = td.message
          AND tc.channel = 'client'
          AND tc.created_at = td.created_at
    )";
if ($conn->query($migrateDiscussionsSql)) {
    echo "Migrated ticket_discussions rows (" . $conn->affected_rows . " rows)\n";
} else {
    echo "Error migrating discussions: " . $conn->error . "\n";
}

// ---- Ticket cost estimation audit log ----
echo "\nCreating ticket_cost_estimation_logs table if needed...\n";

if (!tableExists($conn, 'ticket_cost_estimation_logs')) {
    $costLogSql = "CREATE TABLE `ticket_cost_estimation_logs` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($costLogSql)) {
        echo "Created ticket_cost_estimation_logs table\n";
    } else {
        echo "Error creating ticket_cost_estimation_logs: " . $conn->error . "\n";
    }
} else {
    echo "ticket_cost_estimation_logs table already exists\n";
}

echo "Commercial workflow migration complete.\n";

// ---- Ticket team assignments ----
echo "\nCreating ticket_assignments table if needed...\n";

if (!tableExists($conn, 'ticket_assignments')) {
    $assignmentSql = "CREATE TABLE `ticket_assignments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($assignmentSql)) {
        echo "Created ticket_assignments table\n";
    } else {
        echo "Error creating ticket_assignments: " . $conn->error . "\n";
    }
} else {
    echo "ticket_assignments table already exists\n";
}

echo "\nAdding admin_dev channel to ticket_comments...\n";

$channelCheck = $conn->query("SHOW COLUMNS FROM `ticket_comments` LIKE 'channel'");
if ($channelCheck && $channelCheck->num_rows > 0) {
    $channelRow = $channelCheck->fetch_assoc();
    if (strpos($channelRow['Type'], 'admin_dev') === false) {
        if ($conn->query("ALTER TABLE `ticket_comments` MODIFY COLUMN `channel` ENUM('team', 'client', 'admin_dev') NOT NULL DEFAULT 'team'")) {
            echo "Extended ticket_comments.channel with admin_dev\n";
        } else {
            echo "Error extending channel enum: " . $conn->error . "\n";
        }
    } else {
        echo "ticket_comments.channel already includes admin_dev\n";
    }
}

echo "Developer assignment migration complete.\n";

