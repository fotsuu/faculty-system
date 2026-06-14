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
                                @php
                                    // Normalize headers and choose ordering: Student Name first, then existing headers, Remarks/Status last
                                    $rawHeaders = $excelPreviewData['headers'] ?? [];
                                    // find indices for name-like and remark/status-like headers
                                    $nameIdx = null;
                                    $remarkIdx = null;
                                    foreach ($rawHeaders as $i => $h) {
                                        $low = strtolower(trim((string)$h));
                                        if ($nameIdx === null && (strpos($low, 'name') !== false || strpos($low, 'student') !== false)) {
                                            $nameIdx = $i;
                                        }
                                        if ($remarkIdx === null && (strpos($low, 'remark') !== false || strpos($low, 'remarks') !== false || strpos($low, 'status') !== false || strpos($low, 'passed') !== false || strpos($low, 'failed') !== false)) {
                                            $remarkIdx = $i;
                                        }
                                    }

                                    $orderedHeaders = [];
                                    if ($nameIdx !== null) {
                                        $orderedHeaders[] = $rawHeaders[$nameIdx];
                                    } else {
                                        $orderedHeaders[] = 'Student Name';
                                    }

                                    // Add all headers except the chosen name/remark ones
                                    foreach ($rawHeaders as $i => $h) {
                                        if ($i === $nameIdx || $i === $remarkIdx) continue;
                                        $orderedHeaders[] = $h;
                                    }

                                    if ($remarkIdx !== null) {
                                        $orderedHeaders[] = $rawHeaders[$remarkIdx];
                                    } else {
                                        $orderedHeaders[] = 'Remarks';
                                    }
                                @endphp

                                <thead style="position: sticky; top: 0; z-index: 10;">
                                    <tr style="background: #f1f5f9; border-bottom: 1px solid #edf2f7;">
                                        @foreach($orderedHeaders as $hIdx => $header)
                                            @php
                                                $label = trim((string)($header ?? '')) ?: ($hIdx === 0 ? 'Student Name' : '');
                                                $thStyle = 'padding: 12px; font-weight: 700; color: #1e3c72; font-size: 12px;';
                                                $thStyle .= $hIdx === 0 ? ' text-align: left;' : ' text-align: center;';
                                            @endphp
                                            <th style="{{ $thStyle }}">{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($sectionRows as $row)
                                        <tr class="record-row" style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                                            @foreach($orderedHeaders as $colIdx => $header)
                                                @php
                                                    $raw = '';
                                                    $foundOrig = null;
                                                    // try to find original index for this header
                                                    foreach ($rawHeaders as $i => $h) {
                                                        if (strtolower(trim((string)$h)) === strtolower(trim((string)$header))) { $foundOrig = $i; break; }
                                                    }

                                                    if ($foundOrig !== null) {
                                                        $raw = $row[$foundOrig] ?? '';
                                                    } else {
                                                        // fallback heuristics
                                                        if ($colIdx === 0) {
                                                            $raw = $row[$nameIdx ?? 0] ?? $row[0] ?? '';
                                                        } elseif ($colIdx === count($orderedHeaders) - 1) {
                                                            $raw = $row[$remarkIdx ?? (count($row) - 1)] ?? '';
                                                        } else {
                                                            $raw = $row[$colIdx] ?? '';
                                                        }
                                                    }

                                                    if (is_array($raw) || is_object($raw)) { $raw = json_encode($raw); }
                                                    $raw = (string)$raw;
                                                    $trimmed = trim($raw);
                                                    $isNumeric = $trimmed !== '' && is_numeric($trimmed);
                                                    if ($isNumeric) { $display = number_format((float)$trimmed, 2, '.', ''); }
                                                    else { $display = $raw; }

                                                    // styles: name column left and allow wrap; numeric center; remarks left
                                                    if ($colIdx === 0) {
                                                        $tdStyle = 'padding: 12px; color: #334155; text-align: left; white-space: normal;';
                                                    } elseif ($colIdx === count($orderedHeaders) - 1) {
                                                        $tdStyle = 'padding: 12px; color: #64748b; text-align: left; white-space: nowrap;';
                                                    } else {
                                                        $tdStyle = 'padding: 12px; color: #334155; ' . ($isNumeric ? 'text-align: center; font-weight:700; white-space: nowrap;' : 'text-align: left; white-space: nowrap;');
                                                    }
                                                @endphp
                                                <td style="{{ $tdStyle }}">{{ $display }}</td>
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
        
        const supportedExtensions = ['csv', 'xls', 'xlsx'];
        
        fileInput?.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileExtension = fileName.split('.').pop().toLowerCase();
                
                if (!supportedExtensions.includes(fileExtension)) {
                    unsupportedFileModal.style.display = 'flex';
                    this.value = ''; // Clear the file input
                } else {
                    // Valid file - auto-submit
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
            }
        });
    </script>
@endsection
