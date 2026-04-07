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
                <div id="uploadStatus" style="display: none; margin-top: 8px; font-size: 12px; color: #059669; display: flex; align-items: center; gap: 6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span id="uploadMessage">Processing file...</span>
                </div>
            </div>
        </form>

        <style>
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>
    </div>

    <!-- Records Cards with Internal Scrolling -->
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

        @php
            $useExcel = !empty($excelBySection) && !empty($excelPreviewData['headers']);
        @endphp

        @if($useExcel)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(500px, 1fr)); gap: 24px;">
                @foreach($excelBySection as $section => $sectionRows)
                    <div class="record-card" data-section="{{ $section }}" style="
                        background: white;
                        border-radius: 12px;
                        border: 1px solid #edf2f7;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                        display: flex;
                        flex-direction: column;
                        height: 550px;
                    ">
                        <!-- Card Header (Fixed) -->
                        <div style="
                            padding: 16px;
                            border-bottom: 2px solid #edf2f7;
                            background: #f8fafc;
                            border-radius: 12px 12px 0 0;
                            flex-shrink: 0;
                        ">
                            <h4 style="font-size: 15px; font-weight: 700; color: #1e3c72; margin: 0;">Section: {{ $section }}</h4>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">{{ count($sectionRows) }} records</p>
                        </div>

                        <!-- Card Body (Scrollable) -->
                        <div style="
                            flex: 1;
                            overflow-y: auto;
                            overflow-x: auto;
                            position: relative;
                        " class="card-scroll-container">
                            <table class="excelRecordsTable" style="width: 100%; border-collapse: collapse; font-size: 13px; table-layout: auto;">
                                <thead style="position: sticky; top: 0; z-index: 10;">
                                    <tr style="background: #f1f5f9; border-bottom: 1px solid #edf2f7;">
                                        @foreach($excelPreviewData['headers'] as $header)
                                            <th style="padding: 12px; text-align: left; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sectionRows as $row)
                                        <tr class="record-row" style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                                            @foreach(range(0, count($excelPreviewData['headers']) - 1) as $idx)
                                                @php
                                                    $value = $row[$idx] ?? '';
                                                    if (is_numeric($value) && strpos($value, '.') !== false) {
                                                        $value = number_format((float)$value, 2, '.', '');
                                                    }
                                                @endphp
                                                <td style="padding: 12px; color: #334155; white-space: nowrap;">{{ $value }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($records->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(550px, 1fr)); gap: 24px;">
                @foreach($recordsBySection as $section => $sectionRecords)
                    <div class="record-card" data-section="{{ $section }}" style="
                        background: white;
                        border-radius: 12px;
                        border: 1px solid #edf2f7;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                        display: flex;
                        flex-direction: column;
                        height: 550px;
                    ">
                        <!-- Card Header (Fixed) -->
                        <div style="
                            padding: 16px;
                            border-bottom: 2px solid #edf2f7;
                            background: #f8fafc;
                            border-radius: 12px 12px 0 0;
                            flex-shrink: 0;
                        ">
                            <h4 style="font-size: 15px; font-weight: 700; color: #1e3c72; margin: 0;">Section: {{ $section }}</h4>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0 0;">{{ count($sectionRecords) }} records</p>
                        </div>

                        <!-- Card Body (Scrollable) -->
                        <div style="
                            flex: 1;
                            overflow-y: auto;
                            overflow-x: auto;
                            position: relative;
                        " class="card-scroll-container">
                            <table class="recordsTable" style="width: 100%; border-collapse: collapse; font-size: 13px; table-layout: auto;">
                                <thead style="position: sticky; top: 0; z-index: 10;">
                                    <tr style="background: #f1f5f9; border-bottom: 1px solid #edf2f7;">
                                        <th style="padding: 12px; text-align: left; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">Student Name</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">Subject Code</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">Subject Name</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">Grade</th>
                                        <th style="padding: 12px; text-align: center; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sectionRecords as $record)
                                    <tr class="record-row" style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 12px; color: #334155; font-weight: 500;">{{ $record->student->name ?? 'Unknown' }}</td>
                                        <td style="padding: 12px; color: #64748b;">{{ $record->subject->code ?? 'N/A' }}</td>
                                        <td style="padding: 12px; color: #64748b;">{{ $record->subject->name ?? 'Unknown' }}</td>
                                        <td style="padding: 12px; text-align: center; font-weight: 700; color: #1e3c72;">
                                            @php
                                                $grade = $record->grade ?? '-';
                                                if (is_numeric($grade) && strpos((string)$grade, '.') !== false) {
                                                    $grade = number_format((float)$grade, 2, '.', '');
                                                }
                                            @endphp
                                            {{ $grade }}
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Recorded</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
        <div style="text-align: center; padding: 60px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📋</div>
            <p>No class records found. Upload a file to see your data here.</p>
        </div>
        @endif
    </div>

    <!-- Scrollbar Styling for Cards -->
    <style>
        .card-scroll-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .card-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .card-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .card-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Firefox scrollbar */
        .card-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        /* Responsive adjustment for smaller screens */
        @media (max-width: 1200px) {
            .record-card {
                grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)) !important;
            }
        }

        @media (max-width: 768px) {
            .section {
                display: grid !important;
                grid-template-columns: 1fr !important;
            }
        }
    </style>
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
            const cards = document.querySelectorAll('.record-card');

            cards.forEach(card => {
                const rows = card.querySelectorAll('.record-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Hide card if no results match in this card
                card.style.display = visibleCount > 0 || searchTerm === '' ? '' : 'none';
            });
        });

        // File upload validation and auto-submit
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');
        const unsupportedFileModal = document.getElementById('unsupportedFileModal');
        const uploadStatus = document.getElementById('uploadStatus');
        const uploadMessage = document.getElementById('uploadMessage');
        
        const supportedExtensions = ['csv', 'xls', 'xlsx'];
        
        fileInput?.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileExtension = fileName.split('.').pop().toLowerCase();
                
                if (!supportedExtensions.includes(fileExtension)) {
                    unsupportedFileModal.style.display = 'flex';
                    this.value = ''; // Clear the file input
                    uploadStatus.style.display = 'none';
                } else {
                    // Valid file - show loading status and auto-submit
                    uploadStatus.style.display = 'flex';
                    uploadMessage.textContent = `Loading ${fileName}...`;
                    setTimeout(() => {
                        uploadForm.submit();
                    }, 300); // Small delay for UX feedback
                }
            }
        });
        
        uploadForm?.addEventListener('submit', function(e) {
            if (fileInput.files.length === 0) {
                e.preventDefault();
                unsupportedFileModal.style.display = 'flex';
                uploadStatus.style.display = 'none';
            }
        });
    </script>
@endsection
