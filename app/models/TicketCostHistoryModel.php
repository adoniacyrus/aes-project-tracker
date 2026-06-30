<?php

require_once __DIR__ . '/../../config/database.php';

class TicketCostHistoryModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getNextRevisionNumber(int $ticketId): int
    {
        $sql = 'SELECT COALESCE(MAX(revision_number), 0) + 1 AS next_revision
                FROM ticket_cost_history
                WHERE ticket_id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int)($row['next_revision'] ?? 1);
    }

    public function recordRevision(
        int $ticketId,
        int $projectId,
        ?float $oldCost,
        float $newCost,
        string $reason,
        int $changedBy
    ): ?int {
        $newCost = round($newCost, 2);
        $oldRounded = $oldCost !== null ? round($oldCost, 2) : null;

        if ($oldRounded !== null && abs($oldRounded - $newCost) < 0.00001) {
            return null;
        }

        $difference = $oldRounded === null
            ? $newCost
            : round($newCost - $oldRounded, 2);

        $revisionNumber = $this->getNextRevisionNumber($ticketId);
        $reason = trim($reason);

        if ($oldRounded === null) {
            $sql = 'INSERT INTO ticket_cost_history
                    (ticket_id, project_id, old_cost, new_cost, difference, reason, changed_by, revision_number)
                    VALUES (?, ?, NULL, ?, ?, ?, ?, ?)';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                'iiddssi',
                $ticketId,
                $projectId,
                $newCost,
                $difference,
                $reason,
                $changedBy,
                $revisionNumber
            );
        } else {
            $sql = 'INSERT INTO ticket_cost_history
                    (ticket_id, project_id, old_cost, new_cost, difference, reason, changed_by, revision_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                'iidddssi',
                $ticketId,
                $projectId,
                $oldRounded,
                $newCost,
                $difference,
                $reason,
                $changedBy,
                $revisionNumber
            );
        }

        if (!$stmt->execute()) {
            return null;
        }

        return (int)$this->conn->insert_id;
    }

    public function getRevisionHistoryForProject(int $projectId): array
    {
        $sql = 'SELECT tch.*,
                       t.title AS ticket_title,
                       u.full_name AS changed_by_name
                FROM ticket_cost_history tch
                INNER JOIN tickets t ON t.id = tch.ticket_id
                INNER JOIN users u ON u.id = tch.changed_by
                WHERE tch.project_id = ?
                ORDER BY tch.changed_at ASC, tch.id ASC';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function backfillFromEstimationLogs(): int
    {
        if (!$this->tableExists('ticket_cost_estimation_logs') || !$this->tableExists('ticket_cost_history')) {
            return 0;
        }

        $countRes = $this->conn->query('SELECT COUNT(*) AS c FROM ticket_cost_history');
        $countRow = $countRes ? $countRes->fetch_assoc() : null;
        if ((int)($countRow['c'] ?? 0) > 0) {
            return 0;
        }

        $sql = 'SELECT tcel.*, t.project_id
                FROM ticket_cost_estimation_logs tcel
                INNER JOIN tickets t ON t.id = tcel.ticket_id
                ORDER BY tcel.ticket_id ASC, tcel.id ASC';
        $result = $this->conn->query($sql);
        if (!$result) {
            return 0;
        }

        $revisionCounters = [];
        $inserted = 0;

        while ($row = $result->fetch_assoc()) {
            $ticketId = (int)$row['ticket_id'];
            $revisionCounters[$ticketId] = ($revisionCounters[$ticketId] ?? 0) + 1;

            $oldCost = ($row['previous_cost'] !== null && $row['previous_cost'] !== '')
                ? (float)$row['previous_cost']
                : null;
            $newCost = round((float)$row['new_cost'], 2);
            $difference = $oldCost === null
                ? $newCost
                : round($newCost - $oldCost, 2);

            $projectId = (int)$row['project_id'];
            $reason = (string)($row['reason'] ?? '');
            $changedBy = (int)$row['updated_by'];
            $changedAt = $row['created_at'];
            $revisionNumber = $revisionCounters[$ticketId];
            $oldVal = $oldCost !== null ? round($oldCost, 2) : null;

            if ($oldVal === null) {
                $insertSql = 'INSERT INTO ticket_cost_history
                    (ticket_id, project_id, old_cost, new_cost, difference, reason, changed_by, changed_at, revision_number)
                    VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)';
                $stmt = $this->conn->prepare($insertSql);
                $stmt->bind_param(
                    'iiddsisi',
                    $ticketId,
                    $projectId,
                    $newCost,
                    $difference,
                    $reason,
                    $changedBy,
                    $changedAt,
                    $revisionNumber
                );
            } else {
                $insertSql = 'INSERT INTO ticket_cost_history
                    (ticket_id, project_id, old_cost, new_cost, difference, reason, changed_by, changed_at, revision_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $this->conn->prepare($insertSql);
                $stmt->bind_param(
                    'iidddsisi',
                    $ticketId,
                    $projectId,
                    $oldVal,
                    $newCost,
                    $difference,
                    $reason,
                    $changedBy,
                    $changedAt,
                    $revisionNumber
                );
            }

            if ($stmt->execute()) {
                $inserted++;
            }
        }

        return $inserted;
    }

    private function tableExists(string $table): bool
    {
        $table = $this->conn->real_escape_string($table);
        $result = $this->conn->query("SHOW TABLES LIKE '{$table}'");

        return $result && $result->num_rows > 0;
    }
}
