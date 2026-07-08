<?php

define('APP_NAME', $_ENV['APP_NAME'] ?? getenv('APP_NAME') ?: 'AES Project Tracker');

/**
 * Web path prefix where the app is installed (no leading/trailing slashes).
 */
function app_base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $configured = $_ENV['APP_BASE_PATH'] ?? getenv('APP_BASE_PATH');
    if (is_string($configured) && $configured !== '') {
        $cached = trim($configured, '/');
        return $cached;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = trim(dirname($scriptName), '/');
    if ($dir === '.' || $dir === '/') {
        $dir = '';
    }

    $cached = $dir;
    return $cached;
}

/**
 * Full application base URL (scheme + host + optional subdirectory).
 */
function app_base_url(): string
{
    $configured = $_ENV['BASE_URL'] ?? getenv('BASE_URL');
    if (is_string($configured) && $configured !== '') {
        return rtrim($configured, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = app_base_path();

    return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '');
}

define('BASE_URL', app_base_url());

define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'aes_project_tracker');

define('SESSION_TIMEOUT', 1800);

define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');
define('APP_TIMEZONE_LABEL', $_ENV['APP_TIMEZONE_LABEL'] ?? getenv('APP_TIMEZONE_LABEL') ?: 'IST');

date_default_timezone_set(APP_TIMEZONE);
