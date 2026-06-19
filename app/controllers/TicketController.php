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
     * Enforce access to project
     */
    private function checkProjectAccess($projectId)
    {
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            return true;
        }

        if (!$this->projectModel->isMember($projectId, $_SESSION['user_id'])) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit;
        }
        return true;
    }

    /**
     * List tickets
     */
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

        // If project filter selected, verify access
        if ($projectId > 0) {
            $this->checkProjectAccess($projectId);
        }

        $tickets = $this->ticketModel->getTickets($userId, $userRole, $search, $offset, $limit, $projectId, $category, $priority, $status);
        $totalTickets = $this->ticketModel->getTicketsCount($userId, $userRole, $search, $projectId, $category, $priority, $status);
        $totalPages = ceil($totalTickets / $limit);

        // Fetch all accessible projects to populate filter dropdown
        $projects = $this->projectModel->getProjects($userId, $userRole, '', 0, 100, '', 0);

        $pageTitle = 'Tickets Directory';
        $view = __DIR__ . '/../views/tickets/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * View ticket details, comments, tasks and transitions
     */
    public function view()
    {
        $id = (int)($_GET['id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        // Verify user has access to the associated project
        $this->checkProjectAccess($ticket['project_id']);

        // Fetch project team members (for assignment dropdown)
        $projectMembers = $this->projectModel->getProjectMembers($ticket['project_id']);

        // Fetch comments and attachments
        $comments = $this->ticketModel->getComments($id);
        $attachments = $this->ticketModel->getAttachments($id);

        // Fetch tasks
        $tasks = $this->taskModel->getTasksByTicket($id);

        // Allowed transitions for this user role
        $allowedTransitions = TicketWorkflowService::getAllowedTransitions(
            $ticket['category'],
            $ticket['status'],
            $_SESSION['user_role']
        );

        $pageTitle = "Ticket #" . $ticket['id'] . ": " . $ticket['title'];
        $view = __DIR__ . '/../views/tickets/view.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Create a ticket
     */
    public function create()
    {
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        // Get pre-selected project if available
        $selectedProjectId = (int)($_GET['project_id'] ?? 0);
        if ($selectedProjectId > 0) {
            $this->checkProjectAccess($selectedProjectId);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $data = [
                'project_id'  => (int)($_POST['project_id'] ?? 0),
                'title'       => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category'    => trim($_POST['category'] ?? 'Bug Fix'),
                'priority'    => trim($_POST['priority'] ?? 'medium'),
                'created_by'  => $userId,
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'due_date'    => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'      => 'Open' // Always starts as Open
            ];

            // Verify project access
            $this->checkProjectAccess($data['project_id']);

            if (empty($data['title']) || empty($data['description'])) {
                set_flash_message('danger', 'Please enter a ticket title and description.');
                redirect('tickets-create', ['project_id' => $data['project_id']]);
            }

            $ticketId = $this->ticketModel->createTicket($data);
            if ($ticketId) {
                // Log action
                $this->activityLogModel->log(
                    $userId,
                    $_SESSION['user_email'],
                    'ticket_created',
                    "Created ticket #$ticketId: {$data['title']} in project ID {$data['project_id']}"
                );

                set_flash_message('success', 'Ticket created successfully.');
                redirect('tickets-view', ['id' => $ticketId]);
            } else {
                set_flash_message('danger', 'Error creating ticket. Please try again.');
                redirect('tickets-create', ['project_id' => $data['project_id']]);
            }
        }

        // Get projects list that the user is assigned to
        $projects = $this->projectModel->getProjects($userId, $userRole, '', 0, 100, '', 0);

        // Prepopulate users of selected project
        $projectMembers = [];
        if ($selectedProjectId > 0) {
            $projectMembers = $this->projectModel->getProjectMembers($selectedProjectId);
        }

        $pageTitle = 'Create New Ticket';
        $view = __DIR__ . '/../views/tickets/create.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Edit a ticket
     */
    public function edit()
    {
        $id = (int)($_GET['id'] ?? 0);
        $ticket = $this->ticketModel->findById($id);

        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        // Access check
        $this->checkProjectAccess($ticket['project_id']);

        // Check permission to edit (Creator, Assignee, or Admin)
        if (($_SESSION['user_role'] ?? '') !== 'admin' && 
            (int)$ticket['created_by'] !== (int)$_SESSION['user_id'] && 
            (int)$ticket['assigned_to'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $data = [
                'project_id'  => (int)($_POST['project_id'] ?? $ticket['project_id']),
                'title'       => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category'    => trim($_POST['category'] ?? $ticket['category']),
                'priority'    => trim($_POST['priority'] ?? $ticket['priority']),
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'due_date'    => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status'      => trim($_POST['status'] ?? $ticket['status'])
            ];

            // Verify project access
            $this->checkProjectAccess($data['project_id']);

            if (empty($data['title']) || empty($data['description'])) {
                set_flash_message('danger', 'Please enter a ticket title and description.');
                redirect('tickets-edit', ['id' => $id]);
            }

            if ($this->ticketModel->updateTicket($id, $data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_updated',
                    "Updated ticket #$id: {$data['title']}"
                );
                set_flash_message('success', 'Ticket updated successfully.');
                redirect('tickets-view', ['id' => $id]);
            } else {
                set_flash_message('danger', 'Error updating ticket.');
                redirect('tickets-edit', ['id' => $id]);
            }
        }

        // Fetch accessible projects & members
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        $projects = $this->projectModel->getProjects($userId, $userRole, '', 0, 100, '', 0);
        $projectMembers = $this->projectModel->getProjectMembers($ticket['project_id']);

        $pageTitle = 'Edit Ticket #' . $ticket['id'];
        $view = __DIR__ . '/../views/tickets/edit.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Submit ticket for workflow state transition
     */
    public function transition()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('tickets');
        }

        verify_csrf();

        $id = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        $ticket = $this->ticketModel->findById($id);
        if (!$ticket) {
            set_flash_message('danger', 'Ticket not found.');
            redirect('tickets');
        }

        $this->checkProjectAccess($ticket['project_id']);

        $userRole = $_SESSION['user_role'];

        // Validate workflow transition rules
        if (TicketWorkflowService::isValidTransition($ticket['category'], $ticket['status'], $newStatus, $userRole)) {
            if ($this->ticketModel->updateStatus($id, $newStatus)) {
                // Log and add system comment
                $commentText = "System Action: Ticket status transitioned from **{$ticket['status']}** to **{$newStatus}**.";
                
                // Customize comments for commercial review workflow
                if ($ticket['status'] === 'Awaiting Admin Approval') {
                    if ($newStatus === 'Open') {
                        $commentText = "System Action: Admin requested developer estimation on this ticket. Status reset to **Open**.";
                    } elseif ($newStatus === 'Approved') {
                        $commentText = "System Action: Admin approved the ticket. Status set to **Approved**.";
                    } elseif ($newStatus === 'Awaiting Payment') {
                        $commentText = "System Action: Admin approved the ticket and requested commercial payment. Status set to **Awaiting Payment**.";
                    } elseif ($newStatus === 'Rejected') {
                        $commentText = "System Action: Admin rejected the ticket request. Status set to **Rejected**.";
                    }
                }
                
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

        redirect('tickets-view', ['id' => $id]);
    }

    /**
     * POST add a comment
     */
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

        $this->checkProjectAccess($ticket['project_id']);

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
            set_flash_message('success', 'Comment added.');
        } else {
            set_flash_message('danger', 'Failed to add comment.');
        }

        redirect('tickets-view', ['id' => $ticketId]);
    }

    /**
     * POST upload a file attachment
     */
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

        $this->checkProjectAccess($ticket['project_id']);

        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            set_flash_message('danger', 'Error uploading file. Please select a valid file.');
            redirect('tickets-view', ['id' => $ticketId]);
        }

        $file = $_FILES['attachment'];
        $originalName = basename($file['name']);
        $fileSize = $file['size'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Security: Block script files
        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'sh', 'py', 'pl', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd', 'js'];
        if (in_array($ext, $blockedExtensions)) {
            set_flash_message('danger', 'Forbidden file type. Uploading scripts or executables is blocked.');
            redirect('tickets-view', ['id' => $ticketId]);
        }

        // Upload directory
        $uploadDir = __DIR__ . '/../../storage/uploads/attachments/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Create secure .htaccess inside storage/uploads/ to block execution of files
        $htaccessPath = __DIR__ . '/../../storage/uploads/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "<FilesMatch \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi|exe)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>";
            file_put_contents($htaccessPath, $htaccessContent);
        }

        // Generate unique name to prevent collisions/directory traversal
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Store database record (relative path for serving)
            $dbPath = 'storage/uploads/attachments/' . $uniqueName;

            if ($this->ticketModel->addAttachment($ticketId, $_SESSION['user_id'], $originalName, $dbPath, $fileSize)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'ticket_attachment_uploaded',
                    "Uploaded attachment: $originalName to ticket #$ticketId"
                );
                set_flash_message('success', 'File attached successfully.');
            } else {
                set_flash_message('danger', 'Failed to log file details in database.');
                unlink($targetPath); // Remove file
            }
        } else {
            set_flash_message('danger', 'Failed to move uploaded file to target storage.');
        }

        redirect('tickets-view', ['id' => $ticketId]);
    }

    /**
     * GET/POST delete an attachment
     */
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

        $this->checkProjectAccess($ticket['project_id']);

        // Check permissions: creator, admin, or attachment owner
        if (($_SESSION['user_role'] ?? '') !== 'admin' && 
            (int)$attachment['user_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit;
        }

        // Delete from storage
        $filePath = __DIR__ . '/../../' . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
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

        redirect('tickets-view', ['id' => $attachment['ticket_id']]);
    }
}
