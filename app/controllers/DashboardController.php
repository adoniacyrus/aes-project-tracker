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

        $stats = [];

        // Fetch User and System KPI Stats
        $userStats = $this->userModel->getDashboardStats();
        $projectStats = $projectModel->getProjectsDashboardStats($userId, $userRole);
        $ticketStats = $ticketModel->getTicketsDashboardStats($userId, $userRole);

        // Map KPI values depending on role
        if ($userRole === 'admin') {
            $stats['total_users'] = $userStats['total_users'] ?? 0;
            $stats['active_users'] = $userStats['active_users'] ?? 0;
            $stats['total_projects'] = $projectStats['total'] ?? 0;
            $stats['active_projects'] = $projectStats['active'] ?? 0;
            $stats['completed_projects'] = $projectStats['completed'] ?? 0;
            $stats['open_tickets'] = $ticketStats['open'] ?? 0;
            $stats['closed_tickets'] = $ticketStats['closed'] ?? 0;
        } elseif ($userRole === 'developer' || $userRole === 'intern') {
            $stats['assigned_projects'] = $projectStats['total'] ?? 0;
            $stats['assigned_tickets'] = $ticketStats['open'] ?? 0;
            $stats['pending_tasks'] = $taskModel->getTasksCountByUser($userId, 'Pending') + $taskModel->getTasksCountByUser($userId, 'In Progress');
        } elseif ($userRole === 'client') {
            $stats['active_projects'] = $projectStats['total'] ?? 0;
            $stats['open_tickets'] = $ticketStats['open'] ?? 0;
        }

        // Fetch logs based on role
        $recentLogs = [];
        if ($userRole === 'admin') {
            $recentLogs = $this->activityLogModel->getRecentLogs(8);
        } else {
            $userLogs = $this->activityLogModel->getLogsByUser($userId, 8);
            foreach ($userLogs as $log) {
                // Decorate for standard UI output
                $log['first_name'] = $_SESSION['user_name'];
                $log['last_name'] = '';
                $log['role'] = $_SESSION['user_role'];
                $recentLogs[] = $log;
            }
        }

        // Render layout
        $pageTitle = 'Dashboard';
        $view = __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
}