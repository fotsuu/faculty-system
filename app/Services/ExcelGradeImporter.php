<?php

namespace App\Services;

use App\Models\Record;
use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentQuiz;
use App\Models\StudentAttendance;
use App\Models\StudentMidtermExam;
use App\Models\StudentFinalExam;
use App\Models\StudentGradeSummary;
use Illuminate\Support\Facades\Log;

class ExcelGradeImporter
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Import grades from parsed Excel data
     * 
     * @param array $headers Column headers from the Excel sheet
     * @param array $dataRows Data rows to process
     * @param array $meta Metadata (subject, section, instructor)
     * @return array Import statistics
     */
    public function importFromParsedData($headers, $dataRows, $meta = [])
    {
        $stats = [
            'total_rows' => count($dataRows),
            'records_saved' => 0,
            'quizzes_imported' => 0,
            'attendance_imported' => 0,
            'midterm_imported' => 0,
            'final_imported' => 0,
            'grade_summaries_updated' => 0,
            'errors' => [],
        ];

        foreach ($dataRows as $rowIndex => $row) {
            try {
                $this->processDataRow($headers, $row, $meta, $stats, $rowIndex);
            } catch (\Exception $e) {
                $stats['errors'][] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                Log::error("ExcelGradeImporter Row Error: " . $e->getMessage(), ['row' => $rowIndex, 'data' => $row]);
            }
        }

        Log::info("Excel import completed with stats: " . json_encode($stats));
        return $stats;
    }

    /**
     * Process a single data row and categorize/import data
     */
    private function processDataRow($headers, $row, $meta, &$stats, $rowIndex = null)
    {
        if (empty($headers)) {
            return; // Skip if no headers
        }
        
        // Normalize row length to match headers: pad if short, slice if long
        $headerCount = count($headers);
        if (count($row) < $headerCount) {
            $row = array_pad($row, $headerCount, '');
        } elseif (count($row) > $headerCount) {
            $row = array_slice($row, 0, $headerCount);
        }
        
        // Create header => value map (preserve raw values)
        $data = array_combine($headers, $row);
        if ($data === false) {
            Log::error('Failed to combine headers and row', ['headers' => $headers, 'row' => $row]);
            return; // Skip if combine fails
        }

        // Extract student identification
        $studentId = $this->findValue($data, ['id no.', 'id no', 'student id', 'student_id', 'id', 'sid']);
        $studentName = $this->findValue($data, ['name', 'student name', 'student_name', 'NAME']);

        if (!$studentId) {
            return; // Skip rows without student ID
        }

        // Find or create student
        $student = Student::updateOrCreate(
            ['student_id' => trim($studentId)],
            [
                'name' => $studentName ?? 'Unknown',
                'email' => strtolower(str_replace(' ', '.', $studentName ?? 'unknown')) . '@student.edu',
                'program' => $this->findValue($data, ['program', 'course', 'dept']) ?? 'General Studies',
                'year_level' => $this->findValue($data, ['year', 'level', 'year level', 'yr']),
                'user_id' => $this->user->id,
            ]
        );

        // Extract subject info
        $subjectCode = $meta['subject'] ?? $this->findValue($data, ['subject', 'subject code', 'code']);
        $subjectCode = $this->normalizeSubjectCode($subjectCode);

        // Find or create subject
        $subject = Subject::firstOrCreate(
            ['code' => $subjectCode, 'user_id' => $this->user->id],
            [
                'name' => ucwords(str_replace('_', ' ', $subjectCode)),
                'user_id' => $this->user->id,
                'status' => 'active',
            ]
        );

        // Update subject metadata if available
        if (!empty($meta['section']) || !empty($meta['instructor'])) {
            $subject->update([
                'section' => $meta['section'] ?? $subject->section,
                'instructor' => $meta['instructor'] ?? $subject->instructor,
            ]);
        }

        // Extract summary values
        $midTotal = $this->sanitizeNumeric($this->findValue($data, ['mid total', 'mid_total', 'midterm total', 'mid-total', 'Mid Total']));
        $totalAll = $this->sanitizeNumeric($this->findValue($data, ['total_all', 'total all', 'total-all', 'TOTAL_all']));
        $finalTerm = $this->sanitizeNumeric($this->findValue($data, ['final_term', 'final term', 'final-term', 'Final_Term']));
        
        // Priority to "Final Grade" or "GPA" columns as the authoritative grade
        $finalGradeRaw = $this->findValue($data, ['final grade', 'final_grade', 'final-grade', 'Final Grade', 'GPA', 'GWA']);
        $numericGrade = is_numeric($finalGradeRaw) ? (float)$finalGradeRaw : null;
        $rawGradeValue = $finalGradeRaw ?? null;

        $labTotal = $this->sanitizeNumeric($this->findValue($data, ['lab equivalent', 'laboratory total', 'lab total']));
        $nonLabTotal = $this->sanitizeNumeric($this->findValue($data, ['nonlab equi', 'non-laboratory total', 'non-lab total']));
        
        // If final grade is missing but totalAll is present, map it using the PH scale table
        if (is_null($numericGrade) && !is_null($totalAll)) {
            $numericGrade = $this->mapTotalToPHGrade($totalAll);
            $rawGradeValue = $numericGrade;
        }

        // Calculate grade point (PH scale 1.0 - 5.0 where 1.0 is best)
        $gradePoint = $numericGrade;
        
        // If the value is on a 0-100 scale, convert it to 1.0-5.0 scale
        if (!is_null($gradePoint) && $gradePoint > 5.0) {
            $gradePoint = $this->mapTotalToPHGrade($gradePoint);
        }

        // Save full row into records so original Excel data is preserved
        Record::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'subject_id' => $subject->id,
                'student_id' => $student->id,
            ],
            [
                'file_name' => $meta['filename'] ?? null,
                'notes' => 'Imported from Excel',
                'scores' => $data,
                'row_index' => $rowIndex,
                'raw_grade' => (string)$rawGradeValue,
                'numeric_grade' => $numericGrade,
                'grade_point' => $gradePoint,
                'midterm_total' => $midTotal,
                'final_term_total' => $finalTerm,
                'total_all' => $totalAll,
                'laboratory_total' => $labTotal,
                'non_laboratory_total' => $nonLabTotal,
                'submission_status' => 'pending',
            ]
        );
        $stats['records_saved']++;

        // Process each score/assessment column
        foreach ($data as $column => $value) {
            if (empty(trim($value)) || !is_numeric($value)) {
                continue; // Skip empty or non-numeric values
            }

            $assessment = $this->categorizeAssessment($column);
            if (!$assessment) {
                if (is_numeric($value)) {
                    // Log::info("Numeric Skip: " . $column . " = " . $value);
                }
                continue; // Skip if column cannot be categorized
            }


            $score = (float) $value;

            switch ($assessment['type']) {
                case 'quiz':
                    $this->importQuiz($student, $subject, $score, $assessment, $stats);
                    break;
                case 'attendance':
                    $this->importAttendance($student, $subject, $value, $assessment, $stats);
                    break;
                case 'midterm':
                    $this->importMidterm($student, $subject, $score, $assessment, $stats);
                    break;
                case 'final':
                    $this->importFinal($student, $subject, $score, $assessment, $stats);
                    break;
            }
        }

        // Update grade summary for this subject/student
        $this->updateGradeSummary($student, $subject);
        $stats['grade_summaries_updated']++;
    }

    /**
     * Import a quiz score
     */
    private function importQuiz($student, $subject, $score, $assessment, &$stats)
    {
        $quizType = $assessment['subtype'] ?? 'non_laboratory';
        $quizNumber = $assessment['number'] ?? 1;

        StudentQuiz::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'subject_id' => $subject->id,
                'student_id' => $student->id,
                'quiz_type' => $quizType,
                'quiz_number' => $quizNumber,
            ],
            [
                'score' => $score,
                'total_points' => 100, // Default to 100; adjust if needed
                'quiz_date' => now(),
                'notes' => 'Imported from Excel',
            ]
        );

        $stats['quizzes_imported']++;
    }

    /**
     * Import attendance record
     */
    private function importAttendance($student, $subject, $value, $assessment, &$stats)
    {
        // Map text/numeric values to attendance status
        $status = 'absent';
        if (strtolower($value) === 'present' || $value == 1) {
            $status = 'present';
        } elseif (strtolower($value) === 'late' || $value == 0.5) {
            $status = 'late';
        } elseif (strtolower($value) === 'excused' || strtolower($value) === 'exc') {
            $status = 'excused';
        }

        StudentAttendance::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'subject_id' => $subject->id,
                'student_id' => $student->id,
                'session_number' => $assessment['number'] ?? 1,
            ],
            [
                'status' => $status,
                'session_date' => now(),
                'notes' => 'Imported from Excel',
            ]
        );

        $stats['attendance_imported']++;
    }

    /**
     * Import midterm exam score
     */
    private function importMidterm($student, $subject, $score, $assessment, &$stats)
    {
        StudentMidtermExam::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'subject_id' => $subject->id,
                'student_id' => $student->id,
            ],
            [
                'exam_score' => $score,
                'total_points' => 100, // Default; adjust if needed
                'exam_portion' => $assessment['portion'] ?? 'regular',
                'exam_date' => now(),
                'notes' => 'Imported from Excel',
                'submission_status' => 'submitted',
            ]
        );

        $stats['midterm_imported']++;
    }

    /**
     * Import final exam score
     */
    private function importFinal($student, $subject, $score, $assessment, &$stats)
    {
        StudentFinalExam::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'subject_id' => $subject->id,
                'student_id' => $student->id,
            ],
            [
                'exam_score' => $score,
                'total_points' => 100, // Default; adjust if needed
                'exam_portion' => $assessment['portion'] ?? 'regular',
                'exam_date' => now(),
                'notes' => 'Imported from Excel',
                'submission_status' => 'submitted',
            ]
        );

        $stats['final_imported']++;
    }

    /**
     * Update student grade summary based on current assessment records
     */
    private function updateGradeSummary($student, $subject)
    {
        $quizzes = StudentQuiz::where('subject_id', $subject->id)
            ->where('student_id', $student->id)
            ->get();

        $labQuizzes = $quizzes->where('quiz_type', 'laboratory');
        $nonLabQuizzes = $quizzes->where('quiz_type', 'non_laboratory');

        $labQuizAvg = $labQuizzes->isNotEmpty()
            ? $labQuizzes->avg('score')
            : 0;

        $nonLabQuizAvg = $nonLabQuizzes->isNotEmpty()
            ? $nonLabQuizzes->avg('score')
            : 0;

        $attendance = StudentAttendance::where('subject_id', $subject->id)
            ->where('student_id', $student->id)
            ->get();

        $presentCount = $attendance->where('status', 'present')->count();
        $lateCount = $attendance->where('status', 'late')->count();
        $labAttendanceTotal = $presentCount + ($lateCount * 0.5);

        $midterm = StudentMidtermExam::where('subject_id', $subject->id)
            ->where('student_id', $student->id)
            ->first();

        $final = StudentFinalExam::where('subject_id', $subject->id)
            ->where('student_id', $student->id)
            ->first();

        // Calculate composite grades (example formula; adjust per your grading policy)
        $labTotal = ($labQuizAvg * 0.4) + ($labAttendanceTotal * 0.2) + (($midterm->exam_score ?? 0) * 0.2) + (($final->exam_score ?? 0) * 0.2);
        $nonLabTotal = ($nonLabQuizAvg * 0.4) + (($midterm->exam_score ?? 0) * 0.3) + (($final->exam_score ?? 0) * 0.3);
        $finalNumeric = ($labTotal + $nonLabTotal) / 2;

        StudentGradeSummary::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'subject_id' => $subject->id,
                'student_id' => $student->id,
            ],
            [
                'lab_quiz_average' => $labQuizAvg,
                'lab_attendance_total' => $labAttendanceTotal,
                'lab_midterm_score' => $midterm?->exam_score ?? 0,
                'lab_final_score' => $final?->exam_score ?? 0,
                'lab_total_grade' => $labTotal,
                'non_lab_quiz_average' => $nonLabQuizAvg,
                'non_lab_attendance_total' => $presentCount, // Count only present days
                'non_lab_midterm_score' => $midterm?->exam_score ?? 0,
                'non_lab_final_score' => $final?->exam_score ?? 0,
                'non_lab_total_grade' => $nonLabTotal,
                'final_numeric_grade' => $finalNumeric,
                'last_updated_at' => now(),
            ]
        );
    }

    /**
     * Find a value from data array by multiple possible column name keys (partial match)
     */
    private function findValue($data, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            $key = strtolower(trim($key));
            foreach ($data as $dataKey => $value) {
                $dataKeyLower = strtolower(trim($dataKey));
                // Exact match or partial match for descriptive headers
                if ($dataKeyLower === $key || (strlen($key) > 3 && stripos($dataKeyLower, $key) !== false)) {
                    if ($value !== null && $value !== '') {
                        return $value;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Categorize a column header as quiz, attendance, midterm, or final
     */
    private function categorizeAssessment($column)
    {
        $lower = strtolower(trim($column));

        // Skip non-assessment columns or totals
        if (preg_match('/id no|name|course|year|total|grade|equivalent|equi/i', $lower)) {
            return null;
        }

        // Final Exam priority
        if (stripos($lower, 'final exam') !== false || (stripos($lower, 'final') !== false && stripos($lower, 'exam') !== false)) {
            return ['type' => 'final', 'portion' => 'final_exam'];
        }

        // Midterm Exam priority
        if (stripos($lower, 'midterm exam') !== false || stripos($lower, 'mid-exam') !== false || (stripos($lower, 'mid') !== false && stripos($lower, 'exam') !== false)) {
            return ['type' => 'midterm', 'portion' => 'mid_exam'];
        }

        // Attendance patterns (including dates like 09/11/2024 or L1, L2)
        if (stripos($lower, 'attendance') !== false || stripos($lower, 'present') !== false || 
            preg_match('/\d{1,2}\/\d{1,2}\/\d{2,4}/', $lower) || // 09/11/2024
            preg_match('/^l\d+$/', $lower)) { // L1, L2
            $number = $this->extractNumber($lower) ?? 1;
            return ['type' => 'attendance', 'number' => $number];
        }

        // Quiz patterns
        if (stripos($lower, 'quiz') !== false || stripos($lower, ' q') !== false || preg_match('/q\d+/i', $lower)) {
            $subtype = (stripos($lower, 'non-lab') !== false || stripos($lower, 'non-laboratory') !== false) ? 'non_laboratory' : (stripos($lower, 'lab') !== false ? 'laboratory' : 'non_laboratory');
            $number = $this->extractNumber($lower) ?? 1;
            return ['type' => 'quiz', 'subtype' => $subtype, 'number' => $number];
        }

        // Laboratory Activities (e.g. "Laboratory Activities (09/11/2024)")
        if (stripos($lower, 'laboratory activities') !== false || (stripos($lower, 'lab activities') !== false && stripos($lower, 'total') === false)) {
            $number = $this->extractNumber($lower) ?? 1;
            return ['type' => 'quiz', 'subtype' => 'laboratory', 'number' => $number];
        }

        return null;
    }

    /**
     * Extract a number from a string (e.g., "Quiz 1" => 1)
     */
    private function extractNumber($str)
    {
        if (preg_match('/\d+/', $str, $matches)) {
            return (int) $matches[0];
        }
        return null;
    }

    /**
     * Sanitize a value to be numeric or null
     */
    private function sanitizeNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        // Handle "DR", "U", etc. by treating them as 0 for calculations if stored in decimal columns
        // Or null if we want to exclude them. Usually 0 is safer for MySQL decimal columns if not nullable
        return 0;
    }

    /**
     * Map a 0-100 total score to PH scale 1.0-5.0
     */
    private function mapTotalToPHGrade($total)
    {
        if ($total >= 93) return 1.00;
        if ($total >= 86) return 1.25;
        if ($total >= 81) return 1.50;
        if ($total >= 76) return 1.75;
        if ($total >= 71) return 2.00;
        if ($total >= 66) return 2.25;
        if ($total >= 61) return 2.50;
        if ($total >= 56) return 2.75;
        if ($total >= 51) return 3.00;
        return 5.00;
    }

    /**
     * Normalize subject code format
     */
    private function normalizeSubjectCode($code)
    {
        if (empty($code)) {
            return 'GEN001';
        }

        // Just trim and uppercase
        $code = trim($code);
        // Take first 20 characters (instead of 10)
        $code = substr($code, 0, 20);
        // Return uppercase
        return strtoupper($code);
    }
}
