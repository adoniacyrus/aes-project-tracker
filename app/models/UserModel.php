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

    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createUser($data)
    {
        $isTempPassword = isset($data['is_temp_password']) ? (int) $data['is_temp_password'] : 1;

        $sql = "INSERT INTO users (full_name, email, phone, password, is_temp_password, role, designation, organization, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssissss",
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['password'],
            $isTempPassword,
            $data['role'],
            $data['designation'],
            $data['organization'],
            $data['status']
        );

        return $stmt->execute();
    }

    public function updateUser($id, $data)
    {
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, designation = ?, organization = ? 
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssssi",
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['role'],
            $data['designation'],
            $data['organization'],
            $id
        );

        return $stmt->execute();
    }

    public function updateProfile($id, $data)
    {
        $sql = "UPDATE users SET full_name = ?, phone = ?, designation = ?, organization = ? 
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssi",
            $data['full_name'],
            $data['phone'],
            $data['designation'],
            $data['organization'],
            $id
        );

        return $stmt->execute();
    }

    public function updatePassword($id, $hashedPassword)
    {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    public function resetPasswordByAdmin($id, $hashedPassword)
    {
        $sql = "UPDATE users SET password = ?, is_temp_password = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    public function clearTemporaryPassword($id, $hashedPassword)
    {
        $sql = "UPDATE users SET password = ?, is_temp_password = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $id);
        return $stmt->execute();
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function updateLastLogin($id)
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getUsers($search = '', $offset = 0, $limit = 10)
    {
        $searchWildcard = "%" . $search . "%";
        $sql = "SELECT * FROM users 
                WHERE full_name LIKE ? 
                   OR email LIKE ? 
                   OR role LIKE ? 
                   OR designation LIKE ? 
                   OR organization LIKE ?
                ORDER BY id DESC 
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssii", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $limit, $offset);
        $stmt->execute();

        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    }

    public function getUsersCount($search = '')
    {
        $searchWildcard = "%" . $search . "%";
        $sql = "SELECT COUNT(*) as count FROM users 
                WHERE full_name LIKE ? 
                   OR email LIKE ? 
                   OR role LIKE ? 
                   OR designation LIKE ? 
                   OR organization LIKE ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] ?? 0;
    }

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

    public function getTaskableUsers()
    {
        $sql = "SELECT id, full_name, email, role, designation FROM users WHERE status = 'active' AND role IN ('admin', 'developer', 'intern') ORDER BY full_name ASC";
        $result = $this->conn->query($sql);
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }

    public function findBySlug($slug)
    {
        $slug = strtolower($slug);
        $sql = "SELECT * FROM users WHERE REPLACE(LOWER(full_name), ' ', '-') = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
