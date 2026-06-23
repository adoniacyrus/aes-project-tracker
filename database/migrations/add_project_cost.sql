-- Add project_cost to projects table (preserves existing rows with default 0.00)
ALTER TABLE `projects`
  ADD COLUMN IF NOT EXISTS `project_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `expected_end_date`;
