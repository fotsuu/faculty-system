<?php
$db = new mysqli('localhost', 'root', '', 'faculty_system');
$result = $db->query('SELECT COUNT(*) as cnt FROM student_quizzes');
$row = $result->fetch_assoc();
echo "Quizzes: " . $row['cnt'] . "\n";

$result = $db->query('SELECT COUNT(*) as cnt FROM student_attendance');
$row = $result->fetch_assoc();
echo "Attendance: " . $row['cnt'] . "\n";

$result = $db->query('SELECT COUNT(*) as cnt FROM student_midterm_exams');
$row = $result->fetch_assoc();
echo "Midterm: " . $row['cnt'] . "\n";

$result = $db->query('SELECT COUNT(*) as cnt FROM student_final_exams');
$row = $result->fetch_assoc();
echo "Final: " . $row['cnt'] . "\n";
$db->close();
?>
