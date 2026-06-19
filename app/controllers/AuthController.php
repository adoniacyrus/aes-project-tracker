<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthController
{
    public function showLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $this->login();

            return;
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    private function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {

            die('Email and Password are required');
        }

        $userModel = new UserModel();

        $user = $userModel->findByEmail($email);

        if (!$user) {

            die('Invalid Email or Password');
        }

        if ($user['status'] !== 'active') {

            die('Account is inactive');
        }

        if (!password_verify($password, $user['password'])) {

            die('Invalid Email or Password');
        }

        $_SESSION['user_id'] = $user['id'];

        $_SESSION['user_name'] =
            $user['first_name'] . ' ' . $user['last_name'];

        $_SESSION['user_email'] = $user['email'];

        $_SESSION['user_role'] = $user['role'];

        header('Location: ?page=dashboard');

        exit;
    }

    public function logout()
    {
        session_unset();

        session_destroy();

        header('Location: ?page=login');

        exit;
    }    
}