@extends('layouts.program_head_new')

@section('title', 'Program Head Dashboard - DSSC CRMS')
@section('page_title', 'Dashboard')

@section('styles')
<style>
    /* Stats Cards */
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
</style>
@endsection

@section('content')
    <div id="dashboard-tab" class="tab-content active">
        <!-- Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
                <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Department Faculty</div>
                <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ $totalFaculty }}</div>
                <div style="font-size: 12px; font-weight: 600; color: #28a745;">Active Members</div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
                <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Total Records</div>
                <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ number_format($totalRecords) }}</div>
                <div style="font-size: 12px; font-weight: 600; color: {{ $recordsGrowthPercent >= 0 ? '#28a745' : '#dc3545' }};">
                    {{ $recordsGrowthPercent >= 0 ? '+' : '' }}{{ $recordsGrowthPercent }}% from last month
                </div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
                <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Overall Pass Rate</div>
                <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ $passRatePercent }}%</div>
                <div style="font-size: 12px; font-weight: 600; color: #28a745;">Across all subjects</div>
            </div>
            <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; border-left: 4px solid #1e3c72;">
                <div style="font-size: 11px; font-weight: 700; color: #999; letter-spacing: 0.5px; margin-bottom: 12px; text-transform: uppercase;">Active Subjects</div>
                <div style="font-size: 28px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">{{ $subjects->count() }}</div>
                <div style="font-size: 12px; font-weight: 600; color: #28a745;">This Semester</div>
            </div>
        </div>

        @include('dashboard.partials.institutional-analytics')

        <!-- Recent Submissions -->
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
                                        <a href="{{ route('program-head.submission.view', [$sub->user_id, $sub->subject_id]) }}" class="btn" style="background:#f1f5f9; color:#1e3c72; padding:4px 12px; font-size:11px; font-weight: 700; text-decoration: none; border-radius: 4px;">View</a>
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
                    <h3 class="section-title">Department Faculty Management</h3>
                    <div class="section-subtitle">Overview of all faculty members in {{ Auth::user()->department }}</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
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
                                <td>{{ $f->subjects_count }}</td>
                                <td>{{ number_format($f->records_count) }}</td>
                                <td>
                                    <button class="btn" onclick="viewFacultyDetails('{{ $f->id }}', '{{ $f->name }}', '{{ $f->email }}', '{{ $f->department }}', '{{ $f->role }}', '{{ $f->subjects_count }}', '{{ $f->records_count }}')" style="background:#f1f5f9; color:#1e3c72; border:none; border-radius:4px; padding:6px 12px; font-size:12px; font-weight:700; cursor:pointer;">View Details</button>
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
        <div class="section">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Faculty Generated Reports</h3>
                    <div class="section-subtitle">Recent reports submitted by your department faculty</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Report Title</th>
                            <th>Faculty Member</th>
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
                                <td><span style="text-transform: capitalize; background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-size: 11px;">{{ str_replace('_', ' ', $report->report_type) }}</span></td>
                                <td>{{ $report->created_at->format('M d, Y') }}</td>
                                <td>
                                    <button type="button" onclick="openReportModal({{ $report->id }})" class="btn" style="background:#f1f5f9; color:#1e3c72; padding:6px 12px; font-size:12px; border:none; border-radius: 4px; font-weight: 600; cursor: pointer;">View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #999;">No faculty reports found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Report View Modal -->
    <div id="viewReportModal" style="display: none; position: fixed; inset: 0; z-index: 4000; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; padding: 20px;">
        <div style="position: relative; width: 100%; height: calc(100% - 40px); max-width: 1200px; background: white; border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.35); overflow: hidden;">
            <button onclick="closeReportModal()" style="position: absolute; top: 12px; right: 12px; z-index: 20; background: #1e3c72; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-weight: 700; cursor: pointer;">✕ Close</button>
            <iframe id="reportIframe" src="about:blank" style="width: 100%; height: 100%; border: none;" sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
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
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 8px;">Department</label>
                        <input type="text" value="{{ Auth::user()->department }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background-color: #f8fafc; color: #94a3b8;">
                    </div>
                    <div style="margin-top: 30px;">
                        <button type="submit" style="padding: 12px 24px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report View Modal -->
    <div id="reportSuccessModalPh" class="modal-overlay">
        <div class="modal-box" style="max-width:800px; width:95%; max-height:90vh; overflow:hidden; display:flex; flex-direction:column; text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #edf2f7;">
                <h3 style="margin:0; color:#1e3c72;">Report Preview</h3>
                <button onclick="document.getElementById('reportSuccessModalPh').classList.remove('show')" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <div id="reportContentPreviewPh" style="flex:1; min-height:200px; max-height:50vh; overflow:auto; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; font-size:12px; font-family:monospace; white-space:pre-wrap;"></div>
            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
                <button type="button" id="reportModalCancelPh" style="padding:10px 20px; background:#f1f5f9; color:#64748b; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Close</button>
                <button type="button" id="reportModalDownloadPh" style="padding:10px 24px; background:#1e3c72; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Download CSV</button>
            </div>
        </div>
    </div>

    <!-- Faculty Details Modal -->
    <div id="facultyDetailsModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div style="background: #1e3c72; padding: 24px; color: white; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Faculty Profile</h3>
                <button type="button" onclick="closeFacultyModal()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer; opacity: 0.8;">✕</button>
            </div>
            
            <div style="padding: 32px;">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img id="facultyDetailAvatar" src="" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <h4 id="facultyDetailName" style="margin: 0 0 4px 0; font-size: 22px; font-weight: 800; color: #1e3c72;"></h4>
                        <div id="facultyDetailRole" style="font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;"></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px;">Email Address</div>
                        <div id="facultyDetailEmail" style="font-size: 14px; font-weight: 600; color: #334155;"></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px;">Department</div>
                        <div id="facultyDetailDept" style="font-size: 14px; font-weight: 600; color: #334155;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #edf2f7;">
                        <div style="font-size: 11px; font-weight: 700; color: #1e3c72; text-transform: uppercase; margin-bottom: 4px;">Active Subjects</div>
                        <div id="facultyDetailSubjects" style="font-size: 24px; font-weight: 800; color: #1e3c72;"></div>
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #edf2f7;">
                        <div style="font-size: 11px; font-weight: 700; color: #1e3c72; text-transform: uppercase; margin-bottom: 4px;">Total Records</div>
                        <div id="facultyDetailRecords" style="font-size: 24px; font-weight: 800; color: #1e3c72;"></div>
                    </div>
                </div>
            </div>

            <div style="padding: 20px 32px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end;">
                <button type="button" onclick="closeFacultyModal()" style="padding: 10px 24px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function viewFacultyDetails(id, name, email, dept, role, subjects, records) {
        document.getElementById('facultyDetailName').textContent = name;
        document.getElementById('facultyDetailEmail').textContent = email;
        document.getElementById('facultyDetailDept').textContent = dept;
        document.getElementById('facultyDetailRole').textContent = role.replace('_', ' ');
        document.getElementById('facultyDetailSubjects').textContent = subjects;
        document.getElementById('facultyDetailRecords').textContent = Number(records).toLocaleString();
        document.getElementById('facultyDetailAvatar').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=f1f5f9&color=1e3c72&size=128`;
        
        document.getElementById('facultyDetailsModal').style.display = 'flex';
    }

    function closeFacultyModal() {
        document.getElementById('facultyDetailsModal').style.display = 'none';
    }

    var pendingReportTypePh = null, pendingReportContentPh = null;
    
    function viewFacultyReportPh(reportType) {
        var csv = generateReportCSVPh(reportType);
        pendingReportTypePh = reportType;
        pendingReportContentPh = csv;
        document.getElementById('reportContentPreviewPh').textContent = csv;
        document.getElementById('reportSuccessModalPh').classList.add('show');
    }

    function generateReportCSVPh(reportType) {
        var timestamp = new Date().toLocaleString();
        var csv = 'Department Report - ' + timestamp + '\n\n';
        var subjects = @json($subjects ?? []);
        var topStudents = @json($topStudents ?? []);
        var passFailRates = @json($passFailRates ?? []);
        var attendanceTrends = @json($attendanceTrends ?? []);
        var analytics = @json($analytics ?? []);
        var gradeDistribution = @json($gradeDistribution ?? []);
        
        switch(reportType) {
            case 'grade':
                csv += 'Student Grade Summary Report\n';
                csv += 'Generated: ' + timestamp + '\n\n';
                csv += 'Subject Code,Student ID,Student Name,Grade\n';
                if (topStudents && topStudents.length > 0) {
                    topStudents.forEach(function(s) {
                        csv += '"' + (s.program||'') + '","' + (s.student_id||'') + '","' + (s.name||'') + '","' + (s.gpa||'') + '"\n';
                    });
                } else {
                    csv += 'No student data available\n';
                }
                break;
            case 'passFailAnalysis':
                csv += 'Pass/Fail Analysis Report\n';
                csv += 'Generated: ' + timestamp + '\n\n';
                csv += 'Subject Code,Total,Passed,Failed,Pass Rate (%)\n';
                if (passFailRates && passFailRates.length > 0) {
                    passFailRates.forEach(function(r) { 
                        var t = (r.pass||0)+(r.fail||0); 
                        csv += '"' + (r.code||'') + '","' + t + '","' + (r.pass||0) + '","' + (r.fail||0) + '","' + (t ? Math.round((r.pass/t)*100) : 0) + '"\n'; 
                    });
                } else {
                    csv += 'No pass/fail data available\n';
                }
                break;
            case 'attendance':
                csv += 'Attendance Report\n';
                csv += 'Generated: ' + timestamp + '\n\n';
                csv += 'Subject Code,Average Attendance\n';
                if (attendanceTrends && attendanceTrends.length > 0) {
                    attendanceTrends.forEach(function(t) { 
                        csv += '"' + (t.code||'') + '";"' + (t.attendance_percent||0).toFixed(2) + '%"\n'; 
                    });
                } else {
                    csv += 'No attendance data available\n';
                }
                break;
            case 'lectureLabSummary':
                csv += 'Performance Summary Report\n';
                csv += 'Generated: ' + timestamp + '\n\n';
                csv += 'Grade,Count\n';
                if (gradeDistribution && Object.keys(gradeDistribution).length > 0) {
                    Object.keys(gradeDistribution).forEach(function(grade) {
                        csv += '"Grade ' + grade + '","' + gradeDistribution[grade] + '"\n';
                    });
                } else {
                    csv += 'No grade distribution data available\n';
                }
                break;
        }
        return csv;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Pass/Fail pie
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

        // Attendance Chart (per semester, per subject)
        const aEl = document.getElementById('attendanceChart');
        if (aEl) {
            const attendanceRaw = {!! json_encode($attendanceTrends) !!};
            const labels = Array.isArray(attendanceRaw) ? attendanceRaw.map((t, i) => t.code || t.name || 'Subject ' + (i + 1)) : [];
            const data = Array.isArray(attendanceRaw) ? attendanceRaw.map((t) => Number(t.attendance_percent || 0)) : [];
            const barColor = 'rgba(34, 100, 178, 0.85)';

            new Chart(aEl, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Attendance % (Semester)',
                        data: data,
                        backgroundColor: labels.map(() => barColor),
                        borderColor: 'rgba(30, 60, 140, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 60
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                        legend: { display: true, position: 'bottom' }
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

        // Performance chart section is intentionally simplified to avoid duplication.

        document.getElementById('reportModalDownloadPh').addEventListener('click', function() {
            if (pendingReportContentPh && pendingReportTypePh) {
                var blob = new Blob([pendingReportContentPh], { type: 'text/csv;charset=utf-8;' });
                var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = pendingReportTypePh + '_' + new Date().toISOString().slice(0,10) + '.csv'; a.click();
            }
            document.getElementById('reportSuccessModalPh').classList.remove('show');
        });

        document.getElementById('reportModalCancelPh').addEventListener('click', function() {
            document.getElementById('reportSuccessModalPh').classList.remove('show');
        });
    });

    function openReportModal(reportId) {
        var modal = document.getElementById('viewReportModal');
        var iframe = document.getElementById('reportIframe');
        iframe.src = '/faculty/reports/' + reportId + '/view?embedded=1';
        modal.style.display = 'flex';
    }

    function closeReportModal() {
        var modal = document.getElementById('viewReportModal');
        var iframe = document.getElementById('reportIframe');
        iframe.src = 'about:blank';
        modal.style.display = 'none';
    }
</script>
@endsection
