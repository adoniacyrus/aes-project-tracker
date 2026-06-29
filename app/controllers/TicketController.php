<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/TeamChatAttachmentModel.php';
require_once __DIR__ . '/../models/TicketWorkflowHistoryModel.php';
require_once __DIR__ . '/../services/TicketWorkflowService.php';
require_once __DIR__ . '/../services/TicketWorkspaceService.php';
require_once __DIR__ . '/../services/NotificationService.php';

class TicketController
{
    private $ticketModel;
    private $projectModel;
    private $userModel;
    private $taskModel;
    private $activityLogModel;
    private $teamChatAttachmentModel;
    private $workflowHistoryModel;

    private const ALLOWED_ATTACHMENT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
    private const MAX_ATTACHMENT_SIZE = 10485760;
    private const TEAM_CHAT_UPLOAD_DIR = 'storage/uploads/team-chat/';

    public function __construct()
    {
        AuthMiddleware::check();

        $this->ticketModel = new TicketModel();
        $this->projectModel = new ProjectModel();
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->activityLogModel = new ActivityLogModel();
        $this->teamChatAttachmentModel = new TeamChatAttachmentModel();
        $this->workflowHistoryModel = new TicketWorkflowHistoryModel();
    }

    /**
     * Check if request is AJAX
     */
    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Helper to return AJAX JSON response or standard Redirect
     */
    private function returnResponse($ticketId, $extra = [], $action = 'workflow_status')
    {
        $ticketCode = get_ticket_code_by_id($ticketId);
        if ($this->isAjax()) {
            $msgType = has_flash_message('success') ? 'success' : (has_flash_message('danger') ? 'danger' : 'info');
            $msg = get_flash_message($msgType);
            if ($msgType === 'success') {
                $context = [];
                if ($action === 'workflow_status') {
                    $ticket = $this->ticketModel->findById($ticketId);
                    $context['display_status'] = TicketWorkflowService::mapToSimplifiedStatus($ticket['status'] ?? '');
                }
                $payload = TicketWorkspaceService::buildAjaxPayload($ticketId, $action, $msg, $context);
            } else {
                $payload = [
                    'success' => false,
                    'message' => $msg,
                ];
            }
            json_response(array_merge($payload, $extra));
        }
        redirect('tickets-view', ['ticket_code' => $ticketCode]);
    }

    /**
     * AJAX response when a dev/intern action hides the ticket from the project team.
     * Skips partial refresh (user no longer has access) and redirects to the ticket list.
     */
    private function returnTeamHiddenResponse($ticketId, $followUpMessage)
    {
        $userRole = $_SESSION['user_role'] ?? '';

        if ($this->isAjax() && in_array($userRole, ['developer', 'intern'], true)) {
            $msgType = has_flash_message('success') ? 'success' : (has_flash_message('danger') ? 'danger' : 'info');
            $msg = get_flash_message($msgType);

            if ($msgType === 'success') {
                json_response([
                    'success' => true,
                    'message' => $msg,
                    'follow_up_message' => $followUpMessage,
                    'redirect' => route('tickets'),
                    'redirect_delay' => 1500,
                    'allowRedirect' => true,
                    'skip_refresh' => true,
                ]);
            }
        }

        $this->returnResponse($ticketId);
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

    private function checkTicketAccess($ticket)
    {
        if (!$this->ticketModel->canUserAccessTicket($ticket, $_SESSION['user_id'], $_SESSION['user_role'])) {
            abort_403();
        }
    }

    private function buildProjectMembersMap($projects)
    {
        $map = [];
        foreach ($projects as $project) {
            $map[$project['id']] = $this->projectModel->getProjectMembers($project['id']);
        }
        return $map;
    }

    private function filterAssignableMembers($members, $userRole)
    {
        if ($userRole === 'client') {
            return [];
        }
        return array_values(array_filter($members, function ($member) {
            return in_array($member['role'], ['developer', 'intern', 'admin'], true);
        }));
    }

    private function filterDeveloperAssignmentMembers($members)
    {
        return array_values(array_filter($members, function ($member) {
            return in_array($member['role'] ?? '', ['developer', 'intern'], true);
        }));
    }

    private function assertFloatingChatAccess($channel, array $ticket)
    {
        $userRole = $_SESSION['user_role'] ?? '';
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($channel === 'client') {
            return can_access_client_chat($userRole);
        }

        if ($channel === 'admin_dev') {
            return can_access_admin_dev_chat($userRole, $ticket, $userId);
        }

        return can_access_team_chat($userRole);
    }

    private function buildReviewWorkflowAjaxResponse($ticketId, $message, $action = 'review_submitted', array $extra = [])
    {
        $ticket = $this->ticketModel->findById($ticketId);
        $displayStatus = TicketWorkflowService::mapToSimplifiedStatus($ticket['status'] ?? '');

        return array_merge(
            TicketWorkspaceService::buildAjaxPayload($ticketId, $action, $message, ['display_status' => $displayStatus]),
            $extra
        );
    }

    private function logTicketWorkflowHistory($ticketId, $action, $label, $comment = null, $visibility = 'all', $performedBy = null)
    {
        if ($performedBy === null) {
            $performedBy = (int)($_SESSION['user_id'] ?? 0);
        }

        $this->workflowHistoryModel->log(
            (int)$ticketId,
            (string)$action,
            (string)$label,
            $performedBy > 0 ? $performedBy : null,
            $comment,
            normalize_workflow_history_visibility($visibility)
        );
    }

    private function logTicketStatusChange($ticketId, $fromRawStatus, $toRawStatus, $performedBy = null)
    {
        if ((string)$fromRawStatus === (string)$toRawStatus) {
            return;
        }

        $fromLabel = TicketWorkflowService::mapToSimplifiedStatus($fromRawStatus);
        $toLabel = TicketWorkflowService::mapToSimplifiedStatus($toRawStatus);
        $historyLabel = $toLabel === 'Completed' ? 'Completed' : 'Status Changed';
        $action = $toLabel === 'Completed' ? 'completed' : 'status_changed';

        $this->logTicketWorkflowHistory(
            (int)$ticketId,
            $action,
            $historyLabel,
            "Status changed from {$fromLabel} to {$toLabel}.",
            'all',
            $performedBy
        );
    }

    private function buildTicketPartialRefreshes($ticketId, array $partials)
    {
        return TicketWorkspaceService::buildPartialRefreshes($ticketId, $partials);
    }

    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $projectId = (int)($_GET['project_id'] ?? 0);
        $category = trim($_GET['category'] ?? '');
        $priority = trim($_GET['priority'] ?? '');
        $status = trim($_GET['status'] ?? '');
        if ($status !== '' && !TicketWorkflowService::isSimplifiedStatus($status)) {
            $status = TicketWorkflowService::mapToSimplifiedStatus($status);
        }

        $pageNum = (int)($_GET['p'] ?? 1);
        if ($pageNum < 1) {
            $pageNum = 1;
        }

        $limit = 10;
        $offset = ($pageNum - 1) * $limit;

        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        if ($projectId > 0) {
            $this->checkProjectAccess($projectId);
        }

        $tickets = $this->ticketModel->getTickets($userId, $userRole, $search, $offset, $limit, $projectId, $category, $priority, $status);
        $totalTickets = $this->ticketModel->getTicketsCount($userId, $userRole, $search, $projectId, $category, $priority, $status);
        $statusCounts = $this->ticketModel->getTicketStatusCounts($userId, $userRole, $search, $projectId, $category, $priority);
        $totalPages = ceil($totalTickets / $limit);
        $showTeamVisibility = ($userRole !== 'client');

        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/tickets/_list_content.php',
                compact('tickets', 'search', 'projectId', 'category', 'priority', 'status', 'pageNum', 'totalPages', 'totalTickets', 'statusCounts', 'showTeamVisibility'),
                'tickets',
                ['q' => $search, 'project_id' => $projectId, 'category' => $category, 'priority' => $priority, 'status' => $status, 'p' => $pageNum]
            );
        }

        $projects = $this->projectModel->getProjects($userId, $userRole, '', 0, 100, '', 0);
        $projectMembersMap = $this->buildProjectMembersMap($projects);
        $canCreateTicket = TicketWorkflowService::canCreateTicket($userRole);

        $pageTitle = 'Tickets Directory';
        $view = __DIR__ . '/../views/tickets/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    public function view()
    {
        $id = (int)($_GET['id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        $userRole = $_SESSION['user_role'];
        $projectMembers = $this->filterAssignableMembers(
            $this->projectModel->getProjectMembers($ticket['project_id']),
            $userRole
        );

        $teamComments = [];
        if (can_access_team_chat($userRole)) {
            $teamComments = team_chat_enrich_comments($this->ticketModel->getComments($id, 'team'), $id);
        }
        $clientComments = [];
        if (can_access_client_chat($userRole)) {
            $clientComments = team_chat_enrich_comments($this->ticketModel->getComments($id, 'client'), $id);
        }
        $adminDevComments = [];
        if (can_access_admin_dev_chat($userRole, $ticket, (int)$_SESSION['user_id'])) {
            $adminDevComments = team_chat_enrich_comments($this->ticketModel->getComments($id, 'admin_dev'), $id);
        }
        $ticketAssignments = $this->ticketModel->getTicketAssignments($id);
        $comments = $teamComments;
        $attachments = $this->ticketModel->getAttachments($id);
        $tasks = $this->taskModel->getTasksByTicket($id);

        $discussions = [];
        if (TicketWorkflowService::canViewDiscussion($userRole)) {
            $discussions = $this->ticketModel->getDiscussions($id);
        }

        $canViewInternal = in_array($userRole, ['admin', 'developer', 'intern'], true);
        $internalDiscussions = [];
        if ($canViewInternal) {
            $internalDiscussions = $this->ticketModel->getInternalDiscussions($id);
        }

        $allowedTransitions = TicketWorkflowService::getAllowedTransitions($ticket, $userRole);
        $displayStatus = TicketWorkflowService::mapToSimplifiedStatus($ticket['status']);
        $canChangeSimplifiedStatus = TicketWorkflowService::canAdminChangeSimplifiedStatus($userRole);
        $isCommercial = TicketWorkflowService::isCommercialCategory($ticket['category']);
        $canCreateTicket = TicketWorkflowService::canCreateTicket($userRole);
        $isAdmin = ($userRole === 'admin');
        $canDiscuss = TicketWorkflowService::canViewDiscussion($userRole);
        $canManageTasks = can_manage_tasks($userRole);
        $currentUserId = (int)$_SESSION['user_id'];
        $taskAssignableMembers = filter_ticket_task_assignable_members(
            $ticket,
            $projectMembers,
            array_column($tasks, 'assigned_member')
        );
        $showTeamChatWidget = can_access_team_chat($userRole);
        $showClientChatWidget = can_access_client_chat($userRole);
        $showAdminDevChatWidget = can_access_admin_dev_chat($userRole, $ticket, $currentUserId);
        $canEditEstimation = can_edit_ticket_estimation($userRole);
        $developerAssignmentMembers = $this->filterDeveloperAssignmentMembers($projectMembers);
        $workflowHistory = $this->workflowHistoryModel->getTicketHistory($id, $userRole);
        $latestWorkflowActivity = $this->workflowHistoryModel->getLatestEntry($id, $userRole);

        if (isset($_GET['partial']) && is_ajax_request()) {
            $partial = $_GET['partial'] ?? '';
            $partialData = compact('ticket', 'allowedTransitions', 'displayStatus', 'canChangeSimplifiedStatus', 'isCommercial', 'isAdmin', 'canDiscuss', 'canViewInternal', 'userRole', 'projectMembers', 'developerAssignmentMembers', 'ticketAssignments', 'tasks', 'discussions', 'internalDiscussions', 'canManageTasks', 'currentUserId', 'taskAssignableMembers', 'attachments', 'teamComments', 'clientComments', 'adminDevComments', 'canEditEstimation', 'showAdminDevChatWidget', 'workflowHistory', 'latestWorkflowActivity');

            if ($partial === 'estimation') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_cost_estimation_card.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'estimation']
                );
            }

            if ($partial === 'ticket-info') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_ticket_information.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'ticket-info']
                );
            }

            if ($partial === 'workflow') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_workflow_card.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'workflow']
                );
            }

            if ($partial === 'assigned-team') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_assigned_team_card.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'assigned-team']
                );
            }

            if ($partial === 'sidebar') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_workflow_sidebar.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'sidebar']
                );
            }

            if ($partial === 'assignment') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_developer_assignment_card.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'assignment']
                );
            }

            if ($partial === 'assignment-modal') {
                if (!$isAdmin) {
                    json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
                }
                respond_partial(
                    __DIR__ . '/../views/tickets/_developer_assignment_modal.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'assignment-modal']
                );
            }

            if ($partial === 'admin-review-modal') {
                if (!$isAdmin || !can_admin_review_ticket($userRole, $ticket)) {
                    json_response(['success' => false, 'message' => 'No admin review is pending for this ticket.'], 403);
                }
                respond_partial(
                    __DIR__ . '/../views/tickets/_admin_review_modal.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'admin-review-modal']
                );
            }

            if ($partial === 'admin-guidance-review-modal') {
                if (!$isAdmin || !can_admin_respond_to_guidance($userRole, $ticket)) {
                    json_response(['success' => false, 'message' => 'No admin guidance request is pending for this ticket.'], 403);
                }
                respond_partial(
                    __DIR__ . '/../views/tickets/_admin_guidance_review_modal.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'admin-guidance-review-modal']
                );
            }

            if ($partial === 'review-comment') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_latest_review_comment.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'review-comment']
                );
            }

            if ($partial === 'workflow-history') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_workflow_history.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'workflow-history']
                );
            }

            if ($partial === 'dynamic') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_dynamic_content.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'dynamic']
                );
            }

            if ($partial === 'attachments') {
                respond_partial(
                    __DIR__ . '/../views/tickets/_attachments.php',
                    $partialData,
                    'tickets-view',
                    ['id' => $id, 'partial' => 'attachments']
                );
            }

            json_response(['success' => false, 'error' => 'Unknown partial.'], 404);
        }

        $pageTitle = "Ticket #" . $ticket['id'] . ": " . $ticket['title'];
        $view = __DIR__ . '/../views/tickets/view.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    public function create()
    {
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        if (!TicketWorkflowService::canCreateTicket($userRole)) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
                exit;
            }
            abort_403();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $category = trim($_POST['category'] ?? 'Bug Fix');
            $initialState = TicketWorkflowService::getInitialWorkflowState($category, $userRole);

            $data = [
                'project_id'  => (int)($_POST['project_id'] ?? 0),
                'title'       => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category'    => $category,
                'priority'    => trim($_POST['priority'] ?? 'medium'),
                'created_by'  => $userId,
                'due_date'    => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'      => $initialState['status'],
                'is_team_visible' => $initialState['is_team_visible'],
                'commercial_review_requested' => 0,
            ];

            $this->checkProjectAccess($data['project_id']);

            if (empty($data['title']) || empty($data['description'])) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Please enter a ticket title and description.']);
                    exit;
                }
                set_flash_message('danger', 'Please enter a ticket title and description.');
                redirect('tickets');
            }

            $ticketId = $this->ticketModel->createTicket($data);
            if ($ticketId) {
                $this->handleAttachmentUploads($ticketId, $_FILES['attachments'] ?? null);

                if ($category === 'Bug Fix') {
                    $this->ticketModel->syncBugFixProjectTeamAssignments($ticketId, $data['project_id'], $userId);
                    $this->logTicketWorkflowHistory(
                        $ticketId,
                        'team_assigned',
                        'Developers Assigned',
                        'All project developers and interns were given access to this Bug Fix ticket.',
                        'internal',
                        $userId
                    );
                }

                $this->logTicketWorkflowHistory($ticketId, 'ticket_created', 'Ticket Created', null, 'all', $userId);

                $this->notify(function (NotificationService $notifications) use ($ticketId, $userId) {
                    $notifications->notifyAdminsTicketCreated($ticketId, $userId);
                });

                $this->activityLogModel->log(
                    $userId,
                    $_SESSION['user_email'],
                    'ticket_created',
                    "Created ticket #$ticketId: {$data['title']} in project ID {$data['project_id']}"
                );

                $ticketCode = get_ticket_code_by_id($ticketId);
                if ($this->isAjax()) {
                    json_response([
                        'success' => true,
                        'message' => 'Ticket created successfully.',
                    ]);
                }
                set_flash_message('success', 'Ticket created successfully.');
                redirect('tickets-view', ['ticket_code' => $ticketCode]);
            }

            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error creating ticket. Please try again.']);
                exit;
            }
            set_flash_message('danger', 'Error creating ticket. Please try again.');
            redirect('tickets');
        }

        // If GET, redirect to Tickets directory
        redirect('tickets');
    }

    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
                exit;
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);
        $userRole = $_SESSION['user_role'];

        if (!can_edit_ticket($userRole, $ticket)) {
            $message = 'You do not have permission to edit this ticket.';
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => $message], 403);
            }
            set_flash_message('danger', $message);
            redirect('tickets-view', ['id' => $id]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $data = [
                'project_id'  => (int)($_POST['project_id'] ?? $ticket['project_id']),
                'title'       => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category'    => trim($_POST['category'] ?? $ticket['category']),
                'priority'    => trim($_POST['priority'] ?? $ticket['priority']),
                'due_date'    => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            ];
            if ($userRole === 'admin') {
                $requestedStatus = trim($_POST['status'] ?? $ticket['status']);
                if (!TicketWorkflowService::isSimplifiedStatus($requestedStatus)) {
                    if ($this->isAjax()) {
                        json_response(['success' => false, 'message' => 'Invalid ticket status selected.'], 422);
                    }
                    set_flash_message('danger', 'Invalid ticket status selected.');
                    redirect('tickets-view', ['id' => $id]);
                }
                $data['status'] = $requestedStatus;
            } else {
                $data['status'] = $ticket['status'];
            }

            $this->checkProjectAccess($data['project_id']);

            if (empty($data['title']) || empty($data['description'])) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Please enter a ticket title and description.']);
                    exit;
                }
                set_flash_message('danger', 'Please enter a ticket title and description.');
                redirect('tickets');
            }

            if ($this->ticketModel->updateTicket($id, $data)) {
                $previousStatus = $ticket['status'];
                $statusChanged = $userRole === 'admin' && (string)$data['status'] !== (string)$previousStatus;
                if ($statusChanged) {
                    $this->logTicketStatusChange($id, $previousStatus, $data['status']);
                }

                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_updated',
                    "Updated ticket #$id: {$data['title']}"
                );
                
                $ticketCode = get_ticket_code_by_id($id);
                if ($this->isAjax()) {
                    $ajaxAction = $statusChanged ? 'workflow_status' : 'ticket_updated';
                    json_response(TicketWorkspaceService::buildAjaxPayload($id, $ajaxAction, 'Ticket updated successfully.'));
                }
                set_flash_message('success', 'Ticket updated successfully.');
                redirect('tickets-view', ['ticket_code' => $ticketCode]);
            }

            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error updating ticket.']);
                exit;
            }
            set_flash_message('danger', 'Error updating ticket.');
            redirect('tickets');
        }

        // If GET & AJAX, return JSON of ticket details to populate edit modal
        if ($this->isAjax()) {
            $userId = $_SESSION['user_id'];
            $projects = $this->projectModel->getProjects($userId, $userRole, '', 0, 100, '', 0);
            $projectMembersMap = $this->buildProjectMembersMap($projects);
            $ticket['display_status'] = TicketWorkflowService::mapToSimplifiedStatus($ticket['status']);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ticket' => $ticket,
                'projects' => $projects,
                'projectMembers' => $projectMembersMap
            ]);
            exit;
        }

        $ticketCode = get_ticket_code_by_id($id);
        redirect('tickets-view', ['ticket_code' => $ticketCode]);
    }

    public function transition()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $id = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $clarificationNote = trim($_POST['clarification_note'] ?? '');

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);
        $userRole = $_SESSION['user_role'];

        if ($newStatus === '__forward_admin__') {
            $previousStatus = $ticket['status'];
            if ($this->ticketModel->updateStatus($id, 'Awaiting Admin Approval')) {
                $this->logTicketStatusChange($id, $previousStatus, 'Awaiting Admin Approval');
                $this->ticketModel->addComment($id, $_SESSION['user_id'], '[Forwarded to Admin] Ticket forwarded to admin for review.');
                set_flash_message('success', 'Ticket forwarded to admin for review.');
            } else {
                set_flash_message('danger', 'Failed to forward ticket.');
            }
            $this->returnResponse($id);
        }

        if ($newStatus === '__request_clarification__') {
            $message = '[Clarification Request] ' . ($clarificationNote ?: 'Please provide additional clarification on this ticket.');
            $previousStatus = $ticket['status'];
            if ($this->ticketModel->updateStatus($id, 'Awaiting Admin Approval')) {
                $this->logTicketStatusChange($id, $previousStatus, 'Awaiting Admin Approval');
                $this->ticketModel->addComment($id, $_SESSION['user_id'], $message);
                set_flash_message('success', 'Clarification request sent to admin.');
            } else {
                set_flash_message('danger', 'Failed to request clarification.');
            }
            $this->returnResponse($id);
        }

        if ($newStatus === '__commercial_review__') {
            if ($this->ticketModel->requestCommercialReview($id)) {
                $this->ticketModel->addComment($id, $_SESSION['user_id'], '[Commercial Review Requested] Developer flagged this ticket as not a bug fix. Ticket is now visible to admin only.');
                $this->logTicketWorkflowHistory(
                    $id,
                    'commercial_review_requested',
                    'Commercial Review Requested',
                    'Ticket hidden from the development team pending admin review.',
                    'admin_client'
                );
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_commercial_review_requested',
                    "Requested commercial review on ticket #$id"
                );
                $this->notify(function (NotificationService $notifications) use ($id, $clarificationNote) {
                    $notifications->notifyAdminsCommercialReviewRequired($id, $clarificationNote);
                });
                set_flash_message('success', 'Commercial review requested. Ticket is now hidden from the project team.');
                $this->returnTeamHiddenResponse(
                    $id,
                    'Ticket forwarded to Admin for commercial review. Redirecting to ticket list...'
                );
            } else {
                set_flash_message('danger', 'Failed to request commercial review.');
            }
            $this->returnResponse($id);
        }

        if (TicketWorkflowService::isSimplifiedStatus($newStatus)) {
            if (!TicketWorkflowService::canAdminChangeSimplifiedStatus($userRole)) {
                set_flash_message('danger', 'Unauthorized workflow status transition.');
                $this->returnResponse($id);
            }

            if (TicketWorkflowService::isValidSimplifiedTransition($ticket, $newStatus, $userRole)) {
                $fromLabel = TicketWorkflowService::mapToSimplifiedStatus($ticket['status']);
                $previousStatus = $ticket['status'];
                if ($this->ticketModel->updateStatus($id, $newStatus)) {
                    $commentText = "System Action: Ticket status transitioned from **{$fromLabel}** to **{$newStatus}**.";
                    $this->ticketModel->addComment($id, $_SESSION['user_id'], $commentText);
                    $this->logTicketStatusChange($id, $previousStatus, $newStatus);

                    $this->activityLogModel->log(
                        $_SESSION['user_id'],
                        $_SESSION['user_email'],
                        'ticket_status_transitioned',
                        "Transitioned ticket #$id from $fromLabel to $newStatus"
                    );

                    set_flash_message('success', "Ticket status updated to $newStatus.");
                } else {
                    set_flash_message('danger', 'Failed to transition ticket status.');
                }
            } else {
                set_flash_message('danger', 'Unauthorized workflow status transition.');
            }

            $this->returnResponse($id);
        }

        if (TicketWorkflowService::isValidTransition($ticket, $newStatus, $userRole)) {
            $previousStatus = $ticket['status'];
            if ($this->ticketModel->updateStatus($id, $newStatus)) {
                $commentText = "System Action: Ticket status transitioned from **{$ticket['status']}** to **{$newStatus}**.";
                $this->ticketModel->addComment($id, $_SESSION['user_id'], $commentText);
                $this->logTicketStatusChange($id, $previousStatus, $newStatus);

                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_status_transitioned',
                    "Transitioned ticket #$id from {$ticket['status']} to $newStatus"
                );

                set_flash_message('success', "Ticket status updated to $newStatus.");
            } else {
                set_flash_message('danger', 'Failed to transition ticket status.');
            }
        } else {
            set_flash_message('danger', 'Unauthorized workflow status transition.');
        }

        $this->returnResponse($id);
    }

    public function sendProposal()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $estimatedCost = (float)($_POST['estimated_cost'] ?? 0);
        $estimatedDeliveryDate = trim($_POST['estimated_delivery_date'] ?? '');

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        if ($estimatedCost <= 0 || empty($estimatedDeliveryDate)) {
            set_flash_message('danger', 'Please provide a valid estimated cost and delivery date.');
            redirect('tickets-view', ['id' => $id]);
        }

        if ($this->ticketModel->updateCommercialProposal($id, $estimatedCost, $estimatedDeliveryDate) &&
            $this->ticketModel->sendProposal($id)) {
            $this->logTicketStatusChange($id, $ticket['status'], 'Awaiting Client Review');
            $this->ticketModel->addDiscussion(
                $id,
                $_SESSION['user_id'],
                "Commercial proposal sent: Estimated cost " . format_rs_currency($estimatedCost, 2) . ", estimated delivery " . date('M d, Y', strtotime($estimatedDeliveryDate)) . "."
            );
            $this->ticketModel->addComment($id, $_SESSION['user_id'], "System Action: Admin sent commercial proposal to client for review.");
            set_flash_message('success', 'Proposal sent to client for review.');
        } else {
            set_flash_message('danger', 'Failed to send proposal.');
        }

        $this->returnResponse($id);
    }

    public function saveEstimation()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (!can_edit_ticket_estimation($_SESSION['user_role'] ?? '')) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $estimatedCost = (float)($_POST['estimated_cost'] ?? 0);
        $estimatedDeliveryDate = trim($_POST['estimated_delivery_date'] ?? '');
        $reason = trim($_POST['cost_change_reason'] ?? '');

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if ($estimatedCost <= 0 || $estimatedDeliveryDate === '') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Please provide a valid estimated cost and delivery date.']);
            }
            set_flash_message('danger', 'Please provide a valid estimated cost and delivery date.');
            redirect('tickets-view', ['id' => $id]);
        }

        $audit = $this->ticketModel->saveCostEstimation(
            $id,
            $estimatedCost,
            $estimatedDeliveryDate,
            $reason,
            (int)$_SESSION['user_id']
        );

        if (!$audit) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to save cost estimation.']);
            }
            set_flash_message('danger', 'Failed to save cost estimation.');
            redirect('tickets-view', ['id' => $id]);
        }

        $chatMessage = build_cost_estimation_chat_message($estimatedCost, $estimatedDeliveryDate, $reason);
        $this->ticketModel->addComment($id, $_SESSION['user_id'], $chatMessage, 'client');
        $this->logTicketWorkflowHistory(
            $id,
            'cost_updated',
            'Ticket Cost Updated',
            'Estimated cost: ' . format_rs_currency($estimatedCost, 2) . '. Delivery: ' . date('M d, Y', strtotime($estimatedDeliveryDate)) . ($reason !== '' ? "\nReason: {$reason}" : ''),
            'admin_client'
        );

        $this->activityLogModel->log(
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'ticket_cost_estimation_updated',
            "Updated cost estimation on ticket #$id"
        );

        $this->notify(function (NotificationService $notifications) use ($id, $audit) {
            $notifications->notifyClientTicketCostUpdated($id, $audit);
        });

        if ($this->isAjax()) {
            json_response(TicketWorkspaceService::buildAjaxPayload($id, 'cost_updated', 'Cost estimation saved successfully.'));
        }

        set_flash_message('success', 'Cost estimation saved successfully.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function assignTeam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $memberIds = $_POST['member_ids'] ?? [];
        if (!is_array($memberIds)) {
            $memberIds = [];
        }
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        $memberIds = array_values(array_filter($memberIds, function ($memberId) {
            return $memberId > 0;
        }));

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (empty($memberIds)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Please select at least one team member.']);
            }
            set_flash_message('danger', 'Please select at least one team member.');
            redirect('tickets-view', ['id' => $id]);
        }

        $projectMembers = $this->filterDeveloperAssignmentMembers(
            $this->projectModel->getProjectMembers($ticket['project_id'])
        );
        $allowedIds = array_map('intval', array_column($projectMembers, 'user_id'));
        foreach ($memberIds as $memberId) {
            if (!in_array($memberId, $allowedIds, true)) {
                if ($this->isAjax()) {
                    json_response(['success' => false, 'message' => 'One or more selected members are not valid for this project.']);
                }
                set_flash_message('danger', 'One or more selected members are not valid for this project.');
                redirect('tickets-view', ['id' => $id]);
            }
        }

        $previousAssignments = $this->ticketModel->getTicketAssignments($id);
        $hadAssignments = !empty($previousAssignments);

        if (!$this->ticketModel->syncTicketAssignments($id, $memberIds, (int)$_SESSION['user_id'])) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to update team assignment.']);
            }
            set_flash_message('danger', 'Failed to update team assignment.');
            redirect('tickets-view', ['id' => $id]);
        }

        $newAssignments = $this->ticketModel->getTicketAssignments($id);
        $chatMessage = build_ticket_assignment_chat_message($previousAssignments, $newAssignments);
        $this->ticketModel->addComment($id, $_SESSION['user_id'], $chatMessage, 'admin_dev');
        $assignmentNames = array_map(function ($row) {
            return $row['full_name'] ?? ('User #' . (int)$row['user_id']);
        }, $newAssignments);
        $this->logTicketWorkflowHistory(
            $id,
            'team_assigned',
            $hadAssignments ? 'Developers Assigned' : 'Developers Assigned',
            !empty($assignmentNames) ? implode(', ', $assignmentNames) : null,
            'internal'
        );

        $displayStatus = TicketWorkflowService::mapToSimplifiedStatus($ticket['status']);
        if ($displayStatus === 'Initiated') {
            $previousStatus = $ticket['status'];
            $this->ticketModel->updateStatus($id, 'Processing');
            $ticket = $this->ticketModel->findById($id);
            $displayStatus = TicketWorkflowService::mapToSimplifiedStatus($ticket['status']);
            $this->logTicketStatusChange($id, $previousStatus, $ticket['status']);
        }

        $this->activityLogModel->log(
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            'ticket_team_assigned',
            "Updated developer assignment on ticket #$id"
        );

        $previousIds = array_map('intval', array_column($previousAssignments, 'user_id'));
        $newIds = array_map('intval', array_column($newAssignments, 'user_id'));
        $addedIds = array_values(array_diff($newIds, $previousIds));
        $this->notify(function (NotificationService $notifications) use ($id, $addedIds) {
            foreach ($addedIds as $memberId) {
                $notifications->notifyTicketAssignment($id, $memberId);
            }
        });

        if ($this->isAjax()) {
            json_response(TicketWorkspaceService::buildAjaxPayload($id, 'assign_team', $hadAssignments ? 'Team assignment updated.' : 'Team assigned successfully.', [
                'display_status' => $displayStatus,
            ]));
        }

        set_flash_message('success', $hadAssignments ? 'Team assignment updated.' : 'Team assigned successfully.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function submitForReview()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $id = (int)($_POST['ticket_id'] ?? 0);
        $comment = trim($_POST['resolution_comment'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? '';

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (!can_submit_ticket_for_review($userRole, $ticket, $userId)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'You cannot submit this ticket for review.'], 403);
            }
            abort_403();
        }

        if (!$this->ticketModel->submitForAdminReview($id, $userId, $comment)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to submit ticket for review.']);
            }
            set_flash_message('danger', 'Failed to submit ticket for review.');
            redirect('tickets-view', ['id' => $id]);
        }

        $submitterName = $_SESSION['user_name'] ?? 'Developer';
        $chatMessage = build_resolution_submitted_chat_message($submitterName, $comment);
        $this->ticketModel->addComment($id, $userId, $chatMessage, 'admin_dev');
        $this->logTicketWorkflowHistory(
            $id,
            'review_submitted',
            'Submitted for Review',
            $comment !== '' ? $comment : null,
            'internal',
            $userId
        );

        $this->activityLogModel->log(
            $userId,
            $_SESSION['user_email'] ?? '',
            'ticket_submitted_for_review',
            "Submitted ticket #$id for admin review"
        );

        $this->notify(function (NotificationService $notifications) use ($id, $userId, $comment) {
            $notifications->notifyAdminsTicketSubmittedForReview($id, $userId, $comment);
        });

        if ($this->isAjax()) {
            json_response($this->buildReviewWorkflowAjaxResponse($id, 'Ticket submitted for admin review.'));
        }

        set_flash_message('success', 'Ticket submitted for admin review.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function requestAdminClarification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $id = (int)($_POST['ticket_id'] ?? 0);
        $comment = trim($_POST['clarification_comment'] ?? '');
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? '';

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (!can_request_admin_clarification($userRole, $ticket, $userId)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'You cannot request admin review on this ticket.'], 403);
            }
            abort_403();
        }

        if ($comment === '') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Please enter a message for the admin.']);
            }
            set_flash_message('danger', 'Please enter a message for the admin.');
            redirect('tickets-view', ['id' => $id]);
        }

        if (!$this->ticketModel->submitAdminGuidanceRequest($id, $userId, $comment)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to submit admin review request. A request may already be pending.']);
            }
            set_flash_message('danger', 'Failed to submit admin review request. A request may already be pending.');
            redirect('tickets-view', ['id' => $id]);
        }

        $requesterName = $_SESSION['user_name'] ?? 'Developer';
        $chatMessage = build_admin_guidance_request_chat_message($requesterName, $comment);
        $this->ticketModel->addComment($id, $userId, $chatMessage, 'admin_dev');
        $this->logTicketWorkflowHistory(
            $id,
            'admin_guidance_requested',
            'Admin Review Requested',
            $comment,
            'internal',
            $userId
        );

        $this->activityLogModel->log(
            $userId,
            $_SESSION['user_email'] ?? '',
            'ticket_admin_guidance_requested',
            "Requested admin review on ticket #$id"
        );

        if ($this->isAjax()) {
            json_response($this->buildReviewWorkflowAjaxResponse($id, 'Your request was sent to the admin.', 'admin_guidance_requested'));
        }

        set_flash_message('success', 'Your request was sent to the admin.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function respondToAdminGuidance()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $comment = trim($_POST['guidance_response_comment'] ?? '');
        $adminUserId = (int)($_SESSION['user_id'] ?? 0);

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (!can_admin_respond_to_guidance('admin', $ticket)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'No admin guidance request is pending for this ticket.']);
            }
            set_flash_message('danger', 'No admin guidance request is pending for this ticket.');
            redirect('tickets-view', ['id' => $id]);
        }

        if ($comment === '') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Please enter a response for the development team.']);
            }
            set_flash_message('danger', 'Please enter a response for the development team.');
            redirect('tickets-view', ['id' => $id]);
        }

        if (!$this->ticketModel->respondToAdminGuidance($id, $adminUserId, $comment)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to send your response.']);
            }
            set_flash_message('danger', 'Failed to send your response.');
            redirect('tickets-view', ['id' => $id]);
        }

        $adminName = $_SESSION['user_name'] ?? 'Admin';
        $chatMessage = build_admin_guidance_response_chat_message($adminName, $comment);
        $this->ticketModel->addComment($id, $adminUserId, $chatMessage, 'admin_dev');
        $this->logTicketWorkflowHistory(
            $id,
            'admin_guidance_responded',
            'Admin Review Response Sent',
            $comment,
            'internal',
            $adminUserId
        );

        $this->activityLogModel->log(
            $adminUserId,
            $_SESSION['user_email'] ?? '',
            'ticket_admin_guidance_responded',
            "Responded to admin guidance request on ticket #$id"
        );

        if ($this->isAjax()) {
            json_response($this->buildReviewWorkflowAjaxResponse($id, 'Your response was sent to the development team.', 'admin_guidance_responded'));
        }

        set_flash_message('success', 'Your response was sent to the development team.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function approveReview()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (!can_admin_review_ticket('admin', $ticket)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'This ticket is not pending admin review.']);
            }
            set_flash_message('danger', 'This ticket is not pending admin review.');
            redirect('tickets-view', ['id' => $id]);
        }

        if (!$this->ticketModel->approveAdminReview($id)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to approve ticket.']);
            }
            set_flash_message('danger', 'Failed to approve ticket.');
            redirect('tickets-view', ['id' => $id]);
        }

        $this->ticketModel->addComment($id, (int)$_SESSION['user_id'], build_review_approved_chat_message(), 'admin_dev');
        $this->logTicketWorkflowHistory($id, 'review_approved', 'Completed', 'Admin approved and completed the ticket.', 'all');

        $this->activityLogModel->log(
            (int)$_SESSION['user_id'],
            $_SESSION['user_email'] ?? '',
            'ticket_review_approved',
            "Approved and completed ticket #$id"
        );

        $this->notify(function (NotificationService $notifications) use ($id) {
            $notifications->notifyDeveloperTicketApproved($id);
            $notifications->notifyClientTicketCompleted($id);
        });

        if ($this->isAjax()) {
            json_response($this->buildReviewWorkflowAjaxResponse($id, 'Ticket marked as Completed.', 'review_approved'));
        }

        set_flash_message('success', 'Ticket marked as Completed.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function returnToDevelopment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $comment = trim($_POST['review_comment'] ?? '');
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (!can_admin_review_ticket('admin', $ticket)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'This ticket is not pending admin review.']);
            }
            set_flash_message('danger', 'This ticket is not pending admin review.');
            redirect('tickets-view', ['id' => $id]);
        }

        if (!$this->ticketModel->returnTicketToDevelopment($id, (int)$_SESSION['user_id'], $comment)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to return ticket to development.']);
            }
            set_flash_message('danger', 'Failed to return ticket to development.');
            redirect('tickets-view', ['id' => $id]);
        }

        $this->ticketModel->addComment($id, (int)$_SESSION['user_id'], build_review_returned_chat_message($comment), 'admin_dev');
        $this->logTicketWorkflowHistory(
            $id,
            'returned_to_development',
            'Returned to Development',
            $comment !== '' ? $comment : null,
            'internal'
        );

        $this->activityLogModel->log(
            (int)$_SESSION['user_id'],
            $_SESSION['user_email'] ?? '',
            'ticket_returned_to_development',
            "Returned ticket #$id to development"
        );

        $this->notify(function (NotificationService $notifications) use ($id, $comment) {
            $notifications->notifyDeveloperTicketReturned($id, $comment);
        });

        if ($this->isAjax()) {
            json_response($this->buildReviewWorkflowAjaxResponse($id, 'Ticket returned to the development team.', 'return_development'));
        }

        set_flash_message('success', 'Ticket returned to the development team.');
        redirect('tickets-view', ['id' => $id]);
    }

    public function confirmPayment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        if ($this->ticketModel->confirmPayment($id)) {
            $this->logTicketStatusChange($id, $ticket['status'], 'Payment Confirmed');
            $this->ticketModel->addDiscussion($id, $_SESSION['user_id'], 'Payment confirmed by admin. Ticket is now visible to the project team.');
            $this->ticketModel->addComment($id, $_SESSION['user_id'], 'System Action: Payment confirmed. Ticket visible to project team.');
            set_flash_message('success', 'Payment confirmed.');
        } else {
            set_flash_message('danger', 'Failed to confirm payment.');
        }

        $this->returnResponse($id);
    }

    public function reclassify()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $newCategory = trim($_POST['category'] ?? '');

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket || !in_array($newCategory, array_merge(['Bug Fix'], TicketWorkflowService::getCommercialCategories()), true)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Invalid ticket or category.']);
            }
            set_flash_message('danger', 'Invalid ticket or category.');
            redirect('tickets');
        }

        $previousCategory = $ticket['category'];
        $initialState = TicketWorkflowService::getInitialWorkflowState($newCategory, 'admin');
        $previousStatus = $ticket['status'];
        if ($this->ticketModel->reclassifyTicket($id, $newCategory, $initialState['status'], $initialState['is_team_visible'])) {
            if ($newCategory === 'Bug Fix') {
                $this->ticketModel->syncBugFixProjectTeamAssignments($id, (int)$ticket['project_id'], (int)$_SESSION['user_id']);
            }

            $this->ticketModel->addComment($id, $_SESSION['user_id'], "System Action: Admin reclassified ticket to **{$newCategory}**. Workflow resumed.");
            if ((string)$initialState['status'] !== (string)$previousStatus) {
                $this->logTicketStatusChange($id, $previousStatus, $initialState['status']);
            }
            $this->logTicketWorkflowHistory(
                $id,
                'category_reclassified',
                'Category Reclassified',
                "Category changed from {$previousCategory} to {$newCategory}.",
                'all'
            );

            if ($this->isAjax()) {
                json_response(TicketWorkspaceService::buildAjaxPayload($id, 'reclassify', 'Ticket reclassified and workflow resumed.'));
            }
            set_flash_message('success', 'Ticket reclassified and workflow resumed.');
        } else {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to reclassify ticket.']);
            }
            set_flash_message('danger', 'Failed to reclassify ticket.');
        }

        redirect('tickets-view', ['id' => $id]);
    }

    public function addDiscussion()
    {
        header('Content-Type: application/json');
        $userRole = $_SESSION['user_role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $ticketId = (int)($_GET['id'] ?? 0);
            $lastId = (int)($_GET['last_id'] ?? 0);

            if (!TicketWorkflowService::canPostDiscussion($userRole)) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized view.']);
                exit;
            }

            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
                exit;
            }

            if (!$this->ticketModel->canUserAccessTicket($ticket, $_SESSION['user_id'], $userRole)) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
                exit;
            }

            $newMessages = $this->ticketModel->getDiscussions($ticketId, $lastId);
            echo json_encode([
                'success' => true,
                'messages' => $newMessages
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        try {
            verify_csrf();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!TicketWorkflowService::canPostDiscussion($userRole)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized role.']);
            exit;
        }

        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
            exit;
        }

        if (!$this->ticketModel->canUserAccessTicket($ticket, $_SESSION['user_id'], $userRole)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit;
        }

        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
            exit;
        }

        if ($this->ticketModel->addDiscussion($ticketId, $_SESSION['user_id'], $message)) {
            $newId = $this->ticketModel->getLastInsertId();
            $this->ticketModel->addComment($ticketId, $_SESSION['user_id'], $message, 'client');
            $this->notify(function (NotificationService $notifications) use ($ticketId, $message) {
                $notifications->notifyCommercialDiscussionUpdate($ticketId, $message, (int) $_SESSION['user_id']);
            });
            $newPost = $this->ticketModel->getDiscussionById($newId);
            echo json_encode([
                'success' => true,
                'message' => 'Message posted to client-admin discussion.',
                'post' => $newPost
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to post message.']);
            exit;
        }
    }

    public function addInternalDiscussion()
    {
        header('Content-Type: application/json');
        $userRole = $_SESSION['user_role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $ticketId = (int)($_GET['id'] ?? 0);
            $lastId = (int)($_GET['last_id'] ?? 0);

            $canViewInternal = in_array($userRole, ['admin', 'developer', 'intern'], true);
            if (!$canViewInternal) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized view.']);
                exit;
            }

            $ticket = $this->ticketModel->findById($ticketId);
            if (!$ticket) {
                echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
                exit;
            }

            if (!$this->ticketModel->canUserAccessTicket($ticket, $_SESSION['user_id'], $userRole)) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
                exit;
            }

            $newMessages = $this->ticketModel->getInternalDiscussions($ticketId, $lastId);
            echo json_encode([
                'success' => true,
                'messages' => $newMessages
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        try {
            verify_csrf();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!in_array($userRole, ['admin', 'developer', 'intern'], true)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized role.']);
            exit;
        }

        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
            exit;
        }

        if (!$this->ticketModel->canUserAccessTicket($ticket, $_SESSION['user_id'], $userRole)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access to this ticket.']);
            exit;
        }

        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
            exit;
        }

        if ($this->ticketModel->addInternalDiscussion($ticketId, $_SESSION['user_id'], $message)) {
            $newId = $this->ticketModel->getLastInsertId();
            $newPost = $this->ticketModel->getInternalDiscussionById($newId);
            echo json_encode([
                'success' => true,
                'message' => 'Message posted to Admin-Team discussion.',
                'post' => $newPost
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save message.']);
            exit;
        }
    }

    public function forwardForApproval()
    {
        header('Content-Type: application/json');
        $userRole = $_SESSION['user_role'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        try {
            verify_csrf();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!in_array($userRole, ['developer', 'intern'], true)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized role.']);
            exit;
        }

        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
            exit;
        }

        if (!$this->ticketModel->canUserAccessTicket($ticket, $_SESSION['user_id'], $userRole)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access to this ticket.']);
            exit;
        }

        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Explanation cannot be empty.']);
            exit;
        }

        // Check if ticket status is in active transitions
        $activeForReview = ['Open', 'In Development', 'Reopened', 'On Hold'];
        if (!in_array($ticket['status'], $activeForReview, true)) {
            echo json_encode(['success' => false, 'message' => 'Ticket is not in a state that can be forwarded.']);
            exit;
        }

        $storedMessage = "[Forwarded for Approval] " . $message;
        $previousStatus = $ticket['status'];
        $dbSuccess = $this->ticketModel->addInternalDiscussion($ticketId, $_SESSION['user_id'], $storedMessage);
        $newId = $dbSuccess ? $this->ticketModel->getLastInsertId() : 0;
        $statusSuccess = $this->ticketModel->updateStatus($ticketId, 'Awaiting Admin Approval');

        if ($dbSuccess && $statusSuccess) {
            $this->logTicketStatusChange($ticketId, $previousStatus, 'Awaiting Admin Approval');
            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'ticket_forwarded_approval',
                "Forwarded ticket #$ticketId to admin for approval"
            );

            $newPost = $this->ticketModel->getInternalDiscussionById($newId);

            echo json_encode([
                'success' => true,
                'message' => 'Ticket forwarded to admin for approval.',
                'post' => $newPost
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to forward ticket.']);
            exit;
        }
    }


    public function addComment()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->pollTeamComments();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $channel = trim($_POST['chat_channel'] ?? $_GET['channel'] ?? 'team');
        $channel = in_array($channel, ['team', 'client', 'admin_dev'], true) ? $channel : 'team';

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $hasAttachment = isset($_FILES['team_chat_attachment'])
            && is_array($_FILES['team_chat_attachment'])
            && ($_FILES['team_chat_attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Ticket not found.']);
            }
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        if (!$this->assertFloatingChatAccess($channel, $ticket)) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            abort_403();
        }

        $this->checkTicketAccess($ticket);

        if ($comment === '' && !$hasAttachment) {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Please enter a message or attach a file.']);
            }
            set_flash_message('danger', 'Please enter a message or attach a file.');
            redirect('tickets-view', ['id' => $ticketId]);
        }

        if ($this->ticketModel->addComment($ticketId, $_SESSION['user_id'], $comment, $channel)) {
            $commentId = (int)$this->ticketModel->getLastInsertId();

            if ($hasAttachment) {
                $uploaded = $this->processTeamChatAttachmentUpload($commentId, $_FILES['team_chat_attachment']);
                if (!$uploaded) {
                    if ($this->isAjax()) {
                        json_response(['success' => false, 'message' => 'Message saved but file upload failed. Check file type and size.']);
                    }
                    set_flash_message('danger', 'Message saved but file upload failed.');
                    redirect('tickets-view', ['id' => $ticketId]);
                }
            }

            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'ticket_comment_added',
                "Added comment on ticket #$ticketId"
            );

            if ($channel === 'client' && $comment !== '') {
                $this->notify(function (NotificationService $notifications) use ($ticketId, $comment) {
                    $notifications->notifyCommercialDiscussionUpdate($ticketId, $comment, (int) $_SESSION['user_id']);
                });
            }

            $newComment = $this->ticketModel->getCommentById($commentId);
            if ($newComment) {
                $newComment = team_chat_enrich_comments([$newComment], $ticketId)[0] ?? $newComment;
            }

            if ($this->isAjax()) {
                json_response([
                    'success' => true,
                    'message' => 'Message sent.',
                    'comment' => $newComment,
                ]);
            }
            set_flash_message('success', 'Comment added.');
        } else {
            if ($this->isAjax()) {
                json_response(['success' => false, 'message' => 'Failed to add comment.']);
            }
            set_flash_message('danger', 'Failed to add comment.');
        }

        $this->returnResponse($ticketId);
    }

    public function downloadTeamChatAttachment()
    {
        $attachmentId = (int)($_GET['id'] ?? $_GET['attachment_id'] ?? 0);
        $attachment = $this->teamChatAttachmentModel->findById($attachmentId);

        if (!$attachment) {
            abort_404();
        }

        $channel = $attachment['channel'] ?? 'team';
        $ticket = $this->ticketModel->findById((int)$attachment['ticket_id']);
        if (!$ticket) {
            abort_404();
        }

        if (!$this->assertFloatingChatAccess($channel, $ticket)) {
            abort_403();
        }

        $this->checkTicketAccess($ticket);

        $filePath = __DIR__ . '/../../' . self::TEAM_CHAT_UPLOAD_DIR . $attachment['file_name'];
        if (!is_file($filePath)) {
            abort_404();
        }

        $originalName = $attachment['original_name'] ?? $attachment['file_name'];
        $mimeType = team_chat_resolve_mime_type($originalName, $attachment['file_type'] ?? null);
        $kind = team_chat_attachment_kind($mimeType, $originalName);
        $forceDownload = isset($_GET['download']) && (string)$_GET['download'] === '1';
        $inline = !$forceDownload && $kind === 'pdf';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . team_chat_content_disposition($originalName, $inline));
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    private function pollTeamComments()
    {
        header('Content-Type: application/json');

        $channel = trim($_GET['channel'] ?? 'team');
        $channel = in_array($channel, ['team', 'client', 'admin_dev'], true) ? $channel : 'team';

        $ticketId = (int)($_GET['id'] ?? $_GET['ticket_id'] ?? 0);
        $lastId = (int)($_GET['last_id'] ?? 0);

        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
            exit;
        }

        if (!$this->assertFloatingChatAccess($channel, $ticket)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }

        $this->checkTicketAccess($ticket);

        $comments = $this->ticketModel->getCommentsSince($ticketId, $lastId, $channel);
        $comments = team_chat_enrich_comments($comments, $ticketId);
        echo json_encode([
            'success' => true,
            'comments' => $comments,
        ]);
        exit;
    }

    public function addAttachment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $ticket = $this->ticketModel->findById($ticketId);

        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (($_SESSION['user_role'] ?? '') !== 'client') {
            set_flash_message('danger', 'Only clients can upload attachments.');
            $this->returnResponse($ticketId, [], 'attachment_updated');
        }

        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            set_flash_message('danger', 'Error uploading file. Please select a valid file.');
            $this->returnResponse($ticketId, [], 'attachment_updated');
        }

        if ($this->processSingleUpload($ticketId, $_FILES['attachment'])) {
            set_flash_message('success', 'File attached successfully.');
        } else {
            set_flash_message('danger', 'Failed to upload attachment.');
        }

        $this->returnResponse($ticketId, [], 'attachment_updated');
    }

    public function deleteAttachment()
    {
        $attachmentId = (int)($_GET['id'] ?? 0);
        $attachment = $this->ticketModel->getAttachmentById($attachmentId);

        if (!$attachment) {
            set_flash_message('danger', 'Attachment not found.');
            redirect('tickets');
        }

        $ticket = $this->ticketModel->findById($attachment['ticket_id']);
        if (!$ticket) {
            set_flash_message('danger', 'Associated ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        $userRole = $_SESSION['user_role'] ?? '';
        if ($userRole === 'client') {
            if ((int)$attachment['user_id'] !== (int)$_SESSION['user_id']) {
                abort_403();
            }
        } elseif ($userRole !== 'admin') {
            abort_403();
        }

        $filePath = __DIR__ . '/../../' . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if ($this->ticketModel->deleteAttachment($attachmentId)) {
            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'ticket_attachment_deleted',
                "Deleted attachment ID $attachmentId ({$attachment['file_name']}) from ticket #{$attachment['ticket_id']}"
            );
            set_flash_message('success', 'Attachment deleted successfully.');
        } else {
            set_flash_message('danger', 'Failed to delete attachment record.');
        }

        $this->returnResponse($attachment['ticket_id'], [], 'attachment_updated');
    }

    public function downloadAttachment()
    {
        $attachmentId = (int)($_GET['id'] ?? 0);
        $attachment = $this->ticketModel->getAttachmentById($attachmentId);

        if (!$attachment) {
            abort_404();
        }

        $ticket = $this->ticketModel->findById($attachment['ticket_id']);
        if (!$ticket) {
            abort_404();
        }

        $this->checkTicketAccess($ticket);

        $filePath = __DIR__ . '/../../' . $attachment['file_path'];
        if (!is_file($filePath)) {
            abort_404();
        }

        $mimeType = $attachment['mime_type'] ?? mime_content_type($filePath) ?: 'application/octet-stream';
        $safeName = str_replace(['"', "\r", "\n"], '', $attachment['file_name']);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        readfile($filePath);
        exit;
    }


    private function handleAttachmentUploads($ticketId, $files)
    {
        if (!$files || !isset($files['name'])) {
            return;
        }

        if (!is_array($files['name'])) {
            if ($files['error'] === UPLOAD_ERR_OK) {
                $this->processSingleUpload($ticketId, $files);
            }
            return;
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $this->processSingleUpload($ticketId, $file);
        }
    }

    private function processSingleUpload($ticketId, $file)
    {
        $originalName = basename($file['name']);
        $fileSize = $file['size'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = $file['type'] ?? null;

        if ($fileSize > self::MAX_ATTACHMENT_SIZE) {
            return false;
        }

        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'sh', 'py', 'pl', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd', 'js'];
        if (in_array($ext, $blockedExtensions, true) || !in_array($ext, self::ALLOWED_ATTACHMENT_EXTENSIONS, true)) {
            return false;
        }

        $uploadDir = __DIR__ . '/../../storage/uploads/attachments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $htaccessPath = __DIR__ . '/../../storage/uploads/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi|exe)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>";
            file_put_contents($htaccessPath, $htaccessContent);
        }

        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        $dbPath = 'storage/uploads/attachments/' . $uniqueName;

        if ($this->ticketModel->addAttachment($ticketId, $_SESSION['user_id'], $originalName, $dbPath, $fileSize, $mimeType)) {
            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'ticket_attachment_uploaded',
                "Uploaded attachment: $originalName to ticket #$ticketId"
            );
            return true;
        }

        unlink($targetPath);
        return false;
    }

    private function processTeamChatAttachmentUpload($commentId, $file)
    {
        $originalName = basename($file['name']);
        $fileSize = (int)$file['size'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = $file['type'] ?? null;

        if ($fileSize <= 0 || $fileSize > self::MAX_ATTACHMENT_SIZE) {
            return false;
        }

        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'sh', 'py', 'pl', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd', 'js'];
        $allowedExtensions = team_chat_allowed_extensions();
        if (in_array($ext, $blockedExtensions, true) || !in_array($ext, $allowedExtensions, true)) {
            return false;
        }

        $uploadDir = __DIR__ . '/../../' . self::TEAM_CHAT_UPLOAD_DIR;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $htaccessPath = __DIR__ . '/../../storage/uploads/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi|exe)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>";
            file_put_contents($htaccessPath, $htaccessContent);
        }

        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        $attachmentId = $this->teamChatAttachmentModel->create(
            $commentId,
            $_SESSION['user_id'],
            $uniqueName,
            $originalName,
            $fileSize,
            $mimeType
        );

        if (!$attachmentId) {
            if (is_file($targetPath)) {
                unlink($targetPath);
            }
            return false;
        }

        return true;
    }

    private function notify(callable $callback): void
    {
        try {
            $callback(new NotificationService());
        } catch (Throwable $e) {
            // Notification failures must never interrupt workflow actions.
        }
    }
}
