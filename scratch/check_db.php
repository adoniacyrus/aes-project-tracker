<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->connect();

echo "--- All Projects ---\n";
$res = $conn->query("SELECT id, project_name, project_code FROM projects");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- All Project Members mappings ---\n";
$res2 = $conn->query("SELECT pm.id as pm_id, pm.project_id, pm.user_id, u.full_name, p.project_code FROM project_members pm INNER JOIN users u ON pm.user_id = u.id INNER JOIN projects p ON pm.project_id = p.id");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}
