@extends('layouts.dean_new')

@section('title', 'Dean Dashboard - DSSC CRMS')
@section('page_title', 'Dashboard')

@section('styles')
<style>
    /* Stats Row */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        border-left: 4px solid #1e3c72;
    }
    
    .stat-card-title {
        font-size: 11px;
        font-weight: 700;
        color: #999;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        text-transform: uppercase;
    }
    
    .stat-card-value {
        font-size: 28px;
        font-weight: 800;
        color: #1e3c72;
        margin-bottom: 8px;
    }
    
    .stat-card-change {
        font-size: 12px;
        font-weight: 600;
    }
    .text-success { color: #28a745; }
    .text-danger { color: #dc3545; }

    /* Tabs */
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }

    /* Table Styles */
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        background-color: #f8fafc;
        padding: 12px 16px;
        font-size: 11px;
        font-weight: 700;
        color: #1e3c72;
        text-align: left;
        border-bottom: 2px solid #edf2f7;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .data-table td {
        padding: 16px;
        border-bottom: 1px solid #edf2f7;
        font-size: 13px;
        color: #334155;
    }
    
    .data-table tr:hover {
        background-color: #f1f5f9;
    }

    .faculty-name {
        font-weight: 600;
        color: #1e3c72;
    }
    
    .faculty-dept {
        font-size: 11px;
        color: #64748b;
    }

    /* Charts */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .performer-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .performer-rank {
        width: 30px;
        font-size: 14px;
        font-weight: 700;
        color: #94a3b8;
    }
    .performer-info {
        flex: 1;
    }
    .performer-name {
        font-size: 14px;
        font-weight: 600;
        color: #1e3c72;
    }
    .performer-id {
        font-size: 11px;
        color: #64748b;
    }
    .performer-gpa {
        font-size: 16px;
        font-weight: 700;
        color: #1e3c72;
    }
    .performer-subject {
        font-size: 11px;
        color: #64748b;
    }

    /* Alert Banner */
    .alert-banner {
        background: white;
        border-radius: 12px;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        border-left: 4px solid #1e3c72;
    }
    
    .alert-icon {
        width: 40px;
        height: 40px;
        background: #f1f5f9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #1e3c72;
    }
    
    .alert-content {
        flex: 1;
        font-size: 14px;
        color: #334155;
        font-weight: 500;
    }
    
    .alert-close {
        cursor: pointer;
        color: #94a3b8;
        transition: color 0.2s;
    }
</style>
@endsection

@section('content')
    <div id="dashboard-tab" class="tab-content active">
        <!-- Stats Row -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-card-title">Total Faculty</div>
                <div class="stat-card-value">{{ number_format($totalFaculty) }}</div>
                <div class="stat-card-change text-success">Across all departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Total Records</div>
                <div class="stat-card-value">{{ number_format($totalRecords) }}</div>
                <div class="stat-card-change {{ $recordsGrowthPercent >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $recordsGrowthPercent >= 0 ? '+' : '' }}{{ $recordsGrowthPercent }}% from last month
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Institutional Pass Rate</div>
                <div class="stat-card-value">{{ $passRatePercent }}%</div>
                <div class="stat-card-change text-success">Global Academic Performance</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-title">Active Subjects</div>
                <div class="stat-card-value">{{ $subjects->count() }}</div>
                <div class="stat-card-change text-success">Currently Monitored</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 25px;">
            <!-- Performance Overview -->
            <div class="section">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Institutional Performance</h3>
                        <div class="section-subtitle">Overall Pass/Fail ratio across all departments</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="passFailChart"></canvas>
                </div>
            </div>

            <!-- Grade Distribution -->
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">Grade Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="gradesChart"></canvas>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 25px;">
            <!-- Attendance -->
            <div class="section">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Attendance Trends</h3>
                        <div class="section-subtitle">Institutional student attendance over the last 4 weeks</div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <!-- Top Performing Students (Institutional) -->
            <div class="section">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Institutional Top Performers</h3>
                        <div class="section-subtitle">Based on cumulative GPA across all programs</div>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    @forelse($topStudents as $index => $performer)
                        <div class="performer-item">
                            <div class="performer-rank">#{{ $index + 1 }}</div>
                            <div class="performer-info">
                                <div class="performer-name">{{ $performer['name'] }}</div>
                                <div class="performer-id">{{ $performer['student_id'] }} • {{ $performer['program'] }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div class="performer-gpa">{{ $performer['gpa'] }}</div>
                                <div class="performer-subject">{{ $performer['recordCount'] }} records</div>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 20px; text-align: center; color: #999;">No performance data available.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Submissions Table -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Recent Grade Submissions</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Faculty Member</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Records</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                            <tr>
                                <td>
                                    <div class="faculty-name">{{ $sub->faculty_name }}</div>
                                    <div class="faculty-dept">{{ $sub->faculty_dept }}</div>
                                </td>
                                <td>{{ $sub->subject }}</td>
                                <td>{{ $sub->date }}</td>
                                <td>{{ $sub->records }}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">{{ $sub->status }}</span>
                                        <a href="{{ route('dean.submission.view', [$sub->user_id, $sub->subject_id]) }}" class="btn" style="background:#f1f5f9; color:#1e3c72; padding:4px 12px; font-size:11px; font-weight: 700; text-decoration: none; border-radius: 4px;">View</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #999;">No recent submissions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Faculty Management Tab -->
    <div id="faculty-tab" class="tab-content">
        <div class="section">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Institutional Faculty Management</h3>
                    <div class="section-subtitle">Overview of all faculty members across all departments</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Active Subjects</th>
                            <th>Total Records</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allFaculty as $f)
                            <tr>
                                <td class="faculty-name">{{ $f->name }}</td>
                                <td>{{ $f->email }}</td>
                                <td>{{ $f->department ?? 'N/A' }}</td>
                                <td>{{ $f->subjects_count }}</td>
                                <td>{{ number_format($f->records_count) }}</td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn" style="background:#f1f5f9; color:#1e3c72; padding:6px 12px; font-size:12px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer;">Details</button>
                                        <form action="{{ route('dean.faculty.toggle-status', $f->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn" style="background: {{ $f->status === 'active' ? '#fee2e2' : '#ecfdf5' }}; color: {{ $f->status === 'active' ? '#dc3545' : '#059669' }}; padding:6px 12px; font-size:12px; font-weight: 600; border: none; border-radius: 4px; cursor: pointer;">
                                                {{ $f->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reports Tab -->
    <div id="reports-tab" class="tab-content">
        <div class="section" style="margin-bottom: 25px;">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Institutional Reports</h3>
                    <div class="section-subtitle">View and monitor reports from all departments</div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div style="background:#f8fafc; border-radius:12px; padding:24px; text-align:center; border:1px solid #edf2f7;">
                    <div style="font-size:32px; margin-bottom:15px;">📋</div>
                    <div style="font-size:14px; font-weight:700; color:#1e3c72; margin-bottom:8px;">Institutional Grade Report</div>
                    <button type="button" onclick="viewDeanReport('grade')" style="width:100%; padding:10px; background:#1e3c72; color:white; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">Generate Now</button>
                </div>
                <div style="background:#f8fafc; border-radius:12px; padding:24px; text-align:center; border:1px solid #edf2f7;">
                    <div style="font-size:32px; margin-bottom:15px;">📊</div>
                    <div style="font-size:14px; font-weight:700; color:#1e3c72; margin-bottom:8px;">Global Pass/Fail Analysis</div>
                    <button type="button" onclick="viewDeanReport('passFailAnalysis')" style="width:100%; padding:10px; background:#1e3c72; color:white; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">Generate Now</button>
                </div>
                <div style="background:#f8fafc; border-radius:12px; padding:24px; text-align:center; border:1px solid #edf2f7;">
                    <div style="font-size:32px; margin-bottom:15px;">📅</div>
                    <div style="font-size:14px; font-weight:700; color:#1e3c72; margin-bottom:8px;">Institutional Attendance</div>
                    <button type="button" onclick="viewDeanReport('attendance')" style="width:100%; padding:10px; background:#1e3c72; color:white; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">Generate Now</button>
                </div>
                <div style="background:#f8fafc; border-radius:12px; padding:24px; text-align:center; border:1px solid #edf2f7;">
                    <div style="font-size:32px; margin-bottom:15px;">👥</div>
                    <div style="font-size:14px; font-weight:700; color:#1e3c72; margin-bottom:8px;">Performance Summary</div>
                    <button type="button" onclick="viewDeanReport('lectureLabSummary')" style="width:100%; padding:10px; background:#1e3c72; color:white; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">Generate Now</button>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Faculty Generated Reports (All Departments)</h3>
                    <div class="section-subtitle">Monitor reports submitted by all faculty members</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Report Title</th>
                            <th>Faculty Member</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($facultyReports as $report)
                            <tr>
                                <td style="font-weight: 600; color: #1e3c72;">{{ $report->title }}</td>
                                <td>{{ $report->user->name }}</td>
                                <td>{{ $report->user->department ?? 'N/A' }}</td>
                                <td><span style="text-transform: capitalize; background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-size: 11px;">{{ str_replace('_', ' ', $report->report_type) }}</span></td>
                                <td>{{ $report->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ route('faculty.reports.view', $report->id) }}" class="btn" style="background:#f1f5f9; color:#1e3c72; padding:6px 12px; font-size:12px; text-decoration: none; border-radius: 4px; font-weight: 600;">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #999;">No faculty reports found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Settings Tab -->
    <div id="system-settings-tab" class="tab-content">
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">Institutional Configuration</h3>
                <div class="section-subtitle">Manage system-wide academic settings</div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 8px;">University Name</label>
                        <input type="text" value="Davao del Sur State College" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 8px;">Academic Year</label>
                        <input type="text" value="2023-2024" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 8px;">Active Semester</label>
                        <select style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option>First Semester</option>
                            <option selected>Second Semester</option>
                            <option>Summer</option>
                        </select>
                    </div>
                </div>
                
                <div style="background: #f8fafc; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <h4 style="font-size: 14px; font-weight: 700; color: #1e3c72; margin-bottom: 16px;">System Health</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-size: 13px; color: #64748b;">Total Records</span>
                        <span style="font-size: 13px; font-weight: 700; color: #1e3c72;">{{ number_format($totalRecords) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="font-size: 13px; color: #64748b;">Database Size</span>
                        <span style="font-size: 13px; font-weight: 700; color: #1e3c72;">~25 MB</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <span style="font-size: 13px; color: #64748b;">Last Backup</span>
                        <span style="font-size: 13px; font-weight: 700; color: #1e3c72;">Today, 2:30 AM</span>
                    </div>
                    <button style="width: 100%; padding: 10px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;">Generate System Backup</button>
                </div>
            </div>
            
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                <button style="padding: 12px 24px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Save System Configuration</button>
            </div>
        </div>
    </div>

    <!-- Settings Tab -->

    <div id="settings-tab" class="tab-content">
        <div class="section">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Account Settings</h3>
                    <div class="section-subtitle">Manage your personal information and profile</div>
                </div>
            </div>
            <div style="max-width: 600px; padding: 10px;">
                <form action="{{ route('dean.settings.update') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 8px;">Full Name</label>
                        <input type="text" name="name" value="{{ Auth::user()->name }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 8px;">Email Address</label>
                        <input type="email" name="email" value="{{ Auth::user()->email }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    <div style="margin-top: 30px;">
                        <button type="submit" style="padding: 12px 24px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report View Modal -->
    <div id="reportSuccessModal" class="modal-overlay">
        <div class="modal-box" style="max-width:800px; width:95%; max-height:90vh; overflow:hidden; display:flex; flex-direction:column; text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #edf2f7;">
                <h3 style="margin:0; color:#1e3c72;">Report Preview</h3>
                <button onclick="document.getElementById('reportSuccessModal').classList.remove('show')" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <div id="reportContentPreview" style="flex:1; min-height:200px; max-height:50vh; overflow:auto; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; font-size:12px; font-family:monospace; white-space:pre-wrap;"></div>
            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
                <button type="button" id="reportModalCancel" style="padding:10px 20px; background:#f1f5f9; color:#64748b; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Close</button>
                <button type="button" id="reportModalDownload" style="padding:10px 24px; background:#1e3c72; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Download CSV</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    var pendingReportType = null, pendingReportContent = null;
    
    function viewDeanReport(reportType) {
        var csv = generateReportCSV(reportType);
        pendingReportType = reportType;
        pendingReportContent = csv;
        document.getElementById('reportContentPreview').textContent = csv;
        document.getElementById('reportSuccessModal').classList.add('show');
    }

    function generateReportCSV(reportType) {
        var timestamp = new Date().toLocaleString();
        var csv = 'Institutional Report - ' + timestamp + '\n\n';
        var passFailRates = @json($passFailRates ?? []);
        var attendanceTrends = @json($attendanceTrends ?? []);
        var analytics = @json($analytics ?? []);
        
        switch(reportType) {
            case 'grade':
                csv += 'Student Grade Summary\nStudent ID,Student Name,Program,GPA\n';
                @json($topStudents ?? []).forEach(function(s) {
                    csv += '"' + (s.student_id||'') + '","' + (s.name||'') + '","' + (s.program||'') + '","' + (s.gpa||'') + '"\n';
                });
                break;
            case 'passFailAnalysis':
                csv += 'Institutional Pass/Fail Analysis\nSubject Code,Total,Passed,Failed,Pass Rate (%)\n';
                passFailRates.forEach(function(r) { 
                    var t = (r.pass||0)+(r.fail||0); 
                    csv += '"' + (r.code||'') + '","' + t + '","' + (r.pass||0) + '","' + (r.fail||0) + '","' + (t ? Math.round((r.pass/t)*100) : 0) + '"\n'; 
                });
                break;
            case 'attendance':
                csv += 'Institutional Attendance Report\nSubject Code,Week 1,Week 2,Week 3,Week 4,Average\n';
                attendanceTrends.forEach(function(t) { 
                    csv += '"' + (t.code||'') + '","' + (t.week1||0) + '","' + (t.week2||0) + '","' + (t.week3||0) + '","' + (t.week4||0) + '","' + (t.average||0) + '"\n'; 
                });
                break;
            case 'lectureLabSummary':
                csv += 'Institutional Performance Summary\nType,Average Score,Count\n';
                if (analytics.examAnalytics) {
                    csv += '"Midterm","' + analytics.examAnalytics.midterm.avg_score + '","' + analytics.examAnalytics.midterm.count + '"\n';
                    csv += '"Final","' + analytics.examAnalytics.final.avg_score + '","' + analytics.examAnalytics.final.count + '"\n';
                }
                break;
        }
        return csv;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Institutional Pass/Fail pie
        const el = document.getElementById('passFailChart');
        if (el) {
            new Chart(el, {
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
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
                    }
                }
            });
        }

        // Institutional Attendance Chart
        const aEl = document.getElementById('attendanceChart');
        if (aEl) {
            const attendanceRaw = {!! json_encode($attendanceTrends) !!};
            const labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            const palette = ['#8FB9E6', '#BFCFE2', '#C7D9EE', '#A9CDEB', '#DDEBF8', '#C8D6E0'];

            const ctxA = aEl.getContext('2d');
            function createGradient(color) {
                const g = ctxA.createLinearGradient(0, 0, 0, 300);
                g.addColorStop(0, color + '33');
                g.addColorStop(0.6, color + '1A');
                g.addColorStop(1, 'rgba(255,255,255,0)');
                return g;
            }

            const datasets = attendanceRaw.slice(0, 6).map((t, i) => {
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
            });

            new Chart(aEl, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { suggestedMin: 0, suggestedMax: 100, ticks: { callback: v => v + '%' }, grid: { color: 'rgba(200,210,220,0.1)' } }
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

        // Institutional Grade Distribution Chart
        const gEl = document.getElementById('gradesChart');
        if (gEl) {
            const raw = @json($gradeDistribution);
            const grades = ['A','B','C','D','F'];
            const values = grades.map(k => Number(raw[k] || 0));
            const colors = ['#A9CDEB', '#C8DDE2', '#E7E6C8', '#EAD7C2', '#E9D6D6'];
            
            const ctxG = gEl.getContext('2d');
            function grad(col) {
                const g = ctxG.createLinearGradient(0, 0, 0, 300);
                g.addColorStop(0, col + '');
                g.addColorStop(0.6, 'rgba(255,255,255,0.6)');
                g.addColorStop(1, 'rgba(255,255,255,0)');
                return g;
            }

            new Chart(gEl, {
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
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(200,210,220,0.1)' } }
                    },
                    plugins: { legend: { display: false } }
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

        document.getElementById('reportModalDownload').addEventListener('click', function() {
            if (pendingReportContent && pendingReportType) {
                var blob = new Blob([pendingReportContent], { type: 'text/csv;charset=utf-8;' });
                var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'dean_' + pendingReportType + '_' + new Date().toISOString().slice(0,10) + '.csv'; a.click();
            }
            document.getElementById('reportSuccessModal').classList.remove('show');
        });

        document.getElementById('reportModalCancel').addEventListener('click', function() {
            document.getElementById('reportSuccessModal').classList.remove('show');
        });
    });
</script>
@endsection
