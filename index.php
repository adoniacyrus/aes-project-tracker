<?php

session_start();

// Load Core Config & Helpers
require_once 'config/config.php';
require_once 'app/helpers/helpers.php';

// Session Security: Handle Timeout (SESSION_TIMEOUT defined as 1800s in config)
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        redirect('login', ['expired' => 1]);
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Generate CSRF Token on session start if not already set
csrf_token();

// Load Middleware
require_once 'app/middleware/AuthMiddleware.php';
require_once 'app/middleware/AdminMiddleware.php';

// Route parsing: prefer pretty routes, fall back to legacy ?page= query
$requestedPage = $_GET['page'] ?? null;
$path = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$basePath = trim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
if ($basePath !== '' && str_starts_with('/' . $path, '/' . $basePath)) {
    $path = trim(substr($path, strlen($basePath)), '/');
}

if ($requestedPage !== null) {
    $page = $requestedPage;
} else {
    switch (true) {
        case $path === '' || $path === 'auth/login':
            $page = 'login';
            break;
        case $path === 'auth/logout':
            $page = 'logout';
            break;
        case $path === 'auth/forgot-password':
            $page = 'forgot-password';
            break;
        case $path === 'auth/reset-password':
            $page = 'reset-password';
            break;
        case $path === 'dashboard':
            $page = 'dashboard';
            break;
        case $path === 'projects':
            $page = 'projects';
            break;
        case $path === 'projects/create':
            $page = 'projects-create';
            break;
        case preg_match('#^projects/([^/]+)/edit$#', $path, $matches):
            $_GET['project_code'] = $matches[1];
            $page = 'projects-edit';
            break;
        case preg_match('#^projects/([^/]+)/team$#', $path, $matches):
            $_GET['project_code'] = $matches[1];
            $page = 'projects-team';
            break;
        case preg_match('#^projects/([^/]+)/archive$#', $path, $matches):
            $_GET['project_code'] = $matches[1];
            $page = 'projects-archive';
            break;
        case preg_match('#^projects/([^/]+)$#', $path, $matches):
            $_GET['project_code'] = $matches[1];
            $page = 'projects-view';
            break;
        case $path === 'tickets':
            $page = 'tickets';
            break;
        case $path === 'tickets/create':
            $page = 'tickets-create';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/edit$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-edit';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/workflow$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-workflow';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/comment$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-comment';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/discussion$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-discussion';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/internal-discussion$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-internal-discussion';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/forward-approval$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-forward-approval';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/proposal$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-proposal';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/payment$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-payment';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/save-estimation$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-save-estimation';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/assign-team$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-assign-team';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/submit-review$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-submit-review';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/approve-review$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-approve-review';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/return-development$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-return-development';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/reclassify$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-reclassify';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/team-chat/attachment/(\d+)$#', $path, $matches):
            $_GET['id'] = $matches[3];
            $_GET['ticket_id'] = $matches[2];
            $page = 'tickets-team-chat-attachment';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/attachment/delete/(\d+)$#', $path, $matches):
            $_GET['id'] = $matches[3];
            $_GET['ticket_id'] = $matches[2];
            $page = 'tickets-delete-attachment';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/attachment/download/(\d+)$#', $path, $matches):
            $_GET['id'] = $matches[3];
            $_GET['ticket_id'] = $matches[2];
            $page = 'tickets-download-attachment';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)/attachment$#', $path, $matches):
            $_GET['ticket_id'] = $matches[2];
            $page = 'tickets-attachment';
            break;
        case preg_match('#^tickets/([a-zA-Z0-9_-]+)-(\d+)(?:/[^/]+)?$#', $path, $matches):
            $_GET['id'] = $matches[2];
            $page = 'tickets-view';
            break;
        case $path === 'users':
            $page = 'users';
            break;
        case $path === 'users/create':
            $page = 'users-create';
            break;
        case preg_match('#^users/([a-zA-Z0-9\-]+)/edit$#', $path, $matches):
            $_GET['id'] = resolve_user_slug($matches[1]);
            if (!$_GET['id']) {
                abort_404();
            }
            $page = 'users-edit';
            break;
        case preg_match('#^users/([a-zA-Z0-9\-]+)/status/([^/]+)$#', $path, $matches):
            $_GET['id'] = resolve_user_slug($matches[1]);
            if (!$_GET['id']) {
                abort_404();
            }
            $_GET['status'] = $matches[2];
            $page = 'users-status';
            break;
        case preg_match('#^users/([a-zA-Z0-9\-]+)/reset-password$#', $path, $matches):
            $_GET['id'] = resolve_user_slug($matches[1]);
            if (!$_GET['id']) {
                abort_404();
            }
            $page = 'users-admin-reset';
            break;
        case preg_match('#^users/([a-zA-Z0-9\-]+)$#', $path, $matches):
            $_GET['id'] = resolve_user_slug($matches[1]);
            if (!$_GET['id']) {
                abort_404();
            }
            $page = 'users-view';
            break;
        case $path === 'profile':
            $page = 'profile';
            break;
        case $path === 'profile/change-password':
            $page = 'profile-change-password';
            break;
        case $path === 'tasks':
            $page = 'tasks';
            break;
        case $path === 'tasks/create':
            $page = 'tasks-create';
            break;
        case preg_match('#^tasks/(\\d+)/edit$#', $path, $matches):
            $_GET['id'] = $matches[1];
            $page = 'tasks-edit';
            break;
        case preg_match('#^tasks/(\\d+)/status$#', $path, $matches):
            $_GET['id'] = $matches[1];
            $page = 'tasks-status';
            break;
        case preg_match('#^tasks/(\\d+)/delete$#', $path, $matches):
            $_GET['id'] = $matches[1];
            $page = 'tasks-delete';
            break;
        default:
            $page = null;
            break;
    }
}

if ($page === null) {
    abort_404();
}
$_GET['page'] = $page;

switch ($page) {

    // === Authentication Routes ===
    case 'login':
        require_once 'app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->showLogin();
        break;

    case 'logout':
        require_once 'app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'forgot-password':
        require_once 'app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->forgotPassword();
        break;

    case 'reset-password':
        require_once 'app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->resetPassword();
        break;

    // === Dashboard Route ===
    case 'dashboard':
        AuthMiddleware::check();
        require_once 'app/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;

    // === Admin User Management Routes ===
    case 'users':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        $controller->index();
        break;

    case 'users-create':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        $controller->create();
        break;

    case 'users-edit':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        $controller->edit();
        break;

    case 'users-view':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        $controller->view();
        break;

    case 'users-status':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        $controller->toggleStatus();
        break;

    case 'users-admin-reset':
        require_once 'app/controllers/UserController.php';
        $controller = new UserController();
        $controller->adminResetPassword();
        break;

    // === User Self Profile Routes ===
    case 'profile':
        require_once 'app/controllers/ProfileController.php';
        $controller = new ProfileController();
        $controller->index();
        break;

    case 'profile-change-password':
        require_once 'app/controllers/ProfileController.php';
        $controller = new ProfileController();
        $controller->changePassword();
        break;

    // === Project Management Routes ===
    case 'projects':
        require_once 'app/controllers/ProjectController.php';
        $controller = new ProjectController();
        $controller->index();
        break;

    case 'projects-create':
        require_once 'app/controllers/ProjectController.php';
        $controller = new ProjectController();
        $controller->create();
        break;

    case 'projects-edit':
        require_once 'app/controllers/ProjectController.php';
        $controller = new ProjectController();
        $controller->edit();
        break;

    case 'projects-view':
        require_once 'app/controllers/ProjectController.php';
        $controller = new ProjectController();
        $controller->view();
        break;

    case 'projects-archive':
        require_once 'app/controllers/ProjectController.php';
        $controller = new ProjectController();
        $controller->archive();
        break;

    case 'projects-team':
        require_once 'app/controllers/ProjectController.php';
        $controller = new ProjectController();
        $controller->teamMembers();
        break;

    // === Ticket Management Routes ===
    case 'tickets':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->index();
        break;

    case 'tickets-create':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->create();
        break;

    case 'tickets-edit':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->edit();
        break;

    case 'tickets-view':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->view();
        break;

    case 'tickets-workflow':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->transition();
        break;

    case 'tickets-comment':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->addComment();
        break;

    case 'tickets-discussion':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->addDiscussion();
        break;

    case 'tickets-internal-discussion':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->addInternalDiscussion();
        break;

    case 'tickets-forward-approval':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->forwardForApproval();
        break;

    case 'tickets-proposal':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->sendProposal();
        break;

    case 'tickets-payment':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->confirmPayment();
        break;

    case 'tickets-save-estimation':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->saveEstimation();
        break;

    case 'tickets-assign-team':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->assignTeam();
        break;

    case 'tickets-submit-review':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->submitForReview();
        break;

    case 'tickets-approve-review':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->approveReview();
        break;

    case 'tickets-return-development':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->returnToDevelopment();
        break;

    case 'tickets-reclassify':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->reclassify();
        break;

    case 'tickets-attachment':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->addAttachment();
        break;

    case 'tickets-delete-attachment':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->deleteAttachment();
        break;

    case 'tickets-download-attachment':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->downloadAttachment();
        break;

    case 'tickets-team-chat-attachment':
        require_once 'app/controllers/TicketController.php';
        $controller = new TicketController();
        $controller->downloadTeamChatAttachment();
        break;

    // === Task Management Routes ===
    case 'tasks':
        require_once 'app/controllers/TaskController.php';
        $controller = new TaskController();
        $controller->index();
        break;

    case 'tasks-create':
        require_once 'app/controllers/TaskController.php';
        $controller = new TaskController();
        $controller->create();
        break;

    case 'tasks-edit':
        require_once 'app/controllers/TaskController.php';
        $controller = new TaskController();
        $controller->edit();
        break;

    case 'tasks-status':
        require_once 'app/controllers/TaskController.php';
        $controller = new TaskController();
        $controller->updateStatus();
        break;

    case 'tasks-delete':
        require_once 'app/controllers/TaskController.php';
        $controller = new TaskController();
        $controller->delete();
        break;

    // === 404 Fallback ===
    default:
        abort_404();
        break;
}