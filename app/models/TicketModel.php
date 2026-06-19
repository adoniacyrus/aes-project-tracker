<?php

require_once __DIR__ . '/../../config/database.php';

class TicketModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Find a ticket by ID
     */
    public function findById($id)
    {
        $sql = "SELECT t.*, p.project_name, p.project_code, 
                       uc.first_name as creator_first, uc.last_name as creator_last, 
                       ua.first_name as assignee_first, ua.last_name as assignee_last 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users uc ON t.created_by = uc.id 
                LEFT JOIN users ua ON t.assigned_to = ua.id 
                WHERE t.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create a ticket
     */
    public function createTicket($data)
    {
        $sql = "INSERT INTO tickets (project_id, title, description, category, priority, created_by, assigned_to, due_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issssiiSS",
            $data['project_id'],
            $data['title'],
            $data['description'],
            $data['category'],
            $data['priority'],
            $data['created_by'],
            $data['assigned_to'],
            $data['due_date'],
            $data['status']
        );
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Update ticket properties
     */
    public function updateTicket($id, $data)
    {
        $sql = "UPDATE tickets SET project_id = ?, title = ?, description = ?, category = ?, priority = ?, assigned_to = ?, due_date = ?, status = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issssiisi",
            $data['project_id'],
            $data['title'],
            $data['description'],
            $data['category'],
            $data['priority'],
            $data['assigned_to'],
            $data['due_date'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    /**
     * Update status only
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE tickets SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    /**
     * Assign ticket to a member
     */
    public function assignTicket($id, $userId)
    {
        $sql = "UPDATE tickets SET assigned_to = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $id);
        return $stmt->execute();
    }

    /**
     * Fetch tickets with filters, search, and role access validation
     */
    public function getTickets($userId, $userRole, $search = '', $offset = 0, $limit = 10, $projectId = 0, $category = '', $priority = '', $status = '', $assignedTo = null)
    {
        $searchWildcard = "%" . $search . "%";
        
        $sql = "SELECT DISTINCT t.*, p.project_name, p.project_code, 
                       uc.first_name as creator_first, uc.last_name as creator_last, 
                       ua.first_name as assignee_first, ua.last_name as assignee_last 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users uc ON t.created_by = uc.id 
                LEFT JOIN users ua ON t.assigned_to = ua.id ";

        // Filter projects by team mapping if not admin
        if ($userRole !== 'admin') {
            $sql .= " INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? ";
        } else {
            $sql .= " WHERE 1=1 ";
        }

        // Apply filters
        if ($projectId > 0) {
            $sql .= " AND t.project_id = ? ";
        }
        if ($category !== '') {
            $sql .= " AND t.category = ? ";
        }
        if ($priority !== '') {
            $sql .= " AND t.priority = ? ";
        }
        if ($status !== '') {
            $sql .= " AND t.status = ? ";
        }
        if ($assignedTo !== null) {
            if ($assignedTo === 0) {
                $sql .= " AND t.assigned_to IS NULL ";
            } else {
                $sql .= " AND t.assigned_to = ? ";
            }
        }
        if ($search !== '') {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR p.project_name LIKE ? OR p.project_code LIKE ?) ";
        }

        $sql .= " ORDER BY t.id DESC LIMIT ? OFFSET ? ";

        $stmt = $this->conn->prepare($sql);

        // Bind dynamic params
        $types = "";
        $params = [];

        if ($userRole !== 'admin') {
            $types .= "i";
            $params[] = &$userId;
        }
        if ($projectId > 0) {
            $types .= "i";
            $params[] = &$projectId;
        }
        if ($category !== '') {
            $types .= "s";
            $params[] = &$category;
        }
        if ($priority !== '') {
            $types .= "s";
            $params[] = &$priority;
        }
        if ($status !== '') {
            $types .= "s";
            $params[] = &$status;
        }
        if ($assignedTo !== null && $assignedTo > 0) {
            $types .= "i";
            $params[] = &$assignedTo;
        }
        if ($search !== '') {
            $types .= "ssss";
            $params[] = &$searchWildcard;
            $params[] = &$searchWildcard;
            $params[] = &$searchWildcard;
            $params[] = &$searchWildcard;
        }
        $types .= "ii";
        $params[] = &$limit;
        $params[] = &$offset;

        // Use call_user_func_array for binding dynamically
        $bindArgs = array_merge([$types], $params);
        call_user_func_array([$stmt, 'bind_param'], $bindArgs);

        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    /**
     * Get total ticket count matching search & filters
     */
    public function getTicketsCount($userId, $userRole, $search = '', $projectId = 0, $category = '', $priority = '', $status = '', $assignedTo = null)
    {
        $searchWildcard = "%" . $search . "%";
        
        $sql = "SELECT COUNT(DISTINCT t.id) as count 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id ";

        // Filter projects by team mapping if not admin
        if ($userRole !== 'admin') {
            $sql .= " INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? ";
        } else {
            $sql .= " WHERE 1=1 ";
        }

        // Apply filters
        if ($projectId > 0) {
            $sql .= " AND t.project_id = ? ";
        }
        if ($category !== '') {
            $sql .= " AND t.category = ? ";
        }
        if ($priority !== '') {
            $sql .= " AND t.priority = ? ";
        }
        if ($status !== '') {
            $sql .= " AND t.status = ? ";
        }
        if ($assignedTo !== null) {
            if ($assignedTo === 0) {
                $sql .= " AND t.assigned_to IS NULL ";
            } else {
                $sql .= " AND t.assigned_to = ? ";
            }
        }
        if ($search !== '') {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR p.project_name LIKE ? OR p.project_code LIKE ?) ";
        }

        $stmt = $this->conn->prepare($sql);

        // Bind dynamic params
        $types = "";
        $params = [];

        if ($userRole !== 'admin') {
            $types .= "i";
            $params[] = &$userId;
        }
        if ($projectId > 0) {
            $types .= "i";
            $params[] = &$projectId;
        }
        if ($category !== '') {
            $types .= "s";
            $params[] = &$category;
        }
        if ($priority !== '') {
            $types .= "s";
            $params[] = &$priority;
        }
        if ($status !== '') {
            $types .= "s";
            $params[] = &$status;
        }
        if ($assignedTo !== null && $assignedTo > 0) {
            $types .= "i";
            $params[] = &$assignedTo;
        }
        if ($search !== '') {
            $types .= "ssss";
            $params[] = &$searchWildcard;
            $params[] = &$searchWildcard;
            $params[] = &$searchWildcard;
            $params[] = &$searchWildcard;
        }

        if ($types !== "") {
            $bindArgs = array_merge([$types], $params);
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }

    /**
     * Get comments for a ticket
     */
    public function getComments($ticketId)
    {
        $sql = "SELECT tc.*, u.first_name, u.last_name, u.role, u.email 
                FROM ticket_comments tc 
                INNER JOIN users u ON tc.user_id = u.id 
                WHERE tc.ticket_id = ? 
                ORDER BY tc.id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        return $comments;
    }

    /**
     * Add a comment to a ticket
     */
    public function addComment($ticketId, $userId, $comment)
    {
        $sql = "INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $ticketId, $userId, $comment);
        return $stmt->execute();
    }

    /**
     * Get attachments for a ticket
     */
    public function getAttachments($ticketId)
    {
        $sql = "SELECT ta.*, u.first_name, u.last_name 
                FROM ticket_attachments ta 
                INNER JOIN users u ON ta.user_id = u.id 
                WHERE ta.ticket_id = ? 
                ORDER BY ta.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attachments = [];
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        return $attachments;
    }

    /**
     * Find attachment by ID
     */
    public function getAttachmentById($id)
    {
        $sql = "SELECT * FROM ticket_attachments WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Add file attachment record
     */
    public function addAttachment($ticketId, $userId, $fileName, $filePath, $fileSize)
    {
        $sql = "INSERT INTO ticket_attachments (ticket_id, user_id, file_name, file_path, file_size) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissi", $ticketId, $userId, $fileName, $filePath, $fileSize);
        return $stmt->execute();
    }

    /**
     * Delete attachment record
     */
    public function deleteAttachment($id)
    {
        $sql = "DELETE FROM ticket_attachments WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Dashboard KPI Stats for Tickets
     */
    public function getTicketsDashboardStats($userId, $userRole)
    {
        if ($userRole === 'admin') {
            $open = $this->conn->query("SELECT COUNT(*) as count FROM tickets WHERE status NOT IN ('Closed', 'Rejected')")->fetch_assoc()['count'] ?? 0;
            $closed = $this->conn->query("SELECT COUNT(*) as count FROM tickets WHERE status IN ('Closed', 'Rejected')")->fetch_assoc()['count'] ?? 0;
            return [
                'open' => $open,
                'closed' => $closed
            ];
        }

        // Developer/Intern: see assigned tickets
        if ($userRole === 'developer' || $userRole === 'intern') {
            $stmt1 = $this->conn->prepare("SELECT COUNT(*) as count FROM tickets WHERE assigned_to = ? AND status NOT IN ('Closed', 'Rejected')");
            $stmt1->bind_param("i", $userId);
            $stmt1->execute();
            $open = $stmt1->get_result()->fetch_assoc()['count'] ?? 0;

            $stmt2 = $this->conn->prepare("SELECT COUNT(*) as count FROM tickets WHERE assigned_to = ? AND status IN ('Closed', 'Rejected')");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $closed = $stmt2->get_result()->fetch_assoc()['count'] ?? 0;

            return [
                'open' => $open,
                'closed' => $closed
            ];
        }

        // Client: see tickets belonging to their projects
        if ($userRole === 'client') {
            $stmt1 = $this->conn->prepare("SELECT COUNT(DISTINCT t.id) as count 
                                           FROM tickets t 
                                           INNER JOIN project_members pm ON t.project_id = pm.project_id 
                                           WHERE pm.user_id = ? AND t.status NOT IN ('Closed', 'Rejected')");
            $stmt1->bind_param("i", $userId);
            $stmt1->execute();
            $open = $stmt1->get_result()->fetch_assoc()['count'] ?? 0;

            $stmt2 = $this->conn->prepare("SELECT COUNT(DISTINCT t.id) as count 
                                           FROM tickets t 
                                           INNER JOIN project_members pm ON t.project_id = pm.project_id 
                                           WHERE pm.user_id = ? AND t.status IN ('Closed', 'Rejected')");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $closed = $stmt2->get_result()->fetch_assoc()['count'] ?? 0;

            return [
                'open' => $open,
                'closed' => $closed
            ];
        }

        return ['open' => 0, 'closed' => 0];
    }
}
