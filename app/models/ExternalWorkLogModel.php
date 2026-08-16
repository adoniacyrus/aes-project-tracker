<?php

require_once __DIR__ . '/../../config/database.php';

class ExternalWorkLogModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function findById($id)
    {
        $sql = $this->baseSelectSql() . " WHERE ewl.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO external_work_logs
                    (project_id, created_by, assigned_to, title, description, communication_source,
                     requested_by, work_date, estimated_hours, status, client_reference)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $projectId = (int)$data['project_id'];
        $createdBy = (int)$data['created_by'];
        $assignedTo = (int)$data['assigned_to'];
        $title = (string)$data['title'];
        $description = $data['description'] ?? null;
        $source = (string)$data['communication_source'];
        $requestedBy = $data['requested_by'] ?? null;
        $workDate = (string)$data['work_date'];
        $estimatedHours = $data['estimated_hours'] ?? null;
        $estimatedHours = $estimatedHours === null ? null : (string)$estimatedHours;
        $status = $data['status'] ?? 'Pending';
        $clientReference = $data['client_reference'] ?? null;

        $stmt->bind_param(
            'iiissssssss',
            $projectId,
            $createdBy,
            $assignedTo,
            $title,
            $description,
            $source,
            $requestedBy,
            $workDate,
            $estimatedHours,
            $status,
            $clientReference
        );

        return $stmt->execute() ? (int)$this->conn->insert_id : false;
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE external_work_logs
                SET project_id = ?, assigned_to = ?, title = ?, description = ?,
                    communication_source = ?, requested_by = ?, work_date = ?,
                    estimated_hours = ?, actual_hours = ?, status = ?,
                    client_reference = ?, completion_notes = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        $projectId = (int)$data['project_id'];
        $assignedTo = (int)$data['assigned_to'];
        $title = (string)$data['title'];
        $description = $data['description'] ?? null;
        $source = (string)$data['communication_source'];
        $requestedBy = $data['requested_by'] ?? null;
        $workDate = (string)$data['work_date'];
        $estimatedHours = $data['estimated_hours'] ?? null;
        $estimatedHours = $estimatedHours === null ? null : (string)$estimatedHours;
        $actualHours = $data['actual_hours'] ?? null;
        $actualHours = $actualHours === null ? null : (string)$actualHours;
        $status = (string)$data['status'];
        $clientReference = $data['client_reference'] ?? null;
        $completionNotes = $data['completion_notes'] ?? null;
        $id = (int)$id;

        $stmt->bind_param(
            'iissssssssssi',
            $projectId,
            $assignedTo,
            $title,
            $description,
            $source,
            $requestedBy,
            $workDate,
            $estimatedHours,
            $actualHours,
            $status,
            $clientReference,
            $completionNotes,
            $id
        );

        return $stmt->execute();
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE external_work_logs SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $id = (int)$id;
        $stmt->bind_param('si', $status, $id);

        return $stmt->execute();
    }

    public function complete($id, $actualHours, $completionNotes)
    {
        $sql = "UPDATE external_work_logs
                SET status = 'Completed', actual_hours = ?, completion_notes = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $id = (int)$id;
        $actualHours = $actualHours === null ? null : (string)$actualHours;
        $stmt->bind_param('ssi', $actualHours, $completionNotes, $id);

        return $stmt->execute();
    }

    public function getLogsForUser($role, $userId, array $filters = [])
    {
        [$whereSql, $types, $params] = $this->buildVisibilityClause($role, $userId, $filters, true);
        $sql = $this->baseSelectSql() . $whereSql . " ORDER BY ewl.work_date DESC, ewl.id DESC";

        return $this->fetchAll($sql, $types, $params);
    }

    public function getByProject($projectId, $role, $userId)
    {
        return $this->getLogsForUser($role, $userId, ['project_id' => (int)$projectId]);
    }

    public function getAssignedLogs($userId, $limit = null)
    {
        $sql = $this->baseSelectSql() . " WHERE ewl.assigned_to = ? ORDER BY ewl.work_date DESC, ewl.id DESC";
        $types = 'i';
        $params = [(int)$userId];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $types .= 'i';
            $params[] = (int)$limit;
        }

        return $this->fetchAll($sql, $types, $params);
    }

    public function getDashboardStats($role, $userId)
    {
        $empty = [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'total_hours' => 0,
        ];

        $hoursSelect = "COALESCE(SUM(CASE WHEN status = 'Cancelled' THEN 0 ELSE COALESCE(actual_hours, estimated_hours, 0) END), 0) AS total_hours";

        if ($role === 'admin') {
            $sql = "SELECT
                        COUNT(*) AS total,
                        SUM(status = 'Completed') AS completed,
                        SUM(status = 'Pending') AS pending,
                        SUM(status = 'In Progress') AS in_progress,
                        {$hoursSelect}
                    FROM external_work_logs";
            $result = $this->conn->query($sql);
            $row = $result ? $result->fetch_assoc() : null;
        } elseif (in_array($role, ['developer', 'intern'], true)) {
            $sql = "SELECT
                        COUNT(*) AS total,
                        SUM(status = 'Completed') AS completed,
                        SUM(status = 'Pending') AS pending,
                        SUM(status = 'In Progress') AS in_progress,
                        {$hoursSelect}
                    FROM external_work_logs
                    WHERE assigned_to = ?";
            $stmt = $this->conn->prepare($sql);
            $userId = (int)$userId;
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
        } else {
            return $empty;
        }

        if (!$row) {
            return $empty;
        }

        return [
            'total' => (int)($row['total'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
            'total_hours' => (float)($row['total_hours'] ?? 0),
        ];
    }

    public function getProjectStats($projectId, $role, $userId)
    {
        $filters = ['project_id' => (int)$projectId];
        [$whereSql, $types, $params] = $this->buildVisibilityClause($role, $userId, $filters, false);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'Completed') AS completed,
                    SUM(status = 'Pending') AS pending,
                    SUM(status = 'In Progress') AS in_progress,
                    COALESCE(SUM(CASE
                        WHEN status = 'Cancelled' THEN 0
                        ELSE COALESCE(actual_hours, estimated_hours, 0)
                    END), 0) AS total_hours
                FROM external_work_logs ewl
                " . $whereSql;

        $rows = $this->fetchAll($sql, $types, $params);
        $row = $rows[0] ?? null;

        return [
            'total' => (int)($row['total'] ?? 0),
            'completed' => (int)($row['completed'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
            'total_hours' => (float)($row['total_hours'] ?? 0),
        ];
    }

    public function getForReport($projectId)
    {
        $sql = "SELECT
                    ewl.work_date,
                    ewl.title,
                    ewl.communication_source,
                    ewl.status,
                    ewl.estimated_hours,
                    ewl.actual_hours,
                    assignee.full_name AS assigned_to_name
                FROM external_work_logs ewl
                LEFT JOIN users assignee ON ewl.assigned_to = assignee.id
                WHERE ewl.project_id = ?
                ORDER BY ewl.work_date ASC, ewl.id ASC";
        $stmt = $this->conn->prepare($sql);
        $projectId = (int)$projectId;
        $stmt->bind_param('i', $projectId);
        $stmt->execute();

        $logs = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $hours = $row['actual_hours'] !== null && $row['actual_hours'] !== ''
                ? (float)$row['actual_hours']
                : ((float)($row['estimated_hours'] ?? 0));
            $row['hours_spent'] = $hours;
            $logs[] = $row;
        }

        return $logs;
    }

    public function canViewLog(array $log, $role, $userId): bool
    {
        if ($role === 'admin') {
            return true;
        }

        $userId = (int)$userId;
        if ($role === 'developer') {
            return (int)$log['assigned_to'] === $userId || (int)$log['created_by'] === $userId;
        }

        if ($role === 'intern') {
            return (int)$log['assigned_to'] === $userId;
        }

        return false;
    }

    private function baseSelectSql(): string
    {
        return "SELECT ewl.*,
                       p.project_name,
                       p.project_code,
                       creator.full_name AS created_by_name,
                       assignee.full_name AS assigned_to_name
                FROM external_work_logs ewl
                INNER JOIN projects p ON ewl.project_id = p.id
                LEFT JOIN users creator ON ewl.created_by = creator.id
                LEFT JOIN users assignee ON ewl.assigned_to = assignee.id";
    }

    private function buildVisibilityClause($role, $userId, array $filters, $prefixedWhere)
    {
        $clauses = [];
        $types = '';
        $params = [];

        if ($role === 'developer') {
            $clauses[] = '(ewl.assigned_to = ? OR ewl.created_by = ?)';
            $types .= 'ii';
            $params[] = (int)$userId;
            $params[] = (int)$userId;
        } elseif ($role === 'intern') {
            $clauses[] = 'ewl.assigned_to = ?';
            $types .= 'i';
            $params[] = (int)$userId;
        } elseif ($role !== 'admin') {
            $clauses[] = '1 = 0';
        }

        if (!empty($filters['project_id'])) {
            $clauses[] = 'ewl.project_id = ?';
            $types .= 'i';
            $params[] = (int)$filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[] = 'ewl.status = ?';
            $types .= 's';
            $params[] = (string)$filters['status'];
        }

        if (!empty($filters['assigned_to'])) {
            $clauses[] = 'ewl.assigned_to = ?';
            $types .= 'i';
            $params[] = (int)$filters['assigned_to'];
        }

        $sql = '';
        if (!empty($clauses)) {
            $sql = ($prefixedWhere ? ' WHERE ' : ' WHERE ') . implode(' AND ', $clauses);
        } elseif (!$prefixedWhere) {
            $sql = ' WHERE 1 = 1';
        }

        return [$sql, $types, $params];
    }

    private function fetchAll($sql, $types, array $params)
    {
        $stmt = $this->conn->prepare($sql);
        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}
