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
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    private $userIds;

    /**
     * @param int|array|null $userIds User ID, array of User IDs, or null for all users
     */
    public function __construct($userIds = null)
    {
        if ($userIds === null) {
            $this->userIds = null;
        } else {
            $this->userIds = is_array($userIds) ? $userIds : [$userIds];
        }
    }

    /**
     * Apply user_id filter to a query if userIds is set
     */
    private function applyUserFilter($query)
    {
        if ($this->userIds !== null) {
            return $query->whereIn('user_id', $this->userIds);
        }
        return $query;
    }

    /**
     * Generate comprehensive analytics data for a user
     */
    public function generateAnalytics()
    {
        return [
            'passFailRates' => $this->getPassFailRates(),
            'gradeDistribution' => $this->getGradeDistribution(),
            'attendanceTrends' => $this->getAttendanceTrends(),
            'quizAnalytics' => $this->getQuizAnalytics(),
            'examAnalytics' => $this->getExamAnalytics(),
            'studentPerformance' => $this->getStudentPerformance(),
            'subjectSummary' => $this->getSubjectSummary(),
        ];
    }

    /**
     * Get pass/fail rates per subject
     */
    public function getPassFailRates()
    {
        $subjects = Subject::query();
        $subjects = $this->applyUserFilter($subjects)->get();
        
        return $subjects->map(function ($subject) {
            $records = Record::where('subject_id', $subject->id)->get();
            // In the 1.0-5.0 scale, a grade of 3.0 or better (lower) is passing. 5.0 is fail.
            $passCount = $records->filter(fn($r) => !is_null($r->grade_point) && $r->grade_point > 0 && $r->grade_point <= 3.0)->count();
            
            // If grade_point is null, check numeric_grade as fallback (e.g. percentages >= 75)
            $otherPassCount = $records->filter(function($r) {
                if (!is_null($r->grade_point)) return false;
                if (is_null($r->numeric_grade)) return false;
                $val = (float)$r->numeric_grade;
                // If it looks like a 0-100 scale
                if ($val >= 75 && $val <= 100) return true;
                // If it looks like a 1-5 scale
                if ($val >= 1.0 && $val <= 3.0) return true;
                return false;
            })->count();
            
            $totalPass = $passCount + $otherPassCount;
            $failCount = $records->count() - $totalPass;
            
            return [
                'code' => $subject->code,
                'name' => $subject->name,
                'pass' => $totalPass,
                'fail' => $failCount,
                'total' => $records->count(),
                'pass_rate' => $records->count() > 0 ? round(($totalPass / $records->count()) * 100, 1) : 0,
            ];
        })->toArray();
    }

    /**
     * Get grade distribution (A, B, C, D, F)
     */
    public function getGradeDistribution()
    {
        $records = Record::query();
        $records = $this->applyUserFilter($records)->get();
        
        $distribution = [
            'A' => 0, // 1.0 - 1.25
            'B' => 0, // 1.5 - 1.75
            'C' => 0, // 2.0 - 2.5
            'D' => 0, // 2.75 - 3.0
            'F' => 0, // 5.0
        ];
        
        foreach ($records as $record) {
            $gp = $record->grade_point;
            if ($gp !== null && $gp > 0) {
                if ($gp <= 1.25) {
                    $distribution['A']++;
                } elseif ($gp <= 1.75) {
                    $distribution['B']++;
                } elseif ($gp <= 2.5) {
                    $distribution['C']++;
                } elseif ($gp <= 3.0) {
                    $distribution['D']++;
                } else {
                    $distribution['F']++;
                }
            }
        }
        
        return $distribution;
    }

    /**
     * Get detailed grade point distribution with counts and percentages
     */
    public function getDetailedGradeDistribution()
    {
        $records = Record::query();
        $records = $this->applyUserFilter($records)->get();
        
        $gradePoints = [];
        $totalRecords = $records->count();
        
        if ($totalRecords === 0) {
            return [
                'total_students' => 0,
                'grades' => [],
            ];
        }
        
        foreach ($records as $record) {
            $gp = $record->grade_point;
            if ($gp !== null && $gp > 0) {
                $gpKey = number_format($gp, 2);
                if (!isset($gradePoints[$gpKey])) {
                    $gradePoints[$gpKey] = 0;
                }
                $gradePoints[$gpKey]++;
            }
        }
        
        ksort($gradePoints);
        
        $gradeData = [];
        foreach ($gradePoints as $grade => $count) {
            $percentage = round(($count / $totalRecords) * 100, 1);
            $gradeData[] = [
                'grade' => $grade,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }
        
        return [
            'total_students' => $totalRecords,
            'grades' => $gradeData,
        ];
    }

    /**
     * Get attendance trends per subject
     */
    public function getAttendanceTrends()
    {
        $subjects = Subject::query();
        $subjects = $this->applyUserFilter($subjects)->get();
        
        return $subjects->map(function ($subject) {
            $attendanceQuery = StudentAttendance::where('subject_id', $subject->id);
            $attendanceQuery = $this->applyUserFilter($attendanceQuery);
            $attendances = $attendanceQuery->get();

            if ($attendances->count() > 0) {
                $presentCount = $attendances->where('status', 'present')->count();
                $lateCount = $attendances->where('status', 'late')->count();
                $absentCount = $attendances->where('status', 'absent')->count();
                $excusedCount = $attendances->where('status', 'excused')->count();
                $totalPossible = $attendances->count();

                $attendancePercent = $totalPossible > 0 ? round((($presentCount + $lateCount * 0.5) / $totalPossible) * 100, 1) : 0;

                $sessionAverages = $attendances->groupBy('session_number')->map(function($group) {
                    $p = $group->where('status', 'present')->count();
                    $l = $group->where('status', 'late')->count();
                    return ($group->count() > 0) ? ($p + $l * 0.5) / $group->count() * 100 : 0;
                })->values()->toArray();

                return $this->formatTrendArray($subject, $attendancePercent, $sessionAverages, [
                    'total' => $totalPossible,
                    'present' => $presentCount,
                    'late' => $lateCount,
                    'absent' => $absentCount,
                    'excused' => $excusedCount,
                ]);
            }

            // Try to extract from Record scores
            $extracted = $this->extractDetailedAttendance($subject->id);
            $totalPossible = $extracted['total_possible'];
            $presentCount = $extracted['total_present'];
            $lateCount = $extracted['total_late'] ?? 0;
            $excusedCount = $extracted['total_excused'] ?? 0;
            $absentCount = max(0, $totalPossible - $presentCount - $lateCount - $excusedCount);

            if ($totalPossible > 0) {
                return $this->formatTrendArray($subject, $extracted['average'], $extracted['session_averages'], [
                    'total' => $totalPossible,
                    'present' => $presentCount,
                    'late' => $lateCount,
                    'absent' => $absentCount,
                    'excused' => $excusedCount,
                ]);
            }

            return $this->formatTrendArray($subject, 0, [], [
                'total' => 0,
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'excused' => 0,
            ]);
        })->toArray();
    }

    private function formatTrendArray($subject, $average, $sessionAverages = [], $attendanceData = [])
    {
        // Normalize sessions to 4 weeks
        $count = count($sessionAverages);
        if ($count >= 4) {
            $w1 = $sessionAverages[0];
            $w2 = $sessionAverages[floor($count * 0.33)];
            $w3 = $sessionAverages[floor($count * 0.66)];
            $w4 = $sessionAverages[$count - 1];
        } else {
            $w1 = $sessionAverages[0] ?? $average * 0.95;
            $w2 = $sessionAverages[1] ?? $average * 0.97;
            $w3 = $sessionAverages[2] ?? $average * 0.99;
            $w4 = $sessionAverages[3] ?? $average;
        }

        return array_merge([
            'code' => $subject->code,
            'name' => $subject->name,
            'attendance_percent' => round($average, 1),
            'week1' => round($w1, 1),
            'week2' => round($w2, 1),
            'week3' => round($w3, 1),
            'week4' => round($w4, 1),
            'average' => round($average, 1),
        ], $attendanceData);
    }

    private function extractDetailedAttendance($subjectId)
    {
        $records = Record::where('subject_id', $subjectId)->get();
        $sessionData = []; // Group by column name
        $allColumns = [];
        
        foreach ($records as $record) {
            $scores = $record->scores;
            if (!is_array($scores)) continue;

            foreach ($scores as $column => $value) {
                $lower = strtolower(trim($column));
                $allColumns[$column] = true;
                
                // Detect attendance columns: dates, attendance marks (L/A), or session numbers
                $isAttendanceColumn = false;
                
                // Pattern 1: Date formats (MM/DD/YYYY, M/D/YY, month-day, etc.)
                if (preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}/', $lower) || 
                    preg_match('/^[a-z]{3}[-\s]\d{1,2}/', $lower)) {
                    $isAttendanceColumn = true;
                }
                
                // Pattern 2: Lab/Activity session markers (L1, L2, A1, A2, Lab1, etc.)
                if (preg_match('/^(l|a|lab|activity|session)\s*\d+$/i', $column)) {
                    $isAttendanceColumn = true;
                }
                
                // Pattern 3: Generic session columns (Session 1, Week 1, Day 1, etc.)
                if (preg_match('/^(session|week|day|class)\s*\d+$/i', $column)) {
                    $isAttendanceColumn = true;
                }
                
                // Pattern 4: Attendance/Presence/Roll columns
                if (preg_match('/attendance|present|absent|roll|mark/i', $column)) {
                    $isAttendanceColumn = true;
                }
                
                if ($isAttendanceColumn) {
                    if (!isset($sessionData[$column])) {
                        $sessionData[$column] = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'total' => 0];
                    }

                    $status = $this->categorizeAttendanceStatus($value);
                    $sessionData[$column]['total']++;

                    switch ($status) {
                        case 'present':
                            $sessionData[$column]['present']++;
                            break;
                        case 'late':
                            $sessionData[$column]['late']++;
                            break;
                        case 'excused':
                            $sessionData[$column]['excused']++;
                            break;
                        default:
                            $sessionData[$column]['absent']++;
                            break;
                    }
                }
            }
        }

        // Calculate session averages
        $sessionAverages = [];
        $totalPresent = 0;
        $totalLate = 0;
        $totalAbsent = 0;
        $totalExcused = 0;
        $totalPossible = 0;

        // Sort by column order to maintain temporal sequence
        ksort($sessionData);
        
        foreach ($sessionData as $session) {
            if ($session['total'] > 0) {
                $sessionAverages[] = ($session['present'] / $session['total']) * 100;
                $totalPresent += $session['present'];
                $totalLate += $session['late'] ?? 0;
                $totalAbsent += $session['absent'] ?? 0;
                $totalExcused += $session['excused'] ?? 0;
                $totalPossible += $session['total'];
            }
        }

        $average = $totalPossible > 0 ? ($totalPresent / $totalPossible) * 100 : 0;

        return [
            'session_averages' => $sessionAverages,
            'average' => $average,
            'total_possible' => $totalPossible,
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_absent' => $totalAbsent,
            'total_excused' => $totalExcused,
        ];
    }

    private function categorizeAttendanceStatus($value)
    {
        $normalized = trim(strtolower((string)$value));

        if ($normalized === '') {
            return 'absent';
        }

        if (is_numeric($value)) {
            $numeric = (float)$value;
            if ($numeric >= 1) {
                return 'present';
            }
            if ($numeric === 0.5) {
                return 'late';
            }
            return 'absent';
        }

        if (in_array($normalized, ['present', 'p', 'yes', 'y'], true)) {
            return 'present';
        }
        if (in_array($normalized, ['late', 'l'], true)) {
            return 'late';
        }
        if (in_array($normalized, ['excused', 'exc', 'e'], true)) {
            return 'excused';
        }
        if (in_array($normalized, ['absent', 'a', 'no', 'n', '0'], true)) {
            return 'absent';
        }

        // Unknown but not empty: assume present to be consistent with previous logic.
        return 'present';
    }

    /**
     * Get quiz analytics
     */
    public function getQuizAnalytics()
    {
        $quizzes = StudentQuiz::query();
        $quizzes = $this->applyUserFilter($quizzes)->get();
        
        $labQuizzes = $quizzes->where('quiz_type', 'laboratory');
        $nonLabQuizzes = $quizzes->where('quiz_type', 'non_laboratory');
        
        $labAvg = $labQuizzes->isNotEmpty() ? $labQuizzes->avg('score') : 0;
        $nonLabAvg = $nonLabQuizzes->isNotEmpty() ? $nonLabQuizzes->avg('score') : 0;
        
        return [
            'lab' => [
                'count' => $labQuizzes->count(),
                'avg_score' => round($labAvg, 2),
                'max_score' => $labQuizzes->isNotEmpty() ? $labQuizzes->max('score') : 0,
                'min_score' => $labQuizzes->isNotEmpty() ? $labQuizzes->min('score') : 0,
            ],
            'non_lab' => [
                'count' => $nonLabQuizzes->count(),
                'avg_score' => round($nonLabAvg, 2),
                'max_score' => $nonLabQuizzes->isNotEmpty() ? $nonLabQuizzes->max('score') : 0,
                'min_score' => $nonLabQuizzes->isNotEmpty() ? $nonLabQuizzes->min('score') : 0,
            ],
            'total' => [
                'count' => $quizzes->count(),
                'avg_score' => round($quizzes->isNotEmpty() ? $quizzes->avg('score') : 0, 2),
            ],
        ];
    }

    /**
     * Get exam analytics (midterm + final)
     */
    public function getExamAnalytics()
    {
        $midterms = StudentMidtermExam::query();
        $midterms = $this->applyUserFilter($midterms)->get();
        
        $finals = StudentFinalExam::query();
        $finals = $this->applyUserFilter($finals)->get();
        
        return [
            'midterm' => [
                'count' => $midterms->count(),
                'avg_score' => round($midterms->isNotEmpty() ? $midterms->avg('exam_score') : 0, 2),
                'max_score' => $midterms->isNotEmpty() ? $midterms->max('exam_score') : 0,
                'min_score' => $midterms->isNotEmpty() ? $midterms->min('exam_score') : 0,
                'submitted' => $midterms->where('submission_status', 'submitted')->count(),
            ],
            'final' => [
                'count' => $finals->count(),
                'avg_score' => round($finals->isNotEmpty() ? $finals->avg('exam_score') : 0, 2),
                'max_score' => $finals->isNotEmpty() ? $finals->max('exam_score') : 0,
                'min_score' => $finals->isNotEmpty() ? $finals->min('exam_score') : 0,
                'submitted' => $finals->where('submission_status', 'submitted')->count(),
            ],
        ];
    }

    /**
     * Get student performance rankings
     */
    public function getStudentPerformance()
    {
        $students = StudentGradeSummary::query();
        $students = $this->applyUserFilter($students)
            ->orderByDesc('final_numeric_grade')
            ->limit(10)
            ->get()
            ->map(function ($summary) {
                return [
                    'student_id' => $summary->student->student_id ?? 'N/A',
                    'student_name' => $summary->student->name ?? 'N/A',
                    'subject' => $summary->subject->code ?? 'N/A',
                    'lab_quiz_avg' => $summary->lab_quiz_average,
                    'lab_attendance' => $summary->lab_attendance_total,
                    'midterm_score' => $summary->lab_midterm_score,
                    'final_score' => $summary->lab_final_score,
                    'final_grade' => $summary->final_numeric_grade,
                    'letter_grade' => $this->getLetterGrade($summary->final_numeric_grade, $summary->raw_grade ?? ''),
                ];
            })
            ->toArray();
        
        return $students;
    }

    /**
     * Get subject summary
     */
    public function getSubjectSummary()
    {
        $subjects = Subject::query();
        $subjects = $this->applyUserFilter($subjects)->get();
        
        return $subjects->map(function ($subject) {
            $records = Record::where('subject_id', $subject->id)->get();
            $students = $records->unique('student_id')->count();
            
            $summaries = StudentGradeSummary::where('subject_id', $subject->id)->get();
            $avgFinalGrade = $summaries->isNotEmpty() ? $summaries->avg('final_numeric_grade') : 0;
            
            $quizzes = StudentQuiz::where('subject_id', $subject->id)->get();
            $quizAvg = $quizzes->isNotEmpty() ? $quizzes->avg('score') : 0;
            
            $attendance = StudentAttendance::where('subject_id', $subject->id)->get();
            $presentCount = $attendance->where('status', 'present')->count();
            $attendancePercent = $attendance->count() > 0 
                ? round(($presentCount / $attendance->count()) * 100, 1) 
                : 0;
            
            return [
                'code' => $subject->code,
                'name' => $subject->name,
                'students' => $students,
                'records' => $records->count(),
                'avg_grade' => round($avgFinalGrade, 2),
                'quiz_avg' => round($quizAvg, 2),
                'attendance_percent' => $attendancePercent,
                'laboratory_total' => $records->count() > 0 ? round($records->avg('laboratory_total') ?? 0, 2) : 0,
                'non_laboratory_total' => $records->count() > 0 ? round($records->avg('non_laboratory_total') ?? 0, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Generate CSV report for grades
     */
    public function generateGradeReportCSV($subjectId = null)
    {
        $headers = ['Subject Code', 'Subject Name', 'Student ID', 'Student Name', 'Program', 'Year Level', 'Grade Point', 'Letter Grade', 'Raw Grade', 'Date', 'Semester', 'School Year', 'Section'];
        
        $records = Record::query();
        $records = $this->applyUserFilter($records);
        
        if ($subjectId) {
            $records = $records->where('subject_id', $subjectId);
        }
        
        $records = $records->with(['subject', 'student'])
            ->orderBy('subject_id')
            ->orderBy('student_id')
            ->get();
        
        $rows = $records->map(function ($record) {
            $rawGrade = $record->raw_grade ?? '';
            $isSpecial = in_array(strtoupper(trim($rawGrade)), ['INC', 'DR', 'W', 'P']);
            
            return [
                $record->subject->code ?? 'N/A',
                $record->subject->name ?? 'N/A',
                $record->student->student_id ?? 'N/A',
                $record->student->name ?? 'N/A',
                $record->student->program ?? 'N/A',
                $record->student->year_level ?? '1',
                $record->grade_point ?? ($isSpecial ? $rawGrade : 'N/A'),
                $this->getLetterGrade($record->grade_point, $rawGrade),
                $rawGrade,
                $record->created_at->format('Y-m-d'),
                $record->subject->semester ?? '1st',
                $record->subject->school_year ?? '2025-2026',
                $record->subject->section ?? 'DC1A',
            ];
        })->toArray();
        
        return $this->arrayToCSV($headers, $rows);
    }

    /**
     * Generate CSV report for pass/fail analysis
     */
    public function generatePassFailReportCSV()
    {
        $headers = ['Subject Code', 'Subject Name', 'Total Students', 'Passed', 'Failed', 'Pass Rate (%)'];
        
        $passFailRates = $this->getPassFailRates();
        
        $rows = array_map(function ($rate) {
            return [
                $rate['code'] ?? 'N/A',
                $rate['name'] ?? 'N/A',
                $rate['total'] ?? 0,
                $rate['pass'] ?? 0,
                $rate['fail'] ?? 0,
                $rate['pass_rate'] ?? 0,
            ];
        }, $passFailRates);
        
        return $this->arrayToCSV($headers, $rows);
    }

    /**
     * Generate CSV report for attendance
     */
    public function generateAttendanceReportCSV()
    {
        $headers = ['Subject Code', 'Subject Name', 'Total Sessions', 'Present', 'Late', 'Absent', 'Excused', 'Attendance (%)'];
        
        $trends = $this->getAttendanceTrends();
        
        $rows = array_map(function ($trend) {
            return [
                $trend['code'] ?? 'N/A',
                $trend['name'] ?? 'N/A',
                $trend['total'] ?? 0,
                $trend['present'] ?? 0,
                $trend['late'] ?? 0,
                $trend['absent'] ?? 0,
                $trend['excused'] ?? 0,
                $trend['attendance_percent'] ?? 0,
            ];
        }, $trends);
        
        return $this->arrayToCSV($headers, $rows);
    }

    /**
     * Generate CSV report for lecture & lab summary
     */
    public function generateLectureLabReportCSV()
    {
        $subjects = Subject::query();
        $subjects = $this->applyUserFilter($subjects)->get();
        
        $csv = "CRMS - LECTURE & LAB SUMMARY REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        $csv .= "Subject Code,Subject Name,Lab Performance (%),Lecture Performance (%),Overall Score (%)\n";
        
        foreach ($subjects as $subject) {
            $records = Record::where('subject_id', $subject->id)->get();
            
            // Calculate lab performance (60% of total)
            $allGrades = $records->filter(fn($r) => $r->grade_point !== null);
            $labPerf = $allGrades->count() > 0 
                ? round(($allGrades->avg('grade_point') / 4.0) * 100 * 0.6, 1)
                : 0;
            
            // Calculate lecture performance (40% of total)
            $lecturePerf = $allGrades->count() > 0 
                ? round(($allGrades->avg('grade_point') / 4.0) * 100 * 0.4, 1)
                : 0;
            
            // Overall score
            $overallScore = round($labPerf + $lecturePerf, 1);
            
            $csv .= "\"{$subject->code}\",\"{$subject->name}\",{$labPerf},{$lecturePerf},{$overallScore}\n";
        }
        
        return $csv;
    }

    /**
     * Generate CSV report for comprehensive summary
     */
    public function generateComprehensiveReportCSV()
    {
        $analytics = $this->generateAnalytics();
        
        $csv = "CRMS - COMPREHENSIVE ANALYTICS REPORT\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $csv .= "Scope: " . ($this->userIds ? implode(', ', $this->userIds) : 'All Users') . "\n\n";
        
        // Pass/Fail Rates
        $csv .= "PASS/FAIL RATES BY SUBJECT\n";
        $csv .= "Subject Code,Subject Name,Total,Passed,Failed,Pass Rate (%)\n";
        foreach ($analytics['passFailRates'] as $rate) {
            $csv .= "\"" . ($rate['code'] ?? 'N/A') . "\",\"" . ($rate['name'] ?? 'N/A') . "\"," . ($rate['total'] ?? 0) . "," . ($rate['pass'] ?? 0) . "," . ($rate['fail'] ?? 0) . "," . ($rate['pass_rate'] ?? 0) . "\n";
        }
        $csv .= "\n";
        
        // Grade Distribution
        $csv .= "GRADE DISTRIBUTION\n";
        $csv .= "Grade,Count\n";
        foreach ($analytics['gradeDistribution'] as $grade => $count) {
            $csv .= "{$grade},{$count}\n";
        }
        $csv .= "\n";
        
        // Quiz Analytics
        $csv .= "QUIZ ANALYTICS\n";
        $csv .= "Type,Count,Average Score,Max Score,Min Score\n";
        $csv .= "Laboratory," . $analytics['quizAnalytics']['lab']['count'] . "," 
            . $analytics['quizAnalytics']['lab']['avg_score'] . "," 
            . $analytics['quizAnalytics']['lab']['max_score'] . "," 
            . $analytics['quizAnalytics']['lab']['min_score'] . "\n";
        $csv .= "Non-Laboratory," . $analytics['quizAnalytics']['non_lab']['count'] . "," 
            . $analytics['quizAnalytics']['non_lab']['avg_score'] . "," 
            . $analytics['quizAnalytics']['non_lab']['max_score'] . "," 
            . $analytics['quizAnalytics']['non_lab']['min_score'] . "\n";
        $csv .= "\n";
        
        // Exam Analytics
        $csv .= "EXAM ANALYTICS\n";
        $csv .= "Exam Type,Count,Average Score,Max Score,Min Score,Submitted\n";
        $csv .= "Midterm," . $analytics['examAnalytics']['midterm']['count'] . "," 
            . $analytics['examAnalytics']['midterm']['avg_score'] . "," 
            . $analytics['examAnalytics']['midterm']['max_score'] . "," 
            . $analytics['examAnalytics']['midterm']['min_score'] . "," 
            . $analytics['examAnalytics']['midterm']['submitted'] . "\n";
        $csv .= "Final," . $analytics['examAnalytics']['final']['count'] . "," 
            . $analytics['examAnalytics']['final']['avg_score'] . "," 
            . $analytics['examAnalytics']['final']['max_score'] . "," 
            . $analytics['examAnalytics']['final']['min_score'] . "," 
            . $analytics['examAnalytics']['final']['submitted'] . "\n";
        
        return $csv;
    }

    /**
     * Convert array to CSV string
     */
    private function arrayToCSV($headers, $rows)
    {
        $csv = implode(',', array_map(fn($h) => "\"$h\"", $headers)) . "\n";
        
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => "\"" . str_replace('"', '""', $v) . "\"", $row)) . "\n";
        }
        
        return $csv;
    }

    /**
     * Get letter grade from numeric grade
     */
    private function getLetterGrade($gradePoint, $rawGrade = '')
    {
        $raw = strtoupper(trim($rawGrade));
        if ($raw === 'INC') return 'INC';
        if ($raw === 'DR') return 'DR';
        if ($raw === 'W') return 'W';
        if ($raw === 'P') return 'P';

        if ($gradePoint === null || $gradePoint == 0) {
            return 'N/A';
        }
        
        // PH scale: lower is better
        if ($gradePoint <= 1.25) {
            return 'A';
        } elseif ($gradePoint <= 1.75) {
            return 'B';
        } elseif ($gradePoint <= 2.5) {
            return 'C';
        } elseif ($gradePoint <= 3.0) {
            return 'D';
        }
        
        return 'F';
    }
}
