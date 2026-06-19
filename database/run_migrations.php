<?php

require_once __DIR__ . '/../config/database.php';

echo "Starting DB Migrations...\n";

$database = new Database();
$conn = $database->connect();

$sqlPath = __DIR__ . '/migrations/schema.sql';
if (!file_exists($sqlPath)) {
    die("Error: Schema SQL file not found at $sqlPath\n");
}

$sql = file_get_contents($sqlPath);

// Split queries by semicolon (simple splitter, works for this schema)
$queries = array_filter(array_map('trim', explode(';', $sql)));

$successCount = 0;
$errorCount = 0;

// Turn off foreign key checks temporarily if needed, though not strictly required here
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");

foreach ($queries as $query) {
    if (empty($query)) {
        continue;
    }
    
    if ($conn->query($query)) {
        $successCount++;
    } else {
        echo "Error executing query: " . $conn->error . "\nQuery: " . substr($query, 0, 100) . "...\n";
        $errorCount++;
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\nMigration Finished. Queries executed successfully: $successCount, Errors: $errorCount\n";
if ($errorCount > 0) {
    exit(1);
} else {
    exit(0);
}
