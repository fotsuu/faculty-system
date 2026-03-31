@extends('layouts.faculty_new', ['activePage' => 'submitted-reports'])

@section('title', 'Submitted Reports - DSSC CRMS')
@section('page_title', 'Submitted Reports')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #1e3c72; margin-bottom: 8px;">Submitted Reports</h1>
            <p style="font-size: 14px; color: #64748b;">Track reports you've submitted to program heads and deans</p>
        </div>
    </div>

    @if(isset($submittedReports) && $submittedReports->isNotEmpty())
        <div class="section">
            <div class="section-header">
                <div>
                    <div style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">Your Submitted Reports</div>
                    <div style="font-size: 13px; color: #64748b;">Reports submitted to program heads and administrative staff</div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #edf2f7;">
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Report Title</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Type</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Submitted To</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Submitted Date</th>
                            <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submittedReports as $report)
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
                                    <span style="background: #f3f4f6; color: #374151; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">📊 {{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</span>
                                @endif
                            </td>
                            <td style="padding: 16px;">
                                <div style="font-weight: 600; color: #1e3c72;">{{ $report->recipient->name ?? 'N/A' }}</div>
                                @if($report->recipient)
                                    <div style="font-size: 12px; color: #64748b; text-transform: capitalize;">{{ str_replace('_', ' ', $report->recipient->role) }}</div>
                                @endif
                            </td>
                            <td style="padding: 16px;">
                                @php
                                    $submittedAt = $report->submitted_at;
                                    if (is_string($submittedAt)) {
                                        $submittedAt = \Illuminate\Support\Carbon::parse($submittedAt);
                                    }
                                @endphp
                                <div style="color: #334155;">{{ optional($submittedAt)->format('M j, Y') ?? 'N/A' }}</div>
                                <div style="font-size: 12px; color: #64748b;">{{ optional($submittedAt)->format('g:i A') ?? 'N/A' }}</div>
                            </td>
                            <td style="padding: 16px; text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <a href="{{ route('faculty.reports.view', $report) }}" style="background: #e0f2fe; color: #0369a1; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">View</a>
                                    <a href="{{ route('faculty.reports.download', $report) }}" style="background: #f1f5f9; color: #1e3c72; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">CSV</a>
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
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📤</div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">No Submitted Reports</h3>
            <p style="color: #64748b; margin-bottom: 24px;">You haven't submitted any reports to program heads or deans yet.</p>
            <a href="{{ route('faculty.reports') }}" style="background: #1e3c72; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none;">View All Reports</a>
        </div>
    @endif
@endsection
