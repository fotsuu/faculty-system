@extends('layouts.faculty_new', ['activePage' => 'records'])

@section('title', 'Records - DSSC Faculty System')
@section('page_title', 'Class Records')

@section('content')
    <!-- Upload Section -->
    <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #edf2f7; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72;">Upload New Records</h3>
            <span style="font-size: 12px; color: #64748b; background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">Supports CSV, XLS, XLSX</span>
        </div>
        <form action="{{ route('faculty.records.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm" style="display: flex; gap: 12px; align-items: flex-end;">
            @csrf
            <div style="flex: 1;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;">Select Class Record File</label>
                <input type="file" name="file" id="fileInput" required style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc;">
            </div>
            <button type="submit" style="background: #1e3c72; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; height: 42px; display: flex; align-items: center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload File
            </button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="section">
        <div class="section-header">
            <div>
                <div style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">Academic Records</div>
                <div style="font-size: 13px; color: #64748b;">Total: {{ $totalRecords }} records</div>
            </div>
            <div style="position: relative; width: 300px;">
                <input type="text" id="recordSearch" placeholder="Search records..." style="width: 100%; padding: 10px 16px 10px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        @if($records->count() > 0)
        <div style="overflow-x: auto;">
            <table id="recordsTable" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #edf2f7;">
                        <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Student ID</th>
                        <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Student Name</th>
                        <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Subject Code</th>
                        <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Subject Name</th>
                        <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">Grade</th>
                        <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                    <tr style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                        <td style="padding: 14px 16px; font-weight: 600; color: #1e3c72;">{{ $record->student->student_id ?? 'N/A' }}</td>
                        <td style="padding: 14px 16px; color: #334155; font-weight: 500;">{{ $record->student->name ?? 'Unknown' }}</td>
                        <td style="padding: 14px 16px; color: #64748b;">{{ $record->subject->code ?? 'N/A' }}</td>
                        <td style="padding: 14px 16px; color: #64748b;">{{ $record->subject->name ?? 'Unknown' }}</td>
                        <td style="padding: 14px 16px; text-align: center; font-weight: 700; color: #1e3c72;">{{ $record->grade ?? '-' }}</td>
                        <td style="padding: 14px 16px; text-align: center;">
                            <span style="background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Recorded</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="text-align: center; padding: 60px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📋</div>
            <p>No class records found. Upload a file to see your data here.</p>
        </div>
        @endif
    </div>
@endsection

<!-- Unsupported File Modal -->
<div id="unsupportedFileModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);">
        <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
        <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">Unsupported File Type</h3>
        <p style="font-size: 14px; color: #666; margin-bottom: 24px;">Only CSV, XLS, and XLSX files are supported. Please select a valid file and try again.</p>
        <button type="button" onclick="document.getElementById('unsupportedFileModal').style.display='none'" style="background: #1e3c72; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">OK</button>
    </div>
</div>

@section('scripts')
    <script>
        document.getElementById('recordSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#recordsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // File upload validation
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');
        const unsupportedFileModal = document.getElementById('unsupportedFileModal');
        
        const supportedExtensions = ['csv', 'xls', 'xlsx'];
        
        fileInput?.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileExtension = fileName.split('.').pop().toLowerCase();
                
                if (!supportedExtensions.includes(fileExtension)) {
                    unsupportedFileModal.style.display = 'flex';
                    this.value = ''; // Clear the file input
                }
            }
        });
        
        uploadForm?.addEventListener('submit', function(e) {
            if (fileInput.files.length === 0) {
                e.preventDefault();
                unsupportedFileModal.style.display = 'flex';
            }
        });
    </script>
@endsection
