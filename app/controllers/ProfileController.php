<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

class ProfileController
{
    private $userModel;
    private $activityLogModel;

    public function __construct()
    {
        // Enforce user authentication
        AuthMiddleware::check();
        
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    /**
     * View and edit own profile
     */
    public function index()
    {
        $userId = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $data = [
                'first_name'   => trim($_POST['first_name'] ?? ''),
                'last_name'    => trim($_POST['last_name'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'designation'  => trim($_POST['designation'] ?? ''),
                'organization' => trim($_POST['organization'] ?? '')
            ];
            
            if (empty($data['first_name']) || empty($data['last_name'])) {
                set_flash_message('danger', 'First Name and Last Name are required.');
                redirect('profile');
            }
            
            if ($this->userModel->updateProfile($userId, $data)) {
                // Refresh name in session
                $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
                
                $this->activityLogModel->log(
                    $userId, 
                    $_SESSION['user_email'], 
                    'profile_updated', 
                    'User updated their own profile details'
                );
                
                set_flash_message('success', 'Profile updated successfully.');
            } else {
                set_flash_message('danger', 'Error updating profile. Please try again.');
            }
            
            redirect('profile');
        }
        
        $user = $this->userModel->findById($userId);
        
        $pageTitle = 'My Profile';
        $view = __DIR__ . '/../views/profile/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Change own password
     */
    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                set_flash_message('danger', 'All password fields are required.');
                redirect('profile-change-password');
            }
            
            $userId = $_SESSION['user_id'];
            $user = $this->userModel->findById($userId);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                set_flash_message('danger', 'Incorrect current password.');
                redirect('profile-change-password');
            }
            
            if (strlen($newPassword) < 6) {
                set_flash_message('danger', 'New password must be at least 6 characters.');
                redirect('profile-change-password');
            }
            
            if ($newPassword !== $confirmPassword) {
                set_flash_message('danger', 'New password confirmation does not match.');
                redirect('profile-change-password');
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($this->userModel->updatePassword($userId, $hashedPassword)) {
                $this->activityLogModel->log(
                    $userId, 
                    $_SESSION['user_email'], 
                    'password_changed', 
                    'User changed their own password'
                );
                
                set_flash_message('success', 'Password changed successfully.');
            } else {
                set_flash_message('danger', 'Error updating password. Please try again.');
            }
            
            redirect('profile-change-password');
        }
        
        $pageTitle = 'Change Password';
        $view = __DIR__ . '/../views/profile/change-password.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
}
