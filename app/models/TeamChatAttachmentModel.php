<?php

require_once __DIR__ . '/../../config/database.php';

class TeamChatAttachmentModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($commentId, $uploadedBy, $fileName, $originalName, $fileSize, $fileType)
    {
        $sql = "INSERT INTO team_chat_attachments (comment_id, uploaded_by, file_name, original_name, file_size, file_type)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iissis', $commentId, $uploadedBy, $fileName, $originalName, $fileSize, $fileType);

        if ($stmt->execute()) {
            return (int)$this->conn->insert_id;
        }

        return false;
    }

    public function findById($id)
    {
        $sql = "SELECT tca.*, tc.ticket_id
                FROM team_chat_attachments tca
                INNER JOIN ticket_comments tc ON tca.comment_id = tc.id
                WHERE tca.id = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getByCommentIds(array $commentIds)
    {
        if (empty($commentIds)) {
            return [];
        }

        $commentIds = array_values(array_unique(array_map('intval', $commentIds)));
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $types = str_repeat('i', count($commentIds));

        $sql = "SELECT * FROM team_chat_attachments WHERE comment_id IN ($placeholders) ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$commentIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $commentId = (int)$row['comment_id'];
            if (!isset($grouped[$commentId])) {
                $grouped[$commentId] = [];
            }
            $grouped[$commentId][] = $row;
        }

        return $grouped;
    }

    public function deleteById($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM team_chat_attachments WHERE id = ?');
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }
}
