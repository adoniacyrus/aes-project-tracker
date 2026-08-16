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

    private function getTasksForListing($userRole, $currentUserId, $selectedUserId = null, $statusFilter = '')
    {
        $status = $statusFilter !== '' ? $statusFilter : null;

        if ($userRole === 'admin') {
            $filterUserId = $selectedUserId !== null && $selectedUserId > 0 ? $selectedUserId : null;

            return $this->taskModel->getAllTasks($status, $filterUserId);
        }

        return $this->taskModel->getTasksByUser($currentUserId, $status);
    }

    private function resolveAdminTaskStatus($rawStatus, $fallback = 'Pending'): string
    {
        $status = trim((string)$rawStatus);

        return is_valid_task_status($status) ? $status : $fallback;
    }

    private function resolveTaskStatusFilter($rawStatus, bool $statusProvided): string
    {
        if (!$statusProvided) {
            return 'In Progress';
        }

        $status = trim((string)$rawStatus);
        if ($status === '') {
            return 'In Progress';
        }

        $allowed = ['Pending', 'In Progress', 'Blocked', 'Completed'];

        return in_array($status, $allowed, true) ? $status : 'In Progress';
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

        $statusFilter = $this->resolveTaskStatusFilter(
            $_GET['status'] ?? '',
            array_key_exists('status', $_GET)
        );
        $tasks = $this->getTasksForListing($userRole, $currentUserId, $selectedUserId, $statusFilter);
        $statusCounts = $this->taskModel->getTaskStatusCounts($userRole, $currentUserId, $selectedUserId);

        $taskableUsers = $isAdmin ? $this->userModel->getTaskableUsers() : [];
        $pageTitle = $isAdmin && $selectedUserId
            ? 'All Tasks: ' . ($this->userModel->findById($selectedUserId)['full_name'] ?? 'User')
            : ($isAdmin ? 'All Tasks' : 'My Assigned Tasks');

        $myExternalWorkLogs = [];
        $ewlProjects = [];
        $ewlAssignees = [];
        if ($userRole !== 'client') {
            require_once __DIR__ . '/../models/ExternalWorkLogModel.php';
            $ewlModel = new ExternalWorkLogModel();
            $myExternalWorkLogs = $ewlModel->getAssignedLogs($currentUserId);
            $ewlProjects = $this->projectModel->getProjects($currentUserId, $userRole, '', 0, 200, '', 0);
            $ewlAssignees = $this->userModel->getTaskableUsers();
        }

        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/tasks/_list_content.php',
                compact('tasks', 'statusFilter', 'statusCounts', 'isAdmin', 'currentUserId', 'selectedUserId'),
                'tasks',
                ['user_id' => $selectedUserId ?? '', 'status' => $statusFilter]
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
                'status'          => default_task_status(),
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

            $previousAssignee = (int)($task['assigned_member'] ?? 0);
            $assigneeChanged = $previousAssignee !== (int)$assigneeResult;

            $data = [
                'task_name'       => trim($_POST['task_name'] ?? ''),
                'assigned_member' => $assigneeResult,
                'due_date'        => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'          => $assigneeChanged
                    ? default_task_status()
                    : $this->resolveAdminTaskStatus($_POST['status'] ?? $task['status'], $task['status'] ?? default_task_status()),
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
                if ($assigneeChanged) {
                    try {
                        (new NotificationService())->notifyTaskAssigned($id);
                    } catch (Throwable $e) {
                    }
                }
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

        set_flash_message('info', 'Use the edit button to update tasks.');
        redirect('tasks');
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

        if (!can_manage_tasks($userRole)) {
            if (!is_assignee_task_status_transition_allowed($task['status'] ?? '', $status)) {
                echo json_encode(['success' => false, 'error' => 'Tasks must be started before they can be marked done.']);
                exit;
            }
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
