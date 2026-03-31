@extends('layouts.faculty')

@section('title', 'Dashboard - DSSC CRMS')

@push('styles')
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-box {
            background: white;
            border-radius: 12px;
            padding: 28px 32px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }
        .modal-box h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        .modal-box p {
            font-size: 13px;
            color: #666;
            margin-bottom: 20px;
        }
        .modal-spinner {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            border: 4px solid #e9e9e9;
            border-top-color: #1e3c72;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }
        .modal-actions .btn {
            min-width: 120px;
        }
        .modal-box.report-preview {
            max-width: 720px;
            width: 95%;
            text-align: left;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
        }
        .report-preview .modal-box-title {
            text-align: center;
            margin-bottom: 8px;
        }
        .report-preview .modal-box-subtitle {
            text-align: center;
            margin-bottom: 16px;
        }
        .report-content-view {
            flex: 1;
            min-height: 200px;
            max-height: 50vh;
            overflow: auto;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            font-size: 12px;
            line-height: 1.5;
            font-family: 'Consolas', 'Monaco', monospace;
            white-space: pre-wrap;
            word-break: break-all;
            color: #333;
        }
        .report-content-view:empty::before {
            content: 'No content to display';
            color: #999;
        }
        .modal-actions {
            flex-shrink: 0;
        }

        /* Upload preview modal – full Excel table (lab activities, non-lab, grades) */
        .excel-preview-modal .modal-box {
            max-width: 96%;
            width: 1400px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            text-align: left;
        }
        .excel-preview-modal .modal-box h3 { text-align: center; }
        .excel-preview-modal .modal-box-subtitle { text-align: center; margin-bottom: 12px; }
        .excel-preview-table-wrap {
            flex: 1;
            min-height: 200px;
            max-height: 65vh;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
        }
        .excel-preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .excel-preview-table th,
        .excel-preview-table td {
            border: 1px solid #dee2e6;
            padding: 6px 10px;
            text-align: left;
            white-space: nowrap;
        }
        .excel-preview-table th {
            background: #1e3c72;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .excel-preview-table tr:nth-child(even) { background: #f8f9fa; }
        .excel-preview-table tr:hover { background: #e9ecef; }
        .excel-preview-modal .modal-actions { margin-top: 16px; justify-content: center; gap: 12px; }

        /* Class Records section - scrollable content only */
        #records-section {
            position: fixed;
            top: 80px;
            left: 227px;
            right: 0;
            bottom: 0;
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 20px 30px;
            overflow-y: auto;
            z-index: 100;
            background: white;
        }
        #records-section .section-header {
            position: sticky;
            top: 0;
            background: white;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            z-index: 10;
        }
        #records-section .excel-preview-table-wrap {
            max-height: calc(100vh - 200px);
        }
        
        @media (max-width: 1200px) {
            .subject-grid, .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .subject-grid, .report-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }

            #records-section {
                left: 200px;
                top: 80px;
            }
        }

        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        
        .dashboard-header p {
            font-size: 14px;
            color: #666;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-title {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .stat-card-value {
            font-size: 36px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 12px;
        }
        
        .stat-card-change {
            font-size: 12px;
            color: #28a745;
            font-weight: 600;
        }
        
        .stat-card-icon {
            text-align: right;
            font-size: 24px;
            color: #2a5298;
        }
        
        /* Section */
        .section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
        }
        
        .section-subtitle {
            font-size: 13px;
            color: #999;
            margin-top: 4px;
        }
        
        .view-all {
            color: #2a5298;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        /* Subject Cards */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .subject-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #eee;
        }
        
        .subject-code {
            font-size: 12px;
            color: #2a5298;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .subject-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 12px;
        }
        
        .subject-info {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            background: #e9e9e9;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: #28a745;
        }
        
        .progress-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .subject-buttons {
            display: flex;
            gap: 10px;
        }
        
        .subject-btn {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #1e3c72;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .subject-btn:hover {
            border-color: #2a5298;
            background: #f0f6ff;
        }
        
        /* Charts Section */
        .charts-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .chart-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .chart-tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .chart-tab {
            padding: 12px 0;
            border: none;
            background: none;
            color: #999;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .chart-tab.active {
            color: #2a5298;
            border-bottom-color: #2a5298;
        }
        
        .chart-content {
            display: none;
        }

        /* Report Grid */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        .report-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #eee;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            border-color: #2a5298;
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .report-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .report-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 8px;
        }
        
        .report-desc {
            font-size: 11px;
            color: #999;
            margin-bottom: 15px;
            height: 32px;
            overflow: hidden;
        }
    </style>
@endpush

@section('faculty-content')
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>📊 Dashboard Overview</h1>
        <p>Welcome back, <strong>{{ Auth::user()->name }}</strong>. Monitor your class performance and student progress in real-time.</p>
        @if(session('status'))
            <div style="margin-top:10px;padding:10px;background:#e6ffed;border:1px solid #b7f5c7;color:#27632a;border-radius:6px;">{{ session('status') }}</div>
        @endif
        <div class="dashboard-actions" style="margin-top:25px;">
            <form method="POST" action="{{ route('faculty.records.upload') }}" enctype="multipart/form-data" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;" id="uploadFormDash1">
                @csrf

                <div style="background:#e3f2fd;padding:12px 18px;border-radius:6px;flex:1;min-width:200px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600; color:#1e3c72; margin:0;">
                        <span style="font-size:20px;">📤</span>
                        <span>Upload Excel File</span>
                        <input type="file" name="file" id="dashboardFileInput1" accept=".csv, .xlsx, .xls" style="display:none;">
                    </label>
                    <div style="font-size:11px;color:#666;margin-top:4px;margin-left:30px;">Lab Activities, Non-Lab, Quiz & Exams</div>
                </div>

                <button type="button" class="btn btn-primary" onclick="location.href='{{ route('faculty.reports') }}'" style="font-size:14px; padding:12px 24px;">📊 Generate Analytics Reports</button>
            </form>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-container" style="margin-bottom: {{ ($totalRecords == 0 && $totalStudents == 0 && $activeSubjects == 0) ? '0px' : '30px' }};">
        <div class="stat-card" style="border-left: 4px solid #2a5298;">
            <div class="stat-card-title">📄 Total Uploaded Records</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex:1;">
                    <div class="stat-card-value" style="color: #2a5298;">{{ $totalRecords }}</div>
                    @if($totalRecords > 0)
                        <div class="stat-card-change" style="color: #28a745;">✓ Data imported</div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">All time submissions</div>
                    @else
                        <div style="font-size: 12px; color: #666; margin-top: 4px;">No data uploaded yet<br><em>Import your first Excel file to get started</em></div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="stat-card" style="border-left: 4px solid #28a745;">
            <div class="stat-card-title">👥 Total Students</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex:1;">
                    <div class="stat-card-value" style="color: #28a745;">{{ $totalStudents }}</div>
                    @if($totalStudents > 0)
                        <div class="stat-card-change" style="color: #28a745;">Enrolled students</div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">Active across all courses</div>
                    @else
                        <div style="font-size: 12px; color: #666; margin-top: 4px;">No students assigned yet<br><em>Upload records to see enrolled students</em></div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="stat-card" style="border-left: 4px solid #d4af37;">
            <div class="stat-card-title">📚 Active Subjects</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="flex:1;">
                    <div class="stat-card-value" style="color: #1e3c72;">{{ $activeSubjects }}</div>
                    @if($activeSubjects > 0)
                        <div class="stat-card-change" style="color: #999;">{{ Auth::user()->department ?? 'Faculty' }}</div>
                        <div style="font-size: 11px; color: #999; margin-top: 4px;">Current semester</div>
                    @else
                        <div style="font-size: 12px; color: #666; margin-top: 4px;">No active subjects<br><em>Create a subject to get started</em></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($totalRecords == 0 && $totalStudents == 0 && $activeSubjects == 0)
    <div style="background: linear-gradient(135deg, #f5f7fa 0%, #e8f0f7 100%); border: 1px solid #d4e1f0; border-radius: 8px; padding: 30px; text-align: center; margin-bottom: 30px;">
        <h3 style="font-size: 18px; color: #1e3c72; margin: 0 0 12px 0;">📊 Get Started with Your Analytics</h3>
        <p style="color: #666; font-size: 14px; margin: 0 0 20px 0;">Import your Excel file to start tracking student performance data. Your file should contain columns for Student ID, Lab Activities, Non-Lab Activities, Quiz scores, and Exam results.</p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <form method="POST" action="{{ route('faculty.records.upload') }}" enctype="multipart/form-data" style="display:flex; gap:15px; align-items:center;" id="uploadFormDash2">
                @csrf
                <label class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:14px;">
                    <span>📤 Choose Excel File</span>
                    <input type="file" name="file" id="dashboardFileInput2" accept=".csv, .xlsx, .xls" style="display:none;">
                </label>
            </form>
            <a href="{{ route('faculty.subjects') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-size:14px;">📚 Create Subject</a>
        </div>
    </div>
    @endif
    
    <!-- Subject Overview -->
    <div class="section">
        <div class="section-header">
            <div>
                <div class="section-title">Subject Overview</div>
                <div class="section-subtitle">Current semester performance across your courses</div>
            </div>
            <a href="{{ route('faculty.subjects') }}" class="view-all">View All Subjects →</a>
        </div>
        
        <div class="subject-grid">
            @forelse($subjects as $subject)
            <div class="subject-card">
                <div class="subject-code">{{ $subject['code'] }}</div>
                <div class="subject-name">{{ $subject['name'] }}</div>
                <div class="subject-info">{{ $subject['studentCount'] }} Students • Active</div>
                
                <div class="progress-label">
                    <span>Pass Rate</span>
                    <span style="font-weight: 700; color: #28a745;">{{ $subject['passRate'] }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $subject['passRate'] }}%; background: #28a745;"></div>
                </div>
                
                <div class="progress-label">
                    <span>Attendance</span>
                    <span style="font-weight: 700; color: #1e3c72;">{{ $subject['attendance'] }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $subject['attendance'] }}%; background: #1e3c72;"></div>
                </div>
                
                <div class="subject-buttons">
                    <button class="subject-btn" onclick="alert('View details for {{ $subject['code'] }}')">✓ Details</button>
                    <button class="subject-btn" onclick="alert('Export grades for {{ $subject['code'] }}')">⬇ Grades</button>
                </div>
            </div>
            @empty
            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: #999;">
                <p>No active subjects found. Create a subject to get started.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Grade Reports & Analytics Section -->
    <div id="report-generator" class="section" style="margin-bottom: 30px;">
        <div class="section-header">
            <h3 class="section-title">Grade Reports & Analytics</h3>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 12px; color: #64748b;">Generate Official Reports of Grades (ROG) for your subjects</span>
                <span style="cursor: help; font-size: 18px; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; background: #e2e8f0; border-radius: 50%; color: #64748b; font-weight: bold;" title="Reports of Grades (ROG) - Official academic performance documentation">?</span>
            </div>
        </div>
        
        <!-- Report Types Information -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
            <div style="background: #f8fafc; border-left: 4px solid #0ea5e9; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #0c4a6e; margin-bottom: 6px; font-size: 13px;">📊 Student Performance Report</div>
                <div style="font-size: 12px; color: #475569;">Grade distribution showing how many students received each grade with percentage breakdown</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #10b981; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #065f46; margin-bottom: 6px; font-size: 13px;">📝 Student Grade Report</div>
                <div style="font-size: 12px; color: #475569;">Detailed grade records for each student including final grades and GPA calculations</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #78350f; margin-bottom: 6px; font-size: 13px;">📈 Pass/Fail Analysis</div>
                <div style="font-size: 12px; color: #475569;">Summary of pass and fail rates per subject with percentage calculations</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #8b5cf6; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #4c1d95; margin-bottom: 6px; font-size: 13px;">📋 Attendance Summary</div>
                <div style="font-size: 12px; color: #475569;">Complete attendance records with presence and absence tracking</div>
            </div>
        </div>
        
        <form id="analyticsForm" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; align-items: flex-end; margin-bottom: 24px;">
            @csrf
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;">📑 Select Report Type</label>
                <select name="report_type" id="report_type" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <option value="performance">Student Performance Report (ROG)</option>
                    <option value="grade">Student Grade Report</option>
                    <option value="passFailAnalysis">Pass/Fail Analysis</option>
                    <option value="attendance">Attendance Summary</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;">Subject (Optional)</label>
                <select name="subject_id" id="subject_id" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject['id'] }}">{{ $subject['code'] }} - {{ $subject['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="generateSelectedReport()" style="background: #1e3c72; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; justify-content: center; width: 100%;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Generate Report
            </button>
        </form>
    </div>
    
    <!-- Analytics Section -->
    <!-- Modals -->
    <div id="generatingModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-spinner"></div>
            <h3>Generating Analytics...</h3>
            <p>Please wait while we process the student records and update your dashboard performance metrics.</p>
        </div>
    </div>

    @if($showExcelModal ?? false)
    <div id="excelPreviewModal" class="modal-overlay excel-preview-modal show">
        <div class="modal-box">
            <h3>Excel Import Preview</h3>
            <p class="modal-box-subtitle">Review the imported data before generating analytics</p>
            
            <div class="excel-preview-table-wrap">
                <table class="excel-preview-table">
                    <thead>
                        <tr>
                            @foreach($excelPreviewData['headers'] ?? [] as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excelPreviewData['rows'] ?? [] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    @php
                                        $displayCell = $cell;
                                        if (is_numeric($cell)) {
                                            $cellStr = (string)$cell;
                                            if (strpos($cellStr, '.') !== false) {
                                                $decimals = strlen(explode('.', $cellStr)[1]);
                                                if ($decimals > 2) {
                                                    $displayCell = number_format((float)$cell, 2, '.', '');
                                                } else {
                                                    // preserve original decimal precision as in source
                                                    $displayCell = rtrim(rtrim($cellStr, '0'), '.');
                                                    if ($displayCell === '' || $displayCell === '-') {
                                                        $displayCell = $cellStr;
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    <td>{{ $displayCell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="excelPreviewCancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="excelPreviewGenerate">Confirm & Generate Analytics</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Unsupported File Modal -->
    <div id="unsupportedFileModalDash" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);">
            <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">Unsupported File Type</h3>
            <p style="font-size: 14px; color: #666; margin-bottom: 24px;">Only CSV, XLS, and XLSX files are supported. Please select a valid file and try again.</p>
            <button type="button" onclick="document.getElementById('unsupportedFileModalDash').style.display='none'" style="background: #1e3c72; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">OK</button>
        </div>
    </div>

    <script>
        // File validation for dashboard uploads
        const supportedExtensions = ['csv', 'xls', 'xlsx'];
        const unsupportedFileModalDash = document.getElementById('unsupportedFileModalDash');
        
        function validateDashboardFile(fileInputId, formId) {
            const fileInput = document.getElementById(fileInputId);
            const form = document.getElementById(formId);
            
            fileInput?.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    const fileExtension = fileName.split('.').pop().toLowerCase();
                    
                    if (!supportedExtensions.includes(fileExtension)) {
                        unsupportedFileModalDash.style.display = 'flex';
                        this.value = '';
                    } else {
                        form.submit();
                    }
                }
            });
        }
        
        validateDashboardFile('dashboardFileInput1', 'uploadFormDash1');
        validateDashboardFile('dashboardFileInput2', 'uploadFormDash2');
    </script>
@endsection

@push('scripts')
    <script>
        function showChart(chartId, btn) {
            document.querySelectorAll('.chart-content').forEach(c => c.style.display = 'none');
            document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(chartId + '-chart').style.display = 'block';
            btn.classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // After Excel upload: show preview modal (lab activities, non-lab, grades)
            @if($showExcelModal ?? false)
            var excelPreviewEl = document.getElementById('excelPreviewModal');
            if (excelPreviewEl) excelPreviewEl.classList.add('show');
            @endif
            var excelPreviewModal = document.getElementById('excelPreviewModal');
            var excelPreviewGenerate = document.getElementById('excelPreviewGenerate');
            var excelPreviewCancel = document.getElementById('excelPreviewCancel');
            if (excelPreviewGenerate) {
                excelPreviewGenerate.addEventListener('click', async function() {
                    var generatingModal = document.getElementById('generatingModal');
                    if (generatingModal) generatingModal.classList.add('show');
                    if (excelPreviewModal) excelPreviewModal.classList.remove('show');

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch('{{ route("faculty.analytics.generate") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ report_type: 'comprehensive' }),
                        });

                        const result = await response.json();
                        if (result.success) {
                            showNotification('Analytics generated and data saved successfully!');
                        } else {
                            showNotification('Failed to generate analytics: ' + (result.error || 'Unknown error'), 'error');
                        }
                    } catch (e) {
                        showNotification('Error connecting to server', 'error');
                        console.error(e);
                    } finally {
                        if (generatingModal) generatingModal.classList.remove('show');
                        setTimeout(() => { location.reload(); }, 1000);
                    }
                });
            }
            if (excelPreviewCancel) excelPreviewCancel.addEventListener('click', async function() {
                // Close modal first
                if (excelPreviewModal) excelPreviewModal.classList.remove('show');
                
                // Clear the session data and file on backend
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    await fetch('{{ route("faculty.records.cancel-preview") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                    });
                } catch (e) {
                    console.error('Error cancelling preview:', e);
                }
                
                // Clear file input if it exists on the current page
                const fileInputs = document.querySelectorAll('input[type="file"][name="file"]');
                fileInputs.forEach(input => input.value = '');
            });

            // Charts
            const pfEl = document.getElementById('passFailChart');
            if (pfEl) {
                new Chart(pfEl, {
                    type: 'pie',
                    data: {
                        labels: ['Passed', 'Failed'],
                        datasets: [{
                            data: [{{ $totalPass ?? 0 }}, {{ $totalFail ?? 0 }}],
                            backgroundColor: ['#6EA8DA', '#D9A6A6'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            const aEl = document.getElementById('attendanceChart');
            if (aEl) {
                const attendanceRaw = {!! json_encode(is_array($attendanceTrends) ? $attendanceTrends : (method_exists($attendanceTrends, 'toArray') ? $attendanceTrends->toArray() : [])) !!};
                const labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                const palette = ['#8FB9E6', '#BFCFE2', '#C7D9EE', '#A9CDEB', '#DDEBF8', '#C8D6E0'];

                new Chart(aEl, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: attendanceRaw.map((t, i) => ({
                            label: t.code,
                            data: [t.week1 || 0, t.week2 || 0, t.week3 || 0, t.week4 || 0],
                            borderColor: palette[i % palette.length],
                            backgroundColor: palette[i % palette.length] + '22',
                            fill: true,
                            tension: 0.4
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100 }
                        }
                    }
                });
            }

        });

        function generateSelectedReport() {
            const reportType = document.getElementById('report_type').value;
            
            if (reportType === 'performance') {
                generatePerformanceReportModal();
            } else {
                showNotification(`${reportType} report generation coming soon!`, 'info');
            }
        }

        let performanceReportData = null;

        function generatePerformanceReportModal() {
            const modal = document.createElement('div');
            modal.id = 'perfReportModal';
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(15, 23, 42, 0.6); z-index: 2000;
                display: flex; align-items: center; justify-content: center;
                padding: 20px;
            `;

            const gradeData = {!! json_encode($gradeDistribution ?? ['1.75' => 2, '2.00' => 2, '2.25' => 1, '2.50' => 1, '2.75' => 1, '5.00' => 15]) !!};
            const totalStudents = Object.values(gradeData).reduce((sum, count) => sum + count, 0);
            
            let reportContent = `
                <div style="background: white; border-radius: 12px; padding: 28px;">
                    <div style="text-align: center; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0;">
                        <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin: 0 0 8px 0;">REPORT OF GRADES (ROG)</h3>
                        <p style="margin: 0; font-size: 13px; color: #64748b;">Student Performance Summary</p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-bottom: 24px;">
            `;

            Object.entries(gradeData).forEach(([grade, count]) => {
                const percentage = totalStudents > 0 ? ((count / totalStudents) * 100).toFixed(1) : 0;
                
                reportContent += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="font-weight: 700; color: #1e3c72; font-size: 18px;">Grade ${grade}</span>
                            <span style="background: #1e3c72; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700;">${percentage}%</span>
                        </div>
                        <div style="width: 100%; height: 10px; background: #e2e8f0; border-radius: 5px; margin-bottom: 12px; overflow: hidden;">
                            <div style="width: ${percentage}%; height: 100%; background: linear-gradient(90deg, #1e3c72, #2a5298); border-radius: 5px;"></div>
                        </div>
                        <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            <strong>${count}</strong> out of <strong>${totalStudents}</strong> students received grade <strong>${grade}</strong>
                        </p>
                    </div>
                `;
            });

            reportContent += `
                    </div>

                    <div style="background: #1e3c72; color: white; border-radius: 10px; padding: 20px; text-align: center;">
                        <p style="margin: 0; font-size: 14px; font-weight: 600;">
                            Total Students Evaluated: <span style="font-size: 18px; font-weight: 800;">${totalStudents}</span>
                        </p>
                    </div>
                </div>
            `;

            const box = document.createElement('div');
            box.style.cssText = `
                background: white; border-radius: 16px; max-width: 1100px;
                width: 100%; max-height: 90vh; display: flex; flex-direction: column;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            `;

            box.innerHTML = `
                <div style="padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #1e3c72; margin: 0;">Report of Grades (ROG) - Student Performance</h2>
                        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Generated on ${new Date().toLocaleString()}</p>
                    </div>
                    <button type="button" onclick="document.getElementById('perfReportModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">✕</button>
                </div>

                <div style="flex: 1; overflow-y: auto; padding: 32px; background: #f8fafc;">
                    ${reportContent}
                </div>

                <div style="padding: 20px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end; background: white;">
                    <button type="button" onclick="document.getElementById('perfReportModal').remove()" style="padding: 10px 24px; background: #f1f5f9; color: #1e3c72; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Close</button>
                    <button type="button" onclick="downloadDashboardReport('${JSON.stringify(gradeData).replace(/"/g, '&quot;')}', ${totalStudents})" style="padding: 10px 24px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Download CSV</button>
                </div>
            `;

            modal.appendChild(box);
            document.body.appendChild(modal);

            performanceReportData = {
                gradeData: gradeData,
                totalStudents: totalStudents,
                generatedDate: new Date().toLocaleString()
            };
        }

        function downloadDashboardReport(gradeDataStr, totalStudents) {
            const gradeData = JSON.parse(gradeDataStr);
            
            let csv = 'REPORT OF GRADES (ROG) - STUDENT PERFORMANCE\n';
            csv += `Generated: ${new Date().toLocaleString()}\n`;
            csv += `\n`;
            csv += `Grade,Count,Percentage,Total Students\n`;
            
            for (const [grade, count] of Object.entries(gradeData)) {
                const percentage = totalStudents > 0 ? ((count / totalStudents) * 100).toFixed(1) : 0;
                csv += `${grade},${count},${percentage}%,${totalStudents}\n`;
            }
            
            csv += `\nTotal Students: ${totalStudents}\n`;

            const timestamp = new Date().toLocaleString();
            const filename = `ROG_Student_Performance_${Date.now()}.csv`;

            fetch('{{ route("faculty.reports.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    report_type: 'performance',
                    title: 'Student Performance Report (ROG)',
                    filename: filename,
                    content: csv
                })
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to save report');
                
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.setAttribute('href', URL.createObjectURL(blob));
                link.setAttribute('download', filename);
                link.click();
                
                showNotification('Report of Grades generated and saved successfully!');
                if (document.getElementById('perfReportModal')) {
                    document.getElementById('perfReportModal').remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to save report to database', 'error');
            });
        }

        function generateReport(reportType) {
            const timestamp = new Date().toLocaleString();
            let csv = `CRMS Report - ${timestamp}\n\n`;
            
            csv += reportType + " Report\n";
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', `${reportType}_report.csv`);
            link.click();
            showNotification(`${reportType} report generated!`);
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px;
                background: ${type === 'error' ? '#dc3545' : type === 'info' ? '#0dcaf0' : '#28a745'};
                color: white; padding: 15px 20px; border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 9999;
                font-weight: 500;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);
        }
    </script>
@endpush
