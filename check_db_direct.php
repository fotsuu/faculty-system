<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

// Boot the application
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Get the database connection
$db = $app->make('db');

// Check users
echo "=== USERS ===\n";
$users = $db->table('users')->get();
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
}

// Check current records count
echo "\n=== DATABASE STATS ===\n";
$recordsCount = $db->table('records')->count();
echo "Records Count: $recordsCount\n";

$studentsCount = $db->table('students')->count();
echo "Students Count: $studentsCount\n";

$subjectsCount = $db->table('subjects')->count();
echo "Subjects Count: $subjectsCount\n";

// Show current records
echo "\n=== CURRENT RECORDS ===\n";
$records = $db->table('records')->limit(5)->get();
foreach ($records as $rec) {
    echo "ID: {$rec->id}, User: {$rec->user_id}, Subject: {$rec->subject_id}, Student: {$rec->student_id}, Notes: {$rec->notes}\n";
}
