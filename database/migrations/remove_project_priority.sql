-- Remove priority from projects (priority remains on tickets only)
-- Run via: php database/update_schema.php
-- Or execute manually after backing up the database.

ALTER TABLE `projects` DROP INDEX IF EXISTS `idx_project_priority`;
ALTER TABLE `projects` DROP COLUMN IF EXISTS `priority`;
