<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use App\Models\Record;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function facultyDashboard()
    {
        $user = Auth::user();
        
        // Get counts for the authenticated faculty member
        $totalRecords = Record::where('user_id', $user->id)->count();
        
        // Get unique students enrolled in this faculty member's subjects
        $totalStudents = Student::whereIn('id', 
            Record::where('user_id', $user->id)
                ->distinct('student_id')
                ->pluck('student_id')
        )->count();
        
        $activeSubjects = Subject::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        // Use AnalyticsService for accurate analytics
        $analyticsService = new \App\Services\AnalyticsService($user->id);
        $analytics = $analyticsService->generateAnalytics();
        
        // Get subjects with student counts and analytics
        $passFailRates = collect($analytics['passFailRates']);
        $attendanceTrends = collect($analytics['attendanceTrends']);

        $subjects = Subject::where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($subject) use ($passFailRates, $attendanceTrends) {
                $subjectRecords = Record::where('subject_id', $subject->id)->get();
                $studentCount = $subjectRecords->unique('student_id')->count();
                $recordCount = $subjectRecords->count();
                
                // Get accurate pass rate from analytics service
                $subjectPassFail = $passFailRates->firstWhere('code', $subject->code);
                $passRate = $subjectPassFail ? $subjectPassFail['pass_rate'] : 0;
                
                // Get accurate attendance from analytics service
                $subjectAttendance = $attendanceTrends->firstWhere('code', $subject->code);
                $attendance = $subjectAttendance ? $subjectAttendance['average'] : 0;
                
                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'description' => $subject->description ?? '',
                    'studentCount' => $studentCount,
                    'recordCount' => $recordCount,
                    'passRate' => $passRate,
                    'attendance' => $attendance,
                ];
            });

        // Get top performing students...
        // (existing code remains but moved after analytics initialization)
        $topStudents = Student::whereIn('id',
            Record::where('user_id', $user->id)
                ->distinct('student_id')
                ->pluck('student_id')
        )
        ->get()
        ->map(function ($student) use ($user) {
            $records = Record::where('student_id', $student->id)
                ->where('user_id', $user->id)
                ->get();
            
            $recordCount = $records->count();
            
            // Compute average grade (GPA)
            $gradePoints = 0;
            $gradeCount = 0;
            foreach ($records as $r) {
                if (!is_null($r->grade_point) && $r->grade_point > 0) {
                    $gradePoints += $r->grade_point;
                    $gradeCount++;
                }
            }
            $gpa = $gradeCount > 0 ? round($gradePoints / $gradeCount, 2) : 0;
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'student_id' => $student->student_id,
                'program' => $student->program,
                'gpa' => $gpa,
                'recordCount' => $recordCount,
            ];
        })
        ->filter(fn($s) => $s['gpa'] > 0)
        ->sortBy('gpa') // In PH scale (1.0-5.0), 1.0 is better, so sorting ascending is correct
        ->take(4)
        ->values();

        $passFailRates = $analytics['passFailRates'];
        $gradeDistribution = $analytics['gradeDistribution'];
        $attendanceTrends = $analytics['attendanceTrends'];
        
        // Calculate totals for pass/fail pie chart
        $totalPass = collect($passFailRates)->sum('pass');
        $totalFail = collect($passFailRates)->sum('fail');

        $excelPreviewData = session('excel_preview_data');
        $showExcelModal = $excelPreviewData && (!empty($excelPreviewData['headers']) || !empty($excelPreviewData['rows']));

        // Get faculty's generated reports
        $facultyReports = GeneratedReport::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // Get detailed grade point distribution for student performance report
        $detailedGradeDistribution = $analyticsService->getDetailedGradeDistribution();

        return view('dashboard.faculty', [
            'totalRecords' => $totalRecords,
            'totalStudents' => $totalStudents,
            'activeSubjects' => $activeSubjects,
            'subjects' => $subjects,
            'topStudents' => $topStudents,
            'passFailRates' => $passFailRates,
            'attendanceTrends' => $attendanceTrends,
            'gradeDistribution' => $gradeDistribution,
            'detailedGradeDistribution' => $detailedGradeDistribution,
            'totalPass' => $totalPass,
            'totalFail' => $totalFail,
            'excelPreviewData' => $excelPreviewData ?? ['headers' => [], 'rows' => []],
            'showExcelModal' => $showExcelModal,
            'facultyReports' => $facultyReports,
        ]);
    }

    /** Program Head dashboard: department overview with accurate data. */
    public function programHeadDashboard()
    {
        $user = Auth::user();
        $facultyIds = User::where('role', 'faculty')
            ->when($user->department, fn ($q) => $q->where('department', $user->department))
            ->pluck('id');

        $emptyPayload = [
            'totalFaculty' => 0,
            'pendingReviews' => 0,
            'totalRecords' => 0,
            'recordsGrowthPercent' => 0,
            'passRatePercent' => 0,
            'subjects' => collect(),
            'submissions' => collect(),
            'passFailRates' => collect(),
            'attendanceTrends' => collect(),
            'gradeDistribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0],
            'topStudents' => collect(),
        ];

        if ($facultyIds->isEmpty()) {
            return view('dashboard.program-head', $emptyPayload);
        }

        $now = now();
        $currentStart = $now->copy()->subDays(30);
        $previousStart = $now->copy()->subDays(60);

        $recordsBase = Record::whereIn('user_id', $facultyIds);
        $totalRecords = (clone $recordsBase)->count();
        $recordsCurrent = (clone $recordsBase)->where('created_at', '>=', $currentStart)->count();
        $recordsPrevious = (clone $recordsBase)->where('created_at', '>=', $previousStart)->where('created_at', '<', $currentStart)->count();
        $recordsGrowthPercent = $recordsPrevious > 0 ? (int) round((($recordsCurrent - $recordsPrevious) / $recordsPrevious) * 100) : ($recordsCurrent > 0 ? 100 : 0);

        $totalFaculty = $facultyIds->count();
        
        // Use AnalyticsService for accurate analytics
        $analyticsService = new \App\Services\AnalyticsService($facultyIds->toArray());
        $analytics = $analyticsService->generateAnalytics();
        
        $passFailRates = $analytics['passFailRates'];
        $gradeDistribution = $analytics['gradeDistribution'];
        $attendanceTrends = $analytics['attendanceTrends'];
        
        $allRecords = Record::whereIn('user_id', $facultyIds)->get();
        $totalPass = collect($passFailRates)->sum('pass');
        $totalFail = collect($passFailRates)->sum('fail');
        $passRatePercent = collect($passFailRates)->isNotEmpty() ? (int) round(($totalPass / collect($passFailRates)->sum('total')) * 100) : 0;

        $subjects = Subject::whereIn('user_id', $facultyIds)->where('status', 'active')->get()->map(function ($subject) use ($passFailRates, $attendanceTrends) {
            $subjectRecords = Record::where('subject_id', $subject->id)->get();
            $studentCount = $subjectRecords->unique('student_id')->count();
            $recordCount = $subjectRecords->count();
            
            $subjectPassFail = collect($passFailRates)->firstWhere('code', $subject->code);
            $passRate = $subjectPassFail ? $subjectPassFail['pass_rate'] : 0;
            
            $subjectAttendance = collect($attendanceTrends)->firstWhere('code', $subject->code);
            $attendance = $subjectAttendance ? $subjectAttendance['average'] : 0;

            return [
                'id' => $subject->id, 'code' => $subject->code, 'name' => $subject->name,
                'studentCount' => $studentCount, 'recordCount' => $recordCount, 'passRate' => $passRate, 'attendance' => $attendance,
            ];
        });

        $submissions = Record::whereIn('user_id', $facultyIds)->with(['user', 'subject'])->orderByDesc('created_at')->get()
            ->groupBy(fn ($r) => $r->user_id . '-' . $r->subject_id)->take(10)->map(fn ($group) => (object) [
                'user_id' => $group->first()->user_id,
                'subject_id' => $group->first()->subject_id,
                'faculty_name' => $group->first()->user->name ?? 'N/A',
                'faculty_dept' => $group->first()->user->department ?? '—',
                'subject' => $group->first()->subject->name ?? $group->first()->subject->code ?? 'N/A',
                'date' => $group->first()->created_at->format('Y-m-d'),
                'status' => $group->first()->submission_status ?? 'Approved',
                'records' => $group->count(),
            ])->values();

        $topStudents = Student::whereIn('id', Record::whereIn('user_id', $facultyIds)->distinct()->pluck('student_id'))->get()
            ->map(function ($student) use ($facultyIds) {
                $records = Record::where('student_id', $student->id)->whereIn('user_id', $facultyIds)->get();
                $gradeCount = 0; $gradeSum = 0;
                foreach ($records as $r) {
                    if ($r->grade_point !== null) { $gradeSum += $r->grade_point; $gradeCount++; }
                }
                $gpa = $gradeCount > 0 ? round($gradeSum / $gradeCount, 2) : 0;
                return ['name' => $student->name, 'student_id' => $student->student_id, 'program' => $student->program ?? '—', 'gpa' => $gpa, 'recordCount' => $records->count()];
            })
            ->filter(fn($s) => $s['gpa'] > 0)
            ->sortBy('gpa')
            ->take(4)
            ->values();

        // Get all faculty for management tab
        $allFaculty = User::whereIn('id', $facultyIds)
            ->withCount(['subjects', 'records'])
            ->get();

        $facultyReports = GeneratedReport::whereIn('user_id', $facultyIds)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.program-head', [
            'totalFaculty' => $totalFaculty, 'pendingReviews' => 0, 'totalRecords' => $totalRecords,
            'recordsGrowthPercent' => $recordsGrowthPercent, 'passRatePercent' => $passRatePercent,
            'subjects' => $subjects, 'submissions' => $submissions, 
            'passFailRates' => $passFailRates,
            'attendanceTrends' => $attendanceTrends, 
            'gradeDistribution' => $gradeDistribution, 
            'topStudents' => $topStudents,
            'allFaculty' => $allFaculty,
            'analytics' => $analytics, // Pass the full analytics array
            'totalPass' => $totalPass,
            'totalFail' => $totalFail,
            'facultyReports' => $facultyReports,
        ]);
    }

    public function students()
    {
        $user = Auth::user();
        $studentIds = Record::where('user_id', $user->id)->distinct('student_id')->pluck('student_id');

        $students = Student::whereIn('id', $studentIds)
            ->with(['records' => function ($q) use ($user) {
                $q->where('user_id', $user->id)->with('subject');
            }])
            ->get()
            ->map(function ($student) use ($user) {
                $records = $student->records;
                $recordCount = $records->count();

                // Compute average grade (GPA) from grade points
                // Note: In the 1.0-5.0 scale, lower is better.
                $gradePoints = 0;
                $gradeCount = 0;
                foreach ($records as $r) {
                    $gradePoint = $r->grade_point;
                    if (!is_null($gradePoint) && $gradePoint > 0) {
                        $gradePoints += $gradePoint;
                        $gradeCount++;
                    }
                }

                $gpa = $gradeCount > 0 ? round($gradePoints / $gradeCount, 2) : null;

                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'program' => $student->program,
                    'recordCount' => $recordCount,
                    'gpa' => $gpa,
                ];
            });

        return view('faculty.students', [
            'students' => $students,
            'totalStudents' => $students->count(),
        ]);
    }

    public function subjects()
    {
        $user = Auth::user();
        // eager-load records so per-subject student counts can be computed without N+1 queries
        $subjects = Subject::where('user_id', $user->id)->with(['records.student'])->get();

        return view('faculty.subjects', [
            'subjects' => $subjects,
            'totalSubjects' => $subjects->count(),
        ]);
    }

    public function storeSubject(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Subject::create(array_merge($data, [
            'user_id' => Auth::id(),
            'status' => 'active',
        ]));

        return redirect()->back()->with('status', 'Subject created successfully.');
    }

    /**
     * Return JSON list of students and summary scores for a subject.
     * GET /faculty/subjects/{id}/students
     */
    public function subjectStudents(Subject $subject)
    {
        $user = Auth::user();
        if ($subject->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Return detailed records with all student data
        $students = [];
        foreach ($subject->records as $rec) {
            if (!$rec->student) continue;
            
            $students[] = [
                'id' => $rec->id,
                'student_id' => $rec->student->student_id ?? $rec->student->id,
                'name' => $rec->student->name ?? '',
                'scores' => $rec->scores ?? [],
                'raw_grade' => $rec->raw_grade,
                'numeric_grade' => $rec->numeric_grade,
                'grade_point' => $rec->grade_point,
            ];
        }

        return response()->json(['students' => $students]);
    }

    public function updateSubject(\Illuminate\Http\Request $request, Subject $subject)
    {
        if ($subject->user_id !== Auth::id()) {
            abort(403);
        }
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $subject->update($data);
        return redirect()->back()->with('status', 'Subject updated successfully.');
    }

    public function deleteSubject(Subject $subject)
    {
        if ($subject->user_id !== Auth::id()) {
            abort(403);
        }
        // Delete related records
        Record::where('subject_id', $subject->id)->delete();
        $subject->delete();
        return redirect()->back()->with('status', 'Subject and all its records deleted successfully.');
    }

    public function records()
    {
        $user = Auth::user();
        $records = Record::where('user_id', $user->id)->with('student', 'subject')->get();

        return view('faculty.records', [
            'records' => $records,
            'totalRecords' => $records->count(),
        ]);
    }

    public function reports()
    {
        $user = Auth::user();
        $subjects = Subject::where('user_id', $user->id)->get();
        $generatedReports = GeneratedReport::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('faculty.reports', [
            'subjects' => $subjects,
            'generatedReports' => $generatedReports,
        ]);
    }

    public function storeReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string|max:64',
            'title' => 'required|string|max:255',
            'filename' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        GeneratedReport::create([
            'user_id' => Auth::id(),
            'report_type' => $request->report_type,
            'title' => $request->title,
            'filename' => $request->filename,
            'content' => $request->content,
        ]);

        return response()->json(['success' => true]);
    }

    public function downloadReport(GeneratedReport $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->streamDownload(function () use ($report) {
            echo $report->content;
        }, $report->filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function viewReport(GeneratedReport $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        // Get analytics for accurate performance data
        $analyticsService = new \App\Services\AnalyticsService(Auth::id());
        $analytics = $analyticsService->generateAnalytics();
        $detailedGradeDistribution = $analyticsService->getDetailedGradeDistribution();

        return view('faculty.report-view', [
            'report' => $report,
            'analytics' => $analytics,
            'detailedGradeDistribution' => $detailedGradeDistribution,
        ]);
    }

    public function submitReport(GeneratedReport $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        // Mark as submitted (add submitted_at column later if needed)
        return redirect()->route('faculty.reports')->with('success', 'Report submitted successfully.');
    }

    public function settings()
    {
        return view('faculty.settings', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Delete a record (API endpoint)
     * DELETE /fire/api/record/{id}
     */
    public function deleteRecord(\App\Models\Record $record)
    {
        $user = Auth::user();
        
        // Verify ownership - record must belong to user's subject
        if ($record->subject->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $record->delete();
            return response()->json(['message' => 'Record deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete record', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete all records for a subject (API endpoint)
     * DELETE /fire/api/subject-records/{subject_id}
     */
    public function deleteSubjectRecords(Subject $subject)
    {
        $user = Auth::user();
        
        // Verify ownership
        if ($subject->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $count = $subject->records()->count();
            $subject->records()->delete();
            return response()->json(['message' => 'All subject records deleted successfully', 'count' => $count], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete subject records', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get student grades and attendance data
     */
    public function getStudentGrades($studentId, Request $request)
    {
        $user = Auth::user();
        
        // Find records for this student and faculty member
        $records = Record::where('user_id', $user->id)
            ->whereHas('student', function($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->with('subject')
            ->get();

        $attendanceTotal = 0;
        $attendancePresent = 0;
        $attendanceAbsent = 0;
        
        $quizScores = [];
        $midtermScores = [];
        $finalScores = [];

        $attendanceRecords = [];
        $quizRecords = [];
        $midtermRecords = [];
        $finalRecords = [];

        foreach ($records as $record) {
            $scores = $record->scores;
            if (!is_array($scores)) continue;

            $subjectName = $record->subject->name ?? $record->subject->code ?? 'Unknown Subject';

            foreach ($scores as $column => $value) {
                $lower = strtolower(trim($column));
                
                // Attendance: dates (09/11/2024), L1, L2...
                if (preg_match('/\d{1,2}\/\d{1,2}\/\d{2,4}/', $lower) || 
                    preg_match('/^[a-z]{3}[-\s]\d{1,2}/', $lower) || 
                    preg_match('/^[la]\d+$/', $lower)) {
                    
                    $attendanceTotal++;
                    // Non-numeric or "DR", "U", etc are often absences or drops. 10/10 or 1 is present.
                    $isPresent = (is_numeric($value) && $value > 0);
                    if ($isPresent) {
                        $attendancePresent++;
                    } else {
                        $attendanceAbsent++;
                    }

                    $attendanceRecords[] = [
                        'subject' => $subjectName,
                        'date' => $column,
                        'value' => $value,
                        'status' => $isPresent ? 'Present' : 'Absent'
                    ];
                }
                
                // Quizzes: Q1, Q2, Quiz 1...
                elseif (stripos($lower, 'quiz') !== false || preg_match('/^q\d+$/', $lower)) {
                    if (is_numeric($value)) {
                        $quizScores[] = (float) $value;
                        $quizRecords[] = [
                            'subject' => $subjectName,
                            'name' => $column,
                            'score' => $value
                        ];
                    }
                }
                
                // Midterm: Mid-Exam, Midterm...
                elseif (stripos($lower, 'midterm') !== false || stripos($lower, 'mid-exam') !== false) {
                    if (is_numeric($value)) {
                        $midtermScores[] = (float) $value;
                        $midtermRecords[] = [
                            'subject' => $subjectName,
                            'name' => $column,
                            'score' => $value
                        ];
                    }
                }
                
                // Final: Final Exam, Final_Term...
                elseif (stripos($lower, 'final exam') !== false || stripos($lower, 'final_term') !== false || stripos($lower, 'final grade') !== false) {
                    if (is_numeric($value)) {
                        $finalScores[] = (float) $value;
                        $finalRecords[] = [
                            'subject' => $subjectName,
                            'name' => $column,
                            'score' => $value
                        ];
                    }
                }
            }
        }

        $quizzesTotal = count($quizScores);
        $quizzesAverage = $quizzesTotal > 0 ? array_sum($quizScores) / $quizzesTotal : 0;

        $midtermCount = count($midtermScores);
        $midtermAverage = $midtermCount > 0 ? array_sum($midtermScores) / $midtermCount : 0;

        $finalCount = count($finalScores);
        $finalAverage = $finalCount > 0 ? array_sum($finalScores) / $finalCount : 0;

        return response()->json([
            'attendance_total' => $attendanceTotal,
            'attendance_present' => $attendancePresent,
            'attendance_absent' => $attendanceAbsent,
            'quizzes_total' => $quizzesTotal,
            'quizzes_average' => round($quizzesAverage, 2),
            'midterm_count' => $midtermCount,
            'midterm_average' => round($midtermAverage, 2),
            'final_count' => $finalCount,
            'final_average' => round($finalAverage, 2),
            'attendance_records' => $attendanceRecords,
            'quiz_records' => $quizRecords,
            'midterm_records' => $midtermRecords,
            'final_records' => $finalRecords,
        ]);
    }

    /**
     * Generate analytics report
     */
    public function generateAnalytics(Request $request)
    {
        $user = Auth::user();
        $reportType = $request->input('report_type', 'comprehensive');
        $subjectId = $request->input('subject_id');

        // if there is preview data in session, import it now before generating analytics
        if ($request->session()->has('excel_preview_data')) {
            $preview = $request->session()->pull('excel_preview_data');
            $headers = $preview['headers'] ?? [];
            // prefer raw_rows when available to preserve exact file values
            $rows = $preview['raw_rows'] ?? $preview['rows'] ?? [];
            $rowCount = is_array($rows) ? count($rows) : 0;
            $meta = $preview['meta'] ?? [];
            // filename may be set as well
            $meta['filename'] = $preview['filename'] ?? null;

            $importer = new \App\Services\ExcelGradeImporter($user);
            try {
                $importer->importFromParsedData($headers, $rows, $meta);
                \Log::info('Preview data imported during analytics generation', ['user_id' => $user->id, 'rows' => $rowCount]);
            } catch (\Exception $e) {
                \Log::error('Error importing preview data: ' . $e->getMessage(), ['rows' => $rowCount]);
            }
        }

        $analyticsService = new \App\Services\AnalyticsService($user->role === 'dean' ? null : $user->id);

        try {
            $content = match ($reportType) {
                'grade' => $analyticsService->generateGradeReportCSV($subjectId),
                'passFailAnalysis' => $analyticsService->generatePassFailReportCSV(),
                'attendance' => $analyticsService->generateAttendanceReportCSV(),
                'lectureLabSummary' => $analyticsService->generateLectureLabReportCSV(),
                'comprehensive' => $analyticsService->generateComprehensiveReportCSV(),
                default => $analyticsService->generateComprehensiveReportCSV(),
            };

            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "{$reportType}_{$timestamp}.csv";
            $title = match ($reportType) {
                'grade' => 'Student Grade Report',
                'passFailAnalysis' => 'Pass/Fail Analysis Report',
                'attendance' => 'Attendance Summary Report',
                'lectureLabSummary' => 'Lecture & Lab Summary Report',
                'comprehensive' => 'Comprehensive Analytics Report',
                default => 'Analytics Report',
            };

            // Save to GeneratedReport
            GeneratedReport::create([
                'user_id' => $user->id,
                'report_type' => $reportType,
                'title' => $title,
                'filename' => $filename,
                'content' => $content,
            ]);

            return response()->json([
                'success' => true,
                'report_type' => $reportType,
                'content' => $content,
                'filename' => $filename,
                'title' => $title,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }
}

