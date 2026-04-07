@extends('layouts.program_head_new', ['activePage' => 'reports'])

@section('title', 'Reports - Program Head Dashboard')
@section('page_title', 'Faculty Generated Reports')

@section('content')
    <div class="section">
        <div class="section-header">
            <div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">Faculty Generated Reports</h3>
                <div style="font-size: 13px; color: #64748b;">Reports submitted directly to your account</div>
            </div>
        </div>

        @if($facultyReports->count() > 0)
            <div style="background: white; border-radius: 12px; border: 1px solid #edf2f7; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #edf2f7;">
                                <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72; font-size: 12px;">Report Title</th>
                                <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72; font-size: 12px;">Faculty Member</th>
                                <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72; font-size: 12px;">Report Type</th>
                                <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72; font-size: 12px;">Date</th>
                                <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72; font-size: 12px;">Status</th>
                                <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72; font-size: 12px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facultyReports as $report)
                                <tr style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 14px 16px; color: #1e3c72; font-weight: 600;">{{ $report->title }}</td>
                                    <td style="padding: 14px 16px; color: #334155;">{{ $report->user->name ?? 'N/A' }}</td>
                                    <td style="padding: 14px 16px; color: #64748b;">
                                        <span style="text-transform: capitalize; background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                                            {{ str_replace('_', ' ', $report->report_type) }}
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; color: #64748b;">{{ $report->created_at->format('M d, Y') }}</td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        @if($report->submitted_at)
                                            <span style="background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Submitted</span>
                                        @else
                                            <span style="background: #fef3c7; color: #f59e0b; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Draft</span>
                                        @endif
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <button type="button" onclick="openReportModal({{ $report->id }})" style="background: #1e3c72; color: white; padding: 6px 14px; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid #edf2f7;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📋</div>
                <p style="font-size: 14px; color: #64748b;">No reports have been submitted to your account yet.</p>
            </div>
        @endif
    </div>

    <div id="viewReportModal" style="display: none; position: fixed; inset: 0; z-index: 4000; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; padding: 20px;">
        <div style="position: relative; width: 100%; height: calc(100% - 40px); max-width: 1200px; background: white; border-radius: 12px; box-shadow: 0 15px 40px rgba(0,0,0,0.35); overflow: hidden;">
            <button onclick="closeReportModal()" style="position: absolute; top: 12px; right: 12px; z-index: 20; background: #1e3c72; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-weight: 700; cursor: pointer;">✕ Close</button>
            <iframe id="reportIframe" src="about:blank" style="width: 100%; height: 100%; border: none;" sandbox="allow-same-origin allow-scripts allow-popups allow-forms"></iframe>
        </div>
    </div>

    <script>
        function openReportModal(reportId) {
            const modal = document.getElementById('viewReportModal');
            const frame = document.getElementById('reportIframe');
            frame.src = `/program-head/reports/${reportId}/view?embedded=1`;
            modal.style.display = 'flex';
        }

        function closeReportModal() {
            const modal = document.getElementById('viewReportModal');
            const frame = document.getElementById('reportIframe');
            frame.src = 'about:blank';
            modal.style.display = 'none';
        }
    </script>

    <style>
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #edf2f7;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #edf2f7;
        }
    </style>
@endsection
