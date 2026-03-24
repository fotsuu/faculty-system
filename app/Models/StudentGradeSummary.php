<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentGradeSummary extends Model
{
    use HasFactory;

    protected $table = 'student_grade_summaries';

    protected $fillable = [
        'user_id',
        'subject_id',
        'student_id',
        'lab_quiz_average',
        'lab_attendance_total',
        'lab_midterm_score',
        'lab_final_score',
        'lab_total_grade',
        'non_lab_quiz_average',
        'non_lab_attendance_total',
        'non_lab_midterm_score',
        'non_lab_final_score',
        'non_lab_total_grade',
        'midterm_grade',
        'final_grade',
        'final_numeric_grade',
        'final_letter_grade',
        'last_updated_at',
    ];

    protected $casts = [
        'lab_quiz_average' => 'float',
        'lab_attendance_total' => 'float',
        'lab_midterm_score' => 'float',
        'lab_final_score' => 'float',
        'lab_total_grade' => 'float',
        'non_lab_quiz_average' => 'float',
        'non_lab_attendance_total' => 'float',
        'non_lab_midterm_score' => 'float',
        'non_lab_final_score' => 'float',
        'non_lab_total_grade' => 'float',
        'midterm_grade' => 'float',
        'final_grade' => 'float',
        'final_numeric_grade' => 'float',
        'last_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Convert numeric grade to letter grade
    public function getLetterGradeAttribute()
    {
        $grade = $this->final_numeric_grade;
        
        if ($grade === null) {
            return null;
        }

        if ($grade >= 1.0 && $grade <= 1.5) {
            return 'A';
        } elseif ($grade > 1.5 && $grade <= 2.0) {
            return 'B';
        } elseif ($grade > 2.0 && $grade <= 2.5) {
            return 'C';
        } elseif ($grade > 2.5 && $grade <= 3.0) {
            return 'D';
        } else {
            return 'F';
        }
    }
}
