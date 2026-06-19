<?php

/**
 * XSS Protection helper
 * Escapes HTML characters for output.
 */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get or generate CSRF token
 */
function csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF token input field
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token from request
 */
function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            http_response_code(403);
            die('Error: Invalid or missing CSRF token.');
        }
    }
}

/**
 * Set a flash message
 */
function set_flash_message($type, $message)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a flash message
 */
function get_flash_message($type)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $message = $_SESSION['flash_' . $type] ?? null;
    unset($_SESSION['flash_' . $type]);
    return $message;
}

/**
 * Check if flash message exists
 */
function has_flash_message($type)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['flash_' . $type]);
}

/**
 * Redirect utility
 */
function redirect($page, $params = [])
{
    $query = http_build_query(array_merge(['page' => $page], $params));
    header('Location: ?' . $query);
    exit;
}

/**
 * Helper to determine if a page is currently active
 */
function is_active_page($targetPage, $exact = true)
{
    $currentPage = $_GET['page'] ?? 'login';
    if ($exact) {
        return $currentPage === $targetPage;
    }
    return str_starts_with($currentPage, $targetPage);
}
