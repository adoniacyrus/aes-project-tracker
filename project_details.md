# AES Project Tracker — Project Details

## Overview
AES Project Tracker is a lightweight PHP MVC application for tracking projects, tickets, tasks, users, and activity logs. It uses a simple custom MVC structure (controllers, models, views), MySQL for persistence, and stores migrations in the `database/migrations` folder.

## Quick Start
- Web root: place project under your web server (e.g., `c:\xampp\htdocs\aes-project-tracker`).
- Database config: see [config/config.php](config/config.php#L1).
- Run the migrations: see `database/run_migrations.php` or import `database/migrations/schema.sql`.

## Top-level Structure

- `index.php`: Front controller / router entry.
- `README.md`: Project README.
- `app/`: Application code (MVC layers).
- `config/`: Configuration files.
- `database/`: Migrations, seeds, and helper scripts.
- `public/`: Public assets (CSS, JS, images) and upload folder.
- `routes/`: Route definitions.
- `storage/`: Application storage (logs, uploads, etc.).

## `app/` Directory

- `app/controllers/`: Controller classes for request handling
  - `AuthController.php` — Login, logout, password reset flows
  - `DashboardController.php` — Admin/dashboard pages
  - `ProfileController.php` — User profile and password change
  - `ProjectController.php` — CRUD for projects
  - `TaskController.php` — CRUD for tasks
  - `TicketController.php` — CRUD for tickets, ticket workflows
  - `UserController.php` — User management
- `app/helpers/`:
  - `helpers.php` — Shared helper functions
- `app/middleware/`:
  - `AuthMiddleware.php` — Protects authenticated routes
  - `AdminMiddleware.php` — Admin-only protection
- `app/models/`:
  - `UserModel.php`, `ProjectModel.php`, `TaskModel.php`, `TicketModel.php`, `ActivityLogModel.php`, `PasswordResetModel.php`
- `app/services/`:
  - `TicketWorkflowService.php` — Ticket state transition logic

## `views/` Directory (UI templates)

- `views/layouts/` — `master.php`, `navbar.php`, `sidebar.php`, `footer.php`
- `views/auth/` — `login.php`, `forgot-password.php`, `reset-password.php`
- `views/dashboard/` — `index.php`
- `views/profile/` — `index.php`, `change-password.php`
- `views/projects/` — `index.php`, `create.php`, `edit.php`, `view.php`, `team-members.php`
- `views/tasks/` — `index.php`, `create.php`, `edit.php`
- `views/tickets/` — `index.php`, `create.php`, `edit.php`, `view.php`
- `views/users/` — `index.php`, `create.php`, `edit.php`, `view.php`
- `views/errors/` — `403.php`

## `config/` Directory

- `config.php` — App-level configuration (site name, base URL)
- `database.php` — DB connection settings (host, user, pass, dbname)

## `database/` Folder

- `migrations/schema.sql` — Full SQL schema and sample seed data (users)
- `run_migrations.php` — Script to execute schema import
- `update_schema.php` — Schema update helper
- `verify_installation.php` — Installation checks
- `seeds/admin-user.sql` — Optional seed SQL for admin user

## `public/` Folder

- `assets/css/custom.css` — Custom styling
- `assets/js/` — JavaScript assets
- `assets/images/` — Static images
- `uploads/` — Uploaded files and attachments

## `routes/` Folder

- `web.php` — Route definitions mapping URIs to controllers/actions

## `storage/` Folder

- `logs/` — Application logs

## Database Schema (current `database/migrations/schema.sql`)

The schema below is included verbatim from `database/migrations/schema.sql`.

```sql
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Ticket Comments Table
CREATE TABLE IF NOT EXISTS `ticket_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_comment_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Ticket Attachments Table
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tasks Table
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
```

## Notes for Newcomers
- The app follows a small custom MVC pattern — controllers under `app/controllers`, models under `app/models`, and views in `views/`.
- Authentication and admin checks are implemented via middleware in `app/middleware/`.
- Ticket workflows are managed by `app/services/TicketWorkflowService.php` and used by `TicketController`.
- Assets are served from `public/` and the project expects the webserver document root to point to the project root (or adjust `index.php`).

---
File created: project_details.md
