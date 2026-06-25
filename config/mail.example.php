<?php

/**
 * SMTP mail configuration loaded from environment variables (.env).
 * See .env.example for required MAIL_* variables.
 */
return [
    'enabled' => filter_var($_ENV['MAIL_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'smtp_host' => $_ENV['MAIL_HOST'] ?? '',
    'smtp_port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'smtp_secure' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'smtp_auth' => filter_var($_ENV['MAIL_AUTH'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'smtp_username' => $_ENV['MAIL_USERNAME'] ?? '',
    'smtp_password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'from_email' => $_ENV['MAIL_FROM'] ?? '',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'AES Project Tracker',
];
