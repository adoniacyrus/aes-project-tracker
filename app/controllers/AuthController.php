<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/PasswordResetModel.php';

class AuthController
{
    private $userModel;
    private $activityLogModel;
    private $passwordResetModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->passwordResetModel = new PasswordResetModel();
    }

    /**
     * Show & handle login
     */
    public function showLogin()
    {
        if (isset($_SESSION['user_id'])) {
            redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $this->login();
            return;
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Handle login logic
     */
    private function login()
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            set_flash_message('danger', 'Email and Password are required.');
            redirect('login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            // Log failed login
            $this->activityLogModel->log(null, $email, 'login_failed', 'Attempt with non-existent email');
            set_flash_message('danger', 'Invalid Email or Password.');
            redirect('login');
        }

        if ($user['status'] !== 'active') {
            $this->activityLogModel->log($user['id'], $email, 'login_failed_inactive', 'Attempt by inactive user');
            set_flash_message('danger', 'Your account is currently inactive. Please contact support.');
            redirect('login');
        }

        if (!password_verify($password, $user['password'])) {
            $this->activityLogModel->log($user['id'], $email, 'login_failed', 'Incorrect password attempt');
            set_flash_message('danger', 'Invalid Email or Password.');
            redirect('login');
        }

        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        $this->userModel->updateLastLogin($user['id']);
        $this->activityLogModel->log($user['id'], $user['email'], 'login_success', 'User logged in successfully');

        redirect('dashboard');
    }

    /**
     * Handle logout logic
     */
    public function logout()
    {
        if (isset($_SESSION['user_id'])) {
            $this->activityLogModel->log($_SESSION['user_id'], $_SESSION['user_email'], 'logout', 'User logged out');
        }

        session_unset();
        session_destroy();
        
        // Start a fresh session to hold the logout flash message
        session_start();
        set_flash_message('success', 'You have been logged out successfully.');
        redirect('login');
    }

    /**
     * Show & handle forgot password
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                set_flash_message('danger', 'Email address is required.');
                redirect('forgot-password');
            }

            $user = $this->userModel->findByEmail($email);
            
            // Standard generic message for security
            set_flash_message('success', 'If the email exists in our system, password reset instructions have been sent.');
            
            if ($user && $user['status'] === 'active') {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $this->passwordResetModel->createToken($email, $token);
                
                // Write email details to log for local simulation
                $resetLink = route('reset-password', ['email' => $email, 'token' => $token]);
                $logDir = __DIR__ . '/../../storage/logs';
                if (!file_exists($logDir)) {
                    mkdir($logDir, 0777, true);
                }
                
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Password reset requested for $email. Reset Link: $resetLink\r\n";
                file_put_contents($logDir . '/mail.log', $logMessage, FILE_APPEND);
                
                // Track request link in session for user convenience in local dev
                $_SESSION['simulated_reset_link'] = $resetLink;
                
                $this->activityLogModel->log($user['id'], $email, 'password_reset_request', 'Reset link requested');
            } else {
                $this->activityLogModel->log(null, $email, 'password_reset_request_failed', 'Reset requested for non-existent/inactive email');
            }
            
            redirect('forgot-password');
        }

        require_once __DIR__ . '/../views/auth/forgot-password.php';
    }

    /**
     * Show & handle reset password
     */
    public function resetPassword()
    {
        $email = trim($_GET['email'] ?? '');
        $token = trim($_GET['token'] ?? '');

        if (empty($email) || empty($token)) {
            set_flash_message('danger', 'Invalid password reset request.');
            redirect('forgot-password');
        }

        // Validate token
        $isValid = $this->passwordResetModel->validateToken($email, $token);
        if (!$isValid) {
            set_flash_message('danger', 'The password reset token is invalid or has expired.');
            redirect('forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $password = trim($_POST['password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if (strlen($password) < 6) {
                set_flash_message('danger', 'Password must be at least 6 characters long.');
                redirect('reset-password', ['email' => $email, 'token' => $token]);
            }

            if ($password !== $confirmPassword) {
                set_flash_message('danger', 'Passwords do not match.');
                redirect('reset-password', ['email' => $email, 'token' => $token]);
            }

            $user = $this->userModel->findByEmail($email);
            if ($user) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $this->userModel->updatePassword($user['id'], $hashedPassword);
                
                // Clear reset token
                $this->passwordResetModel->deleteToken($email);
                
                // Log activity
                $this->activityLogModel->log($user['id'], $email, 'password_reset_success', 'Password reset successfully via token');
                
                set_flash_message('success', 'Your password has been reset successfully. You can now login with your new password.');
                redirect('login');
            } else {
                set_flash_message('danger', 'Error updating password. User not found.');
                redirect('forgot-password');
            }
        }

        require_once __DIR__ . '/../views/auth/reset-password.php';
    }
}