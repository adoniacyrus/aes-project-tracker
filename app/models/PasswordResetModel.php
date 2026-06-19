<?php

require_once __DIR__ . '/../../config/database.php';

class PasswordResetModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Create a reset token for an email address (clears older tokens first)
     */
    public function createToken($email, $token)
    {
        // First delete any existing tokens for this email
        $this->deleteToken($email);
        
        $sql = "INSERT INTO password_resets (email, token) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $email, $token);
        return $stmt->execute();
    }

    /**
     * Validate reset token. Matches token and checks 1-hour expiration.
     */
    public function validateToken($email, $token)
    {
        // Get the latest reset token record for the email
        $sql = "SELECT * FROM password_resets WHERE email = ? AND token = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $reset = $stmt->get_result()->fetch_assoc();
        
        if (!$reset) {
            return false;
        }

        // Check if token is older than 1 hour (3600 seconds)
        $createdAt = strtotime($reset['created_at']);
        $diff = time() - $createdAt;
        
        if ($diff > 3600) {
            // Token expired, let's delete it
            $this->deleteToken($email);
            return false;
        }
        
        return true;
    }

    /**
     * Delete token
     */
    public function deleteToken($email)
    {
        $sql = "DELETE FROM password_resets WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        return $stmt->execute();
    }
}
