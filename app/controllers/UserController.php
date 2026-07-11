<?php

require_once __DIR__ . '/../middleware/AdminMiddleware.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/NotificationService.php';

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
     * Check if request is AJAX
     */
    private function isAjax()
    {
        return is_ajax_request();
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
        
        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/users/_list_content.php',
                compact('users', 'search', 'pageNum', 'totalPages', 'totalUsers'),
                'users',
                ['q' => $search, 'p' => $pageNum]
            );
        }
        
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
            redirect('users');
        }
        
        // Fetch recent logs for this specific user
        $userLogs = $this->activityLogModel->getLogsByUser($id, 15);
        
        $pageTitle = 'View User: ' . $user['full_name'];
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
                'full_name'    => normalize_person_name($_POST['full_name'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'role'         => trim($_POST['role'] ?? 'developer'),
                'designation'  => trim($_POST['designation'] ?? ''),
                'organization' => trim($_POST['organization'] ?? 'AES'),
                'status'       => trim($_POST['status'] ?? 'active'),
            ];
            
            // Validation
            if (empty($data['full_name']) || empty($data['email'])) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Please fill in all required fields.']);
                }
                set_flash_message('danger', 'Please fill in all required fields.');
                redirect('users');
            }

            $nameError = validate_person_name($data['full_name']);
            if ($nameError !== null) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => $nameError]);
                }
                set_flash_message('danger', $nameError);
                redirect('users');
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Invalid email address format.']);
                }
                set_flash_message('danger', 'Invalid email address format.');
                redirect('users');
            }
            
            // Check if email already exists
            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Email address is already in use by another user.']);
                }
                set_flash_message('danger', 'Email address is already in use by another user.');
                redirect('users');
            }
            
            $temporaryPassword = generate_secure_temporary_password();
            $data['password'] = password_hash($temporaryPassword, PASSWORD_DEFAULT);
            $data['is_temp_password'] = 1;
            
            if ($this->userModel->createUser($data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'], 
                    $_SESSION['user_email'], 
                    'user_created', 
                    "Created user: {$data['email']} (Role: {$data['role']})"
                );

                $notificationService = new NotificationService();
                $emailSent = $notificationService->sendWelcomeEmail(
                    $data['full_name'],
                    $data['email'],
                    $temporaryPassword
                );
                unset($temporaryPassword);

                $successMessage = $emailSent
                    ? 'User created successfully. Login credentials have been emailed.'
                    : 'User created successfully. However, the welcome email could not be sent.';
                
                if ($this->isAjax()) {
                    json_response([
                        'success' => true,
                        'message' => $successMessage,
                        'email_sent' => $emailSent,
                    ]);
                }
                set_flash_message('success', $successMessage);
                redirect('users');
            } else {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error creating user. Please try again.']);
                    exit;
                }
                set_flash_message('danger', 'Error creating user. Please try again.');
                redirect('users');
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
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }
            set_flash_message('danger', 'User not found.');
            redirect('users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            
            $data = [
                'full_name'    => normalize_person_name($_POST['full_name'] ?? ''),
                'email'        => trim($_POST['email'] ?? ''),
                'phone'        => trim($_POST['phone'] ?? ''),
                'role'         => trim($_POST['role'] ?? 'developer'),
                'designation'  => trim($_POST['designation'] ?? ''),
                'organization' => trim($_POST['organization'] ?? 'AES')
            ];
            
            // Validation
            if (empty($data['full_name']) || empty($data['email'])) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Please fill in all required fields.']);
                }
                set_flash_message('danger', 'Please fill in all required fields.');
                redirect('users');
            }

            $nameError = validate_person_name($data['full_name']);
            if ($nameError !== null) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => $nameError]);
                }
                set_flash_message('danger', $nameError);
                redirect('users');
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Invalid email address format.']);
                }
                set_flash_message('danger', 'Invalid email address format.');
                redirect('users');
            }
            
            // Check if email already exists on another user
            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing && (int)$existing['id'] !== $id) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Email address is already in use by another user.']);
                }
                set_flash_message('danger', 'Email address is already in use by another user.');
                redirect('users');
            }
            
            try {
                if ($this->userModel->updateUser($id, $data)) {
                    $this->activityLogModel->log(
                        $_SESSION['user_id'], 
                        $_SESSION['user_email'], 
                        'user_updated', 
                        "Updated user profile for ID $id ({$data['email']})"
                    );
                    
                    if ($this->isAjax()) {
                        json_response([
                            'success' => true,
                            'message' => 'User profile updated successfully.',
                        ]);
                    }
                    set_flash_message('success', 'User profile updated successfully.');
                    redirect('users-view', ['id' => $id]);
                }

                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Error updating user profile. Please try again.']);
                }
                set_flash_message('danger', 'Error updating user profile. Please try again.');
                redirect('users');
            } catch (Throwable $e) {
                error_log('UserController::edit failed for user #' . $id . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'Error updating user profile. Please try again.']);
                }
                set_flash_message('danger', 'Error updating user profile. Please try again.');
                redirect('users');
            }
        }
        
        $pageTitle = 'Edit User: ' . $user['full_name'];
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
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
                exit;
            }
            set_flash_message('danger', 'You cannot deactivate your own account.');
            redirect('users');
        }
        
        if (!in_array($status, ['active', 'inactive'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid status specified.']);
                exit;
            }
            set_flash_message('danger', 'Invalid status specified.');
            redirect('users');
        }
        
        $user = $this->userModel->findById($id);
        if (!$user) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }
            set_flash_message('danger', 'User not found.');
            redirect('users');
        }

        if ($status === 'inactive' && is_protected_system_admin($user)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => system_admin_deactivate_message()]);
                exit;
            }
            set_flash_message('danger', system_admin_deactivate_message());
            redirect('users');
        }

        if ($user) {
            $this->userModel->updateStatus($id, $status);
            
            $this->activityLogModel->log(
                $_SESSION['user_id'], 
                $_SESSION['user_email'], 
                'user_status_changed', 
                "Changed status for user ID $id to: $status"
            );
            
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'User status updated successfully.']);
                exit;
            }
            set_flash_message('success', 'User status updated successfully.');
        }
        
        redirect('users');
    }

    /**
     * Admin force resets password of a user
     */
    public function adminResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('users');
        }

        verify_csrf();

        $userId = (int) ($_POST['user_id'] ?? $_GET['id'] ?? 0);

        if ($userId === (int) $_SESSION['user_id']) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'You cannot reset your own password from this action.']);
            }
            set_flash_message('danger', 'You cannot reset your own password from this action.');
            redirect('users');
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'User not found.']);
            }
            set_flash_message('danger', 'User not found.');
            redirect('users');
        }

        $temporaryPassword = generate_secure_temporary_password();
        $hashedPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        if (!$this->userModel->resetPasswordByAdmin($userId, $hashedPassword)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Error resetting password. Please try again.']);
            }
            set_flash_message('danger', 'Error resetting password. Please try again.');
            redirect('users');
        }

        $adminEmail = $_SESSION['user_email'] ?? 'unknown';
        $this->activityLogModel->log(
            $_SESSION['user_id'],
            $adminEmail,
            'user_password_reset_by_admin',
            "Password reset by {$adminEmail} for {$user['full_name']} ({$user['email']}, User ID {$userId}) at " . date('Y-m-d H:i:s')
        );

        $notificationService = new NotificationService();
        $emailSent = $notificationService->sendAdminPasswordResetEmail(
            $user['full_name'],
            $user['email'],
            $temporaryPassword
        );
        unset($temporaryPassword);

        $successMessage = $emailSent
            ? 'Password reset successfully. Login credentials have been emailed to the user.'
            : 'Password reset successfully. However, the email could not be sent.';

        if ($this->isAjax()) {
            json_response([
                'success' => true,
                'message' => $successMessage,
                'email_sent' => $emailSent,
            ]);
        }

        set_flash_message('success', $successMessage);
        redirect('users');
    }
}
