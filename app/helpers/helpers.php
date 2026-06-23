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
 * Derive two-letter initials from a full name.
 */
function user_initials($fullName)
{
    $fullName = trim((string)$fullName);
    if ($fullName === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $fullName);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }

    return strtoupper(substr($fullName, 0, 2));
}

/**
 * Generate a URL-friendly slug
 */
function slugify($text)
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text === '' ? 'item' : $text;
}

/**
 * Determine if a route name exists in the route map
 */
function route_exists($name)
{
    $routes = [
        'login', 'logout', 'forgot-password', 'reset-password',
        'dashboard',
        'projects', 'projects-view', 'projects-create', 'projects-edit', 'projects-team', 'projects-archive',
        'tickets', 'tickets-create', 'tickets-view', 'tickets-edit', 'tickets-workflow', 'tickets-comment', 'tickets-discussion', 'tickets-internal-discussion', 'tickets-forward-approval', 'tickets-proposal', 'tickets-payment', 'tickets-reclassify', 'tickets-attachment', 'tickets-delete-attachment', 'tickets-download-attachment',
        'users', 'users-create', 'users-view', 'users-edit', 'users-status', 'users-admin-reset',
        'profile', 'profile-change-password',
        'tasks', 'tasks-create', 'tasks-edit', 'tasks-status', 'tasks-delete'
    ];
    return in_array($name, $routes, true);
}

/**
 * Helper to get ticket code (PROJECT_CODE-ID) by ticket ID using cache
 */
function get_ticket_code_by_id($id)
{
    static $cache = [];
    if (!isset($cache[$id])) {
        require_once __DIR__ . '/../models/TicketModel.php';
        $ticketModel = new TicketModel();
        $ticket = $ticketModel->findById($id);
        $cache[$id] = $ticket ? ($ticket['project_code'] . '-' . $ticket['id']) : 'ticket-' . $id;
    }
    return $cache[$id];
}

/**
 * Helper to get user slug by user ID using cache
 */
function get_user_slug_by_id($id)
{
    static $cache = [];
    if (!isset($cache[$id])) {
        require_once __DIR__ . '/../models/UserModel.php';
        $userModel = new UserModel();
        $user = $userModel->findById($id);
        $cache[$id] = $user ? slugify($user['full_name']) : 'user-' . $id;
    }
    return $cache[$id];
}

/**
 * Resolve user slug to ID
 */
function resolve_user_slug($slug)
{
    require_once __DIR__ . '/../models/UserModel.php';
    $userModel = new UserModel();
    $user = $userModel->findBySlug($slug);
    return $user ? $user['id'] : null;
}

/**
 * Generate a URL based on named routes
 */
function route($name, $params = [])
{
    $routeMap = [
        'login' => '/auth/login',
        'logout' => '/auth/logout',
        'forgot-password' => '/auth/forgot-password',
        'reset-password' => '/auth/reset-password',
        'dashboard' => '/dashboard',
        'projects' => '/projects',
        'projects-view' => '/projects/{project_code}',
        'projects-create' => '/projects/create',
        'projects-edit' => '/projects/{project_code}/edit',
        'projects-team' => '/projects/{project_code}/team',
        'projects-archive' => '/projects/{project_code}/archive',
        'tickets' => '/tickets',
        'tickets-create' => '/tickets/create',
        'tickets-view' => '/tickets/{ticket_code}',
        'tickets-edit' => '/tickets/{ticket_code}/edit',
        'tickets-workflow' => '/tickets/{ticket_code}/workflow',
        'tickets-comment' => '/tickets/{ticket_code}/comment',
        'tickets-discussion' => '/tickets/{ticket_code}/discussion',
        'tickets-internal-discussion' => '/tickets/{ticket_code}/internal-discussion',
        'tickets-forward-approval' => '/tickets/{ticket_code}/forward-approval',
        'tickets-proposal' => '/tickets/{ticket_code}/proposal',
        'tickets-payment' => '/tickets/{ticket_code}/payment',
        'tickets-reclassify' => '/tickets/{ticket_code}/reclassify',
        'tickets-attachment' => '/tickets/{ticket_code}/attachment',
        'tickets-delete-attachment' => '/tickets/{ticket_code}/attachment/delete/{attachment_id}',
        'tickets-download-attachment' => '/tickets/{ticket_code}/attachment/download/{attachment_id}',
        'users' => '/users',
        'users-create' => '/users/create',
        'users-view' => '/users/{slug}',
        'users-edit' => '/users/{slug}/edit',
        'users-status' => '/users/{slug}/status/{status}',
        'users-admin-reset' => '/users/{slug}/reset-password',
        'profile' => '/profile',
        'profile-change-password' => '/profile/change-password',
        'tasks' => '/tasks',
        'tasks-create' => '/tasks/create',
        'tasks-edit' => '/tasks/{id}/edit',
        'tasks-status' => '/tasks/{id}/status',
        'tasks-delete' => '/tasks/{id}/delete',
    ];

    if (!isset($routeMap[$name])) {
        return '#';
    }

    $path = $routeMap[$name];

    $usedParams = [];

    // Replace {ticket_code} if present
    if (strpos($path, '{ticket_code}') !== false) {
        $ticketCode = '';
        if (isset($params['ticket_code'])) {
            $ticketCode = $params['ticket_code'];
            $usedParams[] = 'ticket_code';
        } elseif (isset($params['project_code']) && (isset($params['id']) || isset($params['ticket_id']))) {
            $ticketId = $params['id'] ?? $params['ticket_id'];
            $ticketCode = $params['project_code'] . '-' . $ticketId;
            if (isset($params['project_code'])) $usedParams[] = 'project_code';
            if (isset($params['id'])) $usedParams[] = 'id';
            if (isset($params['ticket_id'])) $usedParams[] = 'ticket_id';
        } else {
            $ticketId = $params['id'] ?? $params['ticket_id'] ?? null;
            if ($ticketId) {
                $ticketCode = get_ticket_code_by_id($ticketId);
                if (isset($params['id'])) $usedParams[] = 'id';
                if (isset($params['ticket_id'])) $usedParams[] = 'ticket_id';
            }
        }
        $path = str_replace('{ticket_code}', $ticketCode, $path);
    }

    // Replace {slug} if present
    if (strpos($path, '{slug}') !== false && in_array($name, ['users-view', 'users-edit', 'users-status', 'users-admin-reset'], true)) {
        $userSlug = '';
        if (isset($params['slug'])) {
            $userSlug = slugify($params['slug']);
            $usedParams[] = 'slug';
        } elseif (isset($params['full_name'])) {
            $userSlug = slugify($params['full_name']);
            $usedParams[] = 'full_name';
        } else {
            $userId = $params['id'] ?? null;
            if ($userId) {
                $userSlug = get_user_slug_by_id($userId);
                if (isset($params['id'])) $usedParams[] = 'id';
            }
        }
        $path = str_replace('{slug}', $userSlug, $path);
    }

    if (strpos($path, '{slug}') !== false) {
        $slugSource = $params['slug'] ?? $params['title'] ?? '';
        $path = str_replace('{slug}', slugify($slugSource), $path);
        if (isset($params['slug'])) {
            $usedParams[] = 'slug';
        } elseif (isset($params['title'])) {
            $usedParams[] = 'title';
        }
    }

    foreach ($params as $key => $value) {
        $placeholder = '{' . $key . '}';
        if (strpos($path, $placeholder) !== false) {
            $path = str_replace($placeholder, urlencode($value), $path);
            $usedParams[] = $key;
        }
    }

    // If required route placeholders were not provided, fall back to legacy query-based routes
    if (preg_match('#\{[^}]+\}#', $path)) {
        $query = http_build_query(array_merge(['page' => $name], $params));
        return rtrim(BASE_URL, '/') . '/?' . $query;
    }

    $path = preg_replace('#/+#', '/', $path);
    $queryParams = array_diff_key($params, array_flip($usedParams));
    $fullPath = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    if (!empty($queryParams)) {
        $fullPath .= '?' . http_build_query($queryParams);
    }
    return $fullPath;
}

/**
 * Redirect utility using routes when possible
 */
function redirect($name, $params = [])
{
    if (route_exists($name)) {
        $url = route($name, $params);
    } elseif (is_string($name) && (strpos($name, '/') === 0 || strpos($name, '://') !== false)) {
        $url = $name;
    } else {
        $query = http_build_query(array_merge(['page' => $name], $params));
        $url = '?' . $query;
    }

    header('Location: ' . $url);
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

function is_image_attachment($fileName, $mimeType = null)
{
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return true;
    }
    return $mimeType && str_starts_with($mimeType, 'image/');
}

function attachment_preview_type($fileName, $mimeType = null)
{
    if (is_image_attachment($fileName, $mimeType)) {
        return 'image';
    }
    if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'pdf') {
        return 'pdf';
    }
    return 'file';
}

function format_file_size($bytes)
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Build a browser-accessible URL for a stored attachment path.
 */
function attachment_url($filePath, $ticketId = null, $attachmentId = null)
{
    if ($ticketId && $attachmentId) {
        return route('tickets-download-attachment', ['id' => $ticketId, 'attachment_id' => $attachmentId]);
    }
    if (empty($filePath)) {
        return '';
    }
    if (preg_match('#^https?://#i', $filePath)) {
        return $filePath;
    }
    return rtrim(BASE_URL, '/') . '/' . ltrim($filePath, '/');
}

/**
 * Detect AJAX / fetch requests (jQuery X-Requested-With or Accept: application/json).
 */
function is_ajax_request()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strpos($accept, 'application/json') !== false;
}

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Render a view partial to an HTML string.
 */
function render_partial($viewPath, array $data = [])
{
    extract($data, EXTR_SKIP);
    ob_start();
    require $viewPath;
    return ob_get_clean();
}

/**
 * Respond with a rendered partial for in-place DOM updates.
 */
function respond_partial($viewPath, array $data, string $refreshRoute, array $refreshParams = [])
{
    $refreshParams['partial'] = 1;
    json_response([
        'success' => true,
        'html' => render_partial($viewPath, $data),
        'refresh_url' => route($refreshRoute, $refreshParams),
    ]);
}

/**
 * Whether the current user can manage tasks (admin only).
 */
function can_manage_tasks($role = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    return $role === 'admin';
}

/**
 * Whether the user may update a task's status.
 */
function can_update_task_status(array $task, $userId = null, $role = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = $role ?? ($_SESSION['user_role'] ?? '');
    $userId = $userId ?? (int)($_SESSION['user_id'] ?? 0);

    if ($role === 'admin') {
        return true;
    }

    if (!in_array($role, ['developer', 'intern'], true)) {
        return false;
    }

    return (int)($task['assigned_member'] ?? 0) === $userId;
}

/**
 * Project members eligible for task assignment (developers and interns).
 */
function filter_task_assignable_members(array $members)
{
    return array_values(array_filter($members, function ($member) {
        return in_array($member['role'] ?? '', ['developer', 'intern'], true);
    }));
}

/**
 * Whether a ticket is visible to project developers and interns.
 */
function is_ticket_visible_to_project_team(array $ticket)
{
    if (!class_exists('TicketWorkflowService', false)) {
        require_once __DIR__ . '/../services/TicketWorkflowService.php';
    }

    return TicketWorkflowService::isVisibleToProjectTeam($ticket);
}

/**
 * Whether the current user may view project/ticket financial data.
 */
function can_view_project_financials($role = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    return in_array($role, ['admin', 'client'], true);
}

/**
 * Format a number using the Indian numbering system (e.g. 1,50,000).
 */
function format_indian_number($amount, $decimals = 0)
{
    $amount = round((float)$amount, $decimals);
    $negative = $amount < 0;
    $amount = abs($amount);

    $parts = explode('.', number_format($amount, $decimals, '.', ''));
    $intPart = $parts[0];
    $decPart = $parts[1] ?? '';

    $lastThree = strlen($intPart) > 3 ? substr($intPart, -3) : $intPart;
    $rest = strlen($intPart) > 3 ? substr($intPart, 0, -3) : '';

    if ($rest !== '') {
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $formatted = $rest . ',' . $lastThree;
    } else {
        $formatted = $lastThree;
    }

    if ($decimals > 0) {
        $formatted .= '.' . str_pad($decPart, $decimals, '0', STR_PAD_RIGHT);
    }

    return ($negative ? '-' : '') . $formatted;
}

/**
 * Format an amount in Sri Lankan Rupees (Rs.) with Indian comma grouping.
 */
function format_rs_currency($amount, $decimals = 0)
{
    return 'Rs. ' . format_indian_number($amount, $decimals);
}

/**
 * Strip financial fields from a project record for non-privileged roles.
 */
function sanitize_project_for_role(array $project, $role = null)
{
    if (can_view_project_financials($role)) {
        return $project;
    }

    unset($project['project_cost']);
    return $project;
}

/**
 * Strip financial fields from ticket records for non-privileged roles.
 */
function sanitize_tickets_for_role(array $tickets, $role = null)
{
    if (can_view_project_financials($role)) {
        return $tickets;
    }

    foreach ($tickets as &$ticket) {
        unset($ticket['estimated_cost']);
    }
    unset($ticket);

    return $tickets;
}

/**
 * Abort with a 403 Forbidden status and show the layout-consistent or standalone 403 page.
 */
function abort_403()
{
    http_response_code(403);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $pageTitle = 'Access Denied';
        $view = __DIR__ . '/../views/errors/403.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    } else {
        require_once __DIR__ . '/../views/errors/403.php';
    }
    exit;
}

/**
 * Abort with a 404 Not Found status and show the layout-consistent or standalone 404 page.
 */
function abort_404()
{
    http_response_code(404);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $pageTitle = 'Page Not Found';
        $view = __DIR__ . '/../views/errors/404.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    } else {
        require_once __DIR__ . '/../views/errors/404.php';
    }
    exit;
}

