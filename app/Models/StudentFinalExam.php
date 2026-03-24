<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentFinalExam extends Model
{
    use HasFactory;

    protected $table = 'student_final_exams';

    protected $fillable = [
        'user_id',
        'subject_id',
        'student_id',
        'exam_score',
        'total_points',
        'exam_portion',
        'exam_date',
        'notes',
        'submission_status',
    ];

    protected $casts = [
        'exam_score' => 'float',
        'total_points' => 'float',
        'exam_date' => 'date',
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

    // Get percentage score
    public function getPercentageAttribute()
    {
        if ($this->total_points == 0) {
            return 0;
        }
        return round(($this->exam_score / $this->total_points) * 100, 2);
    }
}
