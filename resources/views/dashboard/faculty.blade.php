@extends('layouts.faculty_new', ['activePage' => 'dashboard'])

@section('title', 'Dashboard - DSSC CRMS')
@section('page_title', 'Dashboard')

@section('content')
    <!-- Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
            <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Total Class Records</div>
            <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ number_format($totalRecords) }}</div>
            <div style="font-size: 12px; color: #64748b;">Uploaded to system</div>
        </div>
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
            <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Total Students</div>
            <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ number_format($totalStudents) }}</div>
            <div style="font-size: 12px; color: #64748b;">Enrolled in your subjects</div>
        </div>
        <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
            <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Active Subjects</div>
            <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ $activeSubjects }}</div>
            <div style="font-size: 12px; color: #64748b;">Current semester</div>
        </div>
        <div style="background: #1e3c72; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: center; align-items: center; color: white; cursor: pointer; text-decoration: none;" onclick="location.href='{{ route('faculty.records') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px; margin-bottom: 10px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <div style="font-size: 14px; font-weight: 700; text-align: center;">Upload Excel Class Record</div>
        </div>
    </div>

    <!-- Service Cards -->
    <div class="service-grid">
        <div class="service-card">
            <div class="service-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="service-title">Class Records</div>
            <div class="service-description">Manage your student grades and attendance records efficiently.</div>
            <a href="{{ route('faculty.records') }}" class="service-btn">Manage Records</a>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <div class="service-title">Subject Load</div>
            <div class="service-description">View and manage your assigned subjects and student enrollment.</div>
            <a href="{{ route('faculty.subjects') }}" class="service-btn">View Subjects</a>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <div class="service-title">Logout</div>
            <div class="service-description">Safely end your session and sign out of CRMS.</div>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="service-btn" style="background: none; cursor: pointer; width: 100%;">Logout Now</button>
            </form>
        </div>
    </div>

    <!-- Subject Overview -->
    <div class="section" style="margin-bottom: 30px;">
        <div class="section-header">
            <h3 class="section-title">Subject Overview</h3>
            <div style="font-size: 12px; color: #64748b;">Detailed stats for your currently handled subjects</div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            @foreach($subjects as $subject)
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">{{ $subject['code'] }}</div>
                            <div style="font-size: 13px; color: #64748b; font-weight: 500;">{{ $subject['name'] }}</div>
                        </div>
                        <div style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">Active</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                        <div>
                            <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Students</div>
                            <div style="font-size: 16px; font-weight: 700; color: #1e3c72;">{{ $subject['studentCount'] }}</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Pass Rate</div>
                            <div style="font-size: 16px; font-weight: 700; color: #059669;">{{ $subject['passRate'] }}%</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Attendance</div>
                            <div style="font-size: 16px; font-weight: 700; color: #0891b2;">{{ $subject['attendance'] }}%</div>
                        </div>
                        <div style="text-align: right; display: flex; align-items: flex-end; justify-content: flex-end;">
                            <a href="{{ route('faculty.subjects') }}" style="font-size: 11px; color: #1e3c72; text-decoration: none; font-weight: 700;">View Details →</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Analytics Section -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 30px;">
        <!-- Pass/Fail Rate Chart -->
        <div class="section" style="background: white; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <div class="section-header">
                <div>
                    <h3 class="section-title" style="font-size: 18px; font-weight: 700; color: #1e3c72;">Performance Overview</h3>
                    <div class="section-subtitle" style="font-size: 13px; color: #999; margin-top: 4px;">Pass vs Fail rate per subject</div>
                </div>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="passFailChart"></canvas>
            </div>
        </div>

        <!-- Grade Distribution Chart -->
        <div class="section" style="background: white; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <div class="section-header">
                <h3 class="section-title" style="font-size: 18px; font-weight: 700; color: #1e3c72;">Grade Distribution</h3>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="gradeDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
        <!-- Attendance Trends -->
        <div class="section" style="background: white; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <div class="section-header">
                <div>
                    <h3 class="section-title" style="font-size: 18px; font-weight: 700; color: #1e3c72;">Attendance Trends</h3>
                    <div class="section-subtitle" style="font-size: 13px; color: #999; margin-top: 4px;">Average attendance per subject</div>
                </div>
            </div>
            <div style="height: 250px; position: relative;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <!-- Top Students -->
        <div class="section" style="background: white; border-radius: 8px; padding: 25px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 class="section-title" style="font-size: 18px; font-weight: 700; color: #1e3c72;">Top Performers</h3>
                    <div class="section-subtitle" style="font-size: 13px; color: #999; margin-top: 4px;">Based on cumulative GPA this semester</div>
                </div>
                <a href="{{ route('faculty.students') }}" style="font-size: 12px; color: #1e3c72; text-decoration: none; font-weight: 600;">View All</a>
            </div>
            <ul style="list-style: none; padding: 0;">
                @forelse($topStudents as $index => $performer)
                    <li style="display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                        <div style="width: 30px; font-size: 14px; font-weight: 700; color: #999;">#{{ $index + 1 }}</div>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600; color: #1e3c72;">{{ $performer['name'] }}</div>
                            <div style="font-size: 11px; color: #999;">{{ $performer['student_id'] }} • {{ $performer['program'] }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 16px; font-weight: 700; color: #1e3c72;">{{ $performer['gpa'] }}</div>
                            <div style="font-size: 11px; color: #999;">{{ $performer['recordCount'] }} records</div>
                        </div>
                    </li>
                @empty
                    <li style="padding: 20px; text-align: center; color: #999;">No performance data available.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Report Generator -->
    <div id="report-generator" class="section" style="margin-bottom: 30px;">
        <div class="section-header">
            <h3 class="section-title">Grade Reports & Analytics</h3>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 12px; color: #64748b;">Generate Official Reports of Grades (ROG) for your subjects</div>
                <span style="cursor: help; font-size: 18px; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; background: #e2e8f0; border-radius: 50%; color: #64748b; font-weight: bold;" title="Click a report type label below to learn more about it">?</span>
            </div>
        </div>
        
        <!-- Report Types Information -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
            <div style="background: #f8fafc; border-left: 4px solid #ec4899; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #831843; margin-bottom: 6px; font-size: 13px;">📊 Student Performance Report</div>
                <div style="font-size: 12px; color: #475569;">Grade distribution across all students showing how many students received each grade level with percentage breakdown</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #0ea5e9; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #0c4a6e; margin-bottom: 6px; font-size: 13px;">📝 Student Grade Report</div>
                <div style="font-size: 12px; color: #475569;">Detailed grade records for each student including final grades, letter grades, and GPA calculations</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #10b981; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #065f46; margin-bottom: 6px; font-size: 13px;">📈 Pass/Fail Analysis</div>
                <div style="font-size: 12px; color: #475569;">Summary of pass and fail rates per subject with percentage calculations</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #78350f; margin-bottom: 6px; font-size: 13px;">📋 Attendance Summary</div>
                <div style="font-size: 12px; color: #475569;">Complete attendance records with presence, late, and absence tracking</div>
            </div>
            <div style="background: #f8fafc; border-left: 4px solid #8b5cf6; padding: 16px; border-radius: 8px;">
                <div style="font-weight: 600; color: #4c1d95; margin-bottom: 6px; font-size: 13px;">🎓 Lecture & Lab Summaries</div>
                <div style="font-size: 12px; color: #475569;">Performance breakdown comparing lecture and laboratory components</div>
            </div>
        </div>
        
        <form id="analyticsForm" class="row g-3">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; align-items: flex-end;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;">📑 Select Report Type</label>
                    <select name="report_type" id="report_type" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="performance">Student Performance Report</option>
                        <option value="grade">Student Grade Report</option>
                        <option value="passFailAnalysis">Pass/Fail Analysis</option>
                        <option value="attendance">Attendance Summary</option>
                        <option value="lectureLabSummary">Lecture & Lab Summary</option>
                        <option value="comprehensive">Comprehensive Analytics (All Reports)</option>
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
                <button type="submit" style="background: #1e3c72; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; justify-content: center; width: 100%;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Generate Report
                </button>
            </div>
        </form>
    </div>
    
    <!-- Generated Reports Summary -->
    <div class="section-header">
        <h2 class="section-title">Your Generated Reports</h2>
    </div>
    
    @if(isset($facultyReports) && $facultyReports->isNotEmpty())
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); margin-bottom: 30px;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead style="background: #f8fafc; border-bottom: 2px solid #edf2f7;">
                        <tr>
                            <th style="padding: 12px 16px; text-align: left; font-weight: 700; color: #1e3c72;">Report Title</th>
                            <th style="padding: 12px 16px; text-align: left; font-weight: 700; color: #1e3c72;">Type</th>
                            <th style="padding: 12px 16px; text-align: left; font-weight: 700; color: #1e3c72;">Generated</th>
                            <th style="padding: 12px 16px; text-align: center; font-weight: 700; color: #1e3c72;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facultyReports->take(5) as $report)
                            <tr style="border-bottom: 1px solid #edf2f7;">
                                <td style="padding: 16px; font-weight: 600; color: #1e3c72;">{{ $report->title }}</td>
                                <td style="padding: 16px;">
                                    @switch($report->report_type)
                                        @case('grade')
                                            <span style="background: #dbeafe; color: #0c4a6e; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Student Grades</span>
                                            @break
                                        @case('passFailAnalysis')
                                            <span style="background: #dcfce7; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Pass/Fail</span>
                                            @break
                                        @case('attendance')
                                            <span style="background: #fef3c7; color: #78350f; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Attendance</span>
                                            @break
                                        @case('lectureLabSummary')
                                            <span style="background: #ede9fe; color: #4c1d95; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Lecture & Lab</span>
                                            @break
                                        @default
                                            <span style="background: #f3f4f6; color: #374151; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Comprehensive</span>
                                    @endswitch
                                </td>
                                <td style="padding: 16px; color: #64748b; font-size: 13px;">{{ $report->created_at->format('M d, Y H:i') }}</td>
                                <td style="padding: 16px; text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <a href="{{ route('faculty.reports.view', $report) }}" style="background: #f1f5f9; color: #1e3c72; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">View</a>
                                        <a href="{{ route('faculty.reports.download', $report) }}" style="background: #1e3c72; color: white; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Download</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($facultyReports->count() > 5)
                <div style="text-align: center; padding: 16px; color: #64748b; font-size: 12px;">
                    <a href="{{ route('faculty.reports') }}" style="color: #1e3c72; font-weight: 600; text-decoration: none;">View all {{ $facultyReports->count() }} reports →</a>
                </div>
            @endif
        </div>
    @else
        <div style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); text-align: center; margin-bottom: 30px;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📊</div>
            <div style="color: #64748b; font-size: 14px;">No reports generated yet. Use the Report Generator above to create your first report.</div>
        </div>
    @endif

    <!-- Modals -->
    @if($showExcelModal)
    <div id="excelPreviewModal" class="modal-overlay show">
        <div class="modal-box" style="max-width: 1000px; width: 95%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; text-align: left; padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #edf2f7;">
                <h3 style="margin: 0; color: #1e3c72;">Preview Imported Records</h3>
                <button onclick="document.getElementById('excelPreviewModal').classList.remove('show')" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            
            <div style="flex: 1; overflow: auto; margin-bottom: 20px; border: 1px solid #edf2f7; border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead style="background: #f8fafc; position: sticky; top: 0;">
                        <tr>
                            @foreach($excelPreviewData['headers'] as $header)
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #edf2f7; color: #1e3c72;">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excelPreviewData['rows'] as $row)
                            <tr style="border-bottom: 1px solid #edf2f7;">
                                @foreach($row as $cell)
                                    <td style="padding: 10px 12px; color: #334155;">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($excelPreviewData['rows']) > 0)
                    <div style="padding: 15px; text-align: center; color: #64748b; font-style: italic; background: #f8fafc;">
                        Total: {{ count($excelPreviewData['rows']) }} rows
                    </div>
                @endif
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button onclick="document.getElementById('excelPreviewModal').classList.remove('show')" style="padding: 10px 20px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; color: #64748b; cursor: pointer;">Cancel</button>
                <form id="confirmImportForm">
                    @csrf
                    <button type="submit" style="padding: 10px 24px; background: #1e3c72; border: none; border-radius: 8px; font-weight: 600; color: white; cursor: pointer;">Import All Records</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div id="generating-overlay" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-spinner"></div>
            <h3>Generating Report</h3>
            <p>Please wait while we process your request...</p>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pass/Fail Rate Chart
            const passFailCtx = document.getElementById('passFailChart');
            if (passFailCtx) {
                new Chart(passFailCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Passed', 'Failed'],
                        datasets: [{
                            data: [{{ $totalPass ?? 0 }}, {{ $totalFail ?? 0 }}],
                            backgroundColor: ['#6EA8DA', '#D9A6A6'],
                            hoverOffset: 8,
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, padding: 12 }
                            }
                        }
                    }
                });
            }

            // Grade Distribution Chart
            const gradeCtx = document.getElementById('gradeDistributionChart');
            if (gradeCtx) {
                const raw = {!! json_encode($gradeDistribution) !!};
                const grades = ['A','B','C','D','F'];
                const values = grades.map(k => Number(raw[k] || 0));
                const colors = ['#A9CDEB', '#C8DDE2', '#E7E6C8', '#EAD7C2', '#E9D6D6'];
                
                const ctxG = gradeCtx.getContext('2d');
                function grad(col) {
                    const g = ctxG.createLinearGradient(0, 0, 0, ctxG.canvas.height);
                    g.addColorStop(0, col + '');
                    g.addColorStop(0.6, 'rgba(255,255,255,0.6)');
                    g.addColorStop(1, 'rgba(255,255,255,0)');
                    return g;
                }

                new Chart(gradeCtx, {
                    type: 'bar',
                    data: {
                        labels: grades.map(g => 'Grade ' + g),
                        datasets: [{
                            label: 'Students',
                            data: values,
                            backgroundColor: colors.map(c => grad(c)),
                            borderColor: colors,
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 72
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(200,210,220,0.18)' } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    },
                    plugins: [{
                        id: 'barShadow',
                        beforeDatasetsDraw(chart) {
                            const ctx = chart.ctx;
                            ctx.save();
                            ctx.shadowColor = 'rgba(0,0,0,0.12)';
                            ctx.shadowBlur = 16;
                            ctx.shadowOffsetY = 8;
                        },
                        afterDatasetsDraw(chart) { chart.ctx.restore(); }
                    }]
                });
            }

            // Attendance Trends Chart
            const attendanceCtx = document.getElementById('attendanceChart');
            if (attendanceCtx) {
                const attendanceRaw = {!! json_encode($attendanceTrends) !!};
                const labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                const palette = ['#8FB9E6', '#BFCFE2', '#C7D9EE', '#A9CDEB', '#DDEBF8', '#C8D6E0'];

                const ctxA = attendanceCtx.getContext('2d');
                function createGradient(color) {
                    const g = ctxA.createLinearGradient(0, 0, 0, 300);
                    g.addColorStop(0, color + '33');
                    g.addColorStop(0.6, color + '1A');
                    g.addColorStop(1, 'rgba(255,255,255,0)');
                    return g;
                }

                const datasets = Array.isArray(attendanceRaw) ? attendanceRaw.slice(0, 6).map((t, i) => {
                    const base = palette[i % palette.length];
                    return {
                        label: t.code || ('Subject ' + (i+1)),
                        data: [t.week1 || 0, t.week2 || 0, t.week3 || 0, t.week4 || 0],
                        borderColor: base,
                        backgroundColor: createGradient(base),
                        tension: 0.36,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        fill: true,
                        borderWidth: 2
                    };
                }) : [];

                new Chart(attendanceCtx, {
                    type: 'line',
                    data: { labels: labels, datasets: datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'nearest', axis: 'x', intersect: false },
                        scales: {
                            y: {
                                suggestedMin: 0,
                                suggestedMax: 100,
                                ticks: { callback: v => v + '%' },
                                grid: { color: 'rgba(200,210,220,0.25)', borderDash: [4,4] }
                            },
                            x: { grid: { display: false } }
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8 } }
                        }
                    },
                    plugins: [{
                        id: 'softShadow',
                        beforeDatasetsDraw(chart) {
                            const ctx = chart.ctx;
                            ctx.save();
                            ctx.shadowColor = 'rgba(0,0,0,0.08)';
                            ctx.shadowBlur = 18;
                            ctx.shadowOffsetY = 8;
                        },
                        afterDatasetsDraw(chart) { chart.ctx.restore(); }
                    }]
                });
            }

            // Report Generator
            const analyticsForm = document.getElementById('analyticsForm');
            if (analyticsForm) {
                analyticsForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const overlay = document.getElementById('generating-overlay');
                    overlay.classList.add('show');
                    
                    try {
                        const formData = new FormData(this);
                        const response = await fetch('{{ route('faculty.analytics.generate') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            window.location.href = '{{ route('faculty.reports') }}';
                        } else {
                            throw new Error(data.error || 'Failed to generate report');
                        }
                    } catch (error) {
                        alert('Error: ' + error.message);
                        overlay.classList.remove('show');
                    }
                });
            }

            // Confirm Import
            const confirmImportForm = document.getElementById('confirmImportForm');
            if (confirmImportForm) {
                confirmImportForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const overlay = document.getElementById('generating-overlay');
                    overlay.classList.add('show');
                    
                    try {
                        // Use generateAnalytics route but it will pick up preview data from session
                        const response = await fetch('{{ route('faculty.analytics.generate') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: new FormData()
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            throw new Error(data.error || 'Failed to import records');
                        }
                    } catch (error) {
                        alert('Error: ' + error.message);
                        overlay.classList.remove('show');
                    }
                });
            }
        });
    </script>

    <!-- Submitted Requirements -->
    <div class="section" style="margin-top: 30px; margin-bottom: 50px;">
        <div class="section-header">
            <h3 class="section-title">Submitted Requirements</h3>
            <div style="font-size: 12px; color: #64748b;">Overview of your compliance and submissions</div>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid #edf2f7;">
                        <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #1e3c72; text-transform: uppercase;">Requirement Name</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #1e3c72; text-transform: uppercase;">Submission Date</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #1e3c72; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #1e3c72; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($facultyReports->take(5) as $report)
                        <tr style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 16px; font-size: 14px; font-weight: 600; color: #1e3c72;">{{ $report->title }}</td>
                            <td style="padding: 16px; font-size: 13px; color: #64748b;">{{ $report->created_at->format('M d, Y') }}</td>
                            <td style="padding: 16px;">
                                <span style="background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;">Submitted</span>
                            </td>
                            <td style="padding: 16px;">
                                <a href="{{ route('faculty.reports.view', $report->id) }}" style="color: #1e3c72; font-size: 13px; font-weight: 600; text-decoration: none;">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 30px; text-align: center; color: #94a3b8; font-size: 14px;">No submitted requirements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
