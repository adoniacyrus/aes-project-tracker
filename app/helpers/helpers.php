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
 * Application timezone (Indian Standard Time by default).
 */
function app_timezone(): string
{
    return defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Kolkata';
}

function app_timezone_label(): string
{
    return defined('APP_TIMEZONE_LABEL') ? APP_TIMEZONE_LABEL : 'IST';
}

/**
 * Current application datetime string.
 */
function app_now(string $format = 'Y-m-d H:i:s'): string
{
    return (new DateTimeImmutable('now', new DateTimeZone(app_timezone())))->format($format);
}

/**
 * Format a date value for display.
 */
function format_app_date(?string $date, string $format = 'M d, Y', string $fallback = 'N/A'): string
{
    if (empty($date)) {
        return $fallback;
    }

    $timestamp = strtotime((string)$date);

    return $timestamp !== false ? date($format, $timestamp) : $fallback;
}

/**
 * Format a datetime value for display.
 */
function format_app_datetime(
    ?string $datetime,
    string $format = 'M d, Y H:i:s',
    string $fallback = 'N/A',
    bool $includeTimezoneLabel = false
): string {
    if (empty($datetime)) {
        return $fallback;
    }

    $timestamp = strtotime((string)$datetime);
    if ($timestamp === false) {
        return $fallback;
    }

    $formatted = date($format, $timestamp);

    return $includeTimezoneLabel ? $formatted . ' ' . app_timezone_label() : $formatted;
}

/**
 * Whether a single list filter value differs from its default.
 */
function list_filter_is_active($value, $default): bool
{
    if (is_int($default) || (is_numeric($value) && is_numeric($default) && $default !== '')) {
        return (int)$value !== (int)$default;
    }

    return trim((string)$value) !== trim((string)$default);
}

function has_active_project_list_filters(string $search, string $statusFilter, int $archiveFilter = 0): bool
{
    return $search !== ''
        || list_filter_is_active($statusFilter, 'Processing')
        || list_filter_is_active($archiveFilter, 0);
}

function has_active_ticket_list_filters(string $search, int $projectId, string $category, string $priority, string $status): bool
{
    return $search !== ''
        || $projectId > 0
        || $category !== ''
        || $priority !== ''
        || list_filter_is_active($status, 'Processing');
}

function has_active_task_list_filters(?int $selectedUserId, string $statusFilter): bool
{
    return ($selectedUserId !== null && $selectedUserId > 0)
        || list_filter_is_active($statusFilter, 'In Progress');
}

function has_active_user_list_filters(string $search): bool
{
    return trim($search) !== '';
}

/**
 * Validate a strong password (min 8 chars with upper, lower, number, special).
 * Returns an error message string or null when valid.
 */
function validate_strong_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'New password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'New password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'New password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'New password must contain at least one number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'New password must contain at least one special character.';
    }

    return null;
}

/**
 * Generate a cryptographically secure temporary password (12–16 characters).
 */
function generate_secure_temporary_password(?int $length = null): string
{
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    $special = '@#$%&*!?';
    $all = $upper . $lower . $digits . $special;

    $length = $length ?? random_int(12, 16);
    $length = max(12, min(16, $length));

    $password = [
        $upper[random_int(0, strlen($upper) - 1)],
        $lower[random_int(0, strlen($lower) - 1)],
        $digits[random_int(0, strlen($digits) - 1)],
        $special[random_int(0, strlen($special) - 1)],
    ];

    while (count($password) < $length) {
        $password[] = $all[random_int(0, strlen($all) - 1)];
    }

    for ($i = count($password) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
    }

    return implode('', $password);
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
            if (function_exists('is_ajax_request') && is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Invalid or missing CSRF token. Please refresh and try again.'], 403);
            }
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
 * Normalize a person name for storage/validation.
 */
function normalize_person_name($name)
{
    $name = trim((string) $name);
    $name = str_replace(["\u{2018}", "\u{2019}", "\u{201B}", '`'], "'", $name);
    $name = preg_replace('/\s+/u', ' ', $name);
    return is_string($name) ? $name : '';
}

/**
 * Whether a full name uses only allowed real-world characters.
 * Allows letters, spaces, periods, apostrophes, and hyphens.
 */
function is_valid_person_name($name)
{
    $name = normalize_person_name($name);
    if ($name === '' || mb_strlen($name) > 120) {
        return false;
    }

    // Block control characters and HTML/script delimiters
    if (preg_match('/[\x00-\x1F\x7F<>]/u', $name)) {
        return false;
    }

    // Letters (incl. accents), combining marks, spaces, periods, apostrophes, hyphens
    if (!preg_match("/^[\\p{L}\\p{M} .'\\-]+$/u", $name)) {
        return false;
    }

    // Must contain at least one letter
    return (bool) preg_match('/\\p{L}/u', $name);
}

/**
 * Validate a person name. Returns an error message, or null when valid.
 */
function validate_person_name($name)
{
    $name = normalize_person_name($name);
    if ($name === '') {
        return 'Full name is required.';
    }
    if (mb_strlen($name) > 120) {
        return 'Full name must be 120 characters or fewer.';
    }
    if (!is_valid_person_name($name)) {
        return 'Invalid name format.';
    }
    return null;
}

/**
 * Determine if a route name exists in the route map
 */
function route_exists($name)
{
    $routes = [
        'login', 'logout', 'forgot-password', 'reset-password', 'auth-change-password',
        'dashboard',
        'projects', 'projects-view', 'projects-create', 'projects-edit', 'projects-team', 'projects-archive',
        'projects-financial-report-csv', 'projects-financial-report-pdf',
        'tickets', 'tickets-create', 'tickets-view', 'tickets-edit', 'tickets-workflow', 'tickets-comment', 'tickets-discussion', 'tickets-internal-discussion', 'tickets-forward-approval', 'tickets-proposal', 'tickets-payment', 'tickets-save-estimation', 'tickets-assign-team', 'tickets-submit-review', 'tickets-request-admin-clarification', 'tickets-respond-admin-guidance', 'tickets-approve-review', 'tickets-return-development', 'tickets-reclassify', 'tickets-attachment', 'tickets-delete-attachment', 'tickets-download-attachment', 'tickets-team-chat-attachment',
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
        'auth-change-password' => '/auth/change-password',
        'dashboard' => '/dashboard',
        'projects' => '/projects',
        'projects-view' => '/projects/{project_code}',
        'projects-create' => '/projects/create',
        'projects-edit' => '/projects/{project_code}/edit',
        'projects-team' => '/projects/{project_code}/team',
        'projects-archive' => '/projects/{project_code}/archive',
        'projects-financial-report-csv' => '/projects/{project_code}/financial-report/csv',
        'projects-financial-report-pdf' => '/projects/{project_code}/financial-report/pdf',
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
        'tickets-save-estimation' => '/tickets/{ticket_code}/save-estimation',
        'tickets-assign-team' => '/tickets/{ticket_code}/assign-team',
        'tickets-submit-review' => '/tickets/{ticket_code}/submit-review',
        'tickets-request-admin-clarification' => '/tickets/{ticket_code}/request-admin-clarification',
        'tickets-respond-admin-guidance' => '/tickets/{ticket_code}/respond-admin-guidance',
        'tickets-approve-review' => '/tickets/{ticket_code}/approve-review',
        'tickets-return-development' => '/tickets/{ticket_code}/return-development',
        'tickets-reclassify' => '/tickets/{ticket_code}/reclassify',
        'tickets-attachment' => '/tickets/{ticket_code}/attachment',
        'tickets-delete-attachment' => '/tickets/{ticket_code}/attachment/delete/{attachment_id}',
        'tickets-download-attachment' => '/tickets/{ticket_code}/attachment/download/{attachment_id}',
        'tickets-team-chat-attachment' => '/tickets/{ticket_code}/team-chat/attachment/{attachment_id}',
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
    if (!array_key_exists('partial', $refreshParams)) {
        $refreshParams['partial'] = 1;
    }
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
 * Whether the user can access the shared team chat on ticket details.
 */
function can_access_team_chat($role = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    return in_array($role, ['admin', 'developer', 'intern', 'client'], true);
}

/**
 * Whether the user can access the admin-client commercial chat on ticket details.
 */
function can_access_client_chat($role = null)
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
 * Whether the user can view ticket cost estimation on the workspace.
 */
function can_view_ticket_cost_estimation($role = null)
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
 * Whether the user may edit ticket cost estimation fields.
 */
function can_edit_ticket_estimation($role = null)
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
 * Whether the user can access the admin-developer ticket chat.
 */
function can_access_admin_dev_chat($role = null, array $ticket = [], $userId = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    if ($userId === null) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
    }

    if ($role === 'admin') {
        return true;
    }

    if (!in_array($role, ['developer', 'intern'], true) || empty($ticket['id'])) {
        return false;
    }

    require_once __DIR__ . '/../models/TicketModel.php';
    $ticketModel = new TicketModel();

    return $ticketModel->userHasTicketTeamAccess($ticket, (int)$userId);
}

/**
 * Team members shown on the ticket (assigned or Bug Fix project team).
 */
function get_ticket_visible_team_members(array $ticket, array $projectMembers = [])
{
    $assigned = get_ticket_assigned_members($ticket);
    if (!empty($assigned)) {
        return $assigned;
    }

    if (!TicketWorkflowService::isBugFixOpenToProjectTeam($ticket)) {
        return [];
    }

    if (empty($projectMembers)) {
        require_once __DIR__ . '/../models/ProjectModel.php';
        $projectModel = new ProjectModel();
        $projectMembers = $projectModel->getProjectMembers((int)$ticket['project_id']);
    }

    return array_values(array_filter($projectMembers, function ($member) {
        return in_array($member['role'] ?? '', ['developer', 'intern'], true);
    }));
}

/**
 * Normalize workflow history visibility values.
 */
function normalize_workflow_history_visibility($visibility)
{
    $allowed = ['all', 'internal', 'admin_client'];
    return in_array($visibility, $allowed, true) ? $visibility : 'all';
}

/**
 * SQL visibility filter for workflow history by user role.
 */
function workflow_history_visibility_sql($userRole)
{
    if ($userRole === 'admin') {
        return '';
    }

    if ($userRole === 'client') {
        return " AND h.visibility IN ('all', 'admin_client') ";
    }

    if (in_array($userRole, ['developer', 'intern'], true)) {
        return " AND h.visibility IN ('all', 'internal') ";
    }

    return " AND h.visibility = 'all' ";
}

/**
 * Relative time label for workflow history.
 */
function workflow_time_ago($datetime)
{
    if (empty($datetime)) {
        return 'Just now';
    }

    $timestamp = strtotime((string)$datetime);
    if ($timestamp === false) {
        return '';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return $mins . ' minute' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('M d, Y g:i A', $timestamp) . ' ' . app_timezone_label();
}

/**
 * Format latest workflow activity sentence.
 */
function format_workflow_latest_activity(array $entry)
{
    if (empty($entry)) {
        return '';
    }

    $performer = trim((string)($entry['performer_name'] ?? ''));
    $label = trim((string)($entry['label'] ?? ''));
    $action = (string)($entry['action'] ?? '');

    $sentence = $label;
    if ($performer !== '') {
        switch ($action) {
            case 'review_submitted':
                $sentence = $performer . ' submitted this ticket for review.';
                break;
            case 'admin_guidance_requested':
                $sentence = $performer . ' requested admin review for suggestions or clarification.';
                break;
            case 'admin_guidance_responded':
                $sentence = $performer . ' responded to the admin review request.';
                break;
            case 'completed':
            case 'review_approved':
                $sentence = $performer . ' marked this ticket as Completed.';
                break;
            case 'returned_to_development':
                $sentence = $performer . ' returned this ticket for further development.';
                break;
            case 'team_assigned':
                $sentence = $performer . ' updated developer assignments.';
                break;
            case 'commercial_review_requested':
                $sentence = $performer . ' requested commercial review.';
                break;
            case 'category_reclassified':
                $sentence = $performer . ' reclassified this ticket.';
                break;
            case 'ticket_created':
                $sentence = $performer !== '' ? ($performer . ' created this ticket.') : 'Ticket created.';
                break;
            case 'status_changed':
                $sentence = $performer !== '' ? ($performer . ' changed the ticket status.') : 'Ticket status changed.';
                break;
            default:
                $sentence = $performer !== '' ? ($performer . ' — ' . $label) : $label;
                break;
        }
    }

    return $sentence;
}

/**
 * Build a system message for ticket team assignment changes.
 */
function build_ticket_assignment_chat_message(array $previousAssignments, array $newAssignments)
{
    $nameById = [];
    foreach (array_merge($previousAssignments, $newAssignments) as $row) {
        $nameById[(int)$row['user_id']] = $row['full_name'] ?? ('User #' . (int)$row['user_id']);
    }

    $previousIds = array_map('intval', array_column($previousAssignments, 'user_id'));
    $newIds = array_map('intval', array_column($newAssignments, 'user_id'));
    $previousIds = array_values(array_unique($previousIds));
    $newIds = array_values(array_unique($newIds));

    if (empty($previousIds)) {
        $lines = ['[Ticket Assigned]', 'Ticket assigned to'];
        foreach ($newIds as $id) {
            $lines[] = $nameById[$id] ?? ('User #' . $id);
        }
        return implode("\n", $lines);
    }

    $added = array_values(array_diff($newIds, $previousIds));
    $removed = array_values(array_diff($previousIds, $newIds));

    $lines = ['[Assignment Updated]'];
    if (!empty($added)) {
        $lines[] = 'Added';
        foreach ($added as $id) {
            $lines[] = $nameById[$id] ?? ('User #' . $id);
        }
    }
    if (!empty($removed)) {
        $lines[] = 'Removed';
        foreach ($removed as $id) {
            $lines[] = $nameById[$id] ?? ('User #' . $id);
        }
    }
    if (empty($added) && empty($removed)) {
        $lines[] = 'Assignment saved.';
    }

    return implode("\n", $lines);
}

/**
 * Assigned ticket members for display.
 */
function get_ticket_assigned_members(array $ticket)
{
    if (empty($ticket['id'])) {
        return [];
    }

    require_once __DIR__ . '/../models/TicketModel.php';
    $ticketModel = new TicketModel();

    return $ticketModel->getTicketAssignments((int)$ticket['id']);
}

/**
 * Whether a project member should be pre-checked in the developer assignment form.
 */
function is_ticket_assignment_member_checked(array $member, $hasExistingAssignment, array $assignedUserIds)
{
    $memberId = (int)($member['user_id'] ?? 0);
    if ($hasExistingAssignment) {
        return in_array($memberId, $assignedUserIds, true);
    }

    return ($member['role'] ?? '') === 'developer';
}

/**
 * Prepare developer/intern lists and assignment state for assignment forms.
 */
function prepare_ticket_developer_assignment_form(array $ticket, array $developerAssignmentMembers)
{
    $ticketAssignments = get_ticket_assigned_members($ticket);
    $assignedUserIds = array_map('intval', array_column($ticketAssignments, 'user_id'));
    $hasExistingAssignment = !empty($assignedUserIds);

    $developerMembers = array_values(array_filter($developerAssignmentMembers, function ($member) {
        return ($member['role'] ?? '') === 'developer';
    }));
    $internMembers = array_values(array_filter($developerAssignmentMembers, function ($member) {
        return ($member['role'] ?? '') === 'intern';
    }));

    return compact('developerMembers', 'internMembers', 'assignedUserIds', 'hasExistingAssignment', 'ticketAssignments');
}

/**
 * Whether a ticket is awaiting admin review after developer submission.
 */
function is_ticket_pending_admin_review(array $ticket)
{
    return !empty($ticket['pending_admin_review']);
}

/**
 * Whether a developer has submitted an admin guidance request awaiting response.
 */
function is_ticket_pending_admin_guidance(array $ticket)
{
    return !empty($ticket['pending_admin_guidance']);
}

/**
 * Whether an assigned developer/intern can submit the ticket for admin review.
 */
function can_submit_ticket_for_review($role = null, array $ticket = [], $userId = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    if ($userId === null) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
    }

    if (!in_array($role, ['developer', 'intern'], true) || empty($ticket['id'])) {
        return false;
    }

    if (is_ticket_pending_admin_review($ticket)) {
        return false;
    }

    if (!class_exists('TicketWorkflowService', false)) {
        require_once __DIR__ . '/../services/TicketWorkflowService.php';
    }

    $displayStatus = TicketWorkflowService::mapToSimplifiedStatus($ticket['status'] ?? '');
    $isBugFixOpen = TicketWorkflowService::isBugFixOpenToProjectTeam($ticket);
    $allowedStatuses = $isBugFixOpen ? ['Initiated', 'Processing'] : ['Processing'];
    if (!in_array($displayStatus, $allowedStatuses, true)) {
        return false;
    }

    require_once __DIR__ . '/../models/TicketModel.php';
    $ticketModel = new TicketModel();

    return $ticketModel->userHasTicketTeamAccess($ticket, (int)$userId);
}

/**
 * Whether an assigned developer/intern can request admin suggestions or clarification.
 */
function can_request_admin_clarification($role = null, array $ticket = [], $userId = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    if ($userId === null) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
    }

    if (!in_array($role, ['developer', 'intern'], true) || empty($ticket['id'])) {
        return false;
    }

    if (is_ticket_pending_admin_guidance($ticket)) {
        return false;
    }

    if (!class_exists('TicketWorkflowService', false)) {
        require_once __DIR__ . '/../services/TicketWorkflowService.php';
    }

    $displayStatus = ticket_display_status($ticket);
    if ($displayStatus === 'Completed') {
        return false;
    }

    require_once __DIR__ . '/../models/TicketModel.php';
    $ticketModel = new TicketModel();

    if (!$ticketModel->userHasTicketTeamAccess($ticket, (int)$userId)) {
        return false;
    }

    if (is_ticket_pending_admin_review($ticket)) {
        return true;
    }

    $isBugFixOpen = TicketWorkflowService::isBugFixOpenToProjectTeam($ticket);
    $allowedStatuses = $isBugFixOpen ? ['Initiated', 'Processing'] : ['Processing'];

    return in_array($displayStatus, $allowedStatuses, true);
}

/**
 * Whether admin can review a pending developer submission.
 */
function can_admin_review_ticket($role = null, array $ticket = [])
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    return $role === 'admin' && is_ticket_pending_admin_review($ticket);
}

/**
 * Whether admin can respond to a pending developer guidance request.
 */
function can_admin_respond_to_guidance($role = null, array $ticket = [])
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    return $role === 'admin' && is_ticket_pending_admin_guidance($ticket);
}

/**
 * Whether the user can view the latest admin review comment on a ticket.
 */
function can_view_latest_review_comment($role = null, array $ticket = [], $userId = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    if ($role === 'client' || trim((string)($ticket['latest_review_comment'] ?? '')) === '') {
        return false;
    }

    if ($role === 'admin') {
        return true;
    }

    if ($userId === null) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
    }

    if (!in_array($role, ['developer', 'intern'], true) || empty($ticket['id'])) {
        return false;
    }

    require_once __DIR__ . '/../models/TicketModel.php';
    $ticketModel = new TicketModel();

    return $ticketModel->userHasTicketTeamAccess($ticket, (int)$userId);
}

/**
 * Build admin-developer chat message when a developer marks a ticket completed.
 */
function build_resolution_submitted_chat_message($submitterName, $comment = '')
{
    $lines = ['[Ticket Completed]', $submitterName . ' marked the ticket as Completed.'];
    $comment = trim((string)$comment);
    if ($comment !== '') {
        $lines[] = 'Comment:';
        $lines[] = $comment;
    }

    return implode("\n", $lines);
}

/**
 * Build admin-developer chat message when a developer requests admin guidance.
 */
function build_admin_guidance_request_chat_message($requesterName, $comment = '')
{
    $lines = ['[Admin Review Requested]', $requesterName . ' is asking for suggestions or clarification from admin.'];
    $comment = trim((string)$comment);
    if ($comment !== '') {
        $lines[] = 'Message:';
        $lines[] = $comment;
    }

    return implode("\n", $lines);
}

/**
 * Build admin-developer chat message when admin responds to a guidance request.
 */
function build_admin_guidance_response_chat_message($adminName, $comment = '')
{
    $lines = ['[Admin Review Response]', $adminName . ' responded to the admin review request.'];
    $comment = trim((string)$comment);
    if ($comment !== '') {
        $lines[] = 'Response:';
        $lines[] = $comment;
    }

    return implode("\n", $lines);
}

/**
 * Build admin-developer chat message when admin approves and completes a ticket.
 */
function build_review_approved_chat_message()
{
    return "[Review Approved]\nAdmin marked the ticket as Completed.";
}

/**
 * Build admin-developer chat message when admin returns a ticket to development.
 */
function build_review_returned_chat_message($comment = '')
{
    $lines = ['[Returned to Development]', 'Admin returned the ticket for further development.'];
    $comment = trim((string)$comment);
    if ($comment !== '') {
        $lines[] = 'Comment:';
        $lines[] = $comment;
    }

    return implode("\n", $lines);
}

/**
 * Build a system message for ticket cost estimation updates (admin-client chat).
 */
function build_cost_estimation_chat_message($newCost, $newDeliveryDate, $reason = '')
{
    $lines = [
        '[Ticket Estimate Updated]',
        'Estimated Cost: ' . format_rs_currency((float)$newCost, 2),
        'Delivery: ' . date('d M Y', strtotime($newDeliveryDate)),
    ];

    $reason = trim((string)$reason);
    if ($reason !== '') {
        $lines[] = 'Reason: ' . $reason;
    }

    return implode("\n", $lines);
}

/**
 * Floating chat widget configuration presets.
 */
function floating_chat_config(array $ticket, array $comments, $instance, $channel)
{
    $ticketId = (int)$ticket['id'];
    $configs = [
        'client' => [
            'prefix' => 'client-chat',
            'title' => 'Client Discussion',
            'launcher_label' => 'Client Chat',
            'launcher_icon' => '<i class="ti ti-handshake"></i>',
            'gradient' => 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
            'offset_class' => 'floating-chat-root--client',
        ],
        'admin_dev' => [
            'prefix' => 'admin-dev-chat',
            'title' => 'Developer Chat',
            'launcher_label' => 'Developer Chat',
            'launcher_icon' => '<i class="ti ti-tool"></i>',
            'gradient' => 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)',
            'offset_class' => 'floating-chat-root--admin-dev',
        ],
        'team' => [
            'prefix' => 'team-chat',
            'title' => 'Team Chat',
            'launcher_label' => 'Team Chat',
            'launcher_icon' => '<i class="ti ti-message-circle"></i>',
            'gradient' => 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
            'offset_class' => 'floating-chat-root--team',
        ],
    ];

    $preset = $configs[$instance] ?? $configs['team'];

    return array_merge($preset, [
        'instance' => $instance,
        'channel' => $channel,
        'subtitle' => $instance === 'team'
            ? 'Ticket #' . $ticketId . ' · Admin, developers & client'
            : 'Ticket #' . $ticketId,
        'comments' => $comments,
        'ticket_id' => $ticketId,
        'current_user_id' => (int)($_SESSION['user_id'] ?? 0),
        'poll_url' => route('tickets-comment', ['id' => $ticketId]),
        'post_url' => route('tickets-comment', ['id' => $ticketId]),
    ]);
}

function floating_chat_user_can_access_channel($channel, $role = null, array $ticket = [], $userId = null)
{
    if ($channel === 'client') {
        return can_access_client_chat($role);
    }

    if ($channel === 'admin_dev') {
        return can_access_admin_dev_chat($role, $ticket, $userId);
    }

    return can_access_team_chat($role);
}

/**
 * Format system/timeline messages for the team chat UI.
 */
function format_team_chat_system_message($text)
{
    $text = trim((string)$text);
    $text = preg_replace('/^System Action:\s*/i', '', $text);
    $text = preg_replace('/^\[([^\]]+)\]\s*/', '$1 — ', $text);
    $text = str_replace('**', '', $text);

    return trim($text);
}

function is_team_chat_system_message($text)
{
    $text = trim((string)$text);
    return str_starts_with($text, 'System Action:') || str_starts_with($text, '[');
}

/**
 * Allowed extensions for team chat file uploads.
 */
function team_chat_allowed_extensions()
{
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
}

/**
 * Whether a team chat attachment is an image preview type.
 */
function team_chat_is_image_attachment($fileType, $originalName)
{
    $ext = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($ext, $imageExtensions, true)) {
        return true;
    }

    return is_string($fileType) && str_starts_with(strtolower($fileType), 'image/');
}

/**
 * Resolve MIME type for a team chat attachment from extension.
 */
function team_chat_resolve_mime_type($originalName, $storedType = null)
{
    $ext = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
    ];

    if (isset($map[$ext])) {
        return $map[$ext];
    }

    if (is_string($storedType) && $storedType !== '') {
        return $storedType;
    }

    return 'application/octet-stream';
}

/**
 * Attachment kind for team chat open behavior: image, pdf, document.
 */
function team_chat_attachment_kind($fileType, $originalName)
{
    if (team_chat_is_image_attachment($fileType, $originalName)) {
        return 'image';
    }

    $ext = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        return 'pdf';
    }

    return 'document';
}

/**
 * Build a safe Content-Disposition header for team chat downloads.
 */
function team_chat_content_disposition($originalName, $inline = false)
{
    $disposition = $inline ? 'inline' : 'attachment';
    $asciiFallback = preg_replace('/[^\x20-\x7E]/', '_', (string)$originalName);
    $asciiFallback = str_replace(['"', '\\', "\r", "\n", '&', ';'], '_', $asciiFallback);
    if ($asciiFallback === '') {
        $asciiFallback = 'download';
    }

    return $disposition
        . '; filename="' . $asciiFallback . '"'
        . "; filename*=UTF-8''" . rawurlencode((string)$originalName);
}

/**
 * Public URL for a team chat attachment (local route now; S3-compatible later).
 */
function team_chat_attachment_url($attachmentId, $ticketId, $download = false)
{
    $url = route('tickets-team-chat-attachment', [
        'id' => (int)$ticketId,
        'attachment_id' => (int)$attachmentId,
    ]);

    if ($download) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'download=1';
    }

    return $url;
}

/**
 * Add view/download metadata to a team chat attachment row.
 */
function team_chat_format_attachment(array $attachment, $ticketId)
{
    $attachmentId = (int)($attachment['id'] ?? 0);
    $originalName = $attachment['original_name'] ?? '';
    $fileType = $attachment['file_type'] ?? '';
    $kind = team_chat_attachment_kind($fileType, $originalName);

    return array_merge($attachment, [
        'kind' => $kind,
        'is_image' => $kind === 'image',
        'view_url' => team_chat_attachment_url($attachmentId, $ticketId, false),
        'download_url' => team_chat_attachment_url($attachmentId, $ticketId, true),
        'size_label' => format_file_size($attachment['file_size'] ?? 0),
    ]);
}

/**
 * Attach team chat files to comment records for API/UI responses.
 */
function team_chat_enrich_comments(array $comments, $ticketId)
{
    if (empty($comments)) {
        return $comments;
    }

    require_once __DIR__ . '/../models/TeamChatAttachmentModel.php';
    $attachmentModel = new TeamChatAttachmentModel();
    $commentIds = array_column($comments, 'id');
    $attachmentsByComment = $attachmentModel->getByCommentIds($commentIds);

    foreach ($comments as &$comment) {
        $commentId = (int)($comment['id'] ?? 0);
        $rows = $attachmentsByComment[$commentId] ?? [];
        $comment['attachments'] = array_map(function ($row) use ($ticketId) {
            return team_chat_format_attachment($row, $ticketId);
        }, $rows);
    }
    unset($comment);

    return $comments;
}

/**
 * Whether a user is a protected System Admin account (admin role).
 */
function is_protected_system_admin($user)
{
    return is_array($user) && ($user['role'] ?? '') === 'admin';
}

function system_admin_project_removal_message()
{
    return 'System Admin cannot be removed from a project.';
}

function system_admin_deactivate_message()
{
    return 'System Admin account cannot be deactivated.';
}

/**
 * Simplified display status for a ticket (maps legacy DB statuses).
 */
function ticket_display_status(array $ticket)
{
    $status = TicketWorkflowService::mapToSimplifiedStatus($ticket['status'] ?? '');

    if ($status === 'Initiated') {
        $assignments = get_ticket_assigned_members($ticket);
        if (!empty($assignments)) {
            return 'Processing';
        }
    }

    return $status;
}

/**
 * Bootstrap badge class for a simplified ticket status.
 */
function ticket_display_status_badge_class($displayStatus)
{
    return TicketWorkflowService::getSimplifiedStatusBadgeClass($displayStatus);
}

/**
 * Simplified project statuses shown in UI dropdowns.
 */
function get_project_statuses(): array
{
    return ['Initiated', 'Processing', 'Completed'];
}

/**
 * Map legacy project statuses to simplified values.
 */
function normalize_project_status(string $status): string
{
    static $map = [
        'Proposal Received' => 'Initiated',
        'On Hold' => 'Initiated',
        'Cancelled' => 'Initiated',
        'In Progress' => 'Processing',
        'Maintenance' => 'Processing',
        'Initiated' => 'Initiated',
        'Processing' => 'Processing',
        'Completed' => 'Completed',
    ];

    return $map[$status] ?? 'Initiated';
}

function is_valid_project_status(string $status): bool
{
    return in_array($status, get_project_statuses(), true);
}

/**
 * Bootstrap badge class for a project status.
 */
function project_status_badge_class(string $status): string
{
    switch (normalize_project_status($status)) {
        case 'Initiated':
            return 'bg-warning-subtle text-warning-emphasis border';
        case 'Processing':
            return 'bg-primary-subtle text-primary border';
        case 'Completed':
            return 'bg-success-subtle text-success border';
        default:
            return 'bg-secondary-subtle text-secondary border';
    }
}

/**
 * Display label for a project status (normalized).
 */
function project_display_status(string $status): string
{
    return normalize_project_status($status);
}

/**
 * JSON map of legacy + current project statuses to simplified values (for edit modals).
 */
function project_status_normalize_map_for_js(): string
{
    $statuses = array_unique(array_merge(
        ['Proposal Received', 'On Hold', 'Cancelled', 'In Progress', 'Maintenance'],
        get_project_statuses()
    ));
    $map = [];
    foreach ($statuses as $status) {
        $map[$status] = normalize_project_status($status);
    }

    return json_encode($map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Confirmation message for simplified workflow status changes.
 */
function simplified_workflow_confirm_message($targetStatus)
{
    $messages = [
        'Completed' => 'Are you sure you want to mark this ticket as completed?',
        'Initiated' => 'Are you sure you want to move this ticket back to Initiated?',
        'Processing' => 'Are you sure you want to move this ticket to Processing?',
    ];

    return $messages[$targetStatus] ?? 'Are you sure?';
}

/**
 * Confirmation message for destructive ticket workflow transitions.
 */
function destructive_workflow_confirm_message($targetStatus)
{
    $messages = [
        'Closed' => 'Are you sure you want to close this ticket?',
        'Rejected' => 'Are you sure you want to reject this proposal?',
        'Reopened' => 'Are you sure you want to reopen this ticket?',
        'Resolved' => 'Are you sure you want to mark this ticket as resolved?',
        'On Hold' => 'Are you sure you want to put this ticket on hold?',
        '__commercial_review__' => 'Flag this ticket for commercial review? It will be hidden from the project team.',
    ];

    return $messages[$targetStatus] ?? null;
}

/**
 * Whether the user may edit ticket details (title, description, priority, etc.).
 * Clients cannot edit after submission; admins always can; others only if creator.
 */
function can_edit_ticket($role = null, array $ticket = [])
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    if ($role === 'client') {
        return false;
    }

    if ($role === 'admin') {
        return true;
    }

    $userId = (int)($_SESSION['user_id'] ?? 0);
    return $userId > 0 && (int)($ticket['created_by'] ?? 0) === $userId;
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
 * Valid task workflow statuses.
 */
function get_task_statuses(): array
{
    return ['Pending', 'In Progress', 'Blocked', 'Completed'];
}

function is_valid_task_status(string $status): bool
{
    return in_array($status, get_task_statuses(), true);
}

function default_task_status(): string
{
    return 'Pending';
}

/**
 * Dev/intern assignees use checklist controls instead of a status dropdown.
 */
function uses_task_checklist_status_ui($role = null)
{
    if ($role === null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = $_SESSION['user_role'] ?? '';
    }

    return in_array($role, ['developer', 'intern'], true);
}

/**
 * Allowed status transitions for assignee checklist UI (non-admin).
 */
function get_assignee_task_status_transitions($currentStatus)
{
    switch ($currentStatus) {
        case 'Pending':
            return ['In Progress'];
        case 'In Progress':
            return ['Completed'];
        default:
            return [];
    }
}

function is_assignee_task_status_transition_allowed(string $fromStatus, string $toStatus): bool
{
    return in_array($toStatus, get_assignee_task_status_transitions($fromStatus), true);
}

function task_status_badge_class($status)
{
    switch ($status) {
        case 'Pending':
            return 'bg-info-subtle text-info';
        case 'In Progress':
            return 'bg-primary-subtle text-primary';
        case 'Blocked':
            return 'bg-danger-subtle text-danger';
        case 'Completed':
            return 'bg-success-subtle text-success';
        default:
            return 'bg-secondary-subtle text-secondary';
    }
}

/**
 * Project members who may be assigned tasks (developers and interns).
 */
function filter_task_assignable_members(array $members)
{
    return array_values(array_filter($members, function ($member) {
        return in_array($member['role'] ?? '', ['developer', 'intern'], true);
    }));
}

/**
 * Developers/interns associated with a ticket who may be assigned tasks.
 */
function filter_ticket_task_assignable_members(array $ticket, array $projectMembers = [], array $includeUserIds = [])
{
    $members = get_ticket_visible_team_members($ticket, $projectMembers);
    $memberIds = array_map('intval', array_column($members, 'user_id'));

    foreach ($includeUserIds as $userId) {
        $userId = (int)$userId;
        if ($userId <= 0 || in_array($userId, $memberIds, true)) {
            continue;
        }

        foreach ($projectMembers as $member) {
            if ((int)($member['user_id'] ?? 0) === $userId
                && in_array($member['role'] ?? '', ['developer', 'intern'], true)) {
                $members[] = $member;
                $memberIds[] = $userId;
                break;
            }
        }
    }

    return array_values($members);
}

/**
 * Whether a ticket is visible to project developers and interns.
 */
function is_ticket_visible_to_project_team(array $ticket)
{
    return !empty(get_ticket_assigned_members($ticket));
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

    if (function_exists('is_ajax_request') && is_ajax_request()) {
        json_response(['success' => false, 'message' => 'The requested resource was not found.'], 404);
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

