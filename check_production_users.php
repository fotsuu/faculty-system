<?php
$conn = new mysqli('localhost', 'root', '', 'faculty_system');

echo "=== Production Users Setup ===\n\n";

echo "Program Heads:\n";
$result = $conn->query("SELECT id, name, email, department, role FROM users WHERE role = 'program_head' ORDER BY department");
while ($row = $result->fetch_assoc()) {
    echo "  • {$row['name']} ({$row['email']}) - Dept: {$row['department']}\n";
}

echo "\nFaculty:\n";
$result = $conn->query("SELECT id, name, email, department FROM users WHERE role = 'faculty' ORDER BY department");
while ($row = $result->fetch_assoc()) {
    echo "  • {$row['name']} ({$row['email']}) - Dept: {$row['department']}\n";
}

echo "\nAdmin/Dean:\n";
$result = $conn->query("SELECT id, name, email, role FROM users WHERE role IN ('dean', 'admin')");
while ($row = $result->fetch_assoc()) {
    echo "  • {$row['name']} ({$row['email']}) - Role: {$row['role']}\n";
}

echo "\n=== Ready for Real Data ===\n";
echo "Faculty can now upload class records which will be:\n";
echo "  1. Associated with their user_id\n";
echo "  2. Filtered by department\n";
echo "  3. Visible to their program head only\n";
echo "  4. Visible to dean (all departments)\n";

$conn->close();
