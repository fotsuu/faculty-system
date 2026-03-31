<?php

// Load .env file manually
$env = [];
$lines = file('.env');
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim($value);
}

// Create PDO connection
$dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
$password = !empty($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
$db = new PDO($dsn, $env['DB_USERNAME'], $password);

echo "Cleaning up bad imported data...\n";

// Delete records for this file
$stmt = $db->prepare("DELETE FROM records WHERE user_id = ? AND file_name LIKE ?");
$stmt->execute([1, '%SAD_3DSample%']);

echo "Deleted records for uploaded file\n";

// Count remaining
$stmt = $db->prepare("SELECT COUNT(*) as count FROM records WHERE user_id = ?");
$stmt->execute([1]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Remaining records for user 1: {$result['count']}\n";
