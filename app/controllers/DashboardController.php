<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/ProjectModel.php';
require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../models/TaskModel.php';

class DashboardController
{
    private $userModel;
    private $activityLogModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    public function index()
    {
        AuthMiddleware::check();

        $projectModel = new ProjectModel();
        $ticketModel = new TicketModel();
        $taskModel = new TaskModel();

        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        // AJAX Widget Endpoint
        if (isset($_GET['widget'])) {
            header('Content-Type: application/json');
            $widget = $_GET['widget'];

            if ($widget === 'recent_tickets') {
                if (in_array($userRole, ['developer', 'intern', 'client'])) {
                    $tickets = $ticketModel->getRecentlyUpdatedTicketsForUser($userId, $userRole, 5);
                    echo json_encode(['success' => true, 'data' => $tickets]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                }
                exit;
            }

            if ($widget === 'pending_tasks') {
                if ($userRole === 'developer') {
                    $tasks = $taskModel->getPendingTasksByUser($userId);
                    echo json_encode(['success' => true, 'data' => $tasks]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                }
                exit;
            }

            echo json_encode(['success' => false, 'message' => 'Invalid widget']);
            exit;
        }

        $stats = [];
        $recentLogs = [];
        
        // Developer Dashboard variables
        $developerAssignedProjects = [];
        $developerAssignedTickets = [];
        $upcomingDeadlines = [];

        // Intern Dashboard variables
        $internAssignedTasks = [];
        $internAssignedTickets = [];
        $internPendingWork = [];

        // Client Dashboard variables
        $clientActiveProjects = [];
        $clientCommercialDiscussions = [];

        // Fetch stats & data dynamically depending on role
        if ($userRole === 'admin') {
            $userStats = $this->userModel->getDashboardStats();
            $projectStats = $projectModel->getProjectsDashboardStats($userId, $userRole);
            $ticketStats = $ticketModel->getTicketsDashboardStats($userId, $userRole);

            $stats['total_users'] = $userStats['total_users'] ?? 0;
            $stats['active_users'] = $userStats['active_users'] ?? 0;
            $stats['total_projects'] = $projectStats['total'] ?? 0;
            $stats['active_projects'] = $projectStats['active'] ?? 0;
            $stats['completed_projects'] = $projectStats['completed'] ?? 0;
            $stats['open_tickets'] = $ticketStats['open'] ?? 0;
            $stats['closed_tickets'] = $ticketStats['closed'] ?? 0;

            $recentLogs = $this->activityLogModel->getRecentLogs(8);
        } elseif ($userRole === 'developer') {
            $projectStats = $projectModel->getProjectsDashboardStats($userId, $userRole);
            $ticketStats = $ticketModel->getTicketsDashboardStats($userId, $userRole);

            $stats['assigned_projects'] = $projectStats['total'] ?? 0;
            $stats['assigned_tickets'] = $ticketStats['open'] ?? 0;
            $stats['pending_tasks'] = $taskModel->getPendingTasksCountByUser($userId);

            $developerAssignedProjects = $projectModel->getDeveloperAssignedProjects($userId);
            $developerAssignedTickets = $ticketModel->getDeveloperAssignedTickets($userId);
            $upcomingDeadlines = $ticketModel->getUpcomingDeadlinesForTickets($userId, $userRole, 5);
        } elseif ($userRole === 'intern') {
            $projectStats = $projectModel->getProjectsDashboardStats($userId, $userRole);
            $ticketStats = $ticketModel->getTicketsDashboardStats($userId, $userRole);

            $stats['assigned_projects'] = $projectStats['total'] ?? 0;
            $stats['assigned_tickets'] = $ticketStats['open'] ?? 0;
            $stats['pending_tasks'] = $taskModel->getPendingTasksCountByUser($userId);

            $internAssignedTasks = $taskModel->getTasksByUser($userId);
            $internAssignedTickets = $ticketModel->getInternAssignedTickets($userId);
            $internPendingWork = $taskModel->getPendingTasksByUser($userId);
            $upcomingDeadlines = $ticketModel->getUpcomingDeadlinesForTickets($userId, $userRole, 5);
        } elseif ($userRole === 'client') {
            $projectStats = $projectModel->getProjectsDashboardStats($userId, $userRole);

            $stats['active_projects'] = $projectStats['active'] ?? 0;

            $clientActiveProjects = $projectModel->getClientActiveProjects($userId);
            $clientCommercialDiscussions = $ticketModel->getClientRecentCommercialDiscussions($userId, 5);
        }

        // Render layout
        $pageTitle = 'Dashboard';
        $view = __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
}
