<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/PasswordResetModel.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

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
            $user = $this->userModel->findById((int) $_SESSION['user_id']);
            if ($user && !empty($user['is_temp_password'])) {
                redirect('auth-change-password');
            }
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

        if (!empty($user['is_temp_password'])) {
            redirect('auth-change-password');
        }

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
     * Handle forgot password (AJAX from login modal).
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login');
        }

        verify_csrf();

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Please enter a valid email address.']);
            }
            set_flash_message('danger', 'Please enter a valid email address.');
            redirect('login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || $user['status'] !== 'active') {
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'No account was found with this email address.']);
            }
            set_flash_message('danger', 'No account was found with this email address.');
            redirect('login');
        }

        $temporaryPassword = generate_secure_temporary_password();
        $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        if (!$this->userModel->resetPasswordByAdmin($user['id'], $hashedPassword)) {
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Unable to reset password. Please try again.']);
            }
            set_flash_message('danger', 'Unable to reset password. Please try again.');
            redirect('login');
        }

        $notificationService = new NotificationService();
        $emailSent = $notificationService->sendForgotPasswordEmail(
            $user['full_name'],
            $user['email'],
            $temporaryPassword
        );
        unset($temporaryPassword);

        $this->activityLogModel->log(
            $user['id'],
            $email,
            'password_forgot_request',
            'Temporary password issued via forgot-password at ' . date('Y-m-d H:i:s')
        );

        $successMessage = $emailSent
            ? 'A temporary password has been sent to your email address.'
            : 'Temporary password generated, but the email could not be sent.';

        if (is_ajax_request()) {
            json_response([
                'success' => true,
                'message' => $successMessage,
                'email_sent' => $emailSent,
            ]);
        }

        set_flash_message($emailSent ? 'success' : 'warning', $successMessage);
        redirect('login');
    }

    /**
     * Forced password change after login with a temporary password.
     */
    public function changePassword()
    {
        AuthMiddleware::check();

        $userId = (int) $_SESSION['user_id'];
        $user = $this->userModel->findById($userId);

        if (!$user || empty($user['is_temp_password'])) {
            redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                set_flash_message('danger', 'All password fields are required.');
                redirect('auth-change-password');
            }

            if (!password_verify($currentPassword, $user['password'])) {
                set_flash_message('danger', 'Current temporary password is incorrect.');
                redirect('auth-change-password');
            }

            $passwordError = validate_strong_password($newPassword);
            if ($passwordError !== null) {
                set_flash_message('danger', $passwordError);
                redirect('auth-change-password');
            }

            if ($newPassword !== $confirmPassword) {
                set_flash_message('danger', 'New password confirmation does not match.');
                redirect('auth-change-password');
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($this->userModel->clearTemporaryPassword($userId, $hashedPassword)) {
                $this->activityLogModel->log(
                    $userId,
                    $user['email'],
                    'password_changed',
                    'User changed temporary password after login at ' . date('Y-m-d H:i:s')
                );

                set_flash_message('success', 'Password updated successfully.');
                redirect('dashboard');
            }

            set_flash_message('danger', 'Error updating password. Please try again.');
            redirect('auth-change-password');
        }

        require_once __DIR__ . '/../views/auth/change-password.php';
    }

    /**
     * Legacy token-based reset password (unchanged for backward compatibility).
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