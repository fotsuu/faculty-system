<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = 'student_attendance';

    protected $fillable = [
        'user_id',
        'subject_id',
        'student_id',
        'attendance_type',
        'session_number',
        'status',
        'session_date',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
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

    // Get attendance points (present = 1, late = 0.5, absent/excused = 0)
    public function getPointsAttribute()
    {
        return match($this->status) {
            'present' => 1,
            'late' => 0.5,
            'absent', 'excused' => 0,
            default => 0,
        };
    }
}
