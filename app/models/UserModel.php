<?php

require_once __DIR__ . '/../../config/database.php';

class UserModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Find a user by email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Find a user by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create a new user (Admin access)
     */
    public function createUser($data)
    {
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, role, designation, organization, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssss",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['password'],
            $data['role'],
            $data['designation'],
            $data['organization'],
            $data['status']
        );
        
        return $stmt->execute();
    }

    /**
     * Update a user (Admin access)
     */
    public function updateUser($id, $data)
    {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, designation = ?, organization = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssi",
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['role'],
            $data['designation'],
            $data['organization'],
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Update own profile (Self service - role and status are protected)
     */
    public function updateProfile($id, $data)
    {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, designation = ?, organization = ? 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssi",
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['designation'],
            $data['organization'],
            $id
        );
        
        return $stmt->execute();
    }

    /**
     * Update password
     */
    public function updatePassword($id, $hashedPassword)
    {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    /**
     * Update user status (Activate/Deactivate)
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($id)
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Get paginated users with search support
     */
    public function getUsers($search = '', $offset = 0, $limit = 10)
    {
        $searchWildcard = "%" . $search . "%";
        $sql = "SELECT * FROM users 
                WHERE first_name LIKE ? 
                   OR last_name LIKE ? 
                   OR email LIKE ? 
                   OR role LIKE ? 
                   OR designation LIKE ? 
                   OR organization LIKE ?
                ORDER BY id DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $limit, $offset);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * Get total count of users matching search query
     */
    public function getUsersCount($search = '')
    {
        $searchWildcard = "%" . $search . "%";
        $sql = "SELECT COUNT(*) as count FROM users 
                WHERE first_name LIKE ? 
                   OR last_name LIKE ? 
                   OR email LIKE ? 
                   OR role LIKE ? 
                   OR designation LIKE ? 
                   OR organization LIKE ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }

    /**
     * Get basic dashboard status statistics
     */
    public function getDashboardStats()
    {
        $resTotal = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $total = $resTotal->fetch_assoc()['count'] ?? 0;

        $resActive = $this->conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $active = $resActive->fetch_assoc()['count'] ?? 0;

        return [
            'total_users' => $total,
            'active_users' => $active
        ];
    }

    /**
     * Get all users who can be assigned tasks (admin, developer, intern)
     */
    public function getTaskableUsers()
    {
        $sql = "SELECT id, first_name, last_name, email, role, designation FROM users WHERE status = 'active' AND role IN ('admin', 'developer', 'intern') ORDER BY first_name ASC";
        $result = $this->conn->query($sql);
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }
}