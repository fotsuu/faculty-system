<?php
// Check current records
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
    
    $fileName = '1769795485_SAD_3DSample.xlsx';
    echo "Cleaning records for: " . $fileName . "\n";
    
    $stmt = $pdo->prepare("DELETE FROM records WHERE file_name = ?");
    $stmt->execute([$fileName]);
    
    echo "✅ Deleted: " . $stmt->rowCount() . " records\n";
    
    $countStmt = $pdo->query("SELECT COUNT(*) FROM records");
    $remaining = $countStmt->fetchColumn();
    echo "Remaining records: " . $remaining . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
