<?php

session_start();

require_once 'config/config.php';

require_once 'app/middleware/AuthMiddleware.php';

$page = $_GET['page'] ?? 'login';

switch ($page) {

    case 'login':

        require_once 'app/controllers/AuthController.php';

        $controller = new AuthController();

        $controller->showLogin();

        break;

    case 'dashboard':

        AuthMiddleware::check();

        require_once 'app/controllers/DashboardController.php';

        $controller = new DashboardController();

        $controller->index();

        break;

    case 'logout':

        require_once 'app/controllers/AuthController.php';

        $controller = new AuthController();

        $controller->logout();

        break;


    default:

        echo "404 Page Not Found";
}