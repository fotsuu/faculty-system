@extends('layouts.dean_new')

@section('title', 'Submission Details - DSSC CRMS')
@section('page_title', 'Submission Details')

@section('styles')
<style>
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-approved {
        background-color: #ecfdf5;
        color: #065f46;
    }
    
    .status-rejected {
        background-color: #fef2f2;
        color: #991b1b;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-label {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .info-value {
        font-size: 15px;
        font-weight: 600;
        color: #1e3c72;
    }
    
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
    
    .student-name {
        font-weight: 600;
        color: #1e3c72;
    }
    
    .grade-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        background-color: #f1f5f9;
        color: #1e3c72;
        border: 1px solid #e2e8f0;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #64748b;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
        transition: color 0.2s;
    }

    .btn-back:hover {
        color: #1e3c72;
    }
</style>
@endsection

@section('content')
    <a href="{{ route('dashboard') }}" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Dashboard
    </a>

    @if ($message = Session::get('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; border: 1px solid #d1fae5;">
            {{ $message }}
        </div>
    @endif
    
    @if ($message = Session::get('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; border: 1px solid #fee2e2;">
            {{ $message }}
        </div>
    @endif

    <div class="section">
        <div class="section-header">
            <h3 class="section-title">Submission Information</h3>
            <span class="status-badge status-{{ $status }}">
                {{ $status }}
            </span>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Faculty Member</div>
                <div class="info-value">{{ $faculty_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Subject</div>
                <div class="info-value">{{ $subject }} ({{ $subject_code }})</div>
            </div>
            <div class="info-item">
                <div class="info-label">Submitted Date</div>
                <div class="info-value">{{ $submitted_date }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Records</div>
                <div class="info-value">{{ $submissions->count() }} students</div>
            </div>
        </div>
        
        @if ($status === 'pending')
            <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #f1f5f9;">
                <form method="POST" action="{{ route('dean.submission.approve', [request()->route('userId'), request()->route('subjectId')]) }}" style="flex: 1;">
                    @csrf
                    <button type="submit" style="width: 100%; padding: 12px; background: #1e3c72; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Approve Submission
                    </button>
                </form>
                <button onclick="document.getElementById('rejectForm').style.display = 'block'" style="flex: 1; padding: 12px; background: #fee2e2; color: #991b1b; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reject Submission
                </button>
            </div>
            
            <div id="rejectForm" style="display: none; margin-top: 20px; padding: 24px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                <form method="POST" action="{{ route('dean.submission.reject', [request()->route('userId'), request()->route('subjectId')]) }}">
                    @csrf
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #1e3c72; margin-bottom: 10px;">Reason for Rejection <span style="color: #dc3545;">*</span></label>
                    <textarea name="notes" placeholder="Please provide a reason for rejection..." style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; min-height: 120px; margin-bottom: 16px; resize: vertical;"></textarea>
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Confirm Rejection</button>
                        <button type="button" onclick="document.getElementById('rejectForm').style.display = 'none'" style="flex: 1; padding: 12px; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
    
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">Student Records</h3>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td>{{ $submission->student->student_id ?? 'N/A' }}</td>
                            <td class="student-name">{{ $submission->student->name ?? 'Unknown' }}</td>
                            <td><span class="grade-badge">{{ $submission->raw_grade ?? 'N/A' }}</span></td>
                            <td style="font-weight: 700; color: #1e3c72;">{{ $submission->grade_point ?? 'N/A' }}</td>
                            <td style="color: #64748b; font-style: italic;">{{ substr($submission->notes ?? '', 0, 50) }}{{ strlen($submission->notes ?? '') > 50 ? '...' : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">No records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
