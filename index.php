<?php

session_start();

require_once 'config/config.php';

$page = $_GET['page'] ?? 'login';

switch ($page) {

    case 'login':

        require_once 'app/controllers/AuthController.php';

        $controller = new AuthController();

        $controller->showLogin();

        break;

    case 'dashboard':

        require_once 'app/views/dashboard/index.php';

        break;

    default:

        echo "404 Page Not Found";
}