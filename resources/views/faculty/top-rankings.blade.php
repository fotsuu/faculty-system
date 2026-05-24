@extends('layouts.faculty_new', ['activePage' => 'dashboard'])

@section('title', 'Top Rankings - DSSC Faculty System')
@section('page_title', 'Top Performing Students')

@section('content')
    <div class="section">
        <div class="section-header" style="margin-bottom: 18px;">
            <div>
                <div style="font-size: 18px; font-weight: 800; color: #1e3c72; margin-bottom: 4px;">Top Performing Students</div>
                <div style="font-size: 13px; color: #64748b;">
                    Showing students with GWA ≤ 2.0
                    @if(!empty($selectedYearLevel)) — Year Level: {{ $selectedYearLevel }} @endif
                </div>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-bottom: 14px; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('faculty.dashboard') }}" style="font-size:12px; color:#1e3c72; text-decoration:none; font-weight:700;">← Back to Dashboard</a>
        </div>

        @if(!empty($rankedStudents) && count($rankedStudents) > 0)
            <div style="overflow-x:auto; background:white; border:1px solid #edf2f7; border-radius:12px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #edf2f7;">
                            <th style="padding:16px; text-align:left; font-weight:800; color:#1e3c72; width:60px;">#</th>
                            <th style="padding:16px; text-align:left; font-weight:800; color:#1e3c72;">Student</th>
                            <th style="padding:16px; text-align:left; font-weight:800; color:#1e3c72;">Program</th>
                            <th style="padding:16px; text-align:center; font-weight:800; color:#1e3c72; width:140px;">GWA</th>
                            <th style="padding:16px; text-align:left; font-weight:800; color:#1e3c72;">Subjects</th>
                            <th style="padding:16px; text-align:center; font-weight:800; color:#1e3c72; width:120px;">Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rankedStudents as $idx => $row)
                            <tr style="border-bottom:1px solid #edf2f7;">
                                <td style="padding:14px 16px; color:#1e3c72; font-weight:800;">{{ $idx + 1 }}</td>
                                <td style="padding:14px 16px; color:#334155; font-weight:700;">{{ $row['name'] }}</td>
                                <td style="padding:14px 16px; color:#64748b;">{{ $row['program'] ?? '—' }}</td>
                                <td style="padding:14px 16px; text-align:center; color:#1e3c72; font-weight:900;">{{ number_format($row['gpa'], 2) }}</td>
                                <td style="padding:14px 16px; color:#334155;">{{ $row['subjects'] }}</td>
                                <td style="padding:14px 16px; text-align:center; color:#64748b;">{{ $row['recordCount'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 40px; text-align: center; color:#64748b; background:white; border:1px solid #edf2f7; border-radius:12px;">
                <div style="font-size:48px; opacity:0.2; margin-bottom: 10px;">🎓</div>
                <div style="font-size:14px; font-weight:800; color:#64748b; margin-bottom: 6px;">No ranked students found</div>
                <div style="font-size:13px; color:#94a3b8;">Upload class records and try again. Ensure year level filter matches your imported format (e.g. BSIT-3A → 3).</div>
            </div>
        @endif
    </div>
@endsection

