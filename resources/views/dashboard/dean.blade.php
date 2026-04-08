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

        @if(!empty($allowDevDbReset) && $allowDevDbReset)
        <div class="section" style="border: 2px dashed #dc3545; background: #fff8f8; margin-bottom: 24px;">
            <div class="section-header" style="border-bottom-color: #f1c9cf;">
                <h3 class="section-title" style="color: #b02a37;">Temporary: clear database &amp; seed</h3>
            </div>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                Kini mo- gawas ang tanang data sa mga application tables (users, students, subjects, records, reports, grades, jobs, sessions, cache) unya mo-run ang <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">php artisan db:seed</code>.
                Makalogout ka ug kinahanglan mo-login balik.
            </p>
            <form action="{{ route('dean.dev-reset-database') }}" method="POST" onsubmit="return confirm('Sigurado ka? Ma-delete ang tanang data sa database ug ma-seed balik.');">
                @csrf
                <label for="dev-reset-confirm" style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:6px;">Type <strong>RESET</strong> aron ma-confirm:</label>
                <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                    <input id="dev-reset-confirm" name="confirm" type="text" autocomplete="off" placeholder="RESET" required
                        style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;min-width:200px;font-size:14px;" />
                    <button type="submit" style="background:#dc3545;color:#fff;border:none;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;">
                        Clear tables &amp; seed
                    </button>
                </div>
                @error('confirm')
                    <p style="color:#dc3545;font-size:12px;margin-top:8px;">{{ $message }}</p>
                @enderror
            </form>
        </div>
        @endif

        @include('dashboard.partials.institutional-analytics')

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
                            <th>Program</th>
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
        <div class="section">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Faculty Generated Reports</h3>
                    <div class="section-subtitle">Reports submitted directly to your dean account</div>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Report Title</th>
                            <th>Faculty Member</th>
                            <th>Program</th>
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
                                    <button type="button" onclick="openReportModal({{ $report->id }})" class="btn" style="background:#f1f5f9; color:#1e3c72; padding:6px 12px; font-size:12px; border:none; border-radius: 4px; font-weight: 600; cursor: pointer;">View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #999;">No reports have been submitted to your account yet.</td>
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

    <!-- Report View Modal -->
    <div id="viewReportModal" style="display: none; position: fixed; inset: 0; z-index: 4000; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; padding: 20px;">
        <div style="position: relative; width: 100%; height: calc(100% - 40px); max-width: 1200px; background: white; border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.35); overflow: hidden;">
            <button onclick="closeReportModal()" style="position: absolute; top: 12px; right: 12px; z-index: 20; background: #1e3c72; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-weight: 700; cursor: pointer;">✕ Close</button>
            <iframe id="reportIframe" src="about:blank" style="width: 100%; height: 100%; border: none;" sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
        </div>
    </div>
@endsection

@section('scripts')
<script>
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

        // Institutional Attendance Chart (per week + semester)
        const aEl = document.getElementById('attendanceChart');
        if (aEl) {
            const attendanceRaw = {!! json_encode($attendanceTrends) !!};
            const labels = Array.isArray(attendanceRaw) ? attendanceRaw.map((t, i) => t.code || t.name || 'Subject ' + (i + 1)) : [];
            const week1 = Array.isArray(attendanceRaw) ? attendanceRaw.map((t) => Number(t.week1 || 0)) : [];
            const week2 = Array.isArray(attendanceRaw) ? attendanceRaw.map((t) => Number(t.week2 || 0)) : [];
            const week3 = Array.isArray(attendanceRaw) ? attendanceRaw.map((t) => Number(t.week3 || 0)) : [];
            const week4 = Array.isArray(attendanceRaw) ? attendanceRaw.map((t) => Number(t.week4 || 0)) : [];
            const semester = Array.isArray(attendanceRaw) ? attendanceRaw.map((t) => Number(t.attendance_percent || t.average || 0)) : [];

            new Chart(aEl, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Week 1', data: week1, backgroundColor: 'rgba(147, 197, 253, 0.85)', borderColor: 'rgba(96, 165, 250, 1)', borderWidth: 1, borderRadius: 4 },
                        { label: 'Week 2', data: week2, backgroundColor: 'rgba(96, 165, 250, 0.85)', borderColor: 'rgba(59, 130, 246, 1)', borderWidth: 1, borderRadius: 4 },
                        { label: 'Week 3', data: week3, backgroundColor: 'rgba(59, 130, 246, 0.85)', borderColor: 'rgba(37, 99, 235, 1)', borderWidth: 1, borderRadius: 4 },
                        { label: 'Week 4', data: week4, backgroundColor: 'rgba(37, 99, 235, 0.85)', borderColor: 'rgba(29, 78, 216, 1)', borderWidth: 1, borderRadius: 4 },
                        { label: 'Semester %', data: semester, backgroundColor: 'rgba(16, 185, 129, 0.75)', borderColor: 'rgba(5, 150, 105, 1)', borderWidth: 1, borderRadius: 4 }
                    ]
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

        // Grade distribution chart removed per specification.
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
