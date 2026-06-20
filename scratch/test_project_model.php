<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/ProjectModel.php';

$projectModel = new ProjectModel();

$projectId = 1;
$userId = 3; // Let's check if we can add/remove a user

echo "Checking if user 3 is member of project 1...\n";
$isMemberBefore = $projectModel->isMember($projectId, $userId);
echo "Is member: " . ($isMemberBefore ? "Yes" : "No") . "\n";

if (!$isMemberBefore) {
    echo "Adding user 3 to project 1...\n";
    $added = $projectModel->addProjectMember($projectId, $userId);
    echo "Added successfully: " . ($added ? "Yes" : "No") . "\n";

    echo "Is member now: " . ($projectModel->isMember($projectId, $userId) ? "Yes" : "No") . "\n";

    echo "Removing user 3 from project 1...\n";
    $removed = $projectModel->removeProjectMember($projectId, $userId);
    echo "Removed successfully: " . ($removed ? "Yes" : "No") . "\n";

    echo "Is member now: " . ($projectModel->isMember($projectId, $userId) ? "Yes" : "No") . "\n";
} else {
    echo "User 3 is already a member, removing them first...\n";
    $removed = $projectModel->removeProjectMember($projectId, $userId);
    echo "Removed successfully: " . ($removed ? "Yes" : "No") . "\n";

    echo "Is member now: " . ($projectModel->isMember($projectId, $userId) ? "Yes" : "No") . "\n";
}
