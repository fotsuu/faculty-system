<?php
// Direct MySQL query to check database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "faculty_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Database Seeding Verification ===\n\n";

echo "Faculty Members (role = 'faculty'):\n";
$result = $conn->query("SELECT id, name, email, department FROM users WHERE role = 'faculty'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['name']} ({$row['email']}) - Dept: {$row['department']} - ID: {$row['id']}\n";
    }
    echo "  Total: " . $result->num_rows . "\n";
}

echo "\nStudents by Program:\n";
$result = $conn->query("SELECT COUNT(*) as count, program FROM students GROUP BY program");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  • {$row['program']}: {$row['count']}\n";
    }
}
echo "  Total Students: ";
$result = $conn->query("SELECT COUNT(*) as count FROM students");
$row = $result->fetch_assoc();
echo $row['count'] . "\n";

echo "\nSubjects:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM subjects");
$row = $result->fetch_assoc();
echo "  Total: " . $row['count'] . "\n";
if ($row['count'] > 0) {
    $result = $conn->query("SELECT code, name, user_id FROM subjects LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "    - {$row['code']}: {$row['name']} (Faculty ID: {$row['user_id']})\n";
    }
}

echo "\nRecords:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM records");
$row = $result->fetch_assoc();
echo "  Total: " . $row['count'] . "\n";

echo "\nQuizzes:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM student_quizzes");
$row = $result->fetch_assoc();
echo "  Total: " . $row['count'] . "\n";

echo "\nAttendance Sessions:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM student_attendances");
$row = $result->fetch_assoc();
echo "  Total: " . $row['count'] . "\n";

$conn->close();
