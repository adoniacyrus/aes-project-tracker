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
     * Check if request is AJAX
     */
    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
     * Resolve project ID from numeric id or project code route segment
     */
    private function resolveProjectId()
    {
        $projectId = (int)($_GET['id'] ?? 0);
        if ($projectId > 0) {
            return $projectId;
        }

        $projectCode = trim($_GET['project_code'] ?? '');
        if ($projectCode !== '') {
            $project = $this->projectModel->findByCode($projectCode);
            if ($project) {
                return (int)$project['id'];
            }
        }

        return 0;
    }

    /**
     * Helper to enforce Admin-only actions
     */
    private function enforceAdmin()
    {
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }
    }

    /**
     * Validate project_cost from POST data.
     */
    private function validateProjectCost($rawCost)
    {
        $cost = is_numeric($rawCost) ? (float)$rawCost : 0;
        if ($cost <= 0) {
            return [false, 'Project Cost is required and must be greater than zero.'];
        }

        return [true, round($cost, 2)];
    }

    /**
     * Strip financial fields from project data for AJAX responses.
     */
    private function sanitizeProjectForResponse(array $project)
    {
        return sanitize_project_for_role($project, $_SESSION['user_role'] ?? '');
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
        $canViewFinancials = can_view_project_financials($userRole);

        $projects = $this->projectModel->getProjects($userId, $userRole, $search, $offset, $limit, $statusFilter, $archiveFilter);
        if (!$canViewFinancials) {
            $projects = array_map(function ($project) use ($userRole) {
                return sanitize_project_for_role($project, $userRole);
            }, $projects);
        }
        $totalProjects = $this->projectModel->getProjectsCount($userId, $userRole, $search, $statusFilter, $archiveFilter);
        $totalPages = ceil($totalProjects / $limit);
        
        if (isset($_GET['partial']) && is_ajax_request()) {
            respond_partial(
                __DIR__ . '/../views/projects/_list_content.php',
                compact('projects', 'search', 'statusFilter', 'archiveFilter', 'pageNum', 'totalPages', 'totalProjects', 'canViewFinancials'),
                'projects',
                ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $pageNum]
            );
        }
        
        $pageTitle = $archiveFilter ? 'Archived Projects' : 'Projects Directory';
        $view = __DIR__ . '/../views/projects/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }

    /**
     * View detailed project page
     */
    public function view()
    {
        $id = $this->resolveProjectId();
        $project = $this->projectModel->findById($id);

        if (!$project) {
            set_flash_message('danger', 'Project not found.');
            redirect('projects');
        }

        $this->checkProjectAccess($id);

        $userRole = $_SESSION['user_role'] ?? '';
        $canViewFinancials = can_view_project_financials($userRole);
        if (!$canViewFinancials) {
            $project = sanitize_project_for_role($project, $userRole);
        }

        // Fetch team members
        $members = $this->projectModel->getProjectMembers($id);

        // Fetch associated tickets (will require TicketModel later, but let's query safe)
        $tickets = [];
        $db = new Database();
        $conn = $db->connect();
        $ticketSql = "SELECT t.*, u.full_name as assignee_name 
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
        $tickets = sanitize_tickets_for_role($tickets, $userRole);

        $totalTicketRevenue = $canViewFinancials
            ? $this->projectModel->getTotalApprovedTicketRevenue($id)
            : null;

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
                'status'              => trim($_POST['status'] ?? 'Proposal Received'),
                'created_by'          => $_SESSION['user_id']
            ];

            [$costValid, $costResult] = $this->validateProjectCost($_POST['project_cost'] ?? '');
            if (!$costValid) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $costResult]);
                    exit;
                }
                set_flash_message('danger', $costResult);
                redirect('projects');
            }
            $data['project_cost'] = $costResult;

            // Validation
            if (empty($data['project_name']) || empty($data['project_code'])) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Project Name and Project Code are required.']);
                    exit;
                }
                set_flash_message('danger', 'Project Name and Project Code are required.');
                redirect('projects');
            }

            // Check if code exists
            $existing = $this->projectModel->findByCode($data['project_code']);
            if ($existing) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Project Code is already in use.']);
                    exit;
                }
                set_flash_message('danger', 'Project Code is already in use.');
                redirect('projects');
            }

            $projectId = $this->projectModel->createProject($data);
            if ($projectId) {
                // Log action
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'project_created',
                    "Created project: {$data['project_name']} ({$data['project_code']}) - Cost " . format_rs_currency($data['project_cost'])
                );

                // Auto-assign creator to team member for easy initial seeding
                $this->projectModel->addProjectMember($projectId, $_SESSION['user_id']);

                if ($this->isAjax()) {
                    json_response([
                        'success' => true,
                        'message' => 'Project created successfully.',
                    ]);
                }
                set_flash_message('success', 'Project created successfully.');
                redirect('projects-view', ['project_code' => $data['project_code']]);
            } else {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error creating project. Please try again.']);
                    exit;
                }
                set_flash_message('danger', 'Error creating project. Please try again.');
                redirect('projects');
            }
        }

        // If GET, redirect to Projects Directory
        redirect('projects');
    }

    /**
     * Edit project details
     */
    public function edit()
    {
        $this->enforceAdmin();

        $id = $this->resolveProjectId();
        $project = $this->projectModel->findById($id);

        if (!$project) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Project not found.']);
                exit;
            }
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
                'status'              => trim($_POST['status'] ?? 'Proposal Received')
            ];

            [$costValid, $costResult] = $this->validateProjectCost($_POST['project_cost'] ?? '');
            if (!$costValid) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $costResult]);
                    exit;
                }
                set_flash_message('danger', $costResult);
                redirect('projects');
            }
            $data['project_cost'] = $costResult;

            // Validation
            if (empty($data['project_name']) || empty($data['project_code'])) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Project Name and Project Code are required.']);
                    exit;
                }
                set_flash_message('danger', 'Project Name and Project Code are required.');
                redirect('projects');
            }

            // Check if code exists on another project
            $existing = $this->projectModel->findByCode($data['project_code']);
            if ($existing && (int)$existing['id'] !== $id) {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Project Code is already in use.']);
                    exit;
                }
                set_flash_message('danger', 'Project Code is already in use.');
                redirect('projects');
            }

            if ($this->projectModel->updateProject($id, $data)) {
                $this->activityLogModel->log(
                    $_SESSION['user_id'],
                    $_SESSION['user_email'],
                    'project_updated',
                    "Updated project ID $id: {$data['project_name']} - Cost " . format_rs_currency($data['project_cost'])
                );

                if ($this->isAjax()) {
                    json_response([
                        'success' => true,
                        'message' => 'Project updated successfully.',
                    ]);
                }
                set_flash_message('success', 'Project updated successfully.');
                redirect('projects-view', ['project_code' => $data['project_code']]);
            } else {
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error updating project.']);
                    exit;
                }
                set_flash_message('danger', 'Error updating project.');
                redirect('projects');
            }
        }

        // If GET & AJAX, return JSON of project details to populate edit modal
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'project' => $this->sanitizeProjectForResponse($project)]);
            exit;
        }

        // If regular GET, redirect to Projects Directory or details
        redirect('projects-view', ['project_code' => $project['project_code']]);
    }

    /**
     * Archive/Unarchive project
     */
    public function archive()
    {
        $this->enforceAdmin();

        $id = $this->resolveProjectId();
        $archive = isset($_GET['archive']) && $_GET['archive'] === '1' ? 1 : 0;

        $project = $this->projectModel->findById($id);
        if ($project) {
            $this->projectModel->archiveProject($id, $archive);
            
            $logAction = $archive ? 'project_archived' : 'project_unarchived';
            $logDetails = $archive ? "Archived project ID $id" : "Restored project ID $id";
            $this->activityLogModel->log($_SESSION['user_id'], $_SESSION['user_email'], $logAction, $logDetails);

            $msg = $archive ? 'Project archived successfully.' : 'Project restored successfully.';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg]);
                exit;
            }
            set_flash_message('success', $msg);
        } else {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Project not found.']);
                exit;
            }
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

        $projectId = $this->resolveProjectId();
        $project = $this->projectModel->findById($projectId);

        if (!$project) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Project not found.']);
                exit;
            }
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
                    if ($this->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Team member assigned successfully.']);
                        exit;
                    }
                    set_flash_message('success', 'Team member assigned successfully.');
                } else {
                    if ($this->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Failed to assign team member.']);
                        exit;
                    }
                    set_flash_message('danger', 'Failed to assign team member.');
                }
            } elseif ($action === 'remove') {
                if ($this->projectModel->removeProjectMember($projectId, $userId)) {
                    $user = $this->userModel->findById($userId);
                    $email = $user ? $user['email'] : "ID $userId";
                    $this->activityLogModel->log(
                        $_SESSION['user_id'],
                        $_SESSION['user_email'],
                        'project_member_removed',
                        "Removed user $email from project {$project['project_name']}"
                    );
                    if ($this->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Team member removed successfully.']);
                        exit;
                    }
                    set_flash_message('success', 'Team member removed successfully.');
                } else {
                    if ($this->isAjax()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Failed to remove team member.']);
                        exit;
                    }
                    set_flash_message('danger', 'Failed to remove team member.');
                }
            }
            redirect('projects-view', ['project_code' => $project['project_code']]);
        }

        // If GET & AJAX, return members and available users as JSON
        if ($this->isAjax()) {
            $members = $this->projectModel->getProjectMembers($projectId);
            $availableUsers = $this->projectModel->getAvailableUsersForProject($projectId);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'members' => $members,
                'availableUsers' => $availableUsers
            ]);
            exit;
        }

        // If regular GET, redirect to project view
        redirect('projects-view', ['project_code' => $project['project_code']]);
    }
}
