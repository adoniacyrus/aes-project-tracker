<?php

define('APP_NAME', 'AES Project Tracker');

define('BASE_URL', 'http://localhost/aes-project-tracker');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aes_project_tracker');

define('SESSION_TIMEOUT', 1800);

define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');
define('APP_TIMEZONE_LABEL', $_ENV['APP_TIMEZONE_LABEL'] ?? getenv('APP_TIMEZONE_LABEL') ?: 'IST');

date_default_timezone_set(APP_TIMEZONE);