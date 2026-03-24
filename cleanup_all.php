<?php
// Direct database cleanup — delete ALL records to start fresh
$host = '127.0.0.1';
$db = 'faculty_system';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $tables = [
        'records',
        'students',
        'subjects',
        'student_quizzes',
        'student_attendance',
        'student_midterm_exams',
        'student_final_exams',
        'student_grade_summaries'
    ];
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
        echo "✓ Truncated $table\n";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✅ All data cleared! Database is fresh.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
