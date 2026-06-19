<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

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
        // Fetch User KPI Stats
        $stats = $this->userModel->getDashboardStats();
        
        // Placeholders for Projects and Tickets
        $stats['total_projects'] = 12; // Static placeholder
        $stats['open_tickets'] = 5;    // Static placeholder

        // Fetch recent system logs if user is admin
        $recentLogs = [];
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            $recentLogs = $this->activityLogModel->getRecentLogs(8);
        }

        // Render layout
        $pageTitle = 'Dashboard';
        $view = __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../views/layouts/master.php';
    }
}