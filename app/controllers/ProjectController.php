<?php

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

class ProjectController
{
    private $projectModel;
    private $userModel;
    private $activityLogModel;

    public function __construct()
    {
        // Enforce general user authentication
        AuthMiddleware::check();

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
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit;
        }
        return true;
    }

    /**
     * Helper to enforce Admin-only actions
     */
    private function enforceAdmin()
    {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403);
            require_once __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }

    /**
     * List projects with search, status filter, archive toggle, and pagination
     */
    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $archiveFilter = isset($_GET['archived']) && $_GET['archived'] === '1' ? 1 : 0;
        
        $pageNum = (int)($_GET['p'] ?? 1);
        if ($pageNum < 1) {
            $pageNum = 1;
        }
        
        $limit = 10;
        $offset = ($pageNum - 1) * $limit;
        
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $projects = $this->projectModel->getProjects($userId, $userRole, $search, $offset, $limit, $statusFilter, $archiveFilter);
        $totalProjects = $this->projectModel->getProjectsCount($userId, $userRole, $search, $statusFilter, $archiveFilter);
        $totalPages = ceil($totalProjects / $limit);
        
        $pageTitle = $archiveFilter ? 'Archived Projects' : 'Projects Directory';
        $view = __DIR__ . '/../views/projects/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * View detailed project page
     */
    public function view()
    {
        $id = (int)($_GET['id'] ?? 0);
        $project = $this->projectModel->findById($id);

        if (!$project) {
            set_flash_message('danger', 'Project not found.');
            redirect('projects');
        }

        $this->checkProjectAccess($id);

        // Fetch team members
        $members = $this->projectModel->getProjectMembers($id);

        // Fetch associated tickets (will require TicketModel later, but let's query safe)
        $tickets = [];
        $db = new Database();
        $conn = $db->connect();
        $ticketSql = "SELECT t.*, u.first_name as assignee_first, u.last_name as assignee_last 
                      FROM tickets t 
                      LEFT JOIN users u ON t.assigned_to = u.id 
                      WHERE t.project_id = ? 
                      ORDER BY t.id DESC LIMIT 10";
        $stmt = $conn->prepare($ticketSql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $tickets[] = $row;
        }

        $pageTitle = "[" . $project['project_code'] . "] " . $project['project_name'];
        $view = __DIR__ . '/../views/projects/view.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Create a new project
     */
    public function create()
    {
        $this->enforceAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $data = [
                'project_name'        => trim($_POST['project_name'] ?? ''),
                'project_code'        => strtoupper(trim($_POST['project_code'] ?? '')),
                'client_name'         => trim($_POST['client_name'] ?? ''),
                'organization_name'   => trim($_POST['organization_name'] ?? ''),
                'project_description' => trim($_POST['project_description'] ?? ''),
                'project_type'        => trim($_POST['project_type'] ?? 'Web Application'),
                'technology_stack'    => trim($_POST['technology_stack'] ?? ''),
                'start_date'          => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'expected_end_date'   => !empty($_POST['expected_end_date']) ? $_POST['expected_end_date'] : null,
                'priority'            => trim($_POST['priority'] ?? 'medium'),
                'status'              => trim($_POST['status'] ?? 'Proposal Received'),
                'created_by'          => $_SESSION['user_id']
            ];

            // Validation
            if (empty($data['project_name']) || empty($data['project_code'])) {
                set_flash_message('danger', 'Project Name and Project Code are required.');
                redirect('projects-create');
            }

            // Check if code exists
            $existing = $this->projectModel->findByCode($data['project_code']);
            if ($existing) {
                set_flash_message('danger', 'Project Code is already in use.');
                redirect('projects-create');
            }

            $projectId = $this->projectModel->createProject($data);
            if ($projectId) {
                // Log action
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'project_created',
                    "Created project: {$data['project_name']} ({$data['project_code']})"
                );

                // Auto-assign creator to team member for easy initial seeding
                $this->projectModel->addProjectMember($projectId, $_SESSION['user_id']);

                set_flash_message('success', 'Project created successfully.');
                redirect('projects-view', ['id' => $projectId]);
            } else {
                set_flash_message('danger', 'Error creating project. Please try again.');
                redirect('projects-create');
            }
        }

        $pageTitle = 'Create New Project';
        $view = __DIR__ . '/../views/projects/create.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Edit project details
     */
    public function edit()
    {
        $this->enforceAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $project = $this->projectModel->findById($id);

        if (!$project) {
            set_flash_message('danger', 'Project not found.');
            redirect('projects');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $data = [
                'project_name'        => trim($_POST['project_name'] ?? ''),
                'project_code'        => strtoupper(trim($_POST['project_code'] ?? '')),
                'client_name'         => trim($_POST['client_name'] ?? ''),
                'organization_name'   => trim($_POST['organization_name'] ?? ''),
                'project_description' => trim($_POST['project_description'] ?? ''),
                'project_type'        => trim($_POST['project_type'] ?? 'Web Application'),
                'technology_stack'    => trim($_POST['technology_stack'] ?? ''),
                'start_date'          => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'expected_end_date'   => !empty($_POST['expected_end_date']) ? $_POST['expected_end_date'] : null,
                'priority'            => trim($_POST['priority'] ?? 'medium'),
                'status'              => trim($_POST['status'] ?? 'Proposal Received')
            ];

            // Validation
            if (empty($data['project_name']) || empty($data['project_code'])) {
                set_flash_message('danger', 'Project Name and Project Code are required.');
                redirect('projects-edit', ['id' => $id]);
            }

            // Check if code exists on another project
            $existing = $this->projectModel->findByCode($data['project_code']);
            if ($existing && (int)$existing['id'] !== $id) {
                set_flash_message('danger', 'Project Code is already in use.');
                redirect('projects-edit', ['id' => $id]);
            }

            if ($this->projectModel->updateProject($id, $data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'project_updated',
                    "Updated project ID $id: {$data['project_name']}"
                );

                set_flash_message('success', 'Project updated successfully.');
                redirect('projects-view', ['id' => $id]);
            } else {
                set_flash_message('danger', 'Error updating project.');
                redirect('projects-edit', ['id' => $id]);
            }
        }

        $pageTitle = 'Edit Project: ' . $project['project_name'];
        $view = __DIR__ . '/../views/projects/edit.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * Archive/Unarchive project
     */
    public function archive()
    {
        $this->enforceAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $archive = isset($_GET['archive']) && $_GET['archive'] === '1' ? 1 : 0;

        $project = $this->projectModel->findById($id);
        if ($project) {
            $this->projectModel->archiveProject($id, $archive);
            
            $logAction = $archive ? 'project_archived' : 'project_unarchived';
            $logDetails = $archive ? "Archived project ID $id" : "Restored project ID $id";
            $this->activityLogModel->log($_SESSION['user_id'], $_SESSION['user_email'], $logAction, $logDetails);

            $msg = $archive ? 'Project archived successfully.' : 'Project restored successfully.';
            set_flash_message('success', $msg);
        } else {
            set_flash_message('danger', 'Project not found.');
        }

        redirect('projects');
    }

    /**
     * Manage Team Members mapping (Admin only)
     */
    public function teamMembers()
    {
        $this->enforceAdmin();

        $projectId = (int)($_GET['id'] ?? 0);
        $project = $this->projectModel->findById($projectId);

        if (!$project) {
            set_flash_message('danger', 'Project not found.');
            redirect('projects');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $action = trim($_POST['action'] ?? '');
            $userId = (int)($_POST['user_id'] ?? 0);

            if ($action === 'add') {
                if ($this->projectModel->addProjectMember($projectId, $userId)) {
                    $user = $this->userModel->findById($userId);
                    $email = $user ? $user['email'] : "ID $userId";
                    $this->activityLogModel->log(
                        $_SESSION['user_id'],
                        $_SESSION['user_email'],
                        'project_member_added',
                        "Assigned user $email to project {$project['project_name']}"
                    );
                    set_flash_message('success', 'Team member assigned successfully.');
                } else {
                    set_flash_message('danger', 'Failed to assign team member.');
                }
            } elseif ($action === 'remove') {
                // Prevent removing the creator or yourself if it might lock the project
                if ($this->projectModel->removeProjectMember($projectId, $userId)) {
                    $user = $this->userModel->findById($userId);
                    $email = $user ? $user['email'] : "ID $userId";
                    $this->activityLogModel->log(
                        $_SESSION['user_id'],
                        $_SESSION['user_email'],
                        'project_member_removed',
                        "Removed user $email from project {$project['project_name']}"
                    );
                    set_flash_message('success', 'Team member removed successfully.');
                } else {
                    set_flash_message('danger', 'Failed to remove team member.');
                }
            }
            redirect('projects-team', ['id' => $projectId]);
        }

        // Fetch current members and available users
        $members = $this->projectModel->getProjectMembers($projectId);
        $availableUsers = $this->projectModel->getAvailableUsersForProject($projectId);

        $pageTitle = 'Manage Team: ' . $project['project_name'];
        $view = __DIR__ . '/../views/projects/team-members.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
}
