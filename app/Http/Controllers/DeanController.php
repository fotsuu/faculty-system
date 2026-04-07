<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeanController extends Controller
{
    public function dashboard()
    {
        $selectedSubjectId = request()->query('subject_id');
        $selectedSubjectId = is_numeric($selectedSubjectId) ? (int) $selectedSubjectId : null;
        $selectedSection = request()->query('section');
        $selectedSection = is_string($selectedSection) ? trim($selectedSection) : null;
        if ($selectedSection === '') $selectedSection = null;

        // Get all faculty members (users with role 'faculty')
        $facultyIds = User::where('role', 'faculty')->pluck('id');
        $totalFaculty = $facultyIds->count();

        // Faculty scope that has uploaded class records.
        $analyticsFacultyIds = Record::whereIn('user_id', $facultyIds)
            ->whereNotNull('file_name')
            ->distinct()
            ->pluck('user_id');
        if ($analyticsFacultyIds->isEmpty()) {
            $analyticsFacultyIds = $facultyIds;
        }
        
        // Get total students across all faculty subjects
        $totalStudents = Student::whereIn('id', Record::whereIn('user_id', $analyticsFacultyIds)->distinct('student_id')->pluck('student_id'))->count();
        
        // Get total records from faculty users
        $totalRecords = Record::whereIn('user_id', $analyticsFacultyIds)
            ->whereNotNull('file_name')
            ->count();

        // Calculate growth (mock growth for now or calculate from dates)
        $now = now();
        $currentMonth = Record::whereIn('user_id', $analyticsFacultyIds)
            ->whereNotNull('file_name')
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();
        $lastMonth = Record::whereIn('user_id', $analyticsFacultyIds)
            ->whereNotNull('file_name')
            ->where('created_at', '>=', $now->copy()->subMonth()->startOfMonth())
            ->where('created_at', '<', $now->copy()->startOfMonth())
            ->count();
        $recordsGrowthPercent = $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100) : ($currentMonth > 0 ? 100 : 0);
        
        // Use AnalyticsService for accurate analytics scoped to faculty (with optional subject/section filter)
        $analyticsService = (new \App\Services\AnalyticsService($analyticsFacultyIds->toArray()))
            ->setFilters($selectedSubjectId, $selectedSection);
        $analytics = $analyticsService->generateAnalytics();
        
        $passFailRates = $analytics['passFailRates'];
        $totalPass = collect($passFailRates)->sum('pass');
        $totalFail = collect($passFailRates)->sum('fail');
        $totalAll = collect($passFailRates)->sum('total');
        $passRatePercent = $totalAll > 0 ? round(($totalPass / $totalAll) * 100) : 0;

        // Get class record submissions (grouped by faculty and subject)
        $submissions = Record::whereIn('user_id', $analyticsFacultyIds)
            ->whereNotNull('file_name')
            ->with(['user', 'subject'])
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
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($record) {
                return $record->user_id . '-' . $record->subject_id;
            })
            ->map(function($group) {
                $firstRecord = $group->first();
                $recordsCount = $group->count();
                $status = $firstRecord->submission_status ?? 'Approved';
                
                return (object) [
                    'user_id' => $firstRecord->user_id,
                    'subject_id' => $firstRecord->subject_id,
                    'faculty_name' => $firstRecord->user->name ?? 'Unknown',
                    'faculty_dept' => $firstRecord->user->department ?? 'N/A',
                    'subject' => $firstRecord->subject->name ?? 'Unknown',
                    'date' => $firstRecord->created_at->format('Y-m-d'),
                    'status' => $status,
                    'records' => $recordsCount,
                ];
            })
            ->take(10)
            ->values();
        
        // Get top performing students
        $topStudents = Student::whereIn('id', Record::whereIn('user_id', $analyticsFacultyIds)->distinct()->pluck('student_id'))->get()
            ->map(function($student) use ($analyticsFacultyIds, $selectedSubjectId, $selectedSection) {
                $records = Record::where('student_id', $student->id)
                    ->whereIn('user_id', $analyticsFacultyIds)
                    ->whereNotNull('file_name')
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
                if ($records->isEmpty()) return null;
                $totalGradePoints = 0; $countedRecords = 0;
                foreach ($records as $record) {
                    if (!is_null($record->grade_point) && $record->grade_point > 0) {
                        $totalGradePoints += $record->grade_point; $countedRecords++;
                    }
                }
                if ($countedRecords == 0) return null;
                $gpa = round($totalGradePoints / $countedRecords, 2);
                return [
                    'name' => $student->name,
                    'student_id' => $student->student_id,
                    'program' => $student->program ?? 'N/A',
                    'gpa' => $gpa,
                    'recordCount' => $countedRecords,
                ];
            })
            ->filter()
            ->sortBy('gpa')
            ->take(5)
            ->values();
        
        // Get all faculty for management tab
        $allFaculty = User::where('role', 'faculty')
            ->withCount(['subjects', 'records'])
            ->get();
            
        // Show only reports explicitly submitted to this dean account.
        $facultyReports = \App\Models\GeneratedReport::where('recipient_id', Auth::id())
            ->whereNotNull('submitted_at')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $filterSubjects = Subject::whereIn('user_id', $analyticsFacultyIds)
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $sectionsQuery = Record::whereIn('user_id', $analyticsFacultyIds)
            ->whereNotNull('file_name');
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

        $subjectSectionOptions = collect();
        foreach ($filterSubjects as $subjectOption) {
            $subjectSections = Record::whereIn('user_id', $analyticsFacultyIds)
                ->whereNotNull('file_name')
                ->where('subject_id', $subjectOption->id)
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

            if ($subjectSections->isEmpty()) {
                $subjectSections = collect(['Unassigned']);
            }

            foreach ($subjectSections as $sec) {
                $subjectSectionOptions->push([
                    'value' => $subjectOption->id . '||' . $sec,
                    'subject_id' => $subjectOption->id,
                    'section' => $sec,
                    'label' => $subjectOption->code . ' - ' . $sec,
                ]);
            }
        }
            
        return view('dashboard.dean', [
            'totalFaculty' => $totalFaculty,
            'totalRecords' => $totalRecords,
            'recordsGrowthPercent' => $recordsGrowthPercent,
            'passRatePercent' => $passRatePercent,
            'totalStudents' => $totalStudents,
            'submissions' => $submissions,
            'topStudents' => $topStudents,
            'passFailRates' => $passFailRates,
            'gradeDistribution' => $analytics['gradeDistribution'],
            'attendanceTrends' => $analytics['attendanceTrends'],
            'allFaculty' => $allFaculty,
            'subjects' => $filterSubjects,
            'analytics' => $analytics,
            'totalPass' => $totalPass,
            'totalFail' => $totalFail,
            'facultyReports' => $facultyReports,
            'filterSubjects' => $filterSubjects,
            'filterSections' => $filterSections,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedSection' => $selectedSection,
            'subjectSectionOptions' => $subjectSectionOptions,
        ]);
    }
    
    // Remove the private calculateAnalytics method as it's replaced by AnalyticsService
    
    public function exportAllData()
    {
        // Prepare data for export
        $data = [
            'faculty' => User::where('role', 'faculty')->get(),
            'students' => Student::all(),
            'subjects' => Subject::all(),
            'records' => Record::with(['user', 'subject', 'student'])->get(),
        ];
        
        // Create CSV or JSON response
        $filename = 'faculty-system-export-' . now()->format('Y-m-d-His') . '.json';
        
        return response()->json($data, 200, [
            'Content-Disposition' => "attachment; filename=\"$filename\""
        ]);
    }
    
    public function systemSettings()
    {
        return view('dean.settings');
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return back()->with('status', 'Profile updated successfully!');
    }

    public function classRecords()
    {
        // For Dean, show ALL faculty records across ALL departments
        $facultyIds = User::where('role', 'faculty')->pluck('id');

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
                    'department' => $first->user->department ?? 'N/A',
                    'uploaded_at' => $group->min('created_at'),
                    'record_count' => $group->count(),
                    'subject_count' => $group->pluck('subject_id')->unique()->count(),
                ];
            })
            ->sortByDesc('uploaded_at')
            ->values();

        return view('dean.class-records', [
            'classRecordGroups' => $classRecordGroups,
        ]);
    }

    public function viewClassRecord(Request $request)
    {
        $userId = $request->query('user_id');
        $fileName = $request->query('file_name');
        
        if (!$userId || !$fileName) {
            return redirect()->route('dean.class-records')->with('error', 'Missing parameters.');
        }

        $records = Record::where('user_id', $userId)
            ->where('file_name', $fileName)
            ->with(['student', 'subject', 'user'])
            ->get();

        if ($records->isEmpty()) {
            return redirect()->route('dean.class-records')->with('error', 'Record not found.');
        }

        $uploader = $records->first()->user->name ?? 'Unknown Faculty';
        $uploadedAt = $records->min('created_at');
        
        // Strip numeric prefix for display
        $displayName = preg_replace('/^\d+_/', '', $fileName);

        // Group records by section for separate display
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

        return view('dean.view-record', [
            'records' => $records,
            'fileName' => $fileName,
            'displayName' => $displayName,
            'uploader' => $uploader,
            'uploadedAt' => $uploadedAt,
            'excelBySection' => $excelBySection,
            'headers' => $scoreKeys->toArray(),
        ]);
    }

    public function viewSubmission($userId, $subjectId)
    {
        // Get all records for this faculty-subject combination
        $submissions = Record::with(['user', 'subject', 'student'])
            ->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($submissions->isEmpty()) {
            return back()->with('error', 'Submission not found');
        }
        
        $firstSubmission = $submissions->first();
        
        $viewName = Auth::user()->role === 'dean' ? 'dean.submission-details' : 'program-head.submission-details';
        
        // Ensure the view exists, if not fallback to dean's view or a generic one
        if (!view()->exists($viewName)) {
            $viewName = 'dean.submission-details';
        }

        return view($viewName, [
            'submissions' => $submissions,
            'faculty_name' => $firstSubmission->user->name,
            'subject' => $firstSubmission->subject->name,
            'subject_code' => $firstSubmission->subject->code,
            'status' => $firstSubmission->submission_status ?? 'pending',
            'submitted_date' => $firstSubmission->created_at->format('Y-m-d H:i'),
            'userId' => $userId,
            'subjectId' => $subjectId
        ]);
    }

    public function toggleFacultyStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return back()->with('success', 'Faculty status updated successfully!');
    }
    
    public function approveSubmission(Request $request, $userId, $subjectId)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Update all records for this faculty-subject combination
        Record::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->update([
                'submission_status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $validated['notes'] ?? null,
            ]);
        
        return back()->with('success', 'Class record submission approved successfully!');
    }
    
    public function rejectSubmission(Request $request, $userId, $subjectId)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);
        
        // Update all records for this faculty-subject combination
        Record::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->update([
                'submission_status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $validated['notes'],
            ]);
        
        return back()->with('success', 'Class record submission rejected. Faculty has been notified.');
    }
}
