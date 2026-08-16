<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/ExternalWorkLogModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

class ExternalWorkLogController
{
    private $logModel;
    private $projectModel;
    private $userModel;
    private $activityLogModel;

    public function __construct()
    {
        AuthMiddleware::check();

        $this->logModel = new ExternalWorkLogModel();
        $this->projectModel = new ProjectModel();
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    public function index()
    {
        $this->denyClient();

        $userRole = $_SESSION['user_role'] ?? '';
        $userId = (int)$_SESSION['user_id'];
        $statusFilter = trim((string)($_GET['status'] ?? ''));
        if ($statusFilter !== '' && !in_array($statusFilter, external_work_log_statuses(), true)) {
            $statusFilter = '';
        }

        $filters = [];
        if ($statusFilter !== '') {
            $filters['status'] = $statusFilter;
        }

        $logs = $this->logModel->getLogsForUser($userRole, $userId, $filters);
        $stats = $this->logModel->getDashboardStats($userRole, $userId);
        $projects = $this->projectModel->getProjects($userId, $userRole, '', 0, 200, '', 0);
        $assignees = $this->userModel->getTaskableUsers();
        $canCreate = can_create_external_work_log($userRole);
        $canManage = can_manage_external_work_logs($userRole);
        $ewlRefreshTarget = '#external-work-logs-ajax-content';

        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/external-work-logs/_list_content.php',
                compact('logs', 'stats', 'canCreate', 'canManage', 'userId', 'userRole', 'ewlRefreshTarget', 'statusFilter'),
                'external-work-logs',
                ['status' => $statusFilter]
            );
        }

        $pageTitle = 'External Work Logs';
        $view = __DIR__ . '/../views/external-work-logs/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    public function create()
    {
        $this->denyClient();

        if (!can_create_external_work_log()) {
            $this->fail('You do not have permission to create external work logs.', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('external-work-logs');
        }

        verify_csrf();

        [$valid, $data, $error] = $this->collectFormData(false);
        if (!$valid) {
            $this->fail($error);
        }

        $id = $this->logModel->create($data);
        if (!$id) {
            $this->fail('Unable to create the external work log. Please try again.');
        }

        $project = $this->projectModel->findById($data['project_id']);
        $this->activityLogModel->log(
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'Created External Work Log',
            'Created External Work Log: ' . $data['title']
                . ' (' . ($project['project_code'] ?? 'project') . ')'
        );

        $this->succeed('External work log created successfully.');
    }

    public function edit()
    {
        $this->denyClient();

        $log = $this->requireVisibleLog();
        $userRole = $_SESSION['user_role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (is_ajax_request()) {
                json_response([
                    'success' => true,
                    'log' => $this->serializeLog($log),
                    'can_manage' => can_manage_external_work_logs($userRole),
                ]);
            }
            redirect('external-work-logs');
        }

        if (!can_manage_external_work_logs($userRole)) {
            $this->fail('Only admins can edit external work log details.', 403);
        }

        verify_csrf();

        [$valid, $data, $error] = $this->collectFormData(true, $log);
        if (!$valid) {
            $this->fail($error);
        }

        if (!$this->logModel->update($log['id'], $data)) {
            $this->fail('Unable to update the external work log. Please try again.');
        }

        $this->activityLogModel->log(
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'Updated External Work Log',
            'Updated External Work Log: ' . $data['title']
        );

        $this->succeed('External work log updated successfully.');
    }

    public function updateStatus()
    {
        $this->denyClient();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('external-work-logs');
        }

        verify_csrf();

        $log = $this->requireVisibleLog();
        $userRole = $_SESSION['user_role'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        if (!can_update_external_work_log_status($userRole, $log, $userId)) {
            $this->fail('You do not have permission to update this log status.', 403);
        }

        $newStatus = trim((string)($_POST['status'] ?? ''));
        if (!in_array($newStatus, external_work_log_statuses(), true)) {
            $this->fail('Invalid status.');
        }

        if (!$this->isAllowedTransition($userRole, $log['status'], $newStatus)) {
            $this->fail('That status change is not allowed.');
        }

        if ($newStatus === 'Completed') {
            $notes = trim((string)($_POST['completion_notes'] ?? ''));
            [$hoursValid, $actualHours] = $this->parseHours($_POST['actual_hours'] ?? '', true);
            if ($notes === '') {
                $this->fail('Completion notes are required when marking a log as completed.');
            }
            if (!$hoursValid) {
                $this->fail('Actual hours are required when marking a log as completed.');
            }

            if (!$this->logModel->complete($log['id'], $actualHours, $notes)) {
                $this->fail('Unable to complete the external work log. Please try again.');
            }

            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'Marked External Work Log Completed',
                'Marked External Work Log Completed: ' . $log['title']
            );

            $this->succeed('External work log marked as completed.');
        }

        if (!$this->logModel->updateStatus($log['id'], $newStatus)) {
            $this->fail('Unable to update the external work log status. Please try again.');
        }

        $this->activityLogModel->log(
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'Updated External Work Log',
            'Updated External Work Log status to ' . $newStatus . ': ' . $log['title']
        );

        $this->succeed('External work log status updated.');
    }

    private function collectFormData($isEdit, array $existing = [])
    {
        $userRole = $_SESSION['user_role'] ?? '';
        $userId = (int)$_SESSION['user_id'];

        $projectId = (int)($_POST['project_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $source = trim((string)($_POST['communication_source'] ?? ''));
        $requestedBy = trim((string)($_POST['requested_by'] ?? ''));
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);
        $workDate = trim((string)($_POST['work_date'] ?? ''));
        $clientReference = trim((string)($_POST['client_reference'] ?? ''));

        if ($projectId <= 0) {
            return [false, [], 'Please select a project.'];
        }
        if ($title === '') {
            return [false, [], 'Title is required.'];
        }
        if (!in_array($source, external_work_log_sources(), true)) {
            return [false, [], 'Please select a valid communication source.'];
        }
        if ($requestedBy === '') {
            return [false, [], 'Requested By is required.'];
        }
        if ($assignedTo <= 0) {
            return [false, [], 'Please assign this log to a team member.'];
        }
        if ($workDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
            return [false, [], 'Please provide a valid work date.'];
        }

        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            return [false, [], 'Project not found.'];
        }

        if ($userRole !== 'admin' && !$this->projectModel->isMember($projectId, $userId)) {
            return [false, [], 'You can only document work for projects you belong to.'];
        }

        if (!$this->isAssignableUser($assignedTo)) {
            return [false, [], 'Assignee must be an active admin, developer, or intern.'];
        }

        [$estValid, $estimatedHours] = $this->parseHours($_POST['estimated_hours'] ?? '', false);
        if (!$estValid) {
            return [false, [], 'Estimated hours must be a non-negative number.'];
        }

        $data = [
            'project_id' => $projectId,
            'created_by' => $userId,
            'assigned_to' => $assignedTo,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'communication_source' => $source,
            'requested_by' => $requestedBy,
            'work_date' => $workDate,
            'estimated_hours' => $estimatedHours,
            'status' => 'Pending',
            'client_reference' => $clientReference !== '' ? $clientReference : null,
        ];

        if ($isEdit) {
            $status = trim((string)($_POST['status'] ?? ($existing['status'] ?? 'Pending')));
            if (!in_array($status, external_work_log_statuses(), true)) {
                return [false, [], 'Invalid status.'];
            }

            [$actValid, $actualHours] = $this->parseHours($_POST['actual_hours'] ?? '', false);
            if (!$actValid) {
                return [false, [], 'Actual hours must be a non-negative number.'];
            }

            $notes = trim((string)($_POST['completion_notes'] ?? ''));
            if ($status === 'Completed') {
                if ($notes === '') {
                    return [false, [], 'Completion notes are required when the status is Completed.'];
                }
                if ($actualHours === null) {
                    return [false, [], 'Actual hours are required when the status is Completed.'];
                }
            }

            unset($data['created_by']);
            $data['status'] = $status;
            $data['actual_hours'] = $actualHours;
            $data['completion_notes'] = $notes !== '' ? $notes : null;
        }

        return [true, $data, null];
    }

    private function parseHours($raw, $required)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [$required ? false : true, null];
        }
        if (!is_numeric($raw)) {
            return [false, null];
        }
        $value = round((float)$raw, 2);
        if ($value < 0 || $value > 99999) {
            return [false, null];
        }

        return [true, $value];
    }

    private function isAssignableUser($userId): bool
    {
        foreach ($this->userModel->getTaskableUsers() as $user) {
            if ((int)$user['id'] === (int)$userId) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedTransition($role, $current, $next): bool
    {
        if ($current === $next) {
            return true;
        }

        if ($role === 'admin') {
            return true;
        }

        if ($current === 'Pending' && $next === 'In Progress') {
            return true;
        }
        if ($current === 'In Progress' && $next === 'Completed') {
            return true;
        }

        return false;
    }

    private function requireVisibleLog()
    {
        $id = (int)($_GET['id'] ?? 0);
        $log = $this->logModel->findById($id);
        if (!$log) {
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => 'External work log not found.'], 404);
            }
            abort_404();
        }

        $userRole = $_SESSION['user_role'] ?? '';
        $userId = (int)$_SESSION['user_id'];
        if (!$this->logModel->canViewLog($log, $userRole, $userId)) {
            $this->fail('You do not have access to this external work log.', 403);
        }

        return $log;
    }

    private function serializeLog(array $log): array
    {
        return [
            'id' => (int)$log['id'],
            'project_id' => (int)$log['project_id'],
            'assigned_to' => (int)$log['assigned_to'],
            'title' => $log['title'] ?? '',
            'description' => $log['description'] ?? '',
            'communication_source' => $log['communication_source'] ?? '',
            'requested_by' => $log['requested_by'] ?? '',
            'work_date' => $log['work_date'] ?? '',
            'estimated_hours' => $log['estimated_hours'],
            'actual_hours' => $log['actual_hours'],
            'status' => $log['status'] ?? 'Pending',
            'client_reference' => $log['client_reference'] ?? '',
            'completion_notes' => $log['completion_notes'] ?? '',
        ];
    }

    private function denyClient()
    {
        if (($_SESSION['user_role'] ?? '') === 'client') {
            abort_403();
        }
        if (!can_access_external_work_logs()) {
            abort_403();
        }
    }

    private function fail($message, $statusCode = 200)
    {
        if (is_ajax_request()) {
            json_response(['success' => false, 'message' => $message], $statusCode);
        }
        set_flash_message('danger', $message);
        redirect('external-work-logs');
    }

    private function succeed($message)
    {
        if (is_ajax_request()) {
            json_response(['success' => true, 'message' => $message]);
        }
        set_flash_message('success', $message);
        redirect('external-work-logs');
    }
}
