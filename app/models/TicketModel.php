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
                       ua.full_name as assignee_name,
                       us.full_name as resolution_submitter_name,
                       ug.full_name as guidance_requester_name,
                       ur.full_name as latest_reviewer_name
                FROM tickets t 
                INNER JOIN projects p ON t.project_id = p.id 
                LEFT JOIN users uc ON t.created_by = uc.id 
                LEFT JOIN users ua ON t.assigned_to = ua.id 
                LEFT JOIN users us ON t.resolution_submitted_by = us.id
                LEFT JOIN users ug ON t.guidance_requested_by = ug.id
                LEFT JOIN users ur ON t.latest_review_by = ur.id
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

            if (TicketWorkflowService::isBugFixOpenToProjectTeam($ticket)) {
                return true;
            }

            return $this->isUserAssignedToTicket((int)$ticket['id'], $userId);
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
        return '';
    }

    private function getAssignmentVisibilitySql($userId)
    {
        $userId = (int)$userId;
        $hidden = TicketWorkflowService::getTeamHiddenStatuses();
        $quoted = array_map(function ($status) {
            return "'" . $this->conn->real_escape_string($status) . "'";
        }, $hidden);
        $hiddenList = implode(', ', $quoted);

        return " AND (
            EXISTS (
                SELECT 1 FROM ticket_assignments ta
                WHERE ta.ticket_id = t.id AND ta.user_id = {$userId}
            )
            OR (
                t.category = 'Bug Fix'
                AND t.is_team_visible = 1
                AND t.commercial_review_requested = 0
                AND t.status NOT IN ({$hiddenList})
            )
        ) ";
    }

    public function userHasTicketTeamAccess(array $ticket, $userId)
    {
        $userId = (int)$userId;

        if (TicketWorkflowService::isBugFixOpenToProjectTeam($ticket)) {
            return $this->isProjectMember((int)$ticket['project_id'], $userId);
        }

        return $this->isUserAssignedToTicket((int)$ticket['id'], $userId);
    }

    public function syncBugFixProjectTeamAssignments($ticketId, $projectId, $assignedBy)
    {
        require_once __DIR__ . '/ProjectModel.php';
        $projectModel = new ProjectModel();
        $members = $projectModel->getProjectMembers((int)$projectId);
        $userIds = [];

        foreach ($members as $member) {
            if (in_array($member['role'] ?? '', ['developer', 'intern'], true)) {
                $userIds[] = (int)$member['user_id'];
            }
        }

        if (empty($userIds)) {
            return true;
        }

        return $this->syncTicketAssignments((int)$ticketId, $userIds, (int)$assignedBy);
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

    public function saveCostEstimation($ticketId, $newCost, $newDeliveryDate, $reason, $updatedBy)
    {
        $ticket = $this->findById($ticketId);
        if (!$ticket) {
            return false;
        }

        $previousCost = ($ticket['estimated_cost'] !== null && $ticket['estimated_cost'] !== '')
            ? (float)$ticket['estimated_cost']
            : null;
        $previousDelivery = $ticket['estimated_delivery_date'] ?? null;
        $newCost = round((float)$newCost, 2);
        $reason = trim((string)$reason);

        if (!$this->updateCommercialProposal($ticketId, $newCost, $newDeliveryDate)) {
            return false;
        }

        require_once __DIR__ . '/TicketCostHistoryModel.php';
        $costHistoryModel = new TicketCostHistoryModel();
        $costHistoryModel->recordRevision(
            $ticketId,
            (int)$ticket['project_id'],
            $previousCost,
            $newCost,
            $reason,
            $updatedBy
        );

        $sql = "INSERT INTO ticket_cost_estimation_logs
                (ticket_id, previous_cost, new_cost, previous_delivery_date, new_delivery_date, reason, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $prevCostBind = $previousCost ?? 0.0;
        $stmt->bind_param(
            "iddsssi",
            $ticketId,
            $prevCostBind,
            $newCost,
            $previousDelivery,
            $newDeliveryDate,
            $reason,
            $updatedBy
        );

        if (!$stmt->execute()) {
            return false;
        }

        $logId = (int)$this->conn->insert_id;
        if ($previousCost === null && $logId > 0) {
            $this->conn->query("UPDATE ticket_cost_estimation_logs SET previous_cost = NULL WHERE id = {$logId}");
        }

        return [
            'previous_cost' => $previousCost,
            'new_cost' => $newCost,
            'previous_delivery_date' => $previousDelivery,
            'new_delivery_date' => $newDeliveryDate,
            'reason' => $reason,
            'log_id' => $logId,
        ];
    }

    public function getLatestCostEstimationLog($ticketId)
    {
        $sql = "SELECT tcel.*, u.full_name AS updated_by_name
                FROM ticket_cost_estimation_logs tcel
                INNER JOIN users u ON tcel.updated_by = u.id
                WHERE tcel.ticket_id = ?
                ORDER BY tcel.id DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getTicketAssignments($ticketId)
    {
        $sql = "SELECT ta.*, u.full_name, u.role
                FROM ticket_assignments ta
                INNER JOIN users u ON ta.user_id = u.id
                WHERE ta.ticket_id = ?
                ORDER BY u.role ASC, u.full_name ASC";
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

    public function getTicketAssignmentUserIds($ticketId)
    {
        $sql = "SELECT user_id FROM ticket_assignments WHERE ticket_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['user_id'];
        }

        return $ids;
    }

    public function isUserAssignedToTicket($ticketId, $userId)
    {
        $sql = "SELECT 1 FROM ticket_assignments WHERE ticket_id = ? AND user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $ticketId, $userId);
        $stmt->execute();

        return (bool)$stmt->get_result()->fetch_assoc();
    }

    public function hasTicketAssignments($ticketId)
    {
        $sql = "SELECT 1 FROM ticket_assignments WHERE ticket_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();

        return (bool)$stmt->get_result()->fetch_assoc();
    }

    public function syncTicketAssignments($ticketId, array $userIds, $assignedBy)
    {
        $ticketId = (int)$ticketId;
        $assignedBy = (int)$assignedBy;
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $userIds = array_filter($userIds, function ($id) {
            return $id > 0;
        });

        $this->conn->begin_transaction();

        try {
            $delete = $this->conn->prepare('DELETE FROM ticket_assignments WHERE ticket_id = ?');
            $delete->bind_param('i', $ticketId);
            if (!$delete->execute()) {
                throw new RuntimeException('Failed to clear ticket assignments.');
            }

            if (!empty($userIds)) {
                $insert = $this->conn->prepare(
                    'INSERT INTO ticket_assignments (ticket_id, user_id, assigned_by) VALUES (?, ?, ?)'
                );
                foreach ($userIds as $userId) {
                    $insert->bind_param('iii', $ticketId, $userId, $assignedBy);
                    if (!$insert->execute()) {
                        throw new RuntimeException('Failed to save ticket assignment.');
                    }
                }
            }

            $primaryAssignee = !empty($userIds) ? $userIds[0] : null;
            if ($primaryAssignee === null) {
                $clearAssignee = $this->conn->prepare('UPDATE tickets SET assigned_to = NULL WHERE id = ?');
                $clearAssignee->bind_param('i', $ticketId);
                if (!$clearAssignee->execute()) {
                    throw new RuntimeException('Failed to clear ticket assignee.');
                }
            } else {
                $update = $this->conn->prepare('UPDATE tickets SET assigned_to = ? WHERE id = ?');
                $update->bind_param('ii', $primaryAssignee, $ticketId);
                if (!$update->execute()) {
                    throw new RuntimeException('Failed to update ticket assignee.');
                }
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function isPendingAdminReview(array $ticket)
    {
        return !empty($ticket['pending_admin_review']);
    }

    /**
     * Developer/intern "Mark as Resolved" — complete immediately.
     * Stores the optional comment in the existing resolution_* columns.
     */
    public function submitForAdminReview($ticketId, $userId, $comment)
    {
        $ticketId = (int)$ticketId;
        $userId = (int)$userId;
        $comment = trim((string)$comment);

        $sql = "UPDATE tickets
                SET pending_admin_review = 0,
                    resolution_submitted_by = ?,
                    resolution_submitted_at = NOW(),
                    resolution_comment = ?,
                    status = 'Completed',
                    is_team_visible = 1
                WHERE id = ?
                  AND pending_admin_review = 0
                  AND status <> 'Completed'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isi', $userId, $comment, $ticketId);

        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public function approveAdminReview($ticketId)
    {
        $ticketId = (int)$ticketId;

        $sql = "UPDATE tickets
                SET pending_admin_review = 0,
                    status = 'Completed',
                    is_team_visible = 1
                WHERE id = ? AND pending_admin_review = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);

        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public function returnTicketToDevelopment($ticketId, $adminUserId, $comment)
    {
        $ticketId = (int)$ticketId;
        $adminUserId = (int)$adminUserId;
        $comment = trim((string)$comment);

        $sql = "UPDATE tickets
                SET pending_admin_review = 0,
                    latest_review_comment = ?,
                    latest_review_by = ?,
                    latest_review_at = NOW(),
                    status = 'Processing',
                    is_team_visible = 1
                WHERE id = ? AND pending_admin_review = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sii', $comment, $adminUserId, $ticketId);

        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public function isPendingAdminGuidance(array $ticket)
    {
        return !empty($ticket['pending_admin_guidance']);
    }

    public function submitAdminGuidanceRequest($ticketId, $userId, $comment)
    {
        $ticketId = (int)$ticketId;
        $userId = (int)$userId;
        $comment = trim((string)$comment);

        $sql = "UPDATE tickets
                SET pending_admin_guidance = 1,
                    guidance_requested_by = ?,
                    guidance_requested_at = NOW(),
                    guidance_comment = ?,
                    is_team_visible = 1
                WHERE id = ? AND pending_admin_guidance = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isi', $userId, $comment, $ticketId);

        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public function respondToAdminGuidance($ticketId, $adminUserId, $comment)
    {
        $ticketId = (int)$ticketId;
        $adminUserId = (int)$adminUserId;
        $comment = trim((string)$comment);

        $sql = "UPDATE tickets
                SET pending_admin_guidance = 0,
                    latest_review_comment = ?,
                    latest_review_by = ?,
                    latest_review_at = NOW(),
                    is_team_visible = 1
                WHERE id = ? AND pending_admin_guidance = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sii', $comment, $adminUserId, $ticketId);

        return $stmt->execute() && $stmt->affected_rows > 0;
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
                $sql .= $this->getAssignmentVisibilitySql($userId);
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
            if (TicketWorkflowService::isSimplifiedStatus($status)) {
                $sql .= TicketWorkflowService::buildSimplifiedStatusFilterSql($status);
            } else {
                $sql .= " AND t.status = ? ";
            }
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
            if (!TicketWorkflowService::isSimplifiedStatus($status)) {
                $types .= "s";
                $params[] = &$status;
            }
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
                $sql .= $this->getAssignmentVisibilitySql($userId);
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
            if (TicketWorkflowService::isSimplifiedStatus($status)) {
                $sql .= TicketWorkflowService::buildSimplifiedStatusFilterSql($status);
            } else {
                $sql .= " AND t.status = ? ";
            }
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
            if (!TicketWorkflowService::isSimplifiedStatus($status)) {
                $types .= "s";
                $params[] = &$status;
            }
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
     * Count tickets per simplified status for tab badges (filters except status).
     */
    public function getTicketStatusCounts($userId, $userRole, $search = '', $projectId = 0, $category = '', $priority = '')
    {
        $initiated = $this->getTicketsCount($userId, $userRole, $search, $projectId, $category, $priority, 'Initiated');
        $processing = $this->getTicketsCount($userId, $userRole, $search, $projectId, $category, $priority, 'Processing');
        $completed = $this->getTicketsCount($userId, $userRole, $search, $projectId, $category, $priority, 'Completed');

        return [
            '' => $initiated + $processing + $completed,
            'Initiated' => $initiated,
            'Processing' => $processing,
            'Completed' => $completed,
        ];
    }

    public function getComments($ticketId, $channel = null)
    {
        $sql = "SELECT tc.*, u.full_name, u.role, u.email 
                FROM ticket_comments tc 
                INNER JOIN users u ON tc.user_id = u.id 
                WHERE tc.ticket_id = ? ";
        if ($channel !== null) {
            $sql .= " AND COALESCE(tc.channel, 'team') = ? ";
        }
        $sql .= " ORDER BY tc.id DESC";
        $stmt = $this->conn->prepare($sql);
        if ($channel !== null) {
            $stmt->bind_param("is", $ticketId, $channel);
        } else {
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

    public function getCommentsSince($ticketId, $lastId = 0, $channel = 'team')
    {
        $sql = "SELECT tc.*, u.full_name, u.role, u.email 
                FROM ticket_comments tc 
                INNER JOIN users u ON tc.user_id = u.id 
                WHERE tc.ticket_id = ? AND COALESCE(tc.channel, 'team') = ? ";
        if ($lastId > 0) {
            $sql .= " AND tc.id > ? ORDER BY tc.id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isi", $ticketId, $channel, $lastId);
        } else {
            $sql .= " ORDER BY tc.id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $ticketId, $channel);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
        return $comments;
    }

    public function addComment($ticketId, $userId, $comment, $channel = 'team')
    {
        $channel = in_array($channel, ['team', 'client', 'admin_dev'], true) ? $channel : 'team';
        $sql = "INSERT INTO ticket_comments (ticket_id, user_id, comment, channel) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $ticketId, $userId, $comment, $channel);
        return $stmt->execute();
    }

    public function getCommentById($commentId)
    {
        $sql = "SELECT tc.*, u.full_name, u.role, u.email
                FROM ticket_comments tc
                INNER JOIN users u ON tc.user_id = u.id
                WHERE tc.id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $commentId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
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


