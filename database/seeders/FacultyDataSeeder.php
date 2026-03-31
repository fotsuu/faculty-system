<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Record;
use Illuminate\Database\Seeder;

class FacultyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for seeding
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Clear existing data if any
        Student::truncate();
        Subject::truncate();
        Record::truncate();
        
        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Get the test faculty user
        $faculty = User::where('email', 'test@example.com')->first();
        
        if (!$faculty) {
            $faculty = User::create([
                'name' => 'Dr. John Smith',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'role' => 'faculty',
                'department' => 'Department of History',
            ]);
        }

        // Create sample students
        $students = Student::factory(15)->create();

        // Create sample subjects for this faculty
        $subjects = [
            [
                'code' => 'HIST101',
                'name' => 'Introduction to History',
                'description' => 'Basic history fundamentals',
                'user_id' => $faculty->id,
                'status' => 'active',
            ],
            [
                'code' => 'HIST205',
                'name' => 'World Civilizations',
                'description' => 'Study of world civilizations',
                'user_id' => $faculty->id,
                'status' => 'active',
            ],
            [
                'code' => 'HIST310',
                'name' => 'Medieval Studies',
                'description' => 'Medieval history and culture',
                'user_id' => $faculty->id,
                'status' => 'active',
            ],
            [
                'code' => 'HIST401',
                'name' => 'Research Methods',
                'description' => 'Advanced research techniques',
                'user_id' => $faculty->id,
                'status' => 'active',
            ],
        ];

        foreach ($subjects as $subjectData) {
            Subject::create($subjectData);
        }

        // Create sample records
        $subjectsCollection = Subject::where('user_id', $faculty->id)->get();
        
        foreach ($subjectsCollection as $subject) {
            foreach ($students->random(10) as $student) {
                Record::create([
                    'user_id' => $faculty->id,
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                    'file_name' => 'record_' . $student->id . '.csv',
                    'notes' => 'Uploaded class records',
                ]);
            }
        }
    }
}
