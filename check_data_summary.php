<?php
$conn = new mysqli('localhost', 'root', '', 'faculty_system');

$r = $conn->query('SELECT COUNT(*) as cnt FROM student_attendance');
$row = $r->fetch_assoc();
echo "Attendance records: " . $row['cnt'] . "\n";

$r = $conn->query('SELECT COUNT(*) as cnt FROM student_final_exams');
$row = $r->fetch_assoc();
echo "Final exam records: " . $row['cnt'] . "\n";

$r = $conn->query('SELECT COUNT(*) as cnt FROM student_midterm_exams');
$row = $r->fetch_assoc();
echo "Midterm exam records: " . $row['cnt'] . "\n";

echo "\n=== Complete Data Summary ===\n";
$tables = ['users', 'students', 'subjects', 'records', 'student_quizzes', 'student_attendance', 'student_midterm_exams', 'student_final_exams'];
foreach ($tables as $table) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM $table");
    $row = $r->fetch_assoc();
    echo ucfirst(str_replace('_', ' ', $table)) . ": " . $row['cnt'] . "\n";
}

$conn->close();
