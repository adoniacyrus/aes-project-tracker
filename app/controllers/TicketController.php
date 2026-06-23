<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../services/TicketWorkflowService.php';

class TicketController
{
    private $ticketModel;
    private $projectModel;
    private $userModel;
    private $taskModel;
    private $activityLogModel;

    private const ALLOWED_ATTACHMENT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
    private const MAX_ATTACHMENT_SIZE = 10485760;

    public function __construct()
    {
        AuthMiddleware::check();

        $this->ticketModel = new TicketModel();
        $this->projectModel = new ProjectModel();
        $this->userModel = new UserModel();
        $this->taskModel = new TaskModel();
        $this->activityLogModel = new ActivityLogModel();
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
    private function returnResponse($ticketId, $extra = [])
    {
        $ticketCode = get_ticket_code_by_id($ticketId);
        if ($this->isAjax()) {
            $msgType = has_flash_message('success') ? 'success' : (has_flash_message('danger') ? 'danger' : 'info');
            $msg = get_flash_message($msgType);
            json_response(array_merge([
                'success' => ($msgType === 'success'),
                'message' => $msg,
                'refresh' => route('tickets-view', ['id' => $ticketId, 'partial' => 'dynamic']),
                'target' => '#ticket-dynamic-content',
            ], $extra));
        }
        redirect('tickets-view', ['ticket_code' => $ticketCode]);
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

    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $projectId = (int)($_GET['project_id'] ?? 0);
        $category = trim($_GET['category'] ?? '');
        $priority = trim($_GET['priority'] ?? '');
        $status = trim($_GET['status'] ?? '');

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
        $totalPages = ceil($totalTickets / $limit);

        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/tickets/_list_content.php',
                compact('tickets', 'search', 'projectId', 'category', 'priority', 'status', 'pageNum', 'totalPages', 'totalTickets', 'showAssignee'),
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

        $comments = $this->ticketModel->getComments($id);
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
        $isCommercial = TicketWorkflowService::isCommercialCategory($ticket['category']);
        $canCreateTicket = TicketWorkflowService::canCreateTicket($userRole);
        $isAdmin = ($userRole === 'admin');
        $canDiscuss = TicketWorkflowService::canViewDiscussion($userRole);
        $canManageTasks = can_manage_tasks($userRole);
        $currentUserId = (int)$_SESSION['user_id'];
        $taskAssignableMembers = filter_task_assignable_members($projectMembers);

        if (isset($_GET['partial']) && $_GET['partial'] === 'dynamic' && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/tickets/_dynamic_content.php',
                compact('ticket', 'allowedTransitions', 'isCommercial', 'isAdmin', 'canDiscuss', 'canViewInternal', 'userRole', 'projectMembers', 'tasks', 'discussions', 'internalDiscussions', 'canManageTasks', 'currentUserId', 'taskAssignableMembers'),
                'tickets-view',
                ['id' => $id, 'partial' => 'dynamic']
            );
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

            $assignedTo = null;
            if ($userRole === 'admin' && !empty($_POST['assigned_to'])) {
                $assignedTo = (int)$_POST['assigned_to'];
            }

            $data = [
                'project_id'  => (int)($_POST['project_id'] ?? 0),
                'title'       => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category'    => $category,
                'priority'    => trim($_POST['priority'] ?? 'medium'),
                'created_by'  => $userId,
                'assigned_to' => $assignedTo,
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

        if ($userRole !== 'admin' &&
            (int)$ticket['created_by'] !== (int)$_SESSION['user_id'] &&
            (int)$ticket['assigned_to'] !== (int)$_SESSION['user_id']) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
                exit;
            }
            abort_403();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $assignedTo = $ticket['assigned_to'];
            if ($userRole === 'admin') {
                $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            }

            $data = [
                'project_id'  => (int)($_POST['project_id'] ?? $ticket['project_id']),
                'title'       => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category'    => trim($_POST['category'] ?? $ticket['category']),
                'priority'    => trim($_POST['priority'] ?? $ticket['priority']),
                'assigned_to' => $assignedTo,
                'due_date'    => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'      => trim($_POST['status'] ?? $ticket['status'])
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

            if ($this->ticketModel->updateTicket($id, $data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_updated',
                    "Updated ticket #$id: {$data['title']}"
                );
                
                $ticketCode = get_ticket_code_by_id($id);
                if ($this->isAjax()) {
                    json_response([
                        'success' => true,
                        'message' => 'Ticket updated successfully.',
                        'refresh' => route('tickets-view', ['id' => $id, 'partial' => 'dynamic']),
                        'target' => '#ticket-dynamic-content',
                    ]);
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
            if ($this->ticketModel->updateStatus($id, 'Awaiting Admin Approval')) {
                $this->ticketModel->addComment($id, $_SESSION['user_id'], '[Forwarded to Admin] Ticket forwarded to admin for review.');
                set_flash_message('success', 'Ticket forwarded to admin for review.');
            } else {
                set_flash_message('danger', 'Failed to forward ticket.');
            }
            $this->returnResponse($id);
        }

        if ($newStatus === '__request_clarification__') {
            $message = '[Clarification Request] ' . ($clarificationNote ?: 'Please provide additional clarification on this ticket.');
            if ($this->ticketModel->updateStatus($id, 'Awaiting Admin Approval')) {
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
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_commercial_review_requested',
                    "Requested commercial review on ticket #$id"
                );
                set_flash_message('success', 'Commercial review requested. Ticket is now hidden from the project team.');
            } else {
                set_flash_message('danger', 'Failed to request commercial review.');
            }
            $this->returnResponse($id);
        }

        if (TicketWorkflowService::isValidTransition($ticket, $newStatus, $userRole)) {
            if ($this->ticketModel->updateStatus($id, $newStatus)) {
                $commentText = "System Action: Ticket status transitioned from **{$ticket['status']}** to **{$newStatus}**.";
                $this->ticketModel->addComment($id, $_SESSION['user_id'], $commentText);

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
            $this->ticketModel->addDiscussion($id, $_SESSION['user_id'], 'Payment confirmed by admin. Awaiting team assignment.');
            $this->ticketModel->addComment($id, $_SESSION['user_id'], 'System Action: Payment confirmed. Ready for team assignment.');
            set_flash_message('success', 'Payment confirmed.');
        } else {
            set_flash_message('danger', 'Failed to confirm payment.');
        }

        $this->returnResponse($id);
    }

    public function assignTeam()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $assigneeId = (int)($_POST['assigned_to'] ?? 0);

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket || $assigneeId <= 0) {
            set_flash_message('danger', 'Invalid ticket or assignee.');
            redirect('tickets');
        }

        if ($this->ticketModel->assignAndStartDevelopment($id, $assigneeId)) {
            $this->ticketModel->addComment($id, $_SESSION['user_id'], 'System Action: Admin assigned developer and started development.');
            set_flash_message('success', 'Developer assigned and development started.');
        } else {
            set_flash_message('danger', 'Failed to assign team member.');
        }

        $this->returnResponse($id);
    }

    public function assignDeveloper()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }

        $id = (int)($_POST['ticket_id'] ?? 0);
        $assigneeId = (int)($_POST['assigned_to'] ?? 0);

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket || $assigneeId <= 0) {
            set_flash_message('danger', 'Invalid ticket or assignee.');
            redirect('tickets');
        }

        $newStatus = $ticket['category'] === 'Bug Fix' ? 'In Development' : $ticket['status'];

        if ($this->ticketModel->assignTicket($id, $assigneeId, $newStatus)) {
            $this->ticketModel->addComment($id, $_SESSION['user_id'], 'System Action: Admin assigned a developer to this ticket.');
            set_flash_message('success', 'Developer assigned successfully.');
        } else {
            set_flash_message('danger', 'Failed to assign developer.');
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
            set_flash_message('danger', 'Invalid ticket or category.');
            redirect('tickets');
        }

        $initialState = TicketWorkflowService::getInitialWorkflowState($newCategory, 'admin');
        if ($this->ticketModel->reclassifyTicket($id, $newCategory, $initialState['status'], $initialState['is_team_visible'])) {
            $this->ticketModel->addComment($id, $_SESSION['user_id'], "System Action: Admin reclassified ticket to **{$newCategory}**. Workflow resumed.");
            set_flash_message('success', 'Ticket reclassified and workflow resumed.');
        } else {
            set_flash_message('danger', 'Failed to reclassify ticket.');
        }

        $this->returnResponse($id);
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
        $activeForReview = ['Open', 'In Development', 'Reopened', 'On Hold', 'Approved'];
        if (!in_array($ticket['status'], $activeForReview, true)) {
            echo json_encode(['success' => false, 'message' => 'Ticket is not in a state that can be forwarded.']);
            exit;
        }

        $storedMessage = "[Forwarded for Approval] " . $message;
        $dbSuccess = $this->ticketModel->addInternalDiscussion($ticketId, $_SESSION['user_id'], $storedMessage);
        $newId = $dbSuccess ? $this->ticketModel->getLastInsertId() : 0;
        $statusSuccess = $this->ticketModel->updateStatus($ticketId, 'Awaiting Admin Approval');

        if ($dbSuccess && $statusSuccess) {
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkTicketAccess($ticket);

        if (empty($comment)) {
            set_flash_message('danger', 'Comment content cannot be empty.');
            redirect('tickets-view', ['id' => $ticketId]);
        }

        if ($this->ticketModel->addComment($ticketId, $_SESSION['user_id'], $comment)) {
            $this->activityLogModel->log(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                'ticket_comment_added',
                "Added comment on ticket #$ticketId"
            );
            $comments = $this->ticketModel->getComments($ticketId);
            $newComment = end($comments) ?: null;
            if ($this->isAjax()) {
                json_response([
                    'success' => true,
                    'message' => 'Comment added.',
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

        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            set_flash_message('danger', 'Error uploading file. Please select a valid file.');
            redirect('tickets-view', ['id' => $ticketId]);
        }

        if ($this->processSingleUpload($ticketId, $_FILES['attachment'])) {
            set_flash_message('success', 'File attached successfully.');
        } else {
            set_flash_message('danger', 'Failed to upload attachment.');
        }

        $this->returnResponse($ticketId);
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

        if (($_SESSION['user_role'] ?? '') !== 'admin' &&
            (int)$attachment['user_id'] !== (int)$_SESSION['user_id']) {
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

        $this->returnResponse($attachment['ticket_id']);
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
}
