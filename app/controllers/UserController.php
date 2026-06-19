<?php

require_once __DIR__ . '/../middleware/AdminMiddleware.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

class UserController
{
    private $userModel;
    private $activityLogModel;

    public function __construct()
    {
        // Enforce admin-only access
        AdminMiddleware::check();
        
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    /**
     * List all users with search and pagination
     */
    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $pageNum = (int)($_GET['p'] ?? 1);
        if ($pageNum < 1) {
            $pageNum = 1;
        }
        
        $limit = 10;
        $offset = ($pageNum - 1) * $limit;
        
        $users = $this->userModel->getUsers($search, $offset, $limit);
        $totalUsers = $this->userModel->getUsersCount($search);
        $totalPages = ceil($totalUsers / $limit);
        
        $pageTitle = 'Manage Users';
        $view = __DIR__ . '/../views/users/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * View user details and activity logs
     */
    public function view()
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            set_flash_message('danger', 'User not found.');
            header('Location: ?page=users');
            exit;
        }
        
        // Fetch recent logs for this specific user
        $userLogs = $this->activityLogModel->getLogsByUser($id, 15);
        
        $pageTitle = 'View User: ' . $user['first_name'] . ' ' . $user['last_name'];
        $view = __DIR__ . '/../views/users/view.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Create a new user
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $data = [
                'first_name'   => trim($_POST['first_name'] ?? ''),
                'last_name'    => trim($_POST['last_name'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'password'     => trim($_POST['password'] ?? ''),
                'role'         => trim($_POST['role'] ?? 'developer'),
                'designation'  => trim($_POST['designation'] ?? ''),
                'organization' => trim($_POST['organization'] ?? 'AES'),
                'status'       => trim($_POST['status'] ?? 'active')
            ];
            
            // Validation
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password'])) {
                set_flash_message('danger', 'Please fill in all required fields.');
                header('Location: ?page=users-create');
                exit;
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                set_flash_message('danger', 'Invalid email address format.');
                header('Location: ?page=users-create');
                exit;
            }

            if (strlen($data['password']) < 6) {
                set_flash_message('danger', 'Password must be at least 6 characters.');
                header('Location: ?page=users-create');
                exit;
            }
            
            // Check if email already exists
            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing) {
                set_flash_message('danger', 'Email address is already in use by another user.');
                header('Location: ?page=users-create');
                exit;
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            if ($this->userModel->createUser($data)) {
                $newUser = $this->userModel->findByEmail($data['email']);
                $newUserId = $newUser ? $newUser['id'] : null;
                
                $this->activityLogModel->log(
                    $_SESSION['user_id'], 
                    $_SESSION['user_email'], 
                    'user_created', 
                    "Created user: {$data['email']} (Role: {$data['role']})"
                );
                
                set_flash_message('success', 'User created successfully.');
                header('Location: ?page=users');
                exit;
            } else {
                set_flash_message('danger', 'Error creating user. Please try again.');
                header('Location: ?page=users-create');
                exit;
            }
        }
        
        $pageTitle = 'Add New User';
        $view = __DIR__ . '/../views/users/create.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Edit user details
     */
    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            set_flash_message('danger', 'User not found.');
            header('Location: ?page=users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $data = [
                'first_name'   => trim($_POST['first_name'] ?? ''),
                'last_name'    => trim($_POST['last_name'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'role'         => trim($_POST['role'] ?? 'developer'),
                'designation'  => trim($_POST['designation'] ?? ''),
                'organization' => trim($_POST['organization'] ?? 'AES')
            ];
            
            // Validation
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
                set_flash_message('danger', 'Please fill in all required fields.');
                header("Location: ?page=users-edit&id=$id");
                exit;
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                set_flash_message('danger', 'Invalid email address format.');
                header("Location: ?page=users-edit&id=$id");
                exit;
            }
            
            // Check if email already exists on another user
            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                set_flash_message('danger', 'Email address is already in use by another user.');
                header("Location: ?page=users-edit&id=$id");
                exit;
            }
            
            if ($this->userModel->updateUser($id, $data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'], 
                    $_SESSION['user_email'], 
                    'user_updated', 
                    "Updated user profile for ID $id ({$data['email']})"
                );
                
                set_flash_message('success', 'User profile updated successfully.');
                header("Location: ?page=users-view&id=$id");
                exit;
            } else {
                set_flash_message('danger', 'Error updating user profile. Please try again.');
                header("Location: ?page=users-edit&id=$id");
                exit;
            }
        }
        
        $pageTitle = 'Edit User: ' . $user['first_name'] . ' ' . $user['last_name'];
        $view = __DIR__ . '/../views/users/edit.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Toggle status (Active / Inactive)
     */
    public function toggleStatus()
    {
        $id = (int)($_GET['id'] ?? 0);
        $status = trim($_GET['status'] ?? '');
        
        if ($id === (int)$_SESSION['user_id']) {
            set_flash_message('danger', 'You cannot deactivate your own account.');
            header('Location: ?page=users');
            exit;
        }
        
        if (!in_array($status, ['active', 'inactive'])) {
            set_flash_message('danger', 'Invalid status specified.');
            header('Location: ?page=users');
            exit;
        }
        
        $user = $this->userModel->findById($id);
        if ($user) {
            $this->userModel->updateStatus($id, $status);
            
            $this->activityLogModel->log(
                $_SESSION['user_id'], 
                $_SESSION['user_email'], 
                'user_status_changed', 
                "Changed status for user ID $id to: $status"
            );
            
            set_flash_message('success', 'User status updated successfully.');
        } else {
            set_flash_message('danger', 'User not found.');
        }
        
        header('Location: ?page=users');
        exit;
    }

    /**
     * Admin force resets password of a user
     */
    public function adminResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $userId = (int)($_POST['user_id'] ?? 0);
            $password = trim($_POST['password'] ?? '');
            
            if (empty($password) || strlen($password) < 6) {
                set_flash_message('danger', 'Password must be at least 6 characters.');
                header("Location: ?page=users-edit&id=$userId");
                exit;
            }
            
            $user = $this->userModel->findById($userId);
            if ($user) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $this->userModel->updatePassword($userId, $hashedPassword);
                
                $this->activityLogModel->log(
                    $_SESSION['user_id'], 
                    $_SESSION['user_email'], 
                    'user_password_reset_by_admin', 
                    "Reset password for user ID $userId ({$user['email']})"
                );
                
                set_flash_message('success', 'Password reset successfully.');
            } else {
                set_flash_message('danger', 'User not found.');
            }
            
            header("Location: ?page=users-view&id=$userId");
            exit;
        }
        
        header('Location: ?page=users');
        exit;
    }
}
