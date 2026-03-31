<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Record;
use App\Models\StudentQuiz;
use App\Models\StudentAttendance;
use App\Models\StudentMidtermExam;
use App\Models\StudentFinalExam;

class ProgramHeadDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get BSIT and BSIS faculty
        $bsitFaculty = User::where('role', 'faculty')->where('department', 'BSIT')->get();
        $bsisFaculty = User::where('role', 'faculty')->where('department', 'BSIS')->get();

        // Create sample students for BSIT
        $bsitStudents = [
            ['name' => 'John Reyes', 'student_id' => '2024-0001', 'program' => 'BSIT', 'year_level' => '3', 'email' => 'john.reyes@student.edu'],
            ['name' => 'Maria Santos', 'student_id' => '2024-0002', 'program' => 'BSIT', 'year_level' => '3', 'email' => 'maria.santos@student.edu'],
            ['name' => 'Carlos Villanueva', 'student_id' => '2024-0003', 'program' => 'BSIT', 'year_level' => '2', 'email' => 'carlos.villanueva@student.edu'],
            ['name' => 'Angela Cruz', 'student_id' => '2024-0004', 'program' => 'BSIT', 'year_level' => '2', 'email' => 'angela.cruz@student.edu'],
            ['name' => 'Mark Gonzales', 'student_id' => '2024-0005', 'program' => 'BSIT', 'year_level' => '1', 'email' => 'mark.gonzales@student.edu'],
        ];

        $bsitStudentModels = [];
        foreach ($bsitStudents as $data) {
            $student = Student::updateOrCreate(
                ['student_id' => $data['student_id']],
                $data
            );
            $bsitStudentModels[] = $student;
        }

        // Create sample students for BSIS
        $bsisStudents = [
            ['name' => 'Patricia Lim', 'student_id' => '2024-1001', 'program' => 'BSIS', 'year_level' => '3', 'email' => 'patricia.lim@student.edu'],
            ['name' => 'James Rivera', 'student_id' => '2024-1002', 'program' => 'BSIS', 'year_level' => '3', 'email' => 'james.rivera@student.edu'],
            ['name' => 'Rosa Diaz', 'student_id' => '2024-1003', 'program' => 'BSIS', 'year_level' => '2', 'email' => 'rosa.diaz@student.edu'],
            ['name' => 'Luis Fernandez', 'student_id' => '2024-1004', 'program' => 'BSIS', 'year_level' => '2', 'email' => 'luis.fernandez@student.edu'],
            ['name' => 'Sofia Moreno', 'student_id' => '2024-1005', 'program' => 'BSIS', 'year_level' => '1', 'email' => 'sofia.moreno@student.edu'],
        ];

        $bsisStudentModels = [];
        foreach ($bsisStudents as $data) {
            $student = Student::updateOrCreate(
                ['student_id' => $data['student_id']],
                $data
            );
            $bsisStudentModels[] = $student;
        }

        // Create BSIT subjects and records
        if ($bsitFaculty->count() > 0) {
            $faculty1 = $bsitFaculty->first();
            $bsitSubjects = [
                ['code' => 'CS101', 'name' => 'Introduction to Programming', 'user_id' => $faculty1->id, 'status' => 'active'],
                ['code' => 'CS205', 'name' => 'Data Structures', 'user_id' => $faculty1->id, 'status' => 'active'],
            ];

            foreach ($bsitSubjects as $subjData) {
                $subject = Subject::updateOrCreate(
                    ['code' => $subjData['code'], 'user_id' => $subjData['user_id']],
                    $subjData
                );

                // Create records and assessments for each student
                foreach ($bsitStudentModels as $student) {
                    Record::updateOrCreate(
                        [
                            'user_id' => $faculty1->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'section' => 'A',
                            'file_name' => 'classrecord_' . $subject->code . '.xlsx',
                            'notes' => 'Imported from class record',
                            'scores' => [
                                'Q1' => rand(70, 95),
                                'Q2' => rand(70, 95),
                                'Midterm Exam' => rand(75, 90),
                                'Final Exam' => rand(75, 90),
                            ],
                            'numeric_grade' => rand(75, 95),
                            'grade_point' => round(rand(10, 30) / 10, 2),
                            'submission_status' => 'approved',
                        ]
                    );

                    // Create quiz records
                    StudentQuiz::updateOrCreate(
                        [
                            'user_id' => $faculty1->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                            'quiz_type' => 'non_laboratory',
                            'quiz_number' => 1,
                        ],
                        [
                            'score' => rand(70, 95),
                            'total_points' => 100,
                        ]
                    );

                    // Create attendance records
                    for ($i = 1; $i <= 10; $i++) {
                        StudentAttendance::create([
                            'user_id' => $faculty1->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                            'session_number' => $i,
                            'status' => rand(0, 1) ? 'present' : 'absent',
                        ]);
                    }

                    // Create midterm exam records
                    StudentMidtermExam::updateOrCreate(
                        [
                            'user_id' => $faculty1->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'exam_score' => rand(75, 90),
                            'total_points' => 100,
                        ]
                    );

                    // Create final exam records
                    StudentFinalExam::updateOrCreate(
                        [
                            'user_id' => $faculty1->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'exam_score' => rand(75, 90),
                            'total_points' => 100,
                        ]
                    );
                }
            }
        }

        // Create BSIS subjects and records
        if ($bsisFaculty->count() > 0) {
            $faculty2 = $bsisFaculty->first();
            $bsisSubjects = [
                ['code' => 'IS101', 'name' => 'Information Systems Fundamentals', 'user_id' => $faculty2->id, 'status' => 'active'],
                ['code' => 'IS205', 'name' => 'Database Management', 'user_id' => $faculty2->id, 'status' => 'active'],
            ];

            foreach ($bsisSubjects as $subjData) {
                $subject = Subject::updateOrCreate(
                    ['code' => $subjData['code'], 'user_id' => $subjData['user_id']],
                    $subjData
                );

                // Create records and assessments for each student
                foreach ($bsisStudentModels as $student) {
                    Record::updateOrCreate(
                        [
                            'user_id' => $faculty2->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'section' => 'B',
                            'file_name' => 'classrecord_' . $subject->code . '.xlsx',
                            'notes' => 'Imported from class record',
                            'scores' => [
                                'Q1' => rand(70, 95),
                                'Q2' => rand(70, 95),
                                'Midterm Exam' => rand(75, 90),
                                'Final Exam' => rand(75, 90),
                            ],
                            'numeric_grade' => rand(75, 95),
                            'grade_point' => round(rand(10, 30) / 10, 2),
                            'submission_status' => 'approved',
                        ]
                    );

                    // Create quiz records
                    StudentQuiz::updateOrCreate(
                        [
                            'user_id' => $faculty2->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                            'quiz_type' => 'non_laboratory',
                            'quiz_number' => 1,
                        ],
                        [
                            'score' => rand(70, 95),
                            'total_points' => 100,
                        ]
                    );

                    // Create attendance records
                    for ($i = 1; $i <= 10; $i++) {
                        StudentAttendance::create([
                            'user_id' => $faculty2->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                            'session_number' => $i,
                            'status' => rand(0, 1) ? 'present' : 'absent',
                        ]);
                    }

                    // Create midterm exam records
                    StudentMidtermExam::updateOrCreate(
                        [
                            'user_id' => $faculty2->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'exam_score' => rand(75, 90),
                            'total_points' => 100,
                        ]
                    );

                    // Create final exam records
                    StudentFinalExam::updateOrCreate(
                        [
                            'user_id' => $faculty2->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                        ],
                        [
                            'exam_score' => rand(75, 90),
                            'total_points' => 100,
                        ]
                    );
                }
            }
        }
    }
}
