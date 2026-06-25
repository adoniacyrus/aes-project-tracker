<?php

require_once __DIR__ . '/../../config/database.php';

class NotificationLogModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function log(
        ?int $userId,
        string $email,
        string $type,
        string $subject,
        string $status,
        ?string $errorMessage = null
    ): bool {
        $sql = "INSERT INTO notification_logs (user_id, email, type, subject, status, error_message)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isssss', $userId, $email, $type, $subject, $status, $errorMessage);
        return $stmt->execute();
    }
}
