<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Quiz Scores Table - Para sa lahat ng quizzes (laboratory at non-laboratory)
        Schema::create('student_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('quiz_type', ['laboratory', 'non_laboratory'])->comment('Type of quiz');
            $table->integer('quiz_number')->comment('Quiz number/sequence');
            $table->decimal('score', 5, 2)->nullable()->comment('Quiz score');
            $table->decimal('total_points', 5, 2)->default(10)->comment('Total possible points');
            $table->date('quiz_date')->nullable()->comment('Date ng quiz');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['subject_id', 'student_id', 'quiz_type', 'quiz_number'], 'uk_student_quiz');
            $table->index(['subject_id', 'student_id', 'quiz_type']);
        });

        // 2. Attendance Records Table - Para sa attendance
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('attendance_type', ['laboratory', 'non_laboratory'])->comment('Type of class');
            $table->integer('session_number')->comment('Session/class number');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('absent');
            $table->date('session_date')->nullable()->comment('Date ng session');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['subject_id', 'student_id', 'attendance_type']);
        });

        // 3. Midterm Exam Scores Table
        Schema::create('student_midterm_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('exam_score', 5, 2)->nullable()->comment('Midterm exam score');
            $table->decimal('total_points', 5, 2)->default(100)->comment('Total possible points');
            $table->enum('exam_portion', ['quiz', 'mid_exam'])->default('mid_exam')->comment('Quiz or Exam portion ng midterm');
            $table->date('exam_date')->nullable()->comment('Date ng midterm exam');
            $table->text('notes')->nullable();
            $table->enum('submission_status', ['pending', 'submitted', 'graded'])->default('pending');
            $table->timestamps();
            
            $table->unique(['subject_id', 'student_id', 'exam_portion'], 'uk_midterm_exam');
            $table->index(['subject_id', 'student_id']);
        });

        // 4. Final Exam Scores Table
        Schema::create('student_final_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('exam_score', 5, 2)->nullable()->comment('Final exam score');
            $table->decimal('total_points', 5, 2)->default(100)->comment('Total possible points');
            $table->enum('exam_portion', ['quiz', 'final_exam'])->default('final_exam')->comment('Quiz or Exam portion ng final term');
            $table->date('exam_date')->nullable()->comment('Date ng final exam');
            $table->text('notes')->nullable();
            $table->enum('submission_status', ['pending', 'submitted', 'graded'])->default('pending');
            $table->timestamps();
            
            $table->unique(['subject_id', 'student_id', 'exam_portion'], 'uk_final_exam');
            $table->index(['subject_id', 'student_id']);
        });

        // 5. Computed Grades Table - Para sa summary ng lahat ng grades
        Schema::create('student_grade_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            
            // Laboratory Grade Components
            $table->decimal('lab_quiz_average', 5, 2)->nullable()->comment('Average ng laboratory quizzes');
            $table->decimal('lab_attendance_total', 5, 2)->nullable()->comment('Total laboratory attendance points');
            $table->decimal('lab_midterm_score', 5, 2)->nullable()->comment('Midterm laboratory score');
            $table->decimal('lab_final_score', 5, 2)->nullable()->comment('Final laboratory score');
            $table->decimal('lab_total_grade', 5, 2)->nullable()->comment('Overall laboratory grade');
            
            // Non-Laboratory Grade Components
            $table->decimal('non_lab_quiz_average', 5, 2)->nullable()->comment('Average ng non-laboratory quizzes');
            $table->decimal('non_lab_attendance_total', 5, 2)->nullable()->comment('Total non-laboratory attendance points');
            $table->decimal('non_lab_midterm_score', 5, 2)->nullable()->comment('Midterm non-laboratory score');
            $table->decimal('non_lab_final_score', 5, 2)->nullable()->comment('Final non-laboratory score');
            $table->decimal('non_lab_total_grade', 5, 2)->nullable()->comment('Overall non-laboratory grade');
            
            // Overall Grades
            $table->decimal('midterm_grade', 5, 2)->nullable()->comment('Overall midterm grade');
            $table->decimal('final_grade', 5, 2)->nullable()->comment('Overall final grade');
            $table->decimal('final_numeric_grade', 5, 2)->nullable()->comment('Final numeric grade');
            $table->string('final_letter_grade', 2)->nullable()->comment('Final letter grade (A, B, C, D, F)');
            
            $table->timestamp('last_updated_at')->nullable()->comment('Last time grades were computed');
            $table->timestamps();
            
            $table->unique(['subject_id', 'student_id'], 'uk_grade_summary');
            $table->index(['user_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_grade_summaries');
        Schema::dropIfExists('student_final_exams');
        Schema::dropIfExists('student_midterm_exams');
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('student_quizzes');
    }
};
