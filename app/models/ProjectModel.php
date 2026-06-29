<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../services/TicketWorkflowService.php';

class ProjectModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Find a project by ID
     */
    public function findById($id)
    {
        $sql = "SELECT p.*, u.full_name as creator_name 
                FROM projects p 
                LEFT JOIN users u ON p.created_by = u.id 
                WHERE p.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Find a project by its project code
     */
    public function findByCode($code)
    {
        $sql = "SELECT * FROM projects WHERE project_code = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Generate a unique project code from the project name.
     */
    public function generateProjectCode($projectName)
    {
        $base = $this->buildCodeAbbreviation($projectName);
        $code = $base;
        $suffix = 0;

        while ($this->findByCode($code)) {
            $suffix++;
            $code = $base . '-' . $suffix;
            if (strlen($code) > 20) {
                $trimmedBase = substr($base, 0, max(1, 20 - strlen('-' . $suffix)));
                $code = $trimmedBase . '-' . $suffix;
            }
        }

        return $code;
    }

    /**
     * Build a readable abbreviation from a project name.
     */
    private function buildCodeAbbreviation($projectName)
    {
        $words = preg_split('/\s+/', trim((string)$projectName));
        $words = array_values(array_filter(array_map(function ($word) {
            return preg_replace('/[^a-zA-Z0-9]/', '', $word);
        }, $words)));

        if (empty($words)) {
            return 'PRJ';
        }

        if (count($words) === 1) {
            return substr(strtoupper($words[0]), 0, 20);
        }

        $first = strtoupper($words[0]);

        // Short leading token with multiple words: PREFIX-ACRONYM (e.g. AES-PMS)
        if (strlen($first) <= 4 && count($words) >= 3) {
            $acronym = '';
            foreach (array_slice($words, 1) as $word) {
                $acronym .= strtoupper($word[0]);
            }
            $code = $first . '-' . $acronym;
            return substr($code, 0, 20);
        }

        // Otherwise use acronym of all words (e.g. CRM, IMS, EAS)
        $acronym = '';
        foreach ($words as $word) {
            $acronym .= strtoupper($word[0]);
        }

        return substr($acronym, 0, 20);
    }

    /**
     * Create a new project (Admin only)
     */
    public function createProject($data)
    {
        $sql = "INSERT INTO projects (project_name, project_code, client_name, organization_name, project_description, project_type, technology_stack, start_date, expected_end_date, project_cost, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssdsi",
            $data['project_name'],
            $data['project_code'],
            $data['client_name'],
            $data['organization_name'],
            $data['project_description'],
            $data['project_type'],
            $data['technology_stack'],
            $data['start_date'],
            $data['expected_end_date'],
            $data['project_cost'],
            $data['status'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Update project details (Admin only)
     */
    public function updateProject($id, $data)
    {
        $sql = "UPDATE projects SET project_name = ?, project_code = ?, client_name = ?, organization_name = ?, project_description = ?, project_type = ?, technology_stack = ?, start_date = ?, expected_end_date = ?, project_cost = ?, status = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssdsi",
            $data['project_name'],
            $data['project_code'],
            $data['client_name'],
            $data['organization_name'],
            $data['project_description'],
            $data['project_type'],
            $data['technology_stack'],
            $data['start_date'],
            $data['expected_end_date'],
            $data['project_cost'],
            $data['status'],
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Archive/Unarchive project (Admin only)
     */
    public function archiveProject($id, $isArchived)
    {
        $sql = "UPDATE projects SET is_archived = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $isArchived, $id);
        return $stmt->execute();
    }

    /**
     * Sum estimated_cost for approved commercial tickets on a project.
     */
    public function getTotalApprovedTicketRevenue($projectId)
    {
        $statuses = TicketWorkflowService::getApprovedRevenueStatuses();
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT COALESCE(SUM(estimated_cost), 0) AS total
                FROM tickets
                WHERE project_id = ?
                  AND status IN ($placeholders)
                  AND estimated_cost IS NOT NULL";

        $stmt = $this->conn->prepare($sql);
        $types = 'i' . str_repeat('s', count($statuses));
        $params = array_merge([(int)$projectId], $statuses);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (float)($row['total'] ?? 0);
    }

    /**
     * Get paginated projects list with role restrictions, search, status, and archive filters
     */
    public function getProjects($userId, $userRole, $search = '', $offset = 0, $limit = 10, $statusFilter = '', $archiveFilter = 0)
    {
        $searchWildcard = "%" . $search . "%";
        
        // Base Query
        if ($userRole === 'admin') {
            $sql = "SELECT p.*, 
                           (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
                           (SELECT COUNT(*) FROM tickets WHERE project_id = p.id) as ticket_count
                    FROM projects p 
                    WHERE (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ? OR p.organization_name LIKE ?)";
            
            if ($statusFilter !== '') {
                $sql .= " AND p.status = ?";
            }
            if ($archiveFilter !== null) {
                $sql .= " AND p.is_archived = ?";
            }
            $sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT p.*, 
                           (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
                           (SELECT COUNT(*) FROM tickets WHERE project_id = p.id) as ticket_count
                    FROM projects p 
                    INNER JOIN project_members pm ON p.id = pm.project_id 
                    WHERE pm.user_id = ? 
                      AND (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ? OR p.organization_name LIKE ?)";
            
            if ($statusFilter !== '') {
                $sql .= " AND p.status = ?";
            }
            if ($archiveFilter !== null) {
                $sql .= " AND p.is_archived = ?";
            }
            $sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
        }

        $stmt = $this->conn->prepare($sql);

        // Bind params dynamically based on role and filters
        if ($userRole === 'admin') {
            if ($statusFilter !== '' && $archiveFilter !== null) {
                $stmt->bind_param("sssssiii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter, $archiveFilter, $limit, $offset);
            } elseif ($statusFilter !== '') {
                $stmt->bind_param("sssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter, $limit, $offset);
            } elseif ($archiveFilter !== null) {
                $stmt->bind_param("ssssiii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $archiveFilter, $limit, $offset);
            } else {
                $stmt->bind_param("ssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $limit, $offset);
            }
        } else {
            if ($statusFilter !== '' && $archiveFilter !== null) {
                $stmt->bind_param("isssssiii", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter, $archiveFilter, $limit, $offset);
            } elseif ($statusFilter !== '') {
                $stmt->bind_param("isssssii", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter, $limit, $offset);
            } elseif ($archiveFilter !== null) {
                $stmt->bind_param("issssiii", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $archiveFilter, $limit, $offset);
            } else {
                $stmt->bind_param("issssii", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $limit, $offset);
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        return $projects;
    }

    /**
     * Get total project count matching parameters
     */
    public function getProjectsCount($userId, $userRole, $search = '', $statusFilter = '', $archiveFilter = 0)
    {
        $searchWildcard = "%" . $search . "%";

        if ($userRole === 'admin') {
            $sql = "SELECT COUNT(*) as count FROM projects p 
                    WHERE (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ? OR p.organization_name LIKE ?)";
            
            if ($statusFilter !== '') {
                $sql .= " AND p.status = ?";
            }
            if ($archiveFilter !== null) {
                $sql .= " AND p.is_archived = ?";
            }
        } else {
            $sql = "SELECT COUNT(*) as count FROM projects p 
                    INNER JOIN project_members pm ON p.id = pm.project_id 
                    WHERE pm.user_id = ? 
                      AND (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ? OR p.organization_name LIKE ?)";
            
            if ($statusFilter !== '') {
                $sql .= " AND p.status = ?";
            }
            if ($archiveFilter !== null) {
                $sql .= " AND p.is_archived = ?";
            }
        }

        $stmt = $this->conn->prepare($sql);

        if ($userRole === 'admin') {
            if ($statusFilter !== '' && $archiveFilter !== null) {
                $stmt->bind_param("sssssi", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter, $archiveFilter);
            } elseif ($statusFilter !== '') {
                $stmt->bind_param("sssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter);
            } elseif ($archiveFilter !== null) {
                $stmt->bind_param("ssssi", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $archiveFilter);
            } else {
                $stmt->bind_param("ssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
            }
        } else {
            if ($statusFilter !== '' && $archiveFilter !== null) {
                $stmt->bind_param("isssssi", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter, $archiveFilter);
            } elseif ($statusFilter !== '') {
                $stmt->bind_param("isssss", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $statusFilter);
            } elseif ($archiveFilter !== null) {
                $stmt->bind_param("issssi", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $archiveFilter);
            } else {
                $stmt->bind_param("issss", $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
            }
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }

    /**
     * Count projects per simplified status for tab badges (search + archive aware).
     */
    public function getProjectStatusCounts($userId, $userRole, $search = '', $archiveFilter = 0)
    {
        $searchWildcard = '%' . $search . '%';
        $counts = array_fill_keys(get_project_statuses(), 0);

        if ($userRole === 'admin') {
            $sql = "SELECT p.status, COUNT(*) as count
                    FROM projects p
                    WHERE (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ? OR p.organization_name LIKE ?)";
            if ($archiveFilter !== null) {
                $sql .= ' AND p.is_archived = ?';
            }
            $sql .= ' GROUP BY p.status';
        } else {
            $sql = "SELECT p.status, COUNT(*) as count
                    FROM projects p
                    INNER JOIN project_members pm ON p.id = pm.project_id
                    WHERE pm.user_id = ?
                      AND (p.project_name LIKE ? OR p.project_code LIKE ? OR p.client_name LIKE ? OR p.organization_name LIKE ?)";
            if ($archiveFilter !== null) {
                $sql .= ' AND p.is_archived = ?';
            }
            $sql .= ' GROUP BY p.status';
        }

        $stmt = $this->conn->prepare($sql);

        if ($userRole === 'admin') {
            if ($archiveFilter !== null) {
                $stmt->bind_param('ssssi', $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $archiveFilter);
            } else {
                $stmt->bind_param('ssss', $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
            }
        } else {
            if ($archiveFilter !== null) {
                $stmt->bind_param('issssi', $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $archiveFilter);
            } else {
                $stmt->bind_param('issss', $userId, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = 0;

        while ($row = $result->fetch_assoc()) {
            $normalized = normalize_project_status($row['status'] ?? '');
            if (!isset($counts[$normalized])) {
                continue;
            }
            $count = (int)($row['count'] ?? 0);
            $counts[$normalized] += $count;
            $total += $count;
        }

        return [
            '' => $total,
            'Initiated' => $counts['Initiated'],
            'Processing' => $counts['Processing'],
            'Completed' => $counts['Completed'],
        ];
    }

    /**
     * Get all assigned members for a project
     */
    public function getProjectMembers($projectId)
    {
        $sql = "SELECT pm.id as assignment_id, pm.assigned_at, u.id as user_id, u.full_name, u.email, u.role, u.designation, u.organization 
                FROM project_members pm 
                INNER JOIN users u ON pm.user_id = u.id 
                WHERE pm.project_id = ? 
                ORDER BY u.role, u.full_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        return $members;
    }

    /**
     * Assign a user to a project (Admin only)
     */
    public function addProjectMember($projectId, $userId)
    {
        $sql = "INSERT INTO project_members (project_id, user_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $projectId, $userId);
        return $stmt->execute();
    }

    /**
     * Remove a user from a project (Admin only)
     */
    public function removeProjectMember($projectId, $userId)
    {
        $sql = "DELETE FROM project_members WHERE project_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $projectId, $userId);
        return $stmt->execute();
    }

    /**
     * Check if user is a member of a project
     */
    public function isMember($projectId, $userId)
    {
        $sql = "SELECT id FROM project_members WHERE project_id = ? AND user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $projectId, $userId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Get all active users who can be assigned to this project (not yet assigned)
     */
    public function getAvailableUsersForProject($projectId)
    {
        $sql = "SELECT id, full_name, email, role, designation 
                FROM users 
                WHERE status = 'active' 
                  AND id NOT IN (SELECT user_id FROM project_members WHERE project_id = ?) 
                ORDER BY full_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * Get KPI count of projects for a specific role or user
     */
    public function getProjectsDashboardStats($userId, $userRole)
    {
        if ($userRole === 'admin') {
            $totalRes = $this->conn->query("SELECT COUNT(*) as count FROM projects WHERE is_archived = 0");
            $activeRes = $this->conn->query("SELECT COUNT(*) as count FROM projects WHERE is_archived = 0 AND status IN ('Initiated', 'Processing')");
            $completedRes = $this->conn->query("SELECT COUNT(*) as count FROM projects WHERE is_archived = 0 AND status = 'Completed'");
        } else {
            $stmt1 = $this->conn->prepare("SELECT COUNT(*) as count FROM projects p INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? AND p.is_archived = 0");
            $stmt1->bind_param("i", $userId);
            $stmt1->execute();
            $total = $stmt1->get_result()->fetch_assoc()['count'] ?? 0;

            $stmt2 = $this->conn->prepare("SELECT COUNT(*) as count FROM projects p INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? AND p.is_archived = 0 AND p.status IN ('Initiated', 'Processing')");
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $active = $stmt2->get_result()->fetch_assoc()['count'] ?? 0;

            $stmt3 = $this->conn->prepare("SELECT COUNT(*) as count FROM projects p INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? AND p.is_archived = 0 AND p.status = 'Completed'");
            $stmt3->bind_param("i", $userId);
            $stmt3->execute();
            $completed = $stmt3->get_result()->fetch_assoc()['count'] ?? 0;

            return [
                'total' => $total,
                'active' => $active,
                'completed' => $completed
            ];
        }

        return [
            'total' => $totalRes->fetch_assoc()['count'] ?? 0,
            'active' => $activeRes->fetch_assoc()['count'] ?? 0,
            'completed' => $completedRes->fetch_assoc()['count'] ?? 0
        ];
    }

    public function getClientActiveProjects($clientId)
    {
        $sql = "SELECT p.id, p.project_name, p.project_code, p.status, p.expected_end_date 
                FROM projects p 
                INNER JOIN project_members pm ON p.id = pm.project_id 
                WHERE pm.user_id = ? AND p.status != 'Completed' AND p.is_archived = 0 
                ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        return $projects;
    }

    public function getDeveloperAssignedProjects($devId)
    {
        $sql = "SELECT p.id, p.project_name, p.project_code, p.status, p.expected_end_date 
                FROM projects p 
                INNER JOIN project_members pm ON p.id = pm.project_id 
                WHERE pm.user_id = ? AND p.is_archived = 0 
                ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $devId);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        return $projects;
    }
}

