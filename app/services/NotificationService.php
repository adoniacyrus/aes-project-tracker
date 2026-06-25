<?php

require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/../models/NotificationLogModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/TaskModel.php';

class NotificationService
{
    private MailService $mailService;
    private NotificationLogModel $logModel;
    private UserModel $userModel;
    private TicketModel $ticketModel;
    private ProjectModel $projectModel;
    private TaskModel $taskModel;

    public function __construct()
    {
        $this->mailService = new MailService();
        $this->logModel = new NotificationLogModel();
        $this->userModel = new UserModel();
        $this->ticketModel = new TicketModel();
        $this->projectModel = new ProjectModel();
        $this->taskModel = new TaskModel();
    }

    // -------------------------------------------------------------------------
    // Auth / account emails (delegates to MailService templates)
    // -------------------------------------------------------------------------

    public function sendWelcomeEmail(string $fullName, string $email, string $temporaryPassword): bool
    {
        return $this->mailService->sendWelcomeEmail($fullName, $email, $temporaryPassword);
    }

    public function sendForgotPasswordEmail(string $fullName, string $email, string $temporaryPassword): bool
    {
        return $this->mailService->sendForgotPasswordEmail($fullName, $email, $temporaryPassword);
    }

    public function sendAdminPasswordResetEmail(string $fullName, string $email, string $temporaryPassword): bool
    {
        return $this->mailService->sendPasswordResetEmail($fullName, $email, $temporaryPassword);
    }

    // -------------------------------------------------------------------------
    // Admin notifications
    // -------------------------------------------------------------------------

    public function notifyAdminsTicketCreated(int $ticketId, int $creatorId): void
    {
        $creator = $this->userModel->findById($creatorId);
        if (!$creator || ($creator['role'] ?? '') !== 'client') {
            return;
        }

        $context = $this->getTicketContext($ticketId);
        if (!$context) {
            return;
        }

        $this->notifyAdmins(
            'ticket_created',
            'New Ticket Created',
            'A client has created a new ticket.',
            [
                'Ticket ID' => $context['ticket_code'],
                'Project' => $context['project_name'],
                'Client' => $creator['full_name'],
                'Category' => $context['ticket']['category'] ?? '',
                'Priority' => ucfirst((string) ($context['ticket']['priority'] ?? '')),
                'Created Date' => date('M d, Y H:i', strtotime($context['ticket']['created_at'] ?? 'now')),
            ],
            $context['ticket_url'],
            'View Ticket'
        );
    }

    public function notifyAdminsTicketSubmittedForReview(int $ticketId, int $developerId, string $comment): void
    {
        $developer = $this->userModel->findById($developerId);
        $context = $this->getTicketContext($ticketId);
        if (!$context) {
            return;
        }

        $this->notifyAdmins(
            'ticket_submitted_for_review',
            'Ticket Submitted For Review',
            'A developer has submitted a ticket for your review.',
            [
                'Developer' => $developer['full_name'] ?? 'Developer',
                'Ticket' => $context['ticket_code'],
                'Project' => $context['project_name'],
            ],
            $context['ticket_url'],
            'View Ticket',
            $comment !== '' ? $comment : null
        );
    }

    public function notifyAdminsCommercialReviewRequired(int $ticketId, string $reason = ''): void
    {
        $context = $this->getTicketContext($ticketId);
        if (!$context) {
            return;
        }

        $this->notifyAdmins(
            'commercial_review_required',
            'Commercial Review Required',
            'A developer has requested commercial review for a ticket.',
            [
                'Project' => $context['project_name'],
                'Ticket' => $context['ticket_code'],
            ],
            $context['ticket_url'],
            'View Ticket',
            $reason !== '' ? $reason : 'Developer flagged this ticket as not a bug fix.'
        );
    }

    public function notifyAdminsTaskCompleted(int $taskId): void
    {
        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            return;
        }

        $context = $this->getTicketContext((int) $task['ticket_id']);
        if (!$context) {
            return;
        }

        $this->notifyAdmins(
            'task_completed',
            'Task Completed',
            'A task has been marked as completed.',
            [
                'Task' => $task['task_name'] ?? ('Task #' . $taskId),
                'Ticket' => $context['ticket_code'],
                'Project' => $context['project_name'],
                'Completed By' => $task['assignee_name'] ?? 'Team Member',
            ],
            $context['ticket_url'],
            'View Ticket'
        );
    }

    // -------------------------------------------------------------------------
    // Developer / intern notifications
    // -------------------------------------------------------------------------

    public function notifyProjectAssignment(int $projectId, int $userId): void
    {
        $user = $this->userModel->findById($userId);
        $project = $this->projectModel->findById($projectId);
        if (!$user || !$project || !in_array($user['role'] ?? '', ['developer', 'intern'], true)) {
            return;
        }

        if (!$this->projectModel->isMember($projectId, $userId)) {
            return;
        }

        $this->notifyUser(
            $user,
            'project_assigned',
            'Assigned to Project',
            'You have been added to a project team.',
            [
                'Project Name' => $project['project_name'],
                'Project Code' => $project['project_code'],
                'Role' => ucfirst((string) $user['role']),
                'Client' => $project['client_name'] ?? 'N/A',
                'Start Date' => $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A',
            ],
            route('projects-view', ['project_code' => $project['project_code']]),
            'Open Project'
        );
    }

    public function notifyTicketAssignment(int $ticketId, int $userId): void
    {
        $user = $this->userModel->findById($userId);
        $context = $this->getTicketContext($ticketId);
        if (!$user || !$context || !in_array($user['role'] ?? '', ['developer', 'intern'], true)) {
            return;
        }

        if (!$this->projectModel->isMember((int) $context['ticket']['project_id'], $userId)) {
            return;
        }

        $this->notifyUser(
            $user,
            'ticket_assigned',
            'New Ticket Assigned',
            'You have been assigned to a ticket.',
            [
                'Ticket' => $context['ticket_code'],
                'Priority' => ucfirst((string) ($context['ticket']['priority'] ?? '')),
                'Category' => $context['ticket']['category'] ?? '',
                'Project' => $context['project_name'],
            ],
            $context['ticket_url'],
            'Open Ticket'
        );
    }

    public function notifyTaskAssigned(int $taskId): void
    {
        $task = $this->taskModel->findById($taskId);
        if (!$task || empty($task['assigned_member'])) {
            return;
        }

        $assignee = $this->userModel->findById((int) $task['assigned_member']);
        $context = $this->getTicketContext((int) $task['ticket_id']);
        if (!$assignee || !$context) {
            return;
        }

        $this->notifyUser(
            $assignee,
            'task_assigned',
            'New Task Assigned',
            'A new task has been assigned to you.',
            [
                'Task' => $task['task_name'] ?? ('Task #' . $taskId),
                'Due Date' => !empty($task['due_date']) ? date('M d, Y', strtotime($task['due_date'])) : 'Not set',
                'Ticket' => $context['ticket_code'],
                'Project' => $context['project_name'],
            ],
            route('tasks'),
            'Open Task'
        );
    }

    public function notifyDeveloperTicketReturned(int $ticketId, string $adminComment): void
    {
        $ticket = $this->ticketModel->findById($ticketId);
        $context = $this->getTicketContext($ticketId);
        if (!$ticket || !$context) {
            return;
        }

        $recipientId = (int) ($ticket['resolution_submitted_by'] ?? 0);
        if ($recipientId <= 0) {
            $assignments = $this->ticketModel->getTicketAssignments($ticketId);
            $recipientId = (int) ($assignments[0]['user_id'] ?? 0);
        }

        $user = $this->userModel->findById($recipientId);
        if (!$user || !in_array($user['role'] ?? '', ['developer', 'intern'], true)) {
            return;
        }

        $this->notifyUser(
            $user,
            'ticket_returned',
            'Ticket Returned',
            'An administrator has returned a ticket to development.',
            [
                'Ticket' => $context['ticket_code'],
                'Project' => $context['project_name'],
            ],
            $context['ticket_url'],
            'Continue Development',
            $adminComment !== '' ? $adminComment : null
        );
    }

    public function notifyDeveloperTicketApproved(int $ticketId): void
    {
        $ticket = $this->ticketModel->findById($ticketId);
        $context = $this->getTicketContext($ticketId);
        if (!$ticket || !$context) {
            return;
        }

        $recipientId = (int) ($ticket['resolution_submitted_by'] ?? 0);
        if ($recipientId <= 0) {
            $assignments = $this->ticketModel->getTicketAssignments($ticketId);
            $recipientId = (int) ($assignments[0]['user_id'] ?? 0);
        }

        $user = $this->userModel->findById($recipientId);
        if (!$user || !in_array($user['role'] ?? '', ['developer', 'intern'], true)) {
            return;
        }

        $this->notifyUser(
            $user,
            'ticket_approved',
            'Ticket Approved',
            'Your ticket submission has been approved by an administrator.',
            [
                'Ticket' => $context['ticket_code'],
                'Project' => $context['project_name'],
            ],
            $context['ticket_url'],
            'View Ticket'
        );
    }

    // -------------------------------------------------------------------------
    // Client notifications
    // -------------------------------------------------------------------------

    public function notifyClientProjectCreated(int $projectId): void
    {
        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            return;
        }

        $clients = $this->userModel->findActiveClientUsersForProject($project);
        if (empty($clients)) {
            return;
        }

        $manager = $this->userModel->findById((int) ($project['created_by'] ?? 0));
        $rows = [
            'Project Name' => $project['project_name'],
            'Project Code' => $project['project_code'],
            'Project Description' => $project['project_description'] ?? '',
            'Technology Stack' => $project['technology_stack'] ?? '',
            'Estimated Cost' => format_rs_currency((float) ($project['project_cost'] ?? 0), 2),
            'Start Date' => $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A',
            'Expected End Date' => $project['expected_end_date'] ? date('M d, Y', strtotime($project['expected_end_date'])) : 'N/A',
            'Assigned Manager' => $manager['full_name'] ?? 'AES Team',
        ];

        foreach ($clients as $client) {
            $this->notifyUser(
                $client,
                'project_created_client',
                'Welcome to AES Project Tracker',
                'A new project has been created for you on AES Project Tracker.',
                $rows,
                route('projects-view', ['project_code' => $project['project_code']]),
                'View Project'
            );
        }
    }

    public function notifyClientTicketCostUpdated(int $ticketId, array $audit): void
    {
        $context = $this->getTicketContext($ticketId);
        if (!$context) {
            return;
        }

        $recipients = $this->getTicketClientRecipients($context['ticket']);
        if (empty($recipients)) {
            return;
        }

        $oldCost = $audit['previous_cost'] ?? null;
        $rows = [
            'Ticket' => $context['ticket_code'],
            'Project' => $context['project_name'],
            'Old Cost' => $oldCost !== null ? format_rs_currency((float) $oldCost, 2) : 'Not set',
            'New Cost' => format_rs_currency((float) ($audit['new_cost'] ?? 0), 2),
            'Deadline' => !empty($audit['new_delivery_date']) ? date('M d, Y', strtotime($audit['new_delivery_date'])) : 'N/A',
        ];

        foreach ($recipients as $recipient) {
            $this->notifyUser(
                $recipient,
                'ticket_cost_updated',
                'Ticket Cost Updated',
                'The estimated cost for your ticket has been updated.',
                $rows,
                $context['ticket_url'],
                'Open Ticket',
                !empty($audit['reason']) ? (string) $audit['reason'] : null
            );
        }
    }

    public function notifyClientTicketCompleted(int $ticketId): void
    {
        $context = $this->getTicketContext($ticketId);
        if (!$context) {
            return;
        }

        $recipients = $this->getTicketClientRecipients($context['ticket']);
        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $recipient) {
            $this->notifyUser(
                $recipient,
                'ticket_completed',
                'Ticket Completed',
                'Your ticket has been marked as completed.',
                [
                    'Ticket' => $context['ticket_code'],
                    'Project' => $context['project_name'],
                    'Completion Date' => date('M d, Y H:i'),
                ],
                $context['ticket_url'],
                'View Ticket'
            );
        }
    }

    public function notifyCommercialDiscussionUpdate(int $ticketId, string $message, int $postedByUserId): void
    {
        $context = $this->getTicketContext($ticketId);
        $poster = $this->userModel->findById($postedByUserId);
        if (!$context || !$poster) {
            return;
        }

        $posterRole = $poster['role'] ?? '';
        if ($posterRole === 'admin') {
            $recipients = $this->getTicketClientRecipients($context['ticket']);
        } elseif ($posterRole === 'client') {
            $recipients = $this->userModel->getAdmins();
        } else {
            return;
        }

        foreach ($recipients as $recipient) {
            if ((int) ($recipient['id'] ?? 0) === $postedByUserId) {
                continue;
            }

            $this->notifyUser(
                $recipient,
                'commercial_discussion_update',
                'Commercial Update',
                'There is a new update in the commercial discussion.',
                [
                    'Ticket' => $context['ticket_code'],
                    'Project' => $context['project_name'],
                    'Posted By' => $poster['full_name'] ?? 'User',
                ],
                $context['ticket_url'],
                'View Ticket',
                $message
            );
        }
    }

    /**
     * Extension point for future scheduler/cron integration.
     */
    public function notifyTaskDueTomorrow(int $taskId): void
    {
        // Reserved for future scheduled reminders.
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function notifyAdmins(
        string $type,
        string $subject,
        string $intro,
        array $rows,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $message = null
    ): void {
        foreach ($this->userModel->getAdmins() as $admin) {
            $this->notifyUser($admin, $type, $subject, $intro, $rows, $actionUrl, $actionLabel, $message);
        }
    }

    private function notifyUser(
        array $user,
        string $type,
        string $subject,
        string $intro,
        array $rows,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        ?string $message = null
    ): void {
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $userId = isset($user['id']) ? (int) $user['id'] : null;
        $name = (string) ($user['full_name'] ?? 'User');

        if (!$this->mailService->isEnabled()) {
            $this->logModel->log($userId, $email, $type, $subject, 'skipped', 'Mail service disabled');
            return;
        }

        $htmlBody = $this->renderNotificationEmail($name, $subject, $intro, $rows, $message, $actionUrl, $actionLabel);
        $plainBody = $this->buildPlainText($name, $intro, $rows, $message, $actionUrl);

        try {
            $sent = $this->mailService->sendEmail($email, $name, $subject, $htmlBody, $plainBody);
            $this->logModel->log(
                $userId,
                $email,
                $type,
                $subject,
                $sent ? 'sent' : 'failed',
                $sent ? null : 'Mail delivery failed'
            );
        } catch (Throwable $e) {
            $this->logModel->log($userId, $email, $type, $subject, 'failed', $e->getMessage());
        }
    }

    private function renderNotificationEmail(
        string $name,
        string $title,
        string $intro,
        array $rows,
        ?string $message,
        ?string $actionUrl,
        ?string $actionLabel
    ): string {
        return render_partial(__DIR__ . '/../views/emails/layouts/notification.php', [
            'emailTitle' => $title,
            'greeting' => 'Hello ' . $name . ',',
            'intro' => $intro,
            'rows' => $rows,
            'message' => $message,
            'actionUrl' => $actionUrl,
            'actionLabel' => $actionLabel,
        ]);
    }

    private function buildPlainText(
        string $name,
        string $intro,
        array $rows,
        ?string $message,
        ?string $actionUrl
    ): string {
        $lines = ["Hello {$name},", '', $intro, ''];

        foreach ($rows as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = "{$label}: {$value}";
        }

        if ($message) {
            $lines[] = '';
            $lines[] = $message;
        }

        if ($actionUrl) {
            $lines[] = '';
            $lines[] = $actionUrl;
        }

        $lines[] = '';
        $lines[] = 'Regards,';
        $lines[] = 'AES Project Tracker';

        return implode("\n", $lines);
    }

    private function getTicketContext(int $ticketId): ?array
    {
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return null;
        }

        $project = $this->projectModel->findById((int) $ticket['project_id']);
        $ticketCode = get_ticket_code_by_id($ticketId);

        return [
            'ticket' => $ticket,
            'project' => $project,
            'project_name' => $project['project_name'] ?? 'Project',
            'ticket_code' => $ticketCode,
            'ticket_url' => route('tickets-view', ['ticket_code' => $ticketCode]),
        ];
    }

    private function getTicketClientRecipients(array $ticket): array
    {
        $recipients = [];
        $seen = [];

        $creator = $this->userModel->findById((int) ($ticket['created_by'] ?? 0));
        if ($creator && ($creator['role'] ?? '') === 'client' && ($creator['status'] ?? '') === 'active') {
            $recipients[] = $creator;
            $seen[(int) $creator['id']] = true;
        }

        $project = $this->projectModel->findById((int) ($ticket['project_id'] ?? 0));
        if ($project) {
            foreach ($this->userModel->findActiveClientUsersForProject($project) as $client) {
                $clientId = (int) ($client['id'] ?? 0);
                if ($clientId > 0 && !isset($seen[$clientId])) {
                    $recipients[] = $client;
                    $seen[$clientId] = true;
                }
            }
        }

        return $recipients;
    }
}
