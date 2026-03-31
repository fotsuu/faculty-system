<?php
require_once 'bootstrap/app.php';

use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Record;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Database Seeding Verification ===\n\n";

echo "BSIT Faculty:\n";
$bsitFaculty = User::where('role', 'faculty')->where('department', 'BSIT')->get();
echo "- Found: " . $bsitFaculty->count() . "\n";
foreach ($bsitFaculty as $user) {
    echo "  • {$user->name} ({$user->email}) - ID: {$user->id}\n";
}

echo "\nBSIS Faculty:\n";
$bsisFaculty = User::where('role', 'faculty')->where('department', 'BSIS')->get();
echo "- Found: " . $bsisFaculty->count() . "\n";
foreach ($bsisFaculty as $user) {
    echo "  • {$user->name} ({$user->email}) - ID: {$user->id}\n";
}

echo "\nStudents:\n";
echo "- BSIT Students: " . Student::where('program', 'BSIT')->count() . "\n";
echo "- BSIS Students: " . Student::where('program', 'BSIS')->count() . "\n";
echo "- Total Students: " . Student::count() . "\n";

echo "\nSubjects:\n";
echo "- Total: " . Subject::count() . "\n";
if (Subject::count() === 0) {
    echo "  (No subjects created - seeder may have failed silently)\n";
}

echo "\nRecords:\n";
echo "- Total: " . Record::count() . "\n";
if (Record::count() === 0) {
    echo "  (No records created - seeder may have failed silently)\n";
}

echo "\n";
