@extends('layouts.program_head_new', ['activePage' => 'class-records'])

@section('title', 'View Class Record - Program Head Dashboard')
@section('page_title', 'Class Record Details')

@section('content')
    <div class="section">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #edf2f7;">
            <div>
                <a href="{{ route('program-head.class-records') }}" style="display: inline-flex; align-items: center; color: #1e3c72; text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px; margin-right: 6px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
                <h3 style="font-size: 20px; font-weight: 700; color: #1e3c72; margin: 0;">{{ $displayName }}</h3>
                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                    Uploaded by <span style="font-weight: 600; color: #1e3c72;">{{ $uploader }}</span> on {{ optional($uploadedAt)->format('M d, Y g:i A') }}
                </div>
            </div>
            <div style="position: relative; width: 300px;">
                <input type="text" id="recordSearch" placeholder="Search records..." style="width: 100%; padding: 10px 16px 10px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        @if(!empty($excelBySection) && !empty($headers))
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
                                        @foreach($headers as $header)
                                            <th style="padding: 12px; text-align: left; font-weight: 700; color: #1e3c72; white-space: nowrap; font-size: 12px;">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sectionRows as $row)
                                        <tr class="record-row" style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                                            @foreach(range(0, count($headers) - 1) as $idx)
                                                @php
                                                    $value = $row[$idx] ?? '';
                                                    if (is_numeric($value) && strpos((string)$value, '.') !== false) {
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
        @else
            <div style="text-align: center; padding: 60px; color: #64748b;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📋</div>
                <p>No records found in this file.</p>
            </div>
        @endif
    </div>

    <style>
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #edf2f7;
            margin-bottom: 24px;
        }

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

        .card-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
    </style>
@endsection

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
    </script>
@endsection
