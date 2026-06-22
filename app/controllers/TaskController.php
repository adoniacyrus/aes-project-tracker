<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

class TaskController
{
    private $taskModel;
    private $ticketModel;
    private $projectModel;
    private $userModel;
    private $activityLogModel;

    public function __construct()
    {
        AuthMiddleware::check();

        $this->taskModel = new TaskModel();
        $this->ticketModel = new TicketModel();
        $this->projectModel = new ProjectModel();
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    /**
     * Helper to verify if the user has access to a project
     */
    private function checkProjectAccess($projectId)
    {
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            return true;
        }

        if (!$this->projectModel->isMember($projectId, $_SESSION['user_id'])) {
            abort_403();
        }
        return true;
    }

    /**
     * List all tasks assigned to the logged-in user
     */
    public function index()
    {
        // Clients shouldn't access personal checklist task pages
        if (($_SESSION['user_role'] ?? '') === 'client') {
            abort_403();
        }

        $selectedUserId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : (int)$_SESSION['user_id'];
        
        $selectedUser = $this->userModel->findById($selectedUserId);
        if (!$selectedUser || $selectedUser['role'] === 'client') {
            $selectedUserId = (int)$_SESSION['user_id'];
            $selectedUser = $this->userModel->findById($selectedUserId);
        }
        
        $pendingTasks = $this->taskModel->getTasksByUser($selectedUserId, 'Pending');
        $inProgressTasks = $this->taskModel->getTasksByUser($selectedUserId, 'In Progress');
        $completedTasks = $this->taskModel->getTasksByUser($selectedUserId, 'Completed');
        $blockedTasks = $this->taskModel->getTasksByUser($selectedUserId, 'Blocked');

        // Fetch taskable users list for dropdown filter
        $taskableUsers = $this->userModel->getTaskableUsers();

        $pageTitle = ($selectedUserId === (int)$_SESSION['user_id']) 
            ? 'My Assigned Tasks' 
            : 'Assigned Tasks: ' . $selectedUser['full_name'];

        $view = __DIR__ . '/../views/tasks/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
    /**
     * Create a task
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $taskName = trim($_POST['task_name'] ?? '');
            $assignedMember = !empty($_POST['assigned_member']) ? (int)$_POST['assigned_member'] : null;
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                set_flash_message('danger', 'Ticket not found.');
                redirect('tickets');
            }

            $this->checkProjectAccess($ticket['project_id']);

            if (empty($taskName)) {
                set_flash_message('danger', 'Task name cannot be empty.');
                redirect('tickets-view', ['id' => $ticketId]);
            }

            $data = [
                'ticket_id'       => $ticketId,
                'task_name'       => $taskName,
                'assigned_member' => $assignedMember,
                'due_date'        => $dueDate,
                'status'          => 'Pending'
            ];

            $taskId = $this->taskModel->createTask($data);
            if ($taskId) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'task_created',
                    "Created task checklist item: '$taskName' under ticket #$ticketId"
                );
                if (is_ajax_request()) {
                    $payload = [
                        'success' => true,
                        'message' => 'Task added successfully.',
                    ];
                    if (!empty($_POST['from_ticket_view'])) {
                        $payload['task'] = [
                            'id' => $taskId,
                            'task_name' => $taskName,
                            'status' => 'Pending',
                        ];
                    } else {
                        $payload['redirect'] = route('tickets-view', ['id' => $ticketId]);
                        $payload['allowRedirect'] = true;
                    }
                    json_response($payload);
                }
                set_flash_message('success', 'Task added successfully.');
            } else {
                if (is_ajax_request()) {
                    json_response(['success' => false, 'message' => 'Failed to create task.']);
                }
                set_flash_message('danger', 'Failed to create task.');
            }

            redirect('tickets-view', ['id' => $ticketId]);
        }

        // Handle GET request to render app/views/tasks/create.php
        $ticketId = (int)($_GET['ticket_id'] ?? 0);
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkProjectAccess($ticket['project_id']);

        // Fetch project team members (for assignment dropdown)
        $projectMembers = $this->projectModel->getProjectMembers($ticket['project_id']);

        $pageTitle = 'Add Task to Ticket #' . $ticket['id'];
        $view = __DIR__ . '/../views/tasks/create.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Edit task details
     */
    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $task = $this->taskModel->findById($id);

        if (!$task) {
            set_flash_message('danger', 'Task not found.');
            redirect('tickets');
        }

        $this->checkProjectAccess($task['project_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $data = [
                'task_name'       => trim($_POST['task_name'] ?? ''),
                'assigned_member' => !empty($_POST['assigned_member']) ? (int)$_POST['assigned_member'] : null,
                'due_date'        => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'          => trim($_POST['status'] ?? 'Pending')
            ];

            if (empty($data['task_name'])) {
                set_flash_message('danger', 'Task name cannot be empty.');
                redirect('tasks-edit', ['id' => $id]);
            }

            if ($this->taskModel->updateTask($id, $data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'task_updated',
                    "Updated task ID $id: '{$data['task_name']}'"
                );
                if (is_ajax_request()) {
                    json_response([
                        'success' => true,
                        'message' => 'Task updated successfully.',
                        'redirect' => route('tickets-view', ['id' => $task['ticket_id']]),
                        'allowRedirect' => true,
                    ]);
                }
                set_flash_message('success', 'Task updated successfully.');
                redirect('tickets-view', ['id' => $task['ticket_id']]);
            } else {
                set_flash_message('danger', 'Error updating task.');
                redirect('tasks-edit', ['id' => $id]);
            }
        }

        // Fetch project team members (for assignment dropdown)
        $projectMembers = $this->projectModel->getProjectMembers($task['project_id']);

        $pageTitle = 'Edit Task Details';
        $view = __DIR__ . '/../views/tasks/edit.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Update task status (AJAX handler)
     */
    public function updateStatus()
    {
        // Enforce JSON header
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            exit;
        }

        // Validate CSRF manually for AJAX call (matching helpers validation)
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token.']);
            exit;
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            echo json_encode(['success' => false, 'error' => 'Task not found.']);
            exit;
        }

        // Check project authorization
        if (($_SESSION['user_role'] ?? '') !== 'admin' && 
            !$this->projectModel->isMember($task['project_id'], $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized project access.']);
            exit;
        }

        // Validate status
        $allowedStatuses = ['Pending', 'In Progress', 'Completed', 'Blocked'];
        if (!in_array($status, $allowedStatuses)) {
            echo json_encode(['success' => false, 'error' => 'Invalid status option.']);
            exit;
        }

        if ($this->taskModel->updateStatus($taskId, $status)) {
            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'task_status_updated',
                "Updated status of task ID $taskId to: $status"
            );
            echo json_encode(['success' => true, 'message' => 'Task status updated.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save changes in database.']);
            exit;
        }
    }
}
