<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/NotificationService.php';

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

    private function enforceAdmin()
    {
        if (!can_manage_tasks()) {
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
            }
            abort_403();
        }
    }

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

    private function validateTaskAssignee($projectId, $assigneeId, array $ticket = null, array $includeUserIds = [])
    {
        $assigneeId = (int)$assigneeId;
        if ($assigneeId <= 0) {
            return [false, 'A developer or intern must be assigned to this task.'];
        }

        $projectMembers = $this->projectModel->getProjectMembers($projectId);
        if ($ticket) {
            $members = filter_ticket_task_assignable_members($ticket, $projectMembers, $includeUserIds);
        } else {
            $members = filter_task_assignable_members($projectMembers);
        }

        foreach ($members as $member) {
            if ((int)$member['user_id'] === $assigneeId) {
                return [true, $assigneeId];
            }
        }

        return [false, 'Assignee must be a developer or intern assigned to this ticket.'];
    }

    private function getTasksForListing($userRole, $currentUserId, $selectedUserId = null)
    {
        if ($userRole === 'admin') {
            $filterUserId = $selectedUserId !== null && $selectedUserId > 0 ? $selectedUserId : null;
            return [
                'pending' => $this->taskModel->getAllTasks('Pending', $filterUserId),
                'in_progress' => $this->taskModel->getAllTasks('In Progress', $filterUserId),
                'blocked' => $this->taskModel->getAllTasks('Blocked', $filterUserId),
                'completed' => $this->taskModel->getAllTasks('Completed', $filterUserId),
            ];
        }

        return [
            'pending' => $this->taskModel->getTasksByUser($currentUserId, 'Pending'),
            'in_progress' => $this->taskModel->getTasksByUser($currentUserId, 'In Progress'),
            'blocked' => $this->taskModel->getTasksByUser($currentUserId, 'Blocked'),
            'completed' => $this->taskModel->getTasksByUser($currentUserId, 'Completed'),
        ];
    }

    /**
     * List tasks — My Tasks for dev/intern; all tasks for admin.
     */
    public function index()
    {
        $userRole = $_SESSION['user_role'] ?? '';
        $currentUserId = (int)$_SESSION['user_id'];

        if ($userRole === 'client') {
            abort_403();
        }

        $isAdmin = can_manage_tasks($userRole);
        $selectedUserId = null;

        if ($isAdmin) {
            $selectedUserId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
        }

        $grouped = $this->getTasksForListing($userRole, $currentUserId, $selectedUserId);
        $pendingTasks = $grouped['pending'];
        $inProgressTasks = $grouped['in_progress'];
        $blockedTasks = $grouped['blocked'];
        $completedTasks = $grouped['completed'];

        $taskableUsers = $isAdmin ? $this->userModel->getTaskableUsers() : [];
        $pageTitle = $isAdmin && $selectedUserId
            ? 'All Tasks: ' . ($this->userModel->findById($selectedUserId)['full_name'] ?? 'User')
            : ($isAdmin ? 'All Tasks' : 'My Assigned Tasks');

        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/tasks/_list_content.php',
                compact('pendingTasks', 'inProgressTasks', 'blockedTasks', 'completedTasks', 'isAdmin', 'currentUserId', 'selectedUserId'),
                'tasks',
                ['user_id' => $selectedUserId ?? '']
            );
        }

        $view = __DIR__ . '/../views/tasks/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Create a task (admin only)
     */
    public function create()
    {
        $this->enforceAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            $taskName = trim($_POST['task_name'] ?? '');
            $assignedMember = (int)($_POST['assigned_member'] ?? 0);
            $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                if (is_ajax_request()) {
                    json_response(['success' => false, 'message' => 'Ticket not found.']);
                }
                set_flash_message('danger', 'Ticket not found.');
                redirect('tickets');
            }

            if (empty($taskName)) {
                if (is_ajax_request()) {
                    json_response(['success' => false, 'message' => 'Task name cannot be empty.']);
                }
                set_flash_message('danger', 'Task name cannot be empty.');
                redirect('tickets-view', ['id' => $ticketId]);
            }

            [$assigneeValid, $assigneeResult] = $this->validateTaskAssignee($ticket['project_id'], $assignedMember, $ticket);
            if (!$assigneeValid) {
                if (is_ajax_request()) {
                    json_response(['success' => false, 'message' => $assigneeResult]);
                }
                set_flash_message('danger', $assigneeResult);
                redirect('tickets-view', ['id' => $ticketId]);
            }

            $data = [
                'ticket_id'       => $ticketId,
                'task_name'       => $taskName,
                'assigned_member' => $assigneeResult,
                'due_date'        => $dueDate,
                'status'          => 'Pending',
            ];

            $taskId = $this->taskModel->createTask($data);
            if ($taskId) {
                $assignee = $this->userModel->findById($assigneeResult);
                $assigneeName = $assignee ? $assignee['full_name'] : 'Unknown';
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'task_created',
                    "Created task '$taskName' under ticket #$ticketId, assigned to $assigneeName"
                );
                try {
                    (new NotificationService())->notifyTaskAssigned($taskId);
                } catch (Throwable $e) {
                }
                if (is_ajax_request()) {
                    json_response([
                        'success' => true,
                        'message' => 'Task added successfully.',
                    ]);
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

        $ticketId = (int)($_GET['ticket_id'] ?? 0);
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $projectMembers = filter_ticket_task_assignable_members($ticket, $this->projectModel->getProjectMembers($ticket['project_id']));

        $pageTitle = 'Add Task to Ticket #' . $ticket['id'];
        $view = __DIR__ . '/../views/tasks/create.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Edit task details (admin only)
     */
    public function edit()
    {
        $this->enforceAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $task = $this->taskModel->findById($id);

        if (!$task) {
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Task not found.']);
            }
            set_flash_message('danger', 'Task not found.');
            redirect('tickets');
        }

        $ticket = $this->ticketModel->findById((int)$task['ticket_id']);
        $projectMembers = filter_ticket_task_assignable_members(
            $ticket ?: ['id' => (int)$task['ticket_id'], 'project_id' => (int)$task['project_id']],
            $this->projectModel->getProjectMembers($task['project_id']),
            [(int)$task['assigned_member']]
        );

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $assignedMember = (int)($_POST['assigned_member'] ?? 0);
            [$assigneeValid, $assigneeResult] = $this->validateTaskAssignee(
                $task['project_id'],
                $assignedMember,
                $ticket ?: ['id' => (int)$task['ticket_id'], 'project_id' => (int)$task['project_id']],
                [(int)$task['assigned_member']]
            );
            if (!$assigneeValid) {
                if (is_ajax_request()) {
                    json_response(['success' => false, 'message' => $assigneeResult]);
                }
                set_flash_message('danger', $assigneeResult);
                redirect('tasks-edit', ['id' => $id]);
            }

            $data = [
                'task_name'       => trim($_POST['task_name'] ?? ''),
                'assigned_member' => $assigneeResult,
                'due_date'        => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'          => trim($_POST['status'] ?? 'Pending'),
            ];

            if (empty($data['task_name'])) {
                if (is_ajax_request()) {
                    json_response(['success' => false, 'message' => 'Task name cannot be empty.']);
                }
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
                    ]);
                }
                set_flash_message('success', 'Task updated successfully.');
                redirect('tickets-view', ['id' => $task['ticket_id']]);
            }

            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'Error updating task.']);
            }
            set_flash_message('danger', 'Error updating task.');
            redirect('tasks-edit', ['id' => $id]);
        }

        if (is_ajax_request()) {
            json_response([
                'success' => true,
                'task' => $task,
                'projectMembers' => $projectMembers,
            ]);
        }

        $pageTitle = 'Edit Task Details';
        $view = __DIR__ . '/../views/tasks/edit.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Delete a task (admin only, AJAX)
     */
    public function delete()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        if (!can_manage_tasks()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
            exit;
        }

        $taskId = (int)($_POST['task_id'] ?? $_GET['id'] ?? 0);
        $task = $this->taskModel->findById($taskId);

        if (!$task) {
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
            exit;
        }

        if ($this->taskModel->deleteTask($taskId)) {
            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'task_deleted',
                "Deleted task ID $taskId: '{$task['task_name']}'"
            );
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Failed to delete task.']);
        exit;
    }

    /**
     * Update task status (AJAX)
     */
    public function updateStatus()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            exit;
        }

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

        $userRole = $_SESSION['user_role'] ?? '';
        if ($userRole === 'client') {
            echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
            exit;
        }

        if (($_SESSION['user_role'] ?? '') !== 'admin' &&
            !$this->projectModel->isMember($task['project_id'], $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized project access.']);
            exit;
        }

        if (!can_update_task_status($task)) {
            echo json_encode(['success' => false, 'error' => 'You can only update status of tasks assigned to you.']);
            exit;
        }

        $allowedStatuses = ['Pending', 'In Progress', 'Completed', 'Blocked'];
        if (!in_array($status, $allowedStatuses, true)) {
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
            if ($status === 'Completed') {
                try {
                    (new NotificationService())->notifyAdminsTaskCompleted($taskId);
                } catch (Throwable $e) {
                }
            }
            echo json_encode(['success' => true, 'message' => 'Task status updated.', 'status' => $status]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Failed to save changes in database.']);
        exit;
    }
}
