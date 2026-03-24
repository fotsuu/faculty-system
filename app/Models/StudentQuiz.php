<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentQuiz extends Model
{
    use HasFactory;

    protected $table = 'student_quizzes';

    protected $fillable = [
        'user_id',
        'subject_id',
        'student_id',
        'quiz_type',
        'quiz_number',
        'score',
        'total_points',
        'quiz_date',
        'notes',
    ];

    protected $casts = [
        'score' => 'float',
        'total_points' => 'float',
        'quiz_date' => 'date',
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
        return round(($this->score / $this->total_points) * 100, 2);
    }
}
