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
        header('Location: ?page=login&expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Generate CSRF Token on session start if not already set
csrf_token();

// Load Middleware
require_once 'app/middleware/AuthMiddleware.php';
require_once 'app/middleware/AdminMiddleware.php';

$page = $_GET['page'] ?? 'login';

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

    // === 404 Fallback ===
    default:
        http_response_code(404);
        echo '<div style="font-family:\'Inter\',sans-serif; text-align:center; padding:50px;">';
        echo '<h1>404 Page Not Found</h1>';
        echo '<p>The requested page could not be found.</p>';
        echo '<a href="?page=dashboard">Back to Dashboard</a>';
        echo '</div>';
        break;
}