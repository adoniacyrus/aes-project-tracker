<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthMiddleware
{
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            redirect('login');
        }

        // Verify user is still active in the database
        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user || $user['status'] !== 'active') {
            // User deactivated or deleted, force logout
            session_unset();
            session_destroy();
            redirect('login', ['inactive' => 1]);
        }

        // Update session info in case details were updated by admin
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        require_once __DIR__ . '/TempPasswordMiddleware.php';
        TempPasswordMiddleware::enforce($user);
    }
}