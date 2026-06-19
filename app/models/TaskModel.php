<?php

require_once __DIR__ . '/../../config/database.php';

class TaskModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Find task by ID
     */
    public function findById($id)
    {
        $sql = "SELECT t.*, tk.title as ticket_title, tk.project_id 
                FROM tasks t 
                INNER JOIN tickets tk ON t.ticket_id = tk.id 
                WHERE t.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create a task
     */
    public function createTask($data)
    {
        $sql = "INSERT INTO tasks (ticket_id, task_name, assigned_member, due_date, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "isiss",
            $data['ticket_id'],
            $data['task_name'],
            $data['assigned_member'],
            $data['due_date'],
            $data['status']
        );
        return $stmt->execute();
    }

    /**
     * Update task
     */
    public function updateTask($id, $data)
    {
        $sql = "UPDATE tasks SET task_name = ?, assigned_member = ?, due_date = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sissi",
            $data['task_name'],
            $data['assigned_member'],
            $data['due_date'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    /**
     * Update status
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE tasks SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    /**
     * Fetch tasks for a ticket
     */
    public function getTasksByTicket($ticketId)
    {
        $sql = "SELECT t.*, u.first_name, u.last_name 
                FROM tasks t 
                LEFT JOIN users u ON t.assigned_member = u.id 
                WHERE t.ticket_id = ? 
                ORDER BY t.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        return $tasks;
    }

    /**
     * Fetch tasks assigned to a specific user
     */
    public function getTasksByUser($userId, $status = null)
    {
        $sql = "SELECT t.*, tk.title as ticket_title, tk.project_id, p.project_name, p.project_code 
                FROM tasks t 
                INNER JOIN tickets tk ON t.ticket_id = tk.id 
                INNER JOIN projects p ON tk.project_id = p.id 
                WHERE t.assigned_member = ? ";
        
        if ($status !== null) {
            $sql .= " AND t.status = ? ";
        }
        
        $sql .= " ORDER BY t.due_date ASC, t.id DESC";

        $stmt = $this->conn->prepare($sql);
        if ($status !== null) {
            $stmt->bind_param("is", $userId, $status);
        } else {
            $stmt->bind_param("i", $userId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        return $tasks;
    }

    /**
     * Get KPI count of pending/active tasks for a user
     */
    public function getTasksCountByUser($userId, $status = null)
    {
        $sql = "SELECT COUNT(*) as count FROM tasks WHERE assigned_member = ?";
        if ($status !== null) {
            $sql .= " AND status = ?";
        }
        $stmt = $this->conn->prepare($sql);
        if ($status !== null) {
            $stmt->bind_param("is", $userId, $status);
        } else {
            $stmt->bind_param("i", $userId);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }
}
