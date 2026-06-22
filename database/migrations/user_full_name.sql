-- Safe migration: first_name + last_name -> full_name
-- Run via: php database/update_schema.php

-- 1. Add full_name column if missing
-- 2. Merge existing names: TRIM(CONCAT(first_name, ' ', last_name))
-- 3. Set NOT NULL on full_name
-- 4. Drop first_name and last_name
