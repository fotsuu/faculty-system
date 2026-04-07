<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use App\Models\Record;
use App\Models\Student;
use App\Models\StudentQuiz;
use App\Models\StudentAttendance;
use App\Models\StudentMidtermExam;
use App\Models\StudentFinalExam;
use App\Models\StudentGradeSummary;
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

        $selectedSubjectId = request()->query('subject_id');
        $selectedSubjectId = is_numeric($selectedSubjectId) ? (int) $selectedSubjectId : null;
        $selectedSection = request()->query('section');
        $selectedSection = is_string($selectedSection) ? trim($selectedSection) : null;
        if ($selectedSection === '') $selectedSection = null;
        
        // Count unique uploaded class record files (unique file_name per user)
        $totalRecords = Record::where('user_id', $user->id)
            ->whereNotNull('file_name')
            ->distinct('file_name')
            ->count('file_name');

        // Total unique students across all uploaded class record files
        $totalStudents = Record::where('user_id', $user->id)
            ->distinct('student_id')
            ->count('student_id');

        // Total subjects as number of uploaded class record files (as requested)
        $activeSubjects = $totalRecords;

        // Use AnalyticsService for accurate analytics
        $analyticsService = (new \App\Services\AnalyticsService($user->id))
            ->setFilters($selectedSubjectId, $selectedSection);
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
                $attendance = $subjectAttendance ? ($subjectAttendance['attendance_percent'] ?? $subjectAttendance['average'] ?? 0) : 0;
                
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
        ->map(function ($student) use ($user, $selectedSubjectId, $selectedSection) {
            $records = Record::where('student_id', $student->id)
                ->where('user_id', $user->id)
                ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId))
                ->when(is_string($selectedSection) && $selectedSection !== '', function ($q) use ($selectedSection) {
                    if (strcasecmp($selectedSection, 'Unassigned') === 0) {
                        $q->where(function ($subq) {
                            $subq->whereNull('section')->orWhere('section', '');
                        });
                    } else {
                        $q->where('section', $selectedSection);
                    }
                })
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
            
            // Get unique subject names for this student
            $subjects = $records->map(function($r) {
                return $r->subject->name ?? $r->subject->code ?? 'Unknown';
            })->unique()->implode(', ');
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'student_id' => $student->student_id,
                'program' => $student->program,
                'subjects' => $subjects,
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

        $filterSubjects = Subject::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $sectionsQuery = Record::where('user_id', $user->id);
        if ($selectedSubjectId) {
            $sectionsQuery->where('subject_id', $selectedSubjectId);
        }
        $filterSections = $sectionsQuery
            ->select('section')
            ->distinct()
            ->pluck('section')
            ->map(function ($s) {
                $s = is_string($s) ? trim($s) : '';
                return $s === '' ? 'Unassigned' : $s;
            })
            ->unique()
            ->sort()
            ->values();

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
            'filterSubjects' => $filterSubjects,
            'filterSections' => $filterSections,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedSection' => $selectedSection,
        ]);
    }

    /** Program Head dashboard: department overview with accurate data. */
    public function programHeadDashboard()
    {
        $user = Auth::user();
        $selectedSubjectId = request()->query('subject_id');
        $selectedSubjectId = is_numeric($selectedSubjectId) ? (int) $selectedSubjectId : null;
        $selectedSection = request()->query('section');
        $selectedSection = is_string($selectedSection) ? trim($selectedSection) : null;
        if ($selectedSection === '') $selectedSection = null;

        $facultyIds = User::where('role', 'faculty')
            ->when($user->department, function ($q) use ($user) {
                // Ensure case-insensitive department match and partial prefix handling (e.g. BSIT vs BSIT-3A)
                $dept = trim($user->department);
                $q->where(function ($sub) use ($dept) {
                    $sub->whereRaw('LOWER(department) = ?', [strtolower($dept)])
                        ->orWhere('department', 'like', $dept . '%');
                });
            })
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
            'allFaculty' => collect(),
            'facultyReports' => collect(),
            'analytics' => ['passFailRates' => [], 'gradeDistribution' => [], 'attendanceTrends' => []],
            'totalPass' => 0,
            'totalFail' => 0,
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
        
        // Use AnalyticsService for accurate analytics (scoped by optional subject/section)
        $analyticsService = (new \App\Services\AnalyticsService($facultyIds->toArray()))
            ->setFilters($selectedSubjectId, $selectedSection);
        $analytics = $analyticsService->generateAnalytics();
        
        $passFailRates = $analytics['passFailRates'];
        $gradeDistribution = $analytics['gradeDistribution'];
        $attendanceTrends = $analytics['attendanceTrends'];
        
        $allRecords = Record::whereIn('user_id', $facultyIds)
            ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId))
            ->when(is_string($selectedSection) && $selectedSection !== '', function ($q) use ($selectedSection) {
                if (strcasecmp($selectedSection, 'Unassigned') === 0) {
                    $q->where(function ($subq) {
                        $subq->whereNull('section')->orWhere('section', '');
                    });
                } else {
                    $q->where('section', $selectedSection);
                }
            })
            ->get();
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
            $attendance = $subjectAttendance ? $subjectAttendance['attendance_percent'] : 0;

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
            ->map(function ($student) use ($facultyIds, $selectedSubjectId, $selectedSection) {
                $records = Record::where('student_id', $student->id)->whereIn('user_id', $facultyIds)
                    ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId))
                    ->when(is_string($selectedSection) && $selectedSection !== '', function ($q) use ($selectedSection) {
                        if (strcasecmp($selectedSection, 'Unassigned') === 0) {
                            $q->where(function ($subq) {
                                $subq->whereNull('section')->orWhere('section', '');
                            });
                        } else {
                            $q->where('section', $selectedSection);
                        }
                    })
                    ->get();
                $gradeCount = 0; $gradeSum = 0;
                foreach ($records as $r) {
                    if ($r->grade_point !== null) { $gradeSum += $r->grade_point; $gradeCount++; }
                }
                $gpa = $gradeCount > 0 ? round($gradeSum / $gradeCount, 2) : 0;
                
                // Get unique subject names for this student
                $subjects = $records->map(function($r) {
                    return $r->subject->name ?? $r->subject->code ?? 'Unknown';
                })->unique()->implode(', ');

                return [
                    'name' => $student->name, 
                    'student_id' => $student->student_id, 
                    'program' => $student->program ?? '—', 
                    'subjects' => $subjects,
                    'gpa' => $gpa, 
                    'recordCount' => $records->count()
                ];
            })
            ->filter(fn($s) => $s['gpa'] > 0)
            ->sortBy('gpa')
            ->take(4)
            ->values();

        // Get all faculty for management tab
        $allFaculty = User::whereIn('id', $facultyIds)
            ->withCount(['subjects', 'records'])
            ->get();

        $facultyReports = GeneratedReport::where('recipient_id', $user->id)
            ->whereNotNull('submitted_at')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $filterSubjects = Subject::whereIn('user_id', $facultyIds)
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $sectionsQuery = Record::whereIn('user_id', $facultyIds);
        if ($selectedSubjectId) {
            $sectionsQuery->where('subject_id', $selectedSubjectId);
        }
        $filterSections = $sectionsQuery
            ->select('section')
            ->distinct()
            ->pluck('section')
            ->map(function ($s) {
                $s = is_string($s) ? trim($s) : '';
                return $s === '' ? 'Unassigned' : $s;
            })
            ->unique()
            ->sort()
            ->values();

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
            'filterSubjects' => $filterSubjects,
            'filterSections' => $filterSections,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedSection' => $selectedSection,
        ]);
    }

    /** Program Head Reports: View all reports submitted by their department faculty. */
    public function programHeadReports()
    {
        $user = Auth::user();
        
        // Show only reports explicitly submitted to this program head account.
        $facultyReports = GeneratedReport::where('recipient_id', $user->id)
            ->whereNotNull('submitted_at')
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('program-head.reports', [
            'facultyReports' => $facultyReports,
        ]);
    }

    public function programHeadClassRecords()
    {
        $user = Auth::user();

        $facultyIds = User::where('role', 'faculty')
            ->when($user->department, function ($q) use ($user) {
                $dept = trim($user->department);
                $q->where(function ($sub) use ($dept) {
                    $sub->whereRaw('LOWER(department) = ?', [strtolower($dept)])
                        ->orWhere('department', 'like', $dept . '%');
                });
            })
            ->pluck('id');

        $classRecordGroups = Record::whereIn('user_id', $facultyIds)
            ->whereNotNull('file_name')
            ->with('user')
            ->get()
            ->groupBy(function ($record) {
                return $record->user_id . '||' . $record->file_name;
            })
            ->map(function ($group) {
                $first = $group->first();
                $fileName = $first->file_name;
                // Strip numeric prefix like 1775451194_ for display
                $displayName = preg_replace('/^\d+_/', '', $fileName);
                
                return (object) [
                    'user_id' => $first->user_id,
                    'file_name' => $fileName,
                    'display_name' => $displayName,
                    'uploaded_by' => $first->user->name ?? 'Unknown Faculty',
                    'uploaded_at' => $group->min('created_at'),
                    'record_count' => $group->count(),
                    'subject_count' => $group->pluck('subject_id')->unique()->count(),
                ];
            })
            ->sortByDesc('uploaded_at')
            ->values();

        return view('program-head.class-records', [
            'classRecordGroups' => $classRecordGroups,
        ]);
    }

    public function programHeadViewClassRecord(Request $request)
    {
        $userId = $request->query('user_id');
        $fileName = $request->query('file_name');
        
        if (!$userId || !$fileName) {
            return redirect()->route('program-head.class-records')->with('error', 'Missing parameters.');
        }

        $records = Record::where('user_id', $userId)
            ->where('file_name', $fileName)
            ->with(['student', 'subject', 'user'])
            ->get();

        if ($records->isEmpty()) {
            return redirect()->route('program-head.class-records')->with('error', 'Record not found.');
        }

        $uploader = $records->first()->user->name ?? 'Unknown Faculty';
        $uploadedAt = $records->min('created_at');
        
        // Strip numeric prefix for display
        $displayName = preg_replace('/^\d+_/', '', $fileName);

        // Group records by section for separate display, matching faculty module style
        $scoreKeys = collect();
        foreach ($records as $record) {
            $scores = $record->scores;
            if (is_string($scores)) {
                $scores = json_decode($scores, true);
            }
            if (!is_array($scores)) {
                continue;
            }

            foreach ($scores as $key => $value) {
                if (!$scoreKeys->contains($key)) {
                    $scoreKeys->push($key);
                }
            }
        }

        // Keep student name as the first column for consistent display across environments.
        $nameHeader = $scoreKeys->first(function ($key) {
            $normalized = strtolower(trim((string) $key));
            return in_array($normalized, ['name of student', 'student name', 'name', 'full name'], true);
        });
        if ($nameHeader !== null) {
            $scoreKeys = collect([$nameHeader])
                ->merge($scoreKeys->reject(fn ($key) => $key === $nameHeader))
                ->values();
        }

        $excelBySection = null;
        if ($scoreKeys->isNotEmpty()) {
            $rows = [];
            foreach ($records as $record) {
                $scores = $record->scores;
                if (is_string($scores)) {
                    $scores = json_decode($scores, true);
                }
                if (!is_array($scores)) {
                    continue;
                }

                $row = [];
                foreach ($scoreKeys as $key) {
                    $row[] = isset($scores[$key]) ? $scores[$key] : '';
                }

                $rows[] = [
                    'section' => trim((string)$record->section) === '' ? 'Unassigned' : trim((string)$record->section),
                    'row' => $row,
                ];
            }

            $excelBySection = collect($rows)->groupBy('section')->map(function ($sectionRows) {
                return $sectionRows->pluck('row')->all();
            });
        }

        return view('program-head.view-record', [
            'records' => $records,
            'fileName' => $fileName,
            'displayName' => $displayName,
            'uploader' => $uploader,
            'uploadedAt' => $uploadedAt,
            'excelBySection' => $excelBySection,
            'headers' => $scoreKeys->toArray(),
        ]);
    }

    public function students()
    {
        $user = Auth::user();
        $totalUniqueStudents = Record::where('user_id', $user->id)
            ->distinct('student_id')
            ->count('student_id');

        // Group students per subject + section so the faculty view matches the separation model.
        $records = Record::where('user_id', $user->id)
            ->with(['student', 'subject'])
            ->get();

        $grouped = []; // key => ['subject'=>..., 'section'=>..., 'students'=>[student_id=>...]]

        foreach ($records as $rec) {
            if (!$rec->student || !$rec->subject) {
                continue;
            }

            $sectionLabel = null;
            if ($rec->section === null || $rec->section === '') {
                $sectionLabel = 'Unassigned';
            } else {
                $sectionLabel = (string) $rec->section;
            }

            $subjectCode = $rec->subject->code ?? 'N/A';
            $subjectName = $rec->subject->name ?? $rec->subject->code ?? 'N/A';

            $groupKey = $rec->subject_id . '|' . $sectionLabel;

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'subject_id' => $rec->subject_id,
                    'subject_code' => $subjectCode,
                    'subject_name' => $subjectName,
                    'section' => $sectionLabel,
                    'students' => [],
                ];
            }

            $studentIdKey = $rec->student->id;
            if (!isset($grouped[$groupKey]['students'][$studentIdKey])) {
                $rawProgram = (string)($rec->student->program ?? '');
                $normalizedProgram = trim($rawProgram);
                $inferredProgram = $this->inferProgramFromSection($sectionLabel);
                $displayProgram = ($normalizedProgram === '' || strcasecmp($normalizedProgram, 'General Studies') === 0)
                    ? ($inferredProgram ?: ($normalizedProgram !== '' ? $normalizedProgram : '—'))
                    : $normalizedProgram;

                $grouped[$groupKey]['students'][$studentIdKey] = [
                    'id' => $rec->student->id,
                    'student_id' => $rec->student->student_id ?? $rec->student->id,
                    'name' => $rec->student->name ?? '',
                    'program' => $displayProgram,
                    'recordCount' => 0,
                    'gradePointsSum' => 0,
                    'gradeCount' => 0,
                ];
            }

            $grouped[$groupKey]['students'][$studentIdKey]['recordCount']++;

            $gradePoint = $rec->grade_point;
            // Fallback for previously imported records where grade_point was not persisted:
            // use EQV from scores (commonly the GPA-equivalent column in the uploaded sheet).
            if (is_null($gradePoint) && is_array($rec->scores)) {
                foreach (['EQV', 'eqv', 'Equivalent', 'equivalent', 'GPA', 'gpa'] as $k) {
                    if (array_key_exists($k, $rec->scores) && is_numeric($rec->scores[$k])) {
                        $gradePoint = (float) $rec->scores[$k];
                        break;
                    }
                }
            }
            // Include 0.0 grade points (e.g., failing) to keep GPA accurate.
            if (!is_null($gradePoint)) {
                $grouped[$groupKey]['students'][$studentIdKey]['gradePointsSum'] += $gradePoint;
                $grouped[$groupKey]['students'][$studentIdKey]['gradeCount']++;
            }
        }

        // Finalize student GPA and convert associative arrays to plain arrays.
        $studentGroups = array_values($grouped);
        foreach ($studentGroups as &$group) {
            $studentsArr = [];
            foreach ($group['students'] as $stu) {
                $gpa = $stu['gradeCount'] > 0 ? round($stu['gradePointsSum'] / $stu['gradeCount'], 2) : null;
                $studentsArr[] = [
                    'id' => $stu['id'],
                    'student_id' => $stu['student_id'],
                    'name' => $stu['name'],
                    'program' => $stu['program'],
                    'recordCount' => $stu['recordCount'],
                    'gpa' => $gpa,
                ];
            }
            // Sort students inside group by student_id for consistent rendering.
            usort($studentsArr, function ($a, $b) {
                return strcmp((string) $a['student_id'], (string) $b['student_id']);
            });
            $group['students'] = $studentsArr;
        }
        unset($group);

        // Sort groups by subject code, then section label.
        usort($studentGroups, function ($a, $b) {
            $cmp = strcmp((string) $a['subject_code'], (string) $b['subject_code']);
            if ($cmp !== 0) return $cmp;
            return strcmp((string) $a['section'], (string) $b['section']);
        });

        return view('faculty.students', [
            'studentGroups' => $studentGroups,
            'totalStudents' => $totalUniqueStudents,
        ]);
    }

    /**
     * Infer program from section label (e.g. BSIT-3B => BSIT, IT3D => IT).
     */
    private function inferProgramFromSection(?string $section): ?string
    {
        if (!is_string($section)) {
            return null;
        }

        $clean = strtoupper(trim($section));
        if ($clean === '' || $clean === 'UNASSIGNED') {
            return null;
        }

        // Prefer prefix before dash if it starts with letters (BSIT-3B -> BSIT).
        if (preg_match('/^([A-Z]+)(?:-[A-Z0-9]+)?$/', $clean, $m)) {
            $prefix = $m[1] ?? '';
            // Strip trailing year/section indicators if attached to prefix (e.g. BSIT3 -> BSIT).
            $prefix = preg_replace('/\d+$/', '', $prefix);
            return $prefix !== '' ? $prefix : null;
        }

        // Fallback for compressed forms like IT3D.
        if (preg_match('/^([A-Z]+)\d+[A-Z]?$/', $clean, $m2)) {
            return $m2[1] ?? null;
        }

        return null;
    }

    public function subjects()
    {
        $user = Auth::user();
        // eager-load records so per-subject student counts can be computed without N+1 queries
        $subjects = Subject::where('user_id', $user->id)->with(['records.student'])->get();

        // Get all available sections for dropdown
        $allSections = Subject::getUserSections($user->id);

        // Group subjects by section (handle array sections)
        $groupedBySection = [];
        foreach ($subjects as $subject) {
            $subjectSections = is_array($subject->section) && !empty($subject->section) ? $subject->section : ['Unassigned'];
            
            foreach ($subjectSections as $section) {
                if (!isset($groupedBySection[$section])) {
                    $groupedBySection[$section] = [];
                }
                $groupedBySection[$section][] = $subject;
            }
        }
        
        ksort($groupedBySection);

        return view('faculty.subjects', [
            'subjects' => $subjects,
            'groupedBySection' => $groupedBySection,
            'availableSections' => $allSections,
            'totalSubjects' => $subjects->count(),
        ]);
    }

    public function storeSubject(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'max:50'],
        ]);

        $sections = !empty($data['sections']) ? array_filter($data['sections']) : null;

        Subject::create(array_merge($data, [
            'section' => $sections,
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

        $section = request()->query('section');

        $recordsQuery = $subject->records()->with('student');
        if (is_string($section) && trim($section) !== '') {
            $section = trim($section);
            if (strcasecmp($section, 'Unassigned') === 0) {
                $recordsQuery->where(function ($q) {
                    $q->whereNull('section')->orWhere('section', '');
                });
            } else {
                $recordsQuery->where('section', $section);
            }
        }

        $records = $recordsQuery->get();

        // Derive score headers in a stable order (from the first record that has scores).
        $scoreHeaders = [];
        foreach ($records as $rec) {
            if (!empty($rec->scores) && is_array($rec->scores)) {
                $scoreHeaders = array_keys($rec->scores);
                break;
            }
        }

        // Return detailed records with all student data
        $students = [];
        foreach ($records as $rec) {
            if (!$rec->student) continue;
            
            $students[] = [
                'id' => $rec->id,
                'student_id' => $rec->student->student_id ?? $rec->student->id,
                'name' => $rec->student->name ?? '',
                'scores' => $rec->scores ?? [],
                'raw_grade' => $rec->raw_grade,
                'numeric_grade' => $rec->numeric_grade,
                'grade_point' => $rec->grade_point,
                'section' => $rec->section,
            ];
        }

        return response()->json([
            'students' => $students,
            'scoreHeaders' => $scoreHeaders,
        ]);
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
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', 'max:50'],
        ]);
        
        $sections = !empty($data['sections']) ? array_filter($data['sections']) : null;
        $subject->update(array_merge($data, ['section' => $sections]));
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

        // Group records by section for separate display
        $recordsBySection = $records->groupBy(function ($record) {
            $section = trim((string) $record->section);
            return $section === '' ? 'Unassigned' : $section;
        });

        // If Excel preview was uploaded and is still in session, show that data instead
        $excelPreviewData = session('excel_preview_data');
        $excelBySection = null;

        $headers = [];
        $bodyRows = [];

        if (!empty($excelPreviewData['datasets']) && is_array($excelPreviewData['datasets'])) {
            // merge datasets for preview so user can see everything from all sheets
            $headers = $excelPreviewData['headers'] ?? [];
            foreach ($excelPreviewData['datasets'] as $dataset) {
                if (empty($headers) && !empty($dataset['headers'])) {
                    $headers = $dataset['headers'];
                }
                if (!empty($dataset['rows']) && is_array($dataset['rows'])) {
                    $bodyRows = array_merge($bodyRows, $dataset['rows']);
                }
            }
        } elseif (!empty($excelPreviewData['rows']) && !empty($excelPreviewData['headers'])) {
            $headers = $excelPreviewData['headers'];
            $bodyRows = $excelPreviewData['rows'];
        }

        if (!empty($headers) && !empty($bodyRows)) {
            $sectionIndex = false;
            foreach ($headers as $idx => $header) {
                if (strcasecmp(trim((string) $header), 'section') === 0) {
                    $sectionIndex = $idx;
                    break;
                }
            }

            $excelBySection = collect($bodyRows)->groupBy(function ($row) use ($sectionIndex) {
                if ($sectionIndex !== false && isset($row[$sectionIndex])) {
                    $sectionValue = trim((string)$row[$sectionIndex]);
                    return $sectionValue === '' ? 'Unassigned' : $sectionValue;
                }
                return 'Unassigned';
            });
        }

        // If no in-session preview data exists, fallback to DB records and render them using the same preview-style layout
        $excelTotalRows = 0;
        if (!empty($excelBySection)) {
            $excelTotalRows = collect($excelBySection)->map(fn($rows) => is_array($rows) ? count($rows) : 0)->sum();
        }

        if (empty($excelBySection) && $records->isNotEmpty()) {
            $scoreKeys = collect();
            foreach ($records as $record) {
                $scores = $record->scores;
                if (is_string($scores)) {
                    $scores = json_decode($scores, true);
                }
                if (!is_array($scores)) {
                    continue;
                }

                foreach ($scores as $key => $value) {
                    if (!$scoreKeys->contains($key)) {
                        $scoreKeys->push($key);
                    }
                }
            }

            if ($scoreKeys->isNotEmpty()) {
                $excelPreviewData = [
                    'headers' => $scoreKeys->toArray(),
                    'rows' => [],
                ];

                $rows = [];
                foreach ($records as $record) {
                    $scores = $record->scores;
                    if (is_string($scores)) {
                        $scores = json_decode($scores, true);
                    }
                    if (!is_array($scores)) {
                        continue;
                    }

                    $row = [];
                    foreach ($scoreKeys as $key) {
                        $row[] = isset($scores[$key]) ? $scores[$key] : '';
                    }

                    $rows[] = [
                        'section' => trim((string)$record->section) === '' ? 'Unassigned' : trim((string)$record->section),
                        'row' => $row,
                    ];
                }

                $excelBySection = collect($rows)->groupBy('section')->map(function ($sectionRows) {
                    return $sectionRows->pluck('row')->all();
                });
            }
        }

        $displayTotalRecords = $excelTotalRows > 0 ? $excelTotalRows : $records->count();

        return view('faculty.records', [
            'records' => $records,
            'recordsBySection' => $recordsBySection,
            'totalRecords' => $displayTotalRecords,
            'excelPreviewData' => $excelPreviewData,
            'excelBySection' => $excelBySection,
        ]);
    }

    public function reports()
    {
        $user = Auth::user();
        $subjects = Subject::where('user_id', $user->id)->get();
        $generatedReports = GeneratedReport::where('user_id', $user->id)
            ->with('recipient')
            ->orderByDesc('created_at')
            ->get();

        $recipients = User::whereIn('role', ['program_head', 'dean'])->get();

        return view('faculty.reports', [
            'subjects' => $subjects,
            'generatedReports' => $generatedReports,
            'recipients' => $recipients,
        ]);
    }

    public function submittedReports()
    {
        $user = Auth::user();
        
        // Get reports that this faculty has submitted to program heads/deans
        $submittedReports = GeneratedReport::where('user_id', $user->id)
            ->whereNotNull('recipient_id')
            ->whereNotNull('submitted_at')
            ->with('recipient')
            ->orderByDesc('submitted_at')
            ->get();

        return view('faculty.submitted-reports', [
            'submittedReports' => $submittedReports,
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
        $user = Auth::user();
        
        // Allow faculty to download their own reports or program heads/deans to download submitted reports
        $isOwner = $report->user_id === $user->id;
        $isRecipient = $user->role !== 'faculty' && $report->recipient_id === $user->id && $report->submitted_at !== null;
        
        if (!$isOwner && !$isRecipient) {
            abort(403);
        }

        return response()->streamDownload(function () use ($report) {
            echo $report->content;
        }, $report->filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function viewReport(Request $request, GeneratedReport $report)
    {
        $user = Auth::user();
        
        // Strict access: owner or explicit recipient only.
        $isOwner = $report->user_id === $user->id;
        $isRecipient = $user->role !== 'faculty' && $report->recipient_id === $user->id && $report->submitted_at !== null;

        if (!$isOwner && !$isRecipient) {
            abort(403);
        }

        // Get analytics for accurate performance data
        $analyticsService = new \App\Services\AnalyticsService($report->user_id);
        $analytics = $analyticsService->generateAnalytics();
        $detailedGradeDistribution = $analyticsService->getDetailedGradeDistribution();

        $embedded = $request->query('embedded', false);

        return view('faculty.report-view', [
            'report' => $report,
            'analytics' => $analytics,
            'detailedGradeDistribution' => $detailedGradeDistribution,
            'embedded' => $embedded,
        ]);
    }

    public function submitReport(Request $request, GeneratedReport $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        if (!in_array($recipient->role, ['program_head', 'dean'], true)) {
            return redirect()->route('faculty.reports')->with('error', 'Invalid recipient selected.');
        }

        $report->update([
            'recipient_id' => $request->recipient_id,
            'submitted_at' => now(),
        ]);

        return redirect()->route('faculty.reports')->with('success', 'Report submitted successfully.');
    }

    public function deleteReport(GeneratedReport $report)
    {
        if ($report->user_id !== Auth::id()) {
            abort(403);
        }

        $report->delete();

        return redirect()->route('faculty.reports')->with('success', 'Generated report deleted successfully.');
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

    private function categorizeAttendanceStatus($value)
    {
        $raw = trim((string)$value);
        $normalized = strtolower($raw);

        if ($normalized === '' || $normalized === '-' || $normalized === 'n/a' || $normalized === 'na') {
            return 'absent';
        }

        if (is_numeric($raw)) {
            $numeric = (float)$raw;
            if ($numeric >= 1) {
                return 'present';
            }
            if ($numeric === 0.5) {
                return 'late';
            }
            return 'absent';
        }

        if (strpos($normalized, '/') !== false) {
            $parts = preg_split('/\s*\/\s*/', $normalized);
            if (count($parts) === 2) {
                if (in_array($parts[0], ['p', 'present'], true)) {
                    return 'present';
                }
                if (in_array($parts[0], ['a', 'absent'], true)) {
                    return 'absent';
                }
                if (is_numeric($parts[0]) && is_numeric($parts[1])) {
                    return (float)$parts[0] > 0 ? 'present' : 'absent';
                }
            }
        }

        if (in_array($normalized, ['present', 'p', 'yes', 'y', 'x', '/'], true)) {
            return 'present';
        }
        if (in_array($normalized, ['late', 'l', 't'], true)) {
            return 'late';
        }
        if (in_array($normalized, ['excused', 'exc', 'e'], true)) {
            return 'excused';
        }
        if (in_array($normalized, ['absent', 'a', 'no', 'n', '0'], true)) {
            return 'absent';
        }

        return 'present';
    }

    private function isAttendanceColumn($column)
    {
        $lower = trim(strtolower((string)$column));

        if (preg_match('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $lower) || preg_match('/^[a-z]{3}[\-\s]\d{1,2}/', $lower)) {
            return true;
        }

        if (preg_match('/^(a|att|attendance|day|week|session|l|a)\s*\d+$/i', $column) || preg_match('/^[la]\d+$/i', $column)) {
            return true;
        }

        if (preg_match('/^(p[\s]*\/[\s]*a|a[\s]*\/[\s]*p|present[\s]*\/[\s]*absent|absent[\s]*\/[\s]*present)$/i', $column)) {
            return true;
        }

        if (preg_match('/attendance|present|absent|roll|mark/i', $column) && !preg_match('/total|equivalent|grade/i', $column)) {
            return true;
        }

        return false;
    }

    private function computeAttendanceFromRecordScores($records)
    {
        $attendanceTotal = 0;
        $attendancePresent = 0;
        $attendanceAbsent = 0;
        $attendanceRecords = [];

        foreach ($records as $record) {
            $scores = $record->scores;
            if (!is_array($scores) || empty($scores)) {
                continue;
            }

            foreach ($scores as $column => $value) {
                if (!$this->isAttendanceColumn($column)) {
                    continue;
                }

                $status = $this->categorizeAttendanceStatus($value);
                $attendanceTotal++;

                if ($status === 'present') {
                    $attendancePresent++;
                } elseif ($status === 'late') {
                    $attendancePresent += 0.5;
                    $attendanceAbsent += 0.5;
                } elseif ($status === 'excused') {
                    // excused considered non-present, non-absent
                } else {
                    $attendanceAbsent++;
                }

                $attendanceRecords[] = [
                    'subject' => $record->subject->name ?? $record->subject->code ?? 'Unknown Subject',
                    'session' => $column,
                    'status' => ucfirst($status),
                    'value' => $value,
                    'date' => null,
                ];
            }
        }

        return [
            'attendance_total' => $attendanceTotal,
            'attendance_present' => $attendancePresent,
            'attendance_absent' => $attendanceAbsent,
            'attendance_records' => $attendanceRecords,
        ];
    }

    /**
     * Get student grades and attendance data
     */
    public function getStudentGrades($studentId, Request $request)
    {
        $user = Auth::user();

        $subjectId = $request->query('subject_id');
        $section = $request->query('section');
        
        // Determine the student for this request
        if (ctype_digit((string) $studentId)) {
            $student = Student::where('id', (int) $studentId)->first();
            if (!$student) {
                $student = Student::where('student_id', $studentId)->first();
            }
        } else {
            $student = Student::where('student_id', $studentId)->first();
        }

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Find records for this student and faculty member (legacy record-based data)
        $records = Record::where('user_id', $user->id)
            ->where('student_id', $student->id)
            ->when(is_numeric($subjectId), function ($q) use ($subjectId) {
                $q->where('subject_id', (int)$subjectId);
            })
            ->when(is_string($section) && trim($section) !== '', function ($q) use ($section) {
                $section = trim($section);
                if (strcasecmp($section, 'Unassigned') === 0) {
                    $q->where(function ($subq) {
                        $subq->whereNull('section')->orWhere('section', '');
                    });
                } else {
                    $q->where('section', $section);
                }
            })
            ->with('subject')
            ->get();

        // Load imported assessment records (preferred)
        $studentQuizQuery = StudentQuiz::where('user_id', $user->id)->where('student_id', $student->id);
        $studentMidtermQuery = StudentMidtermExam::where('user_id', $user->id)->where('student_id', $student->id);
        $studentFinalQuery = StudentFinalExam::where('user_id', $user->id)->where('student_id', $student->id);
        $studentAttendanceQuery = StudentAttendance::where('user_id', $user->id)->where('student_id', $student->id);

        if (is_numeric($subjectId)) {
            $studentQuizQuery->where('subject_id', (int)$subjectId);
            $studentMidtermQuery->where('subject_id', (int)$subjectId);
            $studentFinalQuery->where('subject_id', (int)$subjectId);
            $studentAttendanceQuery->where('subject_id', (int)$subjectId);
        }

        $studentQuizRecords = $studentQuizQuery->with('subject')->get();
        $studentMidtermRecords = $studentMidtermQuery->with('subject')->get();
        $studentFinalRecords = $studentFinalQuery->with('subject')->get();
        $studentAttendanceRecords = $studentAttendanceQuery->with('subject')->get();

        $summaryQuery = StudentGradeSummary::where('user_id', $user->id)
            ->where('student_id', $student->id);
        if (is_numeric($subjectId)) {
            $summaryQuery->where('subject_id', (int) $subjectId);
        }
        $summary = $summaryQuery->first();

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

        $labQuizScores = [];
        $nonLabQuizScores = [];

        // Populate from imported per-assessment tables when available
        foreach ($studentAttendanceRecords as $attendance) {
            $attendanceRecords[] = [
                'subject' => $attendance->subject->name ?? $attendance->subject->code ?? 'Unknown Subject',
                'date' => $attendance->session_date ? $attendance->session_date->format('Y-m-d') : 'N/A',
                'value' => null,
                'status' => ucfirst($attendance->status),
            ];

            $attendanceTotal++;
            $status = strtolower($attendance->status);
            if ($status === 'present') {
                $attendancePresent++;
            } elseif ($status === 'absent') {
                $attendanceAbsent++;
            } elseif ($status === 'late') {
                // count late as half presence for display, but still as session
                $attendancePresent += 0.5;
                $attendanceAbsent += 0.5;
            }
        }

        // If there are no imported attendance rows, fallback to parsing legacy classrecord scores.
        if ($attendanceTotal === 0 && $records->isNotEmpty()) {
            $fallback = $this->computeAttendanceFromRecordScores($records);
            $attendanceTotal = $fallback['attendance_total'];
            $attendancePresent = $fallback['attendance_present'];
            $attendanceAbsent = $fallback['attendance_absent'];
            $attendanceRecords = $fallback['attendance_records'];
        }

        $midtermLectureQuizRecords = [];
        $midtermLabQuizRecords = [];
        $finalLabActivityRecords = [];
        $finalNonLabActivityRecords = [];

        foreach ($studentQuizRecords as $quiz) {
            $subjectName = $quiz->subject->name ?? $quiz->subject->code ?? 'Unknown Subject';
            $type = $quiz->quiz_type ?: 'non_laboratory';
            $score = (float) $quiz->score;

            $quizScores[] = $score;

            if ($type === 'laboratory') {
                $labQuizScores[] = $score;
                $midtermLabQuizRecords[] = [
                    'subject' => $subjectName,
                    'name' => 'Quiz ' . ($quiz->quiz_number ?? 'N/A'),
                    'score' => $score,
                    'type' => 'lab',
                ];
                $finalLabActivityRecords[] = [
                    'subject' => $subjectName,
                    'name' => 'Quiz ' . ($quiz->quiz_number ?? 'N/A'),
                    'score' => $score,
                    'type' => 'lab',
                ];
            } else {
                $nonLabQuizScores[] = $score;
                $midtermLectureQuizRecords[] = [
                    'subject' => $subjectName,
                    'name' => 'Quiz ' . ($quiz->quiz_number ?? 'N/A'),
                    'score' => $score,
                    'type' => 'lecture',
                ];
                $finalNonLabActivityRecords[] = [
                    'subject' => $subjectName,
                    'name' => 'Quiz ' . ($quiz->quiz_number ?? 'N/A'),
                    'score' => $score,
                    'type' => 'non-lab',
                ];
            }

            $quizRecords[] = [
                'subject' => $subjectName,
                'name' => 'Quiz ' . ($quiz->quiz_number ?? 'N/A'),
                'score' => $score,
                'type' => $type,
            ];
        }

        foreach ($studentMidtermRecords as $midterm) {
            $subjectName = $midterm->subject->name ?? $midterm->subject->code ?? 'Unknown Subject';
            $score = (float) $midterm->exam_score;
            $midtermScores[] = $score;
            $midtermRecords[] = [
                'subject' => $subjectName,
                'name' => 'Midterm Exam',
                'score' => $score,
            ];

            // Also include in quizzes view per requirement
            $quizRecords[] = [
                'subject' => $subjectName,
                'name' => 'Midterm Exam',
                'score' => $score,
                'type' => 'midterm',
            ];
        }

        foreach ($studentFinalRecords as $final) {
            $subjectName = $final->subject->name ?? $final->subject->code ?? 'Unknown Subject';
            $score = (float) $final->exam_score;
            $finalScores[] = $score;
            $finalRecords[] = [
                'subject' => $subjectName,
                'name' => 'Final Exam',
                'score' => $score,
            ];

            // Also include in quizzes view per requirement
            $quizRecords[] = [
                'subject' => $subjectName,
                'name' => 'Final Exam',
                'score' => $score,
                'type' => 'final',
            ];
        }

        $needsLegacyParsing = $studentQuizRecords->isEmpty() && $studentMidtermRecords->isEmpty() && $studentFinalRecords->isEmpty() && $studentAttendanceRecords->isEmpty();

        if ($needsLegacyParsing) {
            foreach ($records as $record) {
                $scores = $record->scores;
                if (!is_array($scores)) continue;

                $subjectName = $record->subject->name ?? $record->subject->code ?? 'Unknown Subject';

                foreach ($scores as $column => $value) {
                    if ($this->isAttendanceColumn($column)) {
                        $attendanceTotal++;
                        $status = $this->categorizeAttendanceStatus($value);

                        if ($status === 'present') {
                            $attendancePresent++;
                        } elseif ($status === 'late') {
                            $attendancePresent += 0.5;
                            $attendanceAbsent += 0.5;
                        } elseif ($status === 'excused') {
                            // do not increment absent/present totals for excused but count as session
                        } else {
                            // absent or any unrecognized status treated as absent
                            $attendanceAbsent++;
                        }

                        $attendanceRecords[] = [
                            'subject' => $subjectName,
                            'date' => $column,
                            'value' => $value,
                            'status' => ucfirst($status)
                        ];
                    }
                    // Quizzes: Q1, Q2, Quiz 1...
                    elseif (stripos($column, 'quiz') !== false || preg_match('/^q\d+$/i', $column)) {
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
                } else {
                    // For legacy row columns that are numeric but don't match known labels,
                    // treat them as generic quiz/exam entries so they are still counted and displayed.
                    if (is_numeric($value)) {
                        $score = (float) $value;
                        $quizScores[] = $score;
                        $quizRecords[] = [
                            'subject' => $subjectName,
                            'name' => $column,
                            'score' => $score,
                            'type' => 'other',
                        ];
                    }
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

        $labQuizzesTotal = count($labQuizScores);
        $labQuizzesAverage = $labQuizzesTotal > 0 ? array_sum($labQuizScores) / $labQuizzesTotal : 0;

        $nonLabQuizzesTotal = count($nonLabQuizScores);
        $nonLabQuizzesAverage = $nonLabQuizzesTotal > 0 ? array_sum($nonLabQuizScores) / $nonLabQuizzesTotal : 0;

        $allQuizzesWithExams = array_merge($quizScores, $midtermScores, $finalScores);
        $allQuizzesWithExamsTotal = count($allQuizzesWithExams);
        $allQuizzesWithExamsAverage = $allQuizzesWithExamsTotal > 0 ? array_sum($allQuizzesWithExams) / $allQuizzesWithExamsTotal : 0;

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
            'lab_quizzes_total' => $labQuizzesTotal,
            'lab_quizzes_average' => round($labQuizzesAverage, 2),
            'non_lab_quizzes_total' => $nonLabQuizzesTotal,
            'non_lab_quizzes_average' => round($nonLabQuizzesAverage, 2),
            'all_quizzes_with_exams_total' => $allQuizzesWithExamsTotal,
            'all_quizzes_with_exams_average' => round($allQuizzesWithExamsAverage, 2),
            'lab_midterm_score' => $summary->lab_midterm_score ?? 0,
            'lab_final_score' => $summary->lab_final_score ?? 0,
            'non_lab_midterm_score' => $summary->non_lab_midterm_score ?? 0,
            'non_lab_final_score' => $summary->non_lab_final_score ?? 0,
            'lab_total_grade' => $summary->lab_total_grade ?? 0,
            'non_lab_total_grade' => $summary->non_lab_total_grade ?? 0,
            'midterm_lecture_quiz_records' => $midtermLectureQuizRecords,
            'midterm_lab_quiz_records' => $midtermLabQuizRecords,
            'final_lab_activity_records' => $finalLabActivityRecords,
            'final_non_lab_activity_records' => $finalNonLabActivityRecords,
            'attendance_records' => $attendanceRecords,
            'quiz_records' => $quizRecords,
            'midterm_records' => $midtermRecords,
            'final_records' => $finalRecords,
        ]);
    }

    /**
     * Normalize attendance value to status.
     */
    private function determineAttendanceStatus($value)
    {
        $raw = trim((string) $value);
        $lower = strtolower($raw);

        if ($lower === '' || $lower === 'a' || $lower === 'absent' || $lower === 'no' || $lower === 'n') {
            return 'absent';
        }

        if ($lower === 'p' || $lower === 'present' || $lower === 'yes' || $lower === 'y' || $lower === 'x' || $lower === '/') {
            return 'present';
        }

        if ($lower === 'late' || $lower === 'l' || $lower === 't') {
            return 'late';
        }

        if ($lower === 'excused' || $lower === 'exc' || $lower === 'e') {
            return 'excused';
        }

        if (is_numeric($raw)) {
            $numeric = (float) $raw;
            return $numeric > 0 ? 'present' : 'absent';
        }

        return 'absent';
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
            $importer = new \App\Services\ExcelGradeImporter($user);
            try {
                $datasets = $preview['datasets'] ?? null;

                if (is_array($datasets) && !empty($datasets)) {
                    $totalRows = 0;
                    foreach ($datasets as $dataset) {
                        $headers = $dataset['headers'] ?? [];
                        $rows = $dataset['raw_rows'] ?? $dataset['rows'] ?? [];
                        $meta = $dataset['meta'] ?? [];
                        $meta['filename'] = $dataset['filename'] ?? ($preview['filename'] ?? null);
                        $totalRows += is_array($rows) ? count($rows) : 0;
                        $importer->importFromParsedData($headers, $rows, $meta);
                    }

                    \Log::info('Preview datasets imported during analytics generation', [
                        'user_id' => $user->id,
                        'datasets' => count($datasets),
                        'rows' => $totalRows,
                    ]);
                } else {
                    $headers = $preview['headers'] ?? [];
                    // prefer raw_rows when available to preserve exact file values
                    $rows = $preview['raw_rows'] ?? $preview['rows'] ?? [];
                    $rowCount = is_array($rows) ? count($rows) : 0;
                    $meta = $preview['meta'] ?? [];
                    // filename may be set as well
                    $meta['filename'] = $preview['filename'] ?? null;

                    $importer->importFromParsedData($headers, $rows, $meta);
                    \Log::info('Preview data imported during analytics generation', ['user_id' => $user->id, 'rows' => $rowCount]);
                }
            } catch (\Exception $e) {
                \Log::error('Error importing preview data: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                // Return error to user instead of silently failing
                return redirect()->route('dashboard')->with('error', 'Import failed: ' . $e->getMessage());
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

