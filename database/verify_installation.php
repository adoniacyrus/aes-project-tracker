<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/helpers.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/models/ActivityLogModel.php';
require_once __DIR__ . '/../app/models/PasswordResetModel.php';

echo "=== System Verification Script ===\n";

// 1. Syntax Check on all core files
$filesToCheck = [
    'index.php',
    'app/helpers/helpers.php',
    'app/middleware/AuthMiddleware.php',
    'app/middleware/AdminMiddleware.php',
    'app/models/UserModel.php',
    'app/models/ActivityLogModel.php',
    'app/models/PasswordResetModel.php',
    'app/models/ProjectModel.php',
    'app/models/TicketModel.php',
    'app/models/TaskModel.php',
    'app/controllers/AuthController.php',
    'app/controllers/DashboardController.php',
    'app/controllers/UserController.php',
    'app/controllers/ProfileController.php',
    'app/controllers/ProjectController.php',
    'app/controllers/TicketController.php',
    'app/controllers/TaskController.php',
    'app/services/TicketWorkflowService.php'
];

echo "\n1. Checking file syntax...\n";
foreach ($filesToCheck as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (!file_exists($fullPath)) {
        echo "FAIL: File does not exist: $file\n";
        exit(1);
    }
    
    $output = [];
    $resultCode = 0;
    exec("php -l " . escapeshellarg($fullPath), $output, $resultCode);
    if ($resultCode !== 0) {
        echo "FAIL: Syntax error in file: $file\n" . implode("\n", $output) . "\n";
        exit(1);
    }
    echo "OK: $file syntax is clean.\n";
}

// 2. Database Integration Checks
echo "\n2. Running Database Asserts...\n";

// A. Check Database Connection
$db = new Database();
$conn = $db->connect();
if ($conn->connect_error) {
    echo "FAIL: Database connection failed.\n";
    exit(1);
}
echo "OK: Connected to database successfully.\n";

// B. Check UserModel finds seed admin
$userModel = new UserModel();
$admin = $userModel->findByEmail('admin@aes.com');
if (!$admin || $admin['role'] !== 'admin') {
    echo "FAIL: Admin seed user not found or role mismatch.\n";
    exit(1);
}
echo "OK: Found Seed Admin user: " . $admin['full_name'] . " (Role: " . $admin['role'] . ")\n";

// C. Check Activity Log creation
$logModel = new ActivityLogModel();
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'CLI Verification Engine';
if (!$logModel->log($admin['id'], $admin['email'], 'cli_verification', 'Verification suite executed')) {
    echo "FAIL: Could not create user activity log.\n";
    exit(1);
}
echo "OK: Logged verification activity successfully.\n";

$logs = $logModel->getRecentLogs(1);
if (empty($logs) || $logs[0]['action'] !== 'cli_verification') {
    echo "FAIL: Log retrieval mismatch.\n";
    exit(1);
}
echo "OK: Verified log write-to-read roundtrip: " . $logs[0]['details'] . "\n";

// D. Check Password Reset token roundtrip
$resetModel = new PasswordResetModel();
$testToken = bin2hex(random_bytes(32));
if (!$resetModel->createToken($admin['email'], $testToken)) {
    echo "FAIL: Could not insert reset token.\n";
    exit(1);
}
echo "OK: Inserted temporary reset token.\n";

if (!$resetModel->validateToken($admin['email'], $testToken)) {
    echo "FAIL: Reset token validation failed.\n";
    exit(1);
}
echo "OK: Verified reset token successfully.\n";

$resetModel->deleteToken($admin['email']);
if ($resetModel->validateToken($admin['email'], $testToken)) {
    echo "FAIL: Reset token deletion failed.\n";
    exit(1);
}
echo "OK: Token successfully cleaned up.\n";

echo "\n=== ALL CHECKS PASSED SUCCESSFULLY ===\n";
exit(0);
