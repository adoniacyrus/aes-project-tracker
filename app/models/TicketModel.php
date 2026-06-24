<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/TicketWorkflowService.php';

class TicketModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function findById($id)
    {
        $sql = "SELECT t.*, p.project_name, p.project_code, 
                       uc.full_name as creator_name,
                       ua.full_name as assignee_name 
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

    public function canUserAccessTicket($ticket, $userId, $userRole)
    {
        if (!$ticket) {
            return false;
        }

        if ($userRole === 'admin') {
            return true;
        }

        if ($userRole === 'client') {
            return $this->isProjectMember($ticket['project_id'], $userId);
        }

        if ($userRole === 'developer' || $userRole === 'intern') {
            if (!$this->isProjectMember($ticket['project_id'], $userId)) {
                return false;
            }
            return TicketWorkflowService::isVisibleToProjectTeam($ticket);
        }

        return false;
    }

    private function isProjectMember($projectId, $userId)
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $projectId, $userId);
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }

    public function createTicket($data)
    {
        $isTeamVisible = (int)($data['is_team_visible'] ?? 1);
        $commercialReview = (int)($data['commercial_review_requested'] ?? 0);
        $assignedTo = null;

        $sql = "INSERT INTO tickets (project_id, title, description, category, priority, created_by, assigned_to, due_date, status, is_team_visible, commercial_review_requested)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $projectId = $data['project_id'];
        $title = $data['title'];
        $description = $data['description'];
        $category = $data['category'];
        $priority = $data['priority'];
        $createdBy = $data['created_by'];
        $status = $data['status'];
        $dueDate = $data['due_date'] ?? null;

        $stmt->bind_param(
            "issssiissii",
            $projectId,
            $title,
            $description,
            $category,
            $priority,
            $createdBy,
            $assignedTo,
            $dueDate,
            $status,
            $isTeamVisible,
            $commercialReview
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    private function getTeamMemberVisibilitySql()
    {
        $hidden = TicketWorkflowService::getTeamHiddenStatuses();
        $quoted = array_map(function ($status) {
            return "'" . $this->conn->real_escape_string($status) . "'";
        }, $hidden);

        return ' AND t.status NOT IN (' . implode(', ', $quoted) . ') ';
    }

    public function updateTicket($id, $data)
    {
        $status = $data['status'];
        $visibilitySql = '';
        if (TicketWorkflowService::shouldUnlockTeamVisibility($status)) {
            $visibilitySql = ', is_team_visible = 1';
        } elseif (in_array($status, TicketWorkflowService::getTeamHiddenStatuses(), true)) {
            $visibilitySql = ', is_team_visible = 0';
        } elseif (TicketWorkflowService::isTeamVisibleStatus($status)) {
            $visibilitySql = ', is_team_visible = 1';
        }

        $sql = "UPDATE tickets SET project_id = ?, title = ?, description = ?, category = ?, priority = ?, due_date = ?, status = ?{$visibilitySql} 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issssssi",
            $data['project_id'],
            $data['title'],
            $data['description'],
            $data['category'],
            $data['priority'],
            $data['due_date'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    public function updateStatus($id, $status)
    {
        if (TicketWorkflowService::shouldUnlockTeamVisibility($status)) {
            $sql = "UPDATE tickets SET status = ?, is_team_visible = 1 WHERE id = ?";
        } elseif (in_array($status, TicketWorkflowService::getTeamHiddenStatuses(), true)) {
            $sql = "UPDATE tickets SET status = ?, is_team_visible = 0 WHERE id = ?";
        } elseif (TicketWorkflowService::isTeamVisibleStatus($status)) {
            $sql = "UPDATE tickets SET status = ?, is_team_visible = 1 WHERE id = ?";
        } else {
            $sql = "UPDATE tickets SET status = ? WHERE id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function updateCommercialProposal($id, $estimatedCost, $estimatedDeliveryDate)
    {
        $sql = "UPDATE tickets SET estimated_cost = ?, estimated_delivery_date = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("dsi", $estimatedCost, $estimatedDeliveryDate, $id);
        return $stmt->execute();
    }

    public function sendProposal($id)
    {
        $sql = "UPDATE tickets SET status = 'Awaiting Client Review', proposal_sent_at = NOW(), is_team_visible = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function confirmPayment($id)
    {
        $sql = "UPDATE tickets SET status = 'Payment Confirmed', payment_confirmed_at = NOW(), is_team_visible = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function requestCommercialReview($id)
    {
        $sql = "UPDATE tickets SET status = 'Awaiting Admin Approval', is_team_visible = 0, commercial_review_requested = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function reclassifyTicket($id, $category, $status, $isTeamVisible)
    {
        $sql = "UPDATE tickets SET category = ?, status = ?, is_team_visible = ?, commercial_review_requested = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssii", $category, $status, $isTeamVisible, $id);
        return $stmt->execute();
    }

    public function getTickets($userId, $userRole, $search = '', $offset = 0, $limit = 10, $projectId = 0, $category = '', $priority = '', $status = '', $assignedTo = null)
    {
        $searchWildcard = "%" . $search . "%";

        $sql = "SELECT DISTINCT t.*, p.project_name, p.project_code, 
                       uc.full_name as creator_name,
                       ua.full_name as assignee_name 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users uc ON t.created_by = uc.id 
                LEFT JOIN users ua ON t.assigned_to = ua.id ";

        if ($userRole === 'admin') {
            $sql .= " WHERE 1=1 ";
        } else {
            $sql .= " INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? ";
            if ($userRole === 'developer' || $userRole === 'intern') {
                $sql .= $this->getTeamMemberVisibilitySql();
            }
        }

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

    public function getTicketsCount($userId, $userRole, $search = '', $projectId = 0, $category = '', $priority = '', $status = '', $assignedTo = null)
    {
        $searchWildcard = "%" . $search . "%";

        $sql = "SELECT COUNT(DISTINCT t.id) as count 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id ";

        if ($userRole === 'admin') {
            $sql .= " WHERE 1=1 ";
        } else {
            $sql .= " INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? ";
            if ($userRole === 'developer' || $userRole === 'intern') {
                $sql .= $this->getTeamMemberVisibilitySql();
            }
        }

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

    public function getComments($ticketId)
    {
        $sql = "SELECT tc.*, u.full_name, u.role, u.email 
                FROM ticket_comments tc 
                INNER JOIN users u ON tc.user_id = u.id 
                WHERE tc.ticket_id = ? 
                ORDER BY tc.id DESC";
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

    public function getCommentsSince($ticketId, $lastId = 0)
    {
        $sql = "SELECT tc.*, u.full_name, u.role, u.email 
                FROM ticket_comments tc 
                INNER JOIN users u ON tc.user_id = u.id 
                WHERE tc.ticket_id = ? ";
        if ($lastId > 0) {
            $sql .= " AND tc.id > ? ORDER BY tc.id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ticketId, $lastId);
        } else {
            $sql .= " ORDER BY tc.id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $ticketId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        return $comments;
    }

    public function addComment($ticketId, $userId, $comment)
    {
        $sql = "INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $ticketId, $userId, $comment);
        return $stmt->execute();
    }

    public function getDiscussions($ticketId, $lastId = 0)
    {
        $sql = "SELECT td.*, u.full_name, u.role 
                FROM ticket_discussions td 
                INNER JOIN users u ON td.created_by = u.id 
                WHERE td.ticket_id = ? ";
        if ($lastId > 0) {
            $sql .= " AND td.id > ? ORDER BY td.id ASC";
        } else {
            $sql .= " ORDER BY td.id DESC";
        }

        $stmt = $this->conn->prepare($sql);
        if ($lastId > 0) {
            $stmt->bind_param("ii", $ticketId, $lastId);
        } else {
            $stmt->bind_param("i", $ticketId);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $discussions = [];
        while ($row = $result->fetch_assoc()) {
            $discussions[] = $row;
        }

        return $discussions;
    }

    public function addDiscussion($ticketId, $userId, $message)
    {
        $sql = "INSERT INTO ticket_discussions (ticket_id, message, created_by) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $ticketId, $message, $userId);
        return $stmt->execute();
    }

    public function getDiscussionById($id)
    {
        $sql = "SELECT td.*, u.full_name, u.role 
                FROM ticket_discussions td 
                INNER JOIN users u ON td.created_by = u.id 
                WHERE td.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getInternalDiscussions($ticketId, $lastId = 0)
    {
        $sql = "SELECT tid.*, u.full_name, u.role 
                FROM ticket_internal_discussions tid 
                INNER JOIN users u ON tid.user_id = u.id 
                WHERE tid.ticket_id = ? ";
        if ($lastId > 0) {
            $sql .= " AND tid.id > ? ORDER BY tid.id ASC";
        } else {
            $sql .= " ORDER BY tid.id DESC";
        }

        $stmt = $this->conn->prepare($sql);
        if ($lastId > 0) {
            $stmt->bind_param("ii", $ticketId, $lastId);
        } else {
            $stmt->bind_param("i", $ticketId);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $discussions = [];
        while ($row = $result->fetch_assoc()) {
            $discussions[] = $row;
        }

        return $discussions;
    }

    public function addInternalDiscussion($ticketId, $userId, $message)
    {
        $sql = "INSERT INTO ticket_internal_discussions (ticket_id, user_id, message) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $ticketId, $userId, $message);
        return $stmt->execute();
    }

    public function getInternalDiscussionById($id)
    {
        $sql = "SELECT tid.*, u.full_name, u.role 
                FROM ticket_internal_discussions tid 
                INNER JOIN users u ON tid.user_id = u.id 
                WHERE tid.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }



    public function getAttachments($ticketId)
    {
        $sql = "SELECT ta.*, u.full_name 
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

    public function getAttachmentById($id)
    {
        $sql = "SELECT * FROM ticket_attachments WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function addAttachment($ticketId, $userId, $fileName, $filePath, $fileSize, $mimeType = null)
    {
        $sql = "INSERT INTO ticket_attachments (ticket_id, user_id, file_name, file_path, file_size, mime_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissis", $ticketId, $userId, $fileName, $filePath, $fileSize, $mimeType);
        return $stmt->execute();
    }

    public function deleteAttachment($id)
    {
        $sql = "DELETE FROM ticket_attachments WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getTicketsDashboardStats($userId, $userRole)
    {
        if ($userRole === 'admin') {
            $open = $this->conn->query("SELECT COUNT(*) as count FROM tickets WHERE status NOT IN ('Closed', 'Rejected')")->fetch_assoc()['count'] ?? 0;
            $closed = $this->conn->query("SELECT COUNT(*) as count FROM tickets WHERE status IN ('Closed', 'Rejected')")->fetch_assoc()['count'] ?? 0;
            return ['open' => $open, 'closed' => $closed];
        }

        if ($userRole === 'developer' || $userRole === 'intern') {
            $visibilitySql = $this->getTeamMemberVisibilitySql();
            $stmt1 = $this->conn->prepare("SELECT COUNT(DISTINCT t.id) as count 
                FROM tickets t 
                INNER JOIN project_members pm ON t.project_id = pm.project_id 
                WHERE pm.user_id = ?{$visibilitySql} AND t.status NOT IN ('Closed', 'Rejected')");
            $stmt1->bind_param("i", $userId);
            $stmt1->execute();
            $open = $stmt1->get_result()->fetch_assoc()['count'] ?? 0;

            $stmt2 = $this->conn->prepare("SELECT COUNT(DISTINCT t.id) as count 
                FROM tickets t 
                INNER JOIN project_members pm ON t.project_id = pm.project_id 
                WHERE pm.user_id = ?{$visibilitySql} AND t.status IN ('Closed', 'Rejected')");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $closed = $stmt2->get_result()->fetch_assoc()['count'] ?? 0;

            return ['open' => $open, 'closed' => $closed];
        }

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

            return ['open' => $open, 'closed' => $closed];
        }

        return ['open' => 0, 'closed' => 0];
    }

    public function getLastInsertId()
    {
        return $this->conn->insert_id;
    }

    public function getDeveloperAssignedTickets($devId)
    {
        $visibilitySql = $this->getTeamMemberVisibilitySql();
        $sql = "SELECT DISTINCT t.id, t.title, t.priority, t.status, t.due_date, p.project_code 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                INNER JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ?
                WHERE t.status NOT IN ('Closed', 'Rejected'){$visibilitySql}
                ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $devId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getInternAssignedTickets($internId)
    {
        $visibilitySql = $this->getTeamMemberVisibilitySql();
        $sql = "SELECT DISTINCT t.id, t.title, t.priority, t.status, t.due_date, p.project_code 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                INNER JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ?
                WHERE t.status NOT IN ('Closed', 'Rejected'){$visibilitySql}
                ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $internId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getClientOpenTickets($clientId)
    {
        $sql = "SELECT t.id, t.title, t.status, t.priority, t.due_date, p.project_code 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                INNER JOIN project_members pm ON p.id = pm.project_id 
                WHERE pm.user_id = ? AND t.status NOT IN ('Closed', 'Rejected') 
                ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getRecentlyUpdatedTicketsForUser($userId, $userRole, $limit = 5)
    {
        if ($userRole === 'admin') {
            $sql = "SELECT t.id, t.title, t.status, t.updated_at, p.project_code 
                    FROM tickets t 
                    INNER JOIN projects p ON t.project_id = p.id 
                    ORDER BY t.updated_at DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
        } elseif ($userRole === 'developer' || $userRole === 'intern') {
            $visibilitySql = $this->getTeamMemberVisibilitySql();
            $sql = "SELECT DISTINCT t.id, t.title, t.status, t.updated_at, p.project_code 
                    FROM tickets t 
                    INNER JOIN projects p ON t.project_id = p.id 
                    INNER JOIN project_members pm ON p.id = pm.project_id 
                    WHERE pm.user_id = ?{$visibilitySql}
                    ORDER BY t.updated_at DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $limit);
        } else { // client
            $sql = "SELECT DISTINCT t.id, t.title, t.status, t.updated_at, p.project_code 
                    FROM tickets t 
                    INNER JOIN projects p ON t.project_id = p.id 
                    INNER JOIN project_members pm ON p.id = pm.project_id 
                    WHERE pm.user_id = ? 
                    ORDER BY t.updated_at DESC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getClientPendingApprovals($clientId)
    {
        $sql = "SELECT t.id, t.title, t.status, t.estimated_cost, p.project_code 
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                INNER JOIN project_members pm ON p.id = pm.project_id 
                WHERE pm.user_id = ? AND t.status IN ('Awaiting Client Review', 'Awaiting Payment') 
                ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }

    public function getClientRecentCommercialDiscussions($clientId, $limit = 5)
    {
        $sql = "SELECT td.id, td.message, td.created_at, u.full_name, u.role, t.title as ticket_title, p.project_code, t.id as ticket_id 
                FROM ticket_discussions td 
                INNER JOIN tickets t ON td.ticket_id = t.id 
                INNER JOIN projects p ON t.project_id = p.id 
                INNER JOIN project_members pm ON p.id = pm.project_id 
                INNER JOIN users u ON td.created_by = u.id 
                WHERE pm.user_id = ? 
                ORDER BY td.id DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $clientId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $discussions = [];
        while ($row = $result->fetch_assoc()) {
            $discussions[] = $row;
        }
        return $discussions;
    }

    public function getUpcomingDeadlinesForTickets($userId, $userRole, $limit = 5)
    {
        if ($userRole === 'admin') {
            $sql = "SELECT t.id, t.title, t.due_date, p.project_code 
                    FROM tickets t 
                    INNER JOIN projects p ON t.project_id = p.id 
                    WHERE t.status NOT IN ('Closed', 'Rejected') AND t.due_date IS NOT NULL AND t.due_date >= CURDATE() 
                    ORDER BY t.due_date ASC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
        } else {
            $visibilitySql = $this->getTeamMemberVisibilitySql();
            $sql = "SELECT DISTINCT t.id, t.title, t.due_date, p.project_code 
                    FROM tickets t 
                    INNER JOIN projects p ON t.project_id = p.id 
                    INNER JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ?
                    WHERE 1=1{$visibilitySql} AND t.status NOT IN ('Closed', 'Rejected') AND t.due_date IS NOT NULL AND t.due_date >= CURDATE() 
                    ORDER BY t.due_date ASC LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $userId, $limit);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        return $tickets;
    }
}


