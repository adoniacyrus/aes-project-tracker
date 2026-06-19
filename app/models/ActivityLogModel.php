<?php

require_once __DIR__ . '/../../config/database.php';

class ActivityLogModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Create an activity log entry
     */
    public function log($userId, $email, $action, $details = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        $sql = "INSERT INTO user_activity_logs (user_id, email, action, ip_address, user_agent, details) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssss", $userId, $email, $action, $ip, $userAgent, $details);
        return $stmt->execute();
    }

    /**
     * Get recent logs (e.g., for Admin dashboard view)
     */
    public function getRecentLogs($limit = 10)
    {
        $sql = "SELECT l.*, u.first_name, u.last_name, u.role 
                FROM user_activity_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                ORDER BY l.id DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }

    /**
     * Get recent logs for a specific user profile
     */
    public function getLogsByUser($userId, $limit = 10)
    {
        $sql = "SELECT * FROM user_activity_logs 
                WHERE user_id = ? 
                ORDER BY id DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }
}
