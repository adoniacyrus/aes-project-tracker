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
            header('Location: ?page=login');
            exit;
        }

        // Verify user is still active in the database
        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user || $user['status'] !== 'active') {
            // User deactivated or deleted, force logout
            session_unset();
            session_destroy();
            header('Location: ?page=login&inactive=1');
            exit;
        }

        // Update session info in case details were updated by admin
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
    }
}