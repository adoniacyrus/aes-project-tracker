<?php

/**
 * Mail configuration loaded from environment variables (.env).
 * Drivers: smtp (PHPMailer) | elastic (Elastic Email REST API)
 */
return [
    'enabled' => filter_var($_ENV['MAIL_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'driver' => strtolower(trim((string) ($_ENV['MAIL_DRIVER'] ?? 'smtp'))),

    // SMTP (Gmail / any SMTP)
    'smtp_host' => $_ENV['MAIL_HOST'] ?? '',
    'smtp_port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'smtp_secure' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'smtp_auth' => filter_var($_ENV['MAIL_AUTH'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'smtp_username' => $_ENV['MAIL_USERNAME'] ?? '',
    'smtp_password' => $_ENV['MAIL_PASSWORD'] ?? '',

    // Shared from identity
    'from_email' => $_ENV['MAIL_FROM'] ?? '',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'AES Project Tracker',

    // Elastic Email API
    'elastic_api_key' => $_ENV['ELASTIC_EMAIL_API_KEY'] ?? '',
    'elastic_endpoint' => $_ENV['ELASTIC_EMAIL_ENDPOINT'] ?? 'https://api.elasticemail.com/v2/email/send',
];
