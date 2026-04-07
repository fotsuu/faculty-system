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

        // If no student ID found, try to find or create based on name
        if (!$studentId && $studentName) {
            // Try to find existing student by name
            $existingStudent = Student::where('name', trim($studentName))->first();
            if ($existingStudent) {
                $studentId = $existingStudent->student_id;
            } else {
                // Generate a temporary ID from name if needed
                $studentId = 'TEMP_' . strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $studentName), 0, 6)) . '_' . rand(1000, 9999);
            }
        }

        if (!$studentId || !$studentName) {
            return; // Skip rows without both student ID and name
        }

        // Determine year level from row data if available
        $yearLevel = $this->findValue($data, ['year', 'level', 'year level', 'yr', 'grade level']);
        if (is_string($yearLevel)) {
            $yearLevel = trim($yearLevel);
        }

        // Determine program/course from file data or fallback from currently logged in faculty department
        $programFromData = $this->findValue($data, ['program', 'course', 'dept', 'department']);
        if (is_string($programFromData)) {
            $programFromData = trim($programFromData);
        }

        // Parse section or string like BSIT-1A to course/year if program is missing or generic
        if (empty($programFromData) || strcasecmp(trim($programFromData), 'General Studies') === 0) {
            $sectionSource = $meta['section'] ?? '';
            if (is_string($sectionSource) && preg_match('/^([A-Za-z]+)[\- ]?(\d+)/', trim($sectionSource), $m)) {
                if (empty($programFromData) || strcasecmp(trim($programFromData), 'General Studies') === 0) {
                    $programFromData = strtoupper($m[1]);
                }
                if (empty($yearLevel)) {
                    $yearLevel = $m[2];
                }
            }
        }

        // Ensure yearLevel fallback from row data if still missing
        if (empty($yearLevel)) {
            $yearLevel = $this->findValue($data, ['year', 'level', 'year level', 'yr']);
        }

        // Find or create student
        $student = Student::updateOrCreate(
            ['student_id' => trim($studentId)],
            [
                'name' => $studentName ?? 'Unknown',
                'email' => strtolower(str_replace(' ', '.', $studentName ?? 'unknown')) . '@student.edu',
                'program' => $programFromData ?: ($this->user->department ?? 'N/A'),
                'year_level' => $yearLevel ?: '1',
                'user_id' => $this->user->id,
            ]
        );

        // Ensure existing student record updates year level if changed
        if ($yearLevel && $student->year_level !== $yearLevel) {
            $student->update(['year_level' => $yearLevel]);
        }

        // Extract subject info (prefer explicit subject code and name from parsed metadata)
        $subjectCode = $meta['subject_code'] ?? $meta['subject'] ?? $this->findValue($data, ['subject', 'subject code', 'code']);
        $subjectCode = $this->normalizeSubjectCode($subjectCode);

        $subjectNameFromMeta = $meta['subject_name'] ?? null;
        if (empty($subjectNameFromMeta) && isset($meta['subject']) && $meta['subject'] !== $subjectCode) {
            // If subject meta is longer text and not a code pattern, treat it as name
            $subjectNameFromMeta = trim($meta['subject']);
        }

        // Find or create subject
        $subject = Subject::firstOrCreate(
            ['code' => $subjectCode, 'user_id' => $this->user->id],
            [
                'name' => $subjectNameFromMeta ?: ucwords(str_replace('_', ' ', $subjectCode)),
                'user_id' => $this->user->id,
                'status' => 'active',
            ]
        );

        if ($subjectNameFromMeta && $subject->name !== $subjectNameFromMeta) {
            $subject->update(['name' => $subjectNameFromMeta]);
        }

        // Update subject metadata if available
        if (!empty($meta['section']) || !empty($meta['instructor'])) {
            $updateData = [];
            
            // Handle sections: append new section if not already in the list
            if (!empty($meta['section'])) {
                $newSection = $meta['section']; // Could be string or array from upload
                $existingSections = $subject->section ?? [];
                
                // Ensure existingSections is an array
                if (!is_array($existingSections)) {
                    $existingSections = !empty($existingSections) ? [$existingSections] : [];
                }
                
                // If newSection is a string and not in the list, add it
                if (is_string($newSection) && !in_array($newSection, $existingSections)) {
                    $existingSections[] = $newSection;
                } elseif (is_array($newSection)) {
                    // If newSection is an array, merge with existing
                    $existingSections = array_unique(array_merge($existingSections, $newSection));
                }
                
                $updateData['section'] = empty($existingSections) ? null : $existingSections;
            }
            
            if (!empty($meta['instructor'])) {
                $updateData['instructor'] = $meta['instructor'];
            }
            
            if (!empty($updateData)) {
                $subject->update($updateData);
            }
        }

        // Extract summary values
        $midTotal = $this->sanitizeNumeric($this->findValue($data, ['mid total', 'mid_total', 'midterm total', 'mid-total', 'Mid Total']));
        $totalAll = $this->sanitizeNumeric($this->findValue($data, ['total_all', 'total all', 'total-all', 'TOTAL_all']));
        $finalTerm = $this->sanitizeNumeric($this->findValue($data, ['final_term', 'final term', 'final-term', 'Final_Term']));
        
        // Priority to "Final Grade" or "GPA" columns as the authoritative numeric grade
        $finalGradeRaw = $this->findValue($data, ['final grade', 'final_grade', 'final-grade', 'Final Grade', 'GPA', 'GWA']);
        $numericGrade = is_numeric($finalGradeRaw) ? round((float)$finalGradeRaw, 2) : null;
        $rawGradeValue = $finalGradeRaw ?? null;

        // For many class-record formats, EQV is the true PH-grade-point equivalent (e.g., 1.25, 2.50).
        // Use it as grade_point when present, while keeping numericGrade/rawGrade from grade columns.
        $eqvGradeRaw = $this->findValue($data, ['eqv', 'equivalent', 'grade equivalent', 'eqv.']);
        $eqvGradePoint = is_numeric($eqvGradeRaw) ? round((float)$eqvGradeRaw, 2) : null;

        // Maintain exactly two decimals in raw grade for common final-grade format
        if (is_numeric($rawGradeValue) && strpos((string)$rawGradeValue, '.') !== false) {
            $parts = explode('.', (string)$rawGradeValue);
            if (strlen($parts[1]) > 2) {
                $rawGradeValue = number_format((float)$rawGradeValue, 2, '.', '');
            }
        }

        $labTotal = $this->sanitizeNumeric($this->findValue($data, ['lab equivalent', 'laboratory total', 'lab total']));
        $nonLabTotal = $this->sanitizeNumeric($this->findValue($data, ['nonlab equi', 'non-laboratory total', 'non-lab total']));
        
        // If final grade is missing but totalAll is present, map it using the PH scale table
        if (is_null($numericGrade) && !is_null($totalAll)) {
            $numericGrade = $this->mapTotalToPHGrade($totalAll);
            $rawGradeValue = $numericGrade;
        }

        // Calculate grade point (PH scale 1.0 - 5.0 where 1.0 is best)
        $gradePoint = !is_null($eqvGradePoint) ? $eqvGradePoint : $numericGrade;
        
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
                'section' => !empty($meta['section']) ? (string) $meta['section'] : null,
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
        $columnIndex = 0;
        foreach ($data as $column => $value) {
            $assessment = $this->categorizeAssessment($column, $columnIndex);
            if (!$assessment) {
                $columnIndex++;
                continue;
            }

            // Attendance can be symbolic (P/A/L/etc), not strictly numeric.
            if ($assessment['type'] !== 'attendance') {
                if (trim((string) $value) === '' || !is_numeric($value)) {
                    $columnIndex++;
                    continue;
                }
            } else {
                if (trim((string) $value) === '') {
                    $columnIndex++;
                    continue;
                }
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
            $columnIndex++;
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
        $normalized = trim(strtolower((string)$value));

        if ($normalized === "") {
            $status = "absent";
        } elseif (is_numeric($value)) {
            $numeric = (float)$value;
            if ($numeric >= 1) {
                $status = "present";
            } elseif ($numeric == 0.5) {
                $status = "late";
            } else {
                $status = "absent";
            }
        } elseif (in_array($normalized, ["present", "p", "yes", "y", "x", "/"], true)) {
            $status = "present";
        } elseif (in_array($normalized, ["late", "l", "t"], true)) {
            $status = "late";
        } elseif (in_array($normalized, ["excused", "exc", "e"], true)) {
            $status = "excused";
        } elseif (in_array($normalized, ["absent", "a", "no", "n"], true)) {
            $status = "absent";
        } else {
            // Safer default: unknown symbols should not inflate attendance.
            $status = "absent";
        }

        StudentAttendance::updateOrCreate(
            [
                "user_id" => $this->user->id,
                "subject_id" => $subject->id,
                "student_id" => $student->id,
                "attendance_type" => $assessment["subtype"] ?? "non_laboratory",
                "session_number" => $assessment["number"] ?? 1,
            ],
            [
                "status" => $status,
                "session_date" => $assessment["date"] ?? null,
                "notes" => "Imported from Excel",
            ]
        );

        $stats["attendance_imported"]++;
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
    private function categorizeAssessment($column, $columnIndex = null)
    {
        $lower = strtolower(trim($column));

        // Skip purely metadata columns
        if (preg_match('/\b(id no|student id|student|name|course|program|year|section|instructor|remark|remarks|status)\b/i', $lower)) {
            return null;
        }

        // Final Exam priority
        if (stripos($lower, 'final exam') !== false || (stripos($lower, 'finals') !== false || (stripos($lower, 'final') !== false && stripos($lower, 'exam') !== false))) {
            return ['type' => 'final', 'portion' => 'final_exam'];
        }

        // Midterm Exam priority
        if (stripos($lower, 'midterm exam') !== false || stripos($lower, 'mid-exam') !== false || stripos($lower, 'midterm') !== false || stripos($lower, 'prelim') !== false || (stripos($lower, 'mid') !== false && stripos($lower, 'exam') !== false)) {
            return ['type' => 'midterm', 'portion' => 'mid_exam'];
        }

        // Attendance patterns (including dates like 09/11/2024 or A1, A2, Day 1)
        if (stripos($lower, 'attendance') !== false || stripos($lower, 'present') !== false || 
            preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}/', $lower) || // 09/11/2024
            preg_match('/^\d{1,2}[\/-]\d{1,2}$/', $lower) || // 08-22 or 08/22
            preg_match('/^\d+$/', $lower) || // session index only: 1,2,3...
            preg_match('/^(a|att|attendance|day|week|session|day)\s*\d+$/i', $lower)) { // A1, Day 1, Week 1, Session 1
            $number = $this->extractNumber($lower);
            if (preg_match('/^\d{1,2}[\/-]\d{1,2}(?:[\/-]\d{2,4})?$/', $lower)) {
                // Date-like attendance headers should not collide on month/day integers.
                $number = is_numeric($columnIndex) ? ((int)$columnIndex + 1) : ($number ?? 1);
            } else {
                $number = $number ?? (is_numeric($columnIndex) ? ((int)$columnIndex + 1) : 1);
            }
            return [
                'type' => 'attendance',
                'number' => $number,
                'date' => $this->parseAttendanceSessionDate($column),
                'subtype' => 'non_laboratory',
            ];
        }

        // Quiz patterns (includes L1, L2 for Laboratory Quizzes if not matched as attendance)
        if (stripos($lower, 'quiz') !== false || stripos($lower, ' q') !== false || 
            preg_match('/q\s*\d+/i', $lower) || 
            preg_match('/^(l|lab|activity|quiz)\s*\d+$/i', $lower)) { // L1, Lab 1, Activity 1 treated as Lab Quiz
            $subtype = (stripos($lower, 'non-lab') !== false || stripos($lower, 'non-laboratory') !== false) ? 'non_laboratory' : (stripos($lower, 'lab') !== false || preg_match('/^(l|lab)\s*\d+$/i', $lower) ? 'laboratory' : 'non_laboratory');
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

    private function parseAttendanceSessionDate($column)
    {
        $raw = trim((string) $column);
        if ($raw === '') {
            return null;
        }

        // Common formats from attendance sheets: mm-dd, mm/dd, mm-dd-yyyy, mm/dd/yyyy
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})(?:[\/-](\d{2,4}))?$/', $raw, $m)) {
            $month = (int) $m[1];
            $day = (int) $m[2];
            $year = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) now()->year;
            if ($year < 100) {
                $year += 2000;
            }

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
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
