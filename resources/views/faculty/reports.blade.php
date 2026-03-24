@extends('layouts.faculty_new', ['activePage' => 'reports'])

@section('title', 'Reports - DSSC CRMS')
@section('page_title', 'System Reports')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">System Reports</h1>
            <p style="font-size: 14px; color: #64748b;">Generate and manage your reports (ROG)</p>
        </div>
    </div>



    @if(isset($generatedReports) && $generatedReports->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <div>
                    <div style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">Generated Reports</div>
                </div>
                <div style="position: relative; width: 300px;">
                    <input type="text" id="reportSearch" placeholder="Search reports..." style="width: 100%; padding: 10px 16px 10px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table id="reportsTable" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #edf2f7;">
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Report Title</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Type</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Generated At</th>
                            <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($generatedReports as $report)
                        <tr style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 16px;">
                                <div style="font-weight: 600; color: #1e3c72;">{{ $report->title }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $report->filename }}</div>
                            </td>
                            <td style="padding: 16px;">
                                @if($report->report_type === 'performance')
                                    <span style="background: #fce7f3; color: #831843; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">📊 Performance</span>
                                @elseif($report->report_type === 'grade')
                                    <span style="background: #dbeafe; color: #0c4a6e; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">📝 Grade</span>
                                @elseif($report->report_type === 'passFailAnalysis')
                                    <span style="background: #dcfce7; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">📈 Pass/Fail</span>
                                @elseif($report->report_type === 'attendance')
                                    <span style="background: #fef3c7; color: #78350f; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">📋 Attendance</span>
                                @else
                                    <span style="background: #f3f4f6; color: #374151; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">📊 {{ ucfirst($report->report_type) }}</span>
                                @endif
                            </td>
                            <td style="padding: 16px;">
                                <div style="color: #334155;">{{ $report->created_at->format('M j, Y') }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ $report->created_at->format('g:i A') }}</div>
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <a href="{{ route('faculty.reports.view', $report) }}" style="background: #f1f5f9; color: #1e3c72; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">View</a>
                                    <a href="{{ route('faculty.reports.download', $report) }}" style="background: #f1f5f9; color: #1e3c72; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">CSV</a>
                                    <form action="{{ route('faculty.reports.submit', $report) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" style="background: #1e3c72; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">Submit</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 60px; background: white; border-radius: 12px; border: 2px dashed #e2e8f0;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📈</div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">No Reports Found</h3>
            <p style="color: #64748b; margin-bottom: 24px;">You haven't generated any grade reports yet.</p>
            <a href="{{ route('dashboard') }}#report-generator" style="background: #1e3c72; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none;">Go to Dashboard</a>
        </div>
    @endif

    <!-- Performance Report Modal -->
    <div id="performanceReportModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; border-radius: 16px; max-width: 1100px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div style="padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 20px; font-weight: 700; color: #1e3c72; margin: 0;">Report of Grades (ROG) - Student Performance</h2>
                    <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Generated on {{ now()->format('M d, Y • h:i A') }}</p>
                </div>
                <button type="button" onclick="closePerformanceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">✕</button>
            </div>

            <div id="reportModalContent" style="flex: 1; overflow-y: auto; padding: 32px; background: #f8fafc;">
                <!-- Content loaded dynamically -->
            </div>

            <div style="padding: 20px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end; background: white;">
                <button type="button" onclick="closePerformanceModal()" style="padding: 10px 24px; background: #f1f5f9; color: #1e3c72; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Close</button>
                <button type="button" onclick="downloadPerformanceReport()" style="padding: 10px 24px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Download CSV</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('reportSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#reportsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        let performanceReportData = null;

        function generatePerformanceReportModal() {
            const modal = document.getElementById('performanceReportModal');
            const content = document.getElementById('reportModalContent');
            
            const gradeData = {!! json_encode($gradeDistribution ?? ['1.75' => 2, '2.00' => 2, '2.25' => 1, '2.50' => 1, '2.75' => 1, '5.00' => 15]) !!};
            const totalStudents = Object.values(gradeData).reduce((sum, count) => sum + count, 0);
            
            let html = `
                <div style="background: white; border-radius: 12px; padding: 28px;">
                    <div style="text-align: center; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0;">
                        <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin: 0 0 8px 0;">REPORT OF GRADES (ROG)</h3>
                        <p style="margin: 0; font-size: 13px; color: #64748b;">Student Performance Summary</p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-bottom: 24px;">
            `;

            Object.entries(gradeData).forEach(([grade, count]) => {
                const percentage = totalStudents > 0 ? ((count / totalStudents) * 100).toFixed(1) : 0;
                
                html += `
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

            html += `
                    </div>

                    <div style="background: #1e3c72; color: white; border-radius: 10px; padding: 20px; text-align: center;">
                        <p style="margin: 0; font-size: 14px; font-weight: 600;">
                            Total Students Evaluated: <span style="font-size: 18px; font-weight: 800;">${totalStudents}</span>
                        </p>
                    </div>
                </div>
            `;

            content.innerHTML = html;
            modal.style.display = 'flex';

            performanceReportData = {
                gradeData: gradeData,
                totalStudents: totalStudents,
                generatedDate: new Date().toLocaleString()
            };
        }

        function closePerformanceModal() {
            document.getElementById('performanceReportModal').style.display = 'none';
        }

        function downloadPerformanceReport() {
            if (!performanceReportData) return;

            const { gradeData, totalStudents, generatedDate } = performanceReportData;
            
            let csv = 'REPORT OF GRADES (ROG) - STUDENT PERFORMANCE\n';
            csv += `Generated: ${generatedDate}\n`;
            csv += `\n`;
            csv += `Grade,Count,Percentage,Total Students\n`;
            
            for (const [grade, count] of Object.entries(gradeData)) {
                const percentage = totalStudents > 0 ? ((count / totalStudents) * 100).toFixed(1) : 0;
                csv += `${grade},${count},${percentage}%,${totalStudents}\n`;
            }
            
            csv += `\nTotal Students: ${totalStudents}\n`;

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', `ROG_Student_Performance_${Date.now()}.csv`);
            link.click();
            
            showNotification('Report of Grades downloaded successfully!');
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px;
                background: #28a745;
                color: white; padding: 15px 20px; border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 9999;
                font-weight: 500; font-size: 14px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);
        }

        document.addEventListener('click', function(e) {
            const modal = document.getElementById('performanceReportModal');
            if (e.target === modal) {
                closePerformanceModal();
            }
        });
    </script>
@endsection
