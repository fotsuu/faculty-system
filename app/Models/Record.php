<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Record extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'section',
        'student_id',
        'file_path',
        'file_name',
        'notes',
        'raw_grade',
        'numeric_grade',
        'grade_point',
        'scores',
        'row_index',
        'midterm_total',
        'final_term_total',
        'total_all',
        'laboratory_total',
        'non_laboratory_total',
        'submission_status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'numeric_grade' => 'float',
        'grade_point' => 'float',
        'scores' => 'array',
        'midterm_total' => 'float',
        'final_term_total' => 'float',
        'total_all' => 'float',
        'laboratory_total' => 'float',
        'non_laboratory_total' => 'float',
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

    // Extract grade from database fields or notes (numeric or letter)
    public function getGradeAttribute()
    {
        // Prefer explicit numeric grade if present
        if (array_key_exists('numeric_grade', $this->attributes) && $this->attributes['numeric_grade'] !== null && $this->attributes['numeric_grade'] !== '') {
            return round((float)$this->attributes['numeric_grade'], 2);
        }

        // Prefer grade point if present (use raw DB value to avoid accessor recursion)
        if (array_key_exists('grade_point', $this->attributes) && $this->attributes['grade_point'] !== null && $this->attributes['grade_point'] !== '') {
            return round((float)$this->attributes['grade_point'], 2);
        }

        // Fall back to raw grade field
        if (!empty($this->raw_grade)) {
            return is_numeric($this->raw_grade) ? round((float)$this->raw_grade, 2) : $this->raw_grade;
        }

        $notes = $this->notes ?? '';
        
        // Try to extract numeric grade (e.g., "Grade: 1.5")
        if (preg_match('/grade:?\s*([\d.]+)/i', $notes, $m)) {
            return (float)$m[1];
        }
        
        // Try to extract letter grade (e.g., "A", "B")
        if (preg_match('/grade:?\s*([A-DF])/i', $notes, $m)) {
            return strtoupper($m[1]);
        }
        
        // Also accept direct numeric value in notes
        if (is_numeric(trim($notes)) && strlen(trim($notes)) < 10) {
            return (float)trim($notes);
        }
        
        // Also accept direct single-letter notes
        if (preg_match('/^\s*([A-DF])\s*$/i', $notes, $m2)) {
            return strtoupper($m2[1]);
        }

        return null;
    }

    // Compute grade points for the grade (PH Scale 1.0 - 5.0)
    public function getGradePointAttribute()
    {
        // If grade_point is already stored in the database, return it
        if (isset($this->attributes['grade_point']) && $this->attributes['grade_point'] !== null) {
            return (float)$this->attributes['grade_point'];
        }
        
        $grade = $this->grade;
        if (is_null($grade)) return null;
        
        if (is_numeric($grade)) {
            $numGrade = (float)$grade;
            // Return as is if within PH scale range, or map if 0-100
            if ($numGrade >= 1.0 && $numGrade <= 5.0) {
                return round($numGrade, 2);
            }
            if ($numGrade > 5.0 && $numGrade <= 100) {
                if ($numGrade >= 93) return 1.00;
                if ($numGrade >= 86) return 1.25;
                if ($numGrade >= 81) return 1.50;
                if ($numGrade >= 76) return 1.75;
                if ($numGrade >= 71) return 2.00;
                if ($numGrade >= 66) return 2.25;
                if ($numGrade >= 61) return 2.50;
                if ($numGrade >= 56) return 2.75;
                if ($numGrade >= 51) return 3.00;
                return 5.00;
            }
            return round($numGrade, 2);
        } else {
            // Letter grade conversion if applicable
            $map = ['A' => 1.0, 'B' => 2.0, 'C' => 3.0, 'D' => 4.0, 'F' => 5.0];
            return isset($map[$grade]) ? $map[$grade] : null;
        }
    }
}
