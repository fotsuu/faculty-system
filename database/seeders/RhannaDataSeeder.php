<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Record;
use Illuminate\Database\Seeder;

class RhannaDataSeeder extends Seeder
{
    /**
     * Run the database seeds for Rhanna baylosis user.
     */
    public function run(): void
    {
        // Find or create Rhanna baylosis user
        $user = User::where('email', 'rhanna@example.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Rhanna Baylosis',
                'email' => 'rhanna@example.com',
                'password' => bcrypt('password'),
                'role' => 'faculty',
                'department' => 'Department of History',
            ]);
        }

        // Clear existing data for this user
        Record::where('user_id', $user->id)->delete();
        Subject::where('user_id', $user->id)->delete();

        // Create sample students
        $studentNames = [
            'Celestine Parker DVM' => 'STU00001',
            'Michael Johnson' => 'STU00002',
            'Sarah Williams' => 'STU00003',
            'James Brown' => 'STU00004',
            'Emma Davis' => 'STU00005',
            'Oliver Martinez' => 'STU00006',
            'Sophia Rodriguez' => 'STU00007',
            'Noah Garcia' => 'STU00008',
            'Ava Wilson' => 'STU00009',
            'Ethan Taylor' => 'STU00010',
            'Isabella Anderson' => 'STU00011',
            'Lucas Thomas' => 'STU00012',
            'Mia Jackson' => 'STU00013',
            'Mason Lee' => 'STU00014',
            'Charlotte White' => 'STU00015',
            'Logan Harris' => 'STU00016',
        ];

        $students = [];
        foreach ($studentNames as $name => $id) {
            $students[] = Student::firstOrCreate(
                ['student_id' => $id],
                [
                    'name' => $name,
                    'email' => strtolower(str_replace(' ', '.', $name)) . '@student.edu',
                    'program' => 'BA Literature',
                ]
            );
        }

        // Create sample subjects for this faculty
        $subjects = [
            [
                'code' => 'HIS101R',
                'name' => 'Introduction to History',
                'description' => 'Basic history fundamentals',
                'user_id' => $user->id,
                'status' => 'active',
            ],
            [
                'code' => 'HIS205R',
                'name' => 'World Civilizations',
                'description' => 'Study of world civilizations',
                'user_id' => $user->id,
                'status' => 'active',
            ],
            [
                'code' => 'HIS310R',
                'name' => 'Medieval Studies',
                'description' => 'Medieval history and culture',
                'user_id' => $user->id,
                'status' => 'active',
            ],
            [
                'code' => 'HIS401R',
                'name' => 'Research Methods',
                'description' => 'Advanced research techniques',
                'user_id' => $user->id,
                'status' => 'active',
            ],
        ];

        $subjectsCollection = [];
        foreach ($subjects as $subjectData) {
            $subjectsCollection[] = Subject::firstOrCreate(
                ['code' => $subjectData['code']],
                $subjectData
            );
        }

        // Create sample records - distribute students across subjects
        $recordCount = 0;
        foreach ($subjectsCollection as $subject) {
            // Assign 10-12 students per subject
            $subjectStudents = collect($students)->random(min(10, count($students)));
            foreach ($subjectStudents as $student) {
                Record::create([
                    'user_id' => $user->id,
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                    'file_name' => 'sample_record_' . $student->id . '.csv',
                    'notes' => 'Sample grade record',
                ]);
                $recordCount++;
            }
        }
        
        echo "Created " . count($students) . " students, " . count($subjectsCollection) . " subjects, and " . $recordCount . " records for Rhanna Baylosis\n";
    }
}
