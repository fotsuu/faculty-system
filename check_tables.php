<?php
$conn = new mysqli('localhost', 'root', '', 'faculty_system');
$result = $conn->query('SHOW TABLES');
echo "Available Tables:\n";
while ($row = $result->fetch_row()) {
    echo "  - " . $row[0] . "\n";
}

// Check attendance-related tables specifically
echo "\nLooking for attendance tables:\n";
$tables = $conn->query("SHOW TABLES LIKE '%attend%'");
if ($tables && $tables->num_rows > 0) {
    while ($row = $tables->fetch_row()) {
        echo "  Found: " . $row[0] . "\n";
    }
} else {
    echo "  No attendance tables found\n";
}

$conn->close();
