<?php

require_once __DIR__ . '/../../config/database.php';

class TicketWorkflowHistoryModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function log($ticketId, $action, $label, $performedBy = null, $comment = null, $visibility = 'all')
    {
        $ticketId = (int)$ticketId;
        $action = trim((string)$action);
        $label = trim((string)$label);
        $visibility = normalize_workflow_history_visibility($visibility);
        $comment = $comment !== null ? trim((string)$comment) : null;
        if ($comment === '') {
            $comment = null;
        }

        $performedBy = $performedBy !== null ? (int)$performedBy : null;

        $sql = "INSERT INTO ticket_workflow_history (ticket_id, action, label, performed_by, comment, visibility)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ississ', $ticketId, $action, $label, $performedBy, $comment, $visibility);

        return $stmt->execute();
    }

    public function getTicketHistory($ticketId, $userRole)
    {
        $ticketId = (int)$ticketId;
        $visibilitySql = workflow_history_visibility_sql($userRole);

        $sql = "SELECT h.*, u.full_name AS performer_name
                FROM ticket_workflow_history h
                LEFT JOIN users u ON h.performed_by = u.id
                WHERE h.ticket_id = ?{$visibilitySql}
                ORDER BY h.performed_at DESC, h.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getLatestEntry($ticketId, $userRole)
    {
        $ticketId = (int)$ticketId;
        $visibilitySql = workflow_history_visibility_sql($userRole);

        $sql = "SELECT h.*, u.full_name AS performer_name
                FROM ticket_workflow_history h
                LEFT JOIN users u ON h.performed_by = u.id
                WHERE h.ticket_id = ?{$visibilitySql}
                ORDER BY h.performed_at DESC, h.id DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}
