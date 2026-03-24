<?php
// Direct database cleanup — simpler and safer
$dbPath = __DIR__ . '/database/database.sqlite';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/storage/database.sqlite';
}

// For MySQL, try to connect using env vars
$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_DATABASE') ?: 'faculty_system';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $fileName = '1769790142_SAD_3DSample.xlsx';
    $stmt = $pdo->prepare("DELETE FROM records WHERE file_name = ?");
    $stmt->execute([$fileName]);
    
    echo "✅ Cleaned records for " . $fileName . " (Deleted: " . $stmt->rowCount() . ")\n";
    
    $countStmt = $pdo->query("SELECT COUNT(*) FROM records");
    $remaining = $countStmt->fetchColumn();
    echo "Remaining records: " . $remaining . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
