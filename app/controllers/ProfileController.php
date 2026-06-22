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
        AuthMiddleware::check();
        
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    private function isAjax()
    {
        return is_ajax_request();
    }

    public function index()
    {
        $userId = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $data = [
                'full_name'    => trim($_POST['full_name'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'designation'  => trim($_POST['designation'] ?? ''),
                'organization' => trim($_POST['organization'] ?? '')
            ];
            
            if (empty($data['full_name'])) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Full Name is required.']);
                }
                set_flash_message('danger', 'Full Name is required.');
                redirect('profile');
            }
            
            if ($this->userModel->updateProfile($userId, $data)) {
                $_SESSION['user_name'] = $data['full_name'];
                
                $this->activityLogModel->log(
                    $userId, 
                    $_SESSION['user_email'], 
                    'profile_updated', 
                    'User updated their own profile details'
                );
                
                if ($this->isAjax()) {
                    json_response([
                        'success' => true,
                        'message' => 'Profile updated successfully.',
                        'user' => array_merge($data, ['id' => $userId]),
                    ]);
                }
                set_flash_message('success', 'Profile updated successfully.');
            } else {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Error updating profile. Please try again.']);
                }
                set_flash_message('danger', 'Error updating profile. Please try again.');
            }
            
            redirect('profile');
        }
        
        $user = $this->userModel->findById($userId);
        
        $pageTitle = 'My Profile';
        $view = __DIR__ . '/../views/profile/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'All password fields are required.']);
                }
                set_flash_message('danger', 'All password fields are required.');
                redirect('profile-change-password');
            }
            
            $userId = $_SESSION['user_id'];
            $user = $this->userModel->findById($userId);
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Incorrect current password.']);
                }
                set_flash_message('danger', 'Incorrect current password.');
                redirect('profile-change-password');
            }
            
            if (strlen($newPassword) < 6) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'New password must be at least 6 characters.']);
                }
                set_flash_message('danger', 'New password must be at least 6 characters.');
                redirect('profile-change-password');
            }
            
            if ($newPassword !== $confirmPassword) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'New password confirmation does not match.']);
                }
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
                
                if ($this->isAjax()) {
                    json_response(['success' => true, 'message' => 'Password changed successfully.']);
                }
                set_flash_message('success', 'Password changed successfully.');
            } else {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Error updating password. Please try again.']);
                }
                set_flash_message('danger', 'Error updating password. Please try again.');
            }
            
            redirect('profile-change-password');
        }
        
        $pageTitle = 'Change Password';
        $view = __DIR__ . '/../views/profile/change-password.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
}
