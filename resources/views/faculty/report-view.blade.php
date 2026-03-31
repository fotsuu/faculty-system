<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Report - {{ $report->title }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; color: #333; }
        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar Styles */
        .sidebar { width: 227px; background: #1e3c72; color: white; padding: 20px 0; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .sidebar-brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-brand-icon { width: 60px; height: 60px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; }
        .sidebar-brand-icon img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-brand h3 { font-size: 13px; font-weight: 700; letter-spacing: 1px; }
        .sidebar-menu { list-style: none; }
        .sidebar-title { padding: 15px 20px; font-size: 11px; font-weight: 700; letter-spacing: 1px; color: rgba(255,255,255,0.5); text-transform: uppercase; }
        .sidebar-menu li { margin: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; transition: all 0.3s ease; }
        .sidebar-menu a:hover { background-color: rgba(212,175,55,0.1); color: white; }
        .sidebar-menu a.active { background-color: rgba(255,255,255,0.2); color: white; font-weight: 600; }
        .sidebar-menu-icon { width: 20px; height: 20px; margin-right: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .sidebar-logout { margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .btn-logout { width: 100%; padding: 10px; background: transparent; border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease; }
        .btn-logout:hover { background-color: rgba(255,255,255,0.1); border-color: white; }

        .main-content { margin-left: 227px; flex: 1; display: flex; flex-direction: column; }
        
        .top-header { background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); position: sticky; top: 0; z-index: 50; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .header-brand-icon img { width: 50px; height: 50px; object-fit: contain; }
        .header-brand h2 { font-size: 20px; font-weight: 800; letter-spacing: 1.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-brand p { font-size: 12px; color: rgba(255,255,255,0.9); letter-spacing: 1.2px; font-weight: 500; }
        
        .main-area { flex: 1; padding: 30px; overflow-y: auto; }

        /* Report of Grades (ROG) Styles */
        .rog-paper { 
            background: white; 
            width: 100%; 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 40px 60px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            color: #333;
            position: relative;
            min-height: 1000px;
        }

        .rog-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .rog-header h1 { font-size: 20px; text-transform: uppercase; font-weight: 800; margin-bottom: 20px; letter-spacing: 1px; }
        .rog-meta { display: grid; grid-template-columns: 1fr 1fr; text-align: left; font-size: 14px; gap: 10px 40px; }
        .rog-meta-item span:first-child { font-weight: 700; width: 150px; display: inline-block; }
        .rog-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .rog-table th { border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 8px 5px; font-size: 13px; text-align: left; font-weight: 700; }
        .rog-table td { padding: 6px 5px; font-size: 13px; border-bottom: 1px dotted #ccc; }
        .rog-footer { margin-top: 50px; display: flex; justify-content: flex-end; }
        .signature-line { text-align: center; width: 250px; }
        .signature-name { font-weight: 700; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 5px; text-transform: uppercase; }
        .signature-title { font-size: 12px; color: #666; }
        .draft-watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 150px; font-weight: 900; color: rgba(0,0,0,0.03); pointer-events: none; text-transform: uppercase; z-index: 0; }

        /* Embedded mode: only report content + print/download */
        .embedded-report .sidebar,
        .embedded-report .top-header,
        .embedded-report .sidebar-logout {
            display: none !important;
        }

        .embedded-report .main-content {
            margin-left: 0 !important;
        }

        .embedded-report .main-area {
            padding: 18px !important;
            margin-top: 0 !important;
        }

        /* Generic Report Table Styles */
        .generic-report-card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 30px; }
        .generic-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .generic-table th { background: #f8f9fa; padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 700; color: #666; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .generic-table td { padding: 12px 15px; font-size: 14px; border-bottom: 1px solid #eee; }
        .generic-table tr:hover { background: #fcfcfc; }

        @media print {
            .sidebar, .top-header, .action-buttons { display: none !important; }
            .main-content { margin-left: 0 !important; }
            .main-area { padding: 0 !important; }
            .rog-paper { box-shadow: none !important; margin: 0 !important; width: 100% !important; max-width: none !important; }
            .generic-report-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="{{ isset($embedded) && $embedded ? 'embedded-report' : '' }}">
<div class="container">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon"><img src="{{ asset('images/logo.png') }}" alt="DSSC Logo"></div>
            <h3>CRMS</h3>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-title">Main Menu</li>
            <li><a href="{{ route('dashboard') }}"><span class="sidebar-menu-icon">📊</span> Dashboard</a></li>
            <li class="sidebar-title" style="margin-top: 20px;">Analysis</li>
            <li><a href="{{ route('faculty.reports') }}" class="active"><span class="sidebar-menu-icon">📈</span> Reports</a></li>
        </ul>
        <div class="sidebar-logout">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="btn-logout">Sign Out</button></form>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <div class="header-left">
                <div class="header-brand-icon"><img src="{{ asset('images/logo.png') }}" alt="DSSC Logo"></div>
                <div class="header-brand"><h2>DSSC</h2><p>CRMS</p></div>
            </div>
            <div style="font-size: 14px; font-weight: 600; color: white; margin-right: 30px;">{{ Auth::user()->name }}</div>
        </header>

        <div class="main-area">
            <div class="action-buttons" style="margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                @if(empty($embedded) || !$embedded)
                    <a href="{{ route('faculty.reports') }}" style="display: inline-block; padding: 10px 20px; background: #1e3c72; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px;">← Back to Reports List</a>
                @endif
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.print()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 14px;">🖨️ Print Report</button>
                    <a href="{{ route('faculty.reports.download', $report) }}" style="display: inline-block; padding: 10px 20px; background: #2a5298; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px;">⬇ Download CSV</a>
                </div>
            </div>

            @php
                $lines = explode("\n", trim($report->content));
                $headers = [];
                $data = [];
                
                if ($report->report_type === 'performance') {
                    // For performance reports, find the actual data table by looking for "Grade,Count,Percentage,Total Students" line
                    foreach ($lines as $key => $line) {
                        if (stripos($line, 'Grade') !== false && stripos($line, 'Count') !== false) {
                            // Found the header line
                            $headers = str_getcsv($line);
                            // Get remaining lines as data
                            $dataLines = array_slice($lines, $key + 1);
                            $data = array_map('str_getcsv', array_filter($dataLines, fn($l) => !empty(trim($l))));
                            break;
                        }
                    }
                } else {
                    // For other reports, parse normally
                    if (count($lines) > 0) {
                        $headers = str_getcsv(array_shift($lines));
                        $data = array_map('str_getcsv', $lines);
                    }
                }
                
                $isGradeReport = $report->report_type === 'grade';
                $isPerformanceReport = $report->report_type === 'performance';
                $isAttendanceReport = $report->report_type === 'attendance';
            @endphp

            @if($isGradeReport)
                @php
                    $subjectCode = 'N/A';
                    $subjectName = 'N/A';
                    $semester = '1';
                    $schoolYear = '2025-2026';
                    $section = 'N/A';
                    $instructorName = $report->user->name ?? Auth::user()->name;
                    $courseYearSection = 'N/A';

                    $normalizeCourseYearValue = function ($value) {
                        if (is_array($value)) {
                            return implode(', ', array_filter(array_map(fn($v) => trim((string)$v), $value)));
                        }
                        if (!is_string($value)) {
                            return '';
                        }
                        $trim = trim($value);
                        if ($trim === '') {
                            return '';
                        }
                        $json = json_decode($trim, true);
                        if (is_array($json)) {
                            return implode(', ', array_filter(array_map(fn($v) => trim((string)$v), $json)));
                        }
                        return $trim;
                    };

                    $subjectCodeIndex = array_search('Subject Code', $headers);
                    $subjectNameIndex = array_search('Subject Name', $headers);
                    $studentNameIndex = array_search('Student Name', $headers);
                    $studentIdIndex = array_search('Student ID', $headers);
                    $programIndex = array_search('Program', $headers);
                    $yearLevelIndex = array_search('Year Level', $headers);
                    $gradeIndex = array_search('Grade Point', $headers);
                    $letterGradeIndex = array_search('Letter Grade', $headers);
                    $semesterIndex = array_search('Semester', $headers);
                    $schoolYearIndex = array_search('School Year', $headers);
                    $sectionIndex = array_search('Section', $headers);

                    if (!empty($data[0])) {
                        if ($subjectCodeIndex !== false && !empty($data[0][$subjectCodeIndex])) {
                            $subjectCode = $data[0][$subjectCodeIndex];
                        }
                        if ($subjectNameIndex !== false && !empty($data[0][$subjectNameIndex])) {
                            $subjectName = $data[0][$subjectNameIndex];
                        }
                        if ($semesterIndex !== false && !empty($data[0][$semesterIndex])) {
                            $semester = $data[0][$semesterIndex];
                        }
                        if ($schoolYearIndex !== false && !empty($data[0][$schoolYearIndex])) {
                            $schoolYear = $data[0][$schoolYearIndex];
                        }
                        if ($sectionIndex !== false && !empty($data[0][$sectionIndex])) {
                            $section = $normalizeCourseYearValue($data[0][$sectionIndex]);
                        }

                        $course = ($programIndex !== false && !empty($data[0][$programIndex])) ? $data[0][$programIndex] : null;
                        $yearLevel = ($yearLevelIndex !== false && !empty($data[0][$yearLevelIndex])) ? $data[0][$yearLevelIndex] : null;
                        $isGenericCourse = empty($course) || strcasecmp(trim($course), 'General Studies') === 0;

                        if (!$isGenericCourse && $course && $yearLevel) {
                            $courseYearSection = $course . '-' . $yearLevel;
                        } elseif (!$isGenericCourse && $course) {
                            $courseYearSection = $course;
                        } elseif (!empty($section)) {
                            $courseYearSection = $section;
                        } else {
                            $courseYearSection = 'N/A';
                        }

                        // If the report header still shows a generic course, try to derive from row data or student record
                        if ($isGenericCourse || empty($yearLevel) || trim($yearLevel) === '1' || strcasecmp(trim($courseYearSection), 'General Studies-1') === 0) {
                            foreach ($data as $candidateRow) {
                                if (!is_array($candidateRow) || count($candidateRow) < 3) {
                                    continue;
                                }
                                $rowProgram = ($programIndex !== false ? trim((string)($candidateRow[$programIndex] ?? '')) : '');
                                $rowYear = ($yearLevelIndex !== false ? trim((string)($candidateRow[$yearLevelIndex] ?? '')) : '');
                                $rowSection = ($sectionIndex !== false ? $normalizeCourseYearValue($candidateRow[$sectionIndex] ?? '') : '');
                                $rowStudentId = ($studentIdIndex !== false ? trim((string)($candidateRow[$studentIdIndex] ?? '')) : '');

                                if ($rowSection && strcasecmp($rowSection, 'General Studies') !== 0) {
                                    $courseYearSection = $rowSection;
                                    break;
                                }
                                if ($rowProgram && strcasecmp($rowProgram, 'General Studies') !== 0 && $rowYear && trim($rowYear) !== '1') {
                                    $courseYearSection = $rowProgram . '-' . $rowYear;
                                    break;
                                }
                                if ($rowProgram && strcasecmp($rowProgram, 'General Studies') !== 0) {
                                    $courseYearSection = $rowProgram;
                                }

                                if ($rowStudentId) {
                                    $studentRecord = \App\Models\Student::where('student_id', $rowStudentId)->first();
                                    if ($studentRecord && $studentRecord->program && strcasecmp(trim($studentRecord->program), 'General Studies') !== 0) {
                                        $courseYearSection = trim($studentRecord->program);
                                        if ($studentRecord->year_level && trim($studentRecord->year_level) !== '1') {
                                            $courseYearSection .= '-' . trim($studentRecord->year_level);
                                        }
                                        break;
                                    }
                                }
                            }
                        }

                        if ((strcasecmp(trim($courseYearSection), 'General Studies') === 0 || strcasecmp(trim($courseYearSection), 'General Studies-1') === 0) && !empty($section)) {
                            $courseYearSection = $section;
                        }
                    }
                @endphp

                <div class="rog-paper">
                    <div class="draft-watermark">DRAFT</div>
                    <div class="rog-header">
                        <h1>Report of Grades</h1>
                        <div class="rog-meta">
                            <div class="rog-meta-item"><span>Subject Code:</span> {{ $subjectCode }}</div>
                            <div class="rog-meta-item"><span>Semester:</span> {{ $semester }}</div>
                            <div class="rog-meta-item"><span>Subject Description:</span> {{ $subjectName }}</div>
                            <div class="rog-meta-item"><span>School Year:</span> {{ $schoolYear }}</div>
                            <div class="rog-meta-item"><span>Course, Year & Section:</span> {{ $courseYearSection }}</div>
                            <div class="rog-meta-item"><span>Room No:</span> -</div>
                            <div class="rog-meta-item"><span>Time/Day:</span> -</div>
                            <div class="rog-meta-item"><span>Instructor:</span> {{ $instructorName }}</div>
                        </div>
                    </div>
                    <table class="rog-table">
                        <thead>
                            <tr><th style="width: 40px;">#</th><th>Name of Student</th><th>Course & Year</th><th style="width: 120px;">Final Grade</th><th style="width: 120px;">Remarks</th></tr>
                        </thead>
                        <tbody>
                            @forelse($data as $index => $row)
                                @php
                                    if (count($row) < 3) continue;
                                    $studentName = $row[$studentNameIndex] ?? 'Unknown';
                                    $studentId = ($studentIdIndex = array_search('Student ID', $headers)) !== false ? ($row[$studentIdIndex] ?? null) : null;
                                    $program = $row[$programIndex] ?? null;
                                    $yearLevel = $row[$yearLevelIndex] ?? null;
                                    $rowSection = ($sectionIndex !== false ? $normalizeCourseYearValue($row[$sectionIndex] ?? '') : '');

                                    // Prefer actual stored student data if program is generic or missing.
                                    if (empty($program) || strcasecmp(trim($program), 'General Studies') === 0) {
                                        $studentRecord = null;
                                        if ($studentId) {
                                            $studentRecord = \App\Models\Student::where('student_id', trim((string)$studentId))->first();
                                        }
                                        if ($studentRecord) {
                                            $program = $studentRecord->program ?: $program;
                                        }
                                    }

                                    if (empty($yearLevel)) {
                                        if (!isset($studentRecord)) {
                                            if ($studentId) {
                                                $studentRecord = \App\Models\Student::where('student_id', trim((string)$studentId))->first();
                                            }
                                        }
                                        if (!empty($studentRecord->year_level)) {
                                            $yearLevel = $studentRecord->year_level;
                                        }
                                    }

                                    $program = $program ?: 'N/A';
                                    $yearLevel = $yearLevel ?: '1';

                                    $grade = $row[$gradeIndex] ?? 'N/A';
                                    $letterGrade = $row[$letterGradeIndex] ?? 'N/A';

                                    if ($letterGrade === 'INC') $displayGrade = 'INC / -';
                                    elseif ($letterGrade === 'DR') $displayGrade = 'DR';
                                    elseif ($letterGrade === 'W') $displayGrade = 'W';
                                    else $displayGrade = $grade;

                                    $remarks = 'N/A';
                                    if ($letterGrade == 'F') $remarks = 'Failed';
                                    elseif (in_array($letterGrade, ['A', 'B', 'C', 'D'])) $remarks = 'Passed';
                                    elseif ($letterGrade == 'INC') $remarks = 'Incomplete';
                                    elseif ($letterGrade == 'DR') $remarks = 'Dropped';
                                    elseif ($letterGrade == 'W') $remarks = 'Withdrawn';

                                    if (!empty($rowSection) && strcasecmp($rowSection, 'General Studies') !== 0) {
                                        $courseYearDisplay = $rowSection;
                                    } else {
                                        $courseYearDisplay = ($program !== 'N/A' && $yearLevel !== '1') ? $program . '-' . $yearLevel : $program;
                                    }
                                @endphp
                                <tr><td>{{ $index + 1 }}</td><td>{{ $studentName }}</td><td>{{ $courseYearDisplay }}</td><td>{{ $displayGrade }}</td><td>{{ $remarks }}</td></tr>
                            @empty
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: #999;">No student records found.</td></tr>
                            @endforelse
                            @php $currentCount = count(array_filter($data, fn($r) => count($r) >= 3)); @endphp
                            @for($i = $currentCount; $i < 25; $i++)
                                <tr><td>{{ $i + 1 }}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                            @endfor
                        </tbody>
                    </table>
                    <div class="rog-footer"><div class="signature-line"><div class="signature-name">{{ $instructorName }}</div><div class="signature-title">Instructor</div></div></div>
                </div>
            @elseif($isAttendanceReport)
                <div class="generic-report-card">
                    <div style="margin-bottom: 25px; border-bottom: 2px solid #1e3c72; padding-bottom: 15px;">
                        <h1 style="font-size: 24px; color: #1e3c72; margin-bottom: 5px;">Attendance Summary Report</h1>
                        <p style="color: #666; font-size: 14px;">Generated on {{ $report->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>Total Sessions</th>
                                    <th>Present</th>
                                    <th>Late</th>
                                    <th>Absent</th>
                                    <th>Excused</th>
                                    <th>Attendance (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $attendanceRows = $analytics['attendanceTrends'] ?? []; @endphp
                                @forelse($attendanceRows as $row)
                                    <tr>
                                        <td>{{ $row['code'] ?? 'N/A' }}</td>
                                        <td>{{ $row['name'] ?? 'N/A' }}</td>
                                        <td>{{ $row['total'] ?? 0 }}</td>
                                        <td>{{ $row['present'] ?? 0 }}</td>
                                        <td>{{ $row['late'] ?? 0 }}</td>
                                        <td>{{ $row['absent'] ?? 0 }}</td>
                                        <td>{{ $row['excused'] ?? 0 }}</td>
                                        <td>{{ number_format($row['attendance_percent'] ?? $row['average'] ?? 0, 1) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" style="text-align:center; padding:40px; color:#999;">No attendance data found in database.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif($isPerformanceReport)
                @php
                    $performanceData = [];
                    $totalStudents = 0;
                    
                    if (isset($detailedGradeDistribution) && is_array($detailedGradeDistribution)) {
                        $grades = $detailedGradeDistribution['grades'] ?? [];
                        $totalStudents = $detailedGradeDistribution['total_students'] ?? 0;
                        
                        foreach ($grades as $gradeData) {
                            $performanceData[] = [
                                'grade' => $gradeData['grade'],
                                'count' => $gradeData['count'],
                                'percentage' => $gradeData['percentage'],
                            ];
                        }
                    }
                @endphp
                <div class="generic-report-card">
                    <div style="margin-bottom: 30px; text-align: center; border-bottom: 2px solid #1e3c72; padding-bottom: 20px;">
                        <h1 style="font-size: 24px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">Student Performance Summary</h1>
                        <p style="color: #666; font-size: 13px;">Generated on {{ $report->created_at->format('M d, Y • h:i A') }}</p>
                    </div>

                    @if(count($performanceData) > 0)
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                            @foreach($performanceData as $item)
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <span style="font-weight: 700; color: #1e3c72; font-size: 20px;">Grade {{ $item['grade'] }}</span>
                                        <span style="background: #1e3c72; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 700;">{{ $item['percentage'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; margin-bottom: 15px; overflow: hidden;">
                                        <div style="width: {{ $item['percentage'] }}%; height: 100%; background: linear-gradient(90deg, #1e3c72, #2a5298); border-radius: 6px;"></div>
                                    </div>
                                    <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
                                        <strong>{{ $item['count'] }}</strong> out of <strong>{{ $totalStudents }}</strong> students received grade <strong>{{ $item['grade'] }}</strong>
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-radius: 10px; padding: 25px; text-align: center; margin-bottom: 20px;">
                            <p style="margin: 0; font-size: 15px; font-weight: 600;">
                                Total Students Evaluated: <span style="font-size: 24px; font-weight: 800;">{{ $totalStudents }}</span>
                            </p>
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 10px; color: #999;">
                            <p style="margin: 0; font-size: 14px;">No performance data available.</p>
                        </div>
                    @endif
                </div>
            @else
                {{-- Dynamic Table View for Other Reports --}}
                <div class="generic-report-card">
                    <div style="margin-bottom: 25px; border-bottom: 2px solid #1e3c72; padding-bottom: 15px;">
                        <h1 style="font-size: 24px; color: #1e3c72; margin-bottom: 5px;">{{ $report->title }}</h1>
                        <p style="color: #666; font-size: 14px;">Generated on {{ $report->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="generic-table">
                            <thead>
                                <tr>
                                    @foreach($headers as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $row)
                                    @if(count($row) >= count($headers))
                                        <tr>
                                            @foreach($row as $cell)
                                                <td>{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @empty
                                    <tr><td colspan="{{ count($headers) }}" style="text-align: center; padding: 40px; color: #999;">No data available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
