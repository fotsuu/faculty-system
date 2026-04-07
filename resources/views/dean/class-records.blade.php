@extends('layouts.dean_new', ['activePage' => 'class-records'])

@section('title', 'Class Records - Dean Dashboard')
@section('page_title', 'Faculty Uploaded Class Records')

@section('content')
    <div class="section">
        <div class="section-header">
            <div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">Faculty Uploaded Class Records</h3>
                <div style="font-size: 13px; color: #64748b;">All class record uploads from faculty across all departments</div>
            </div>
            <div style="position: relative; width: 300px;">
                <input type="text" id="recordSearch" placeholder="Search records..." style="width: 100%; padding: 10px 16px 10px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        @if($classRecordGroups->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px;">
                @foreach($classRecordGroups as $group)
                    <div class="class-record-card" style="background: white; border-radius: 16px; border: 1px solid #edf2f7; box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06); display: flex; flex-direction: column; overflow: hidden;">
                        <div style="padding: 22px; border-bottom: 1px solid #edf2f7; background: #f8fafc;">
                            <div style="font-size: 14px; font-weight: 700; color: #1e3c72; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $group->display_name }}</div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <div style="font-size: 12px; color: #64748b;">By {{ $group->uploaded_by }}</div>
                                <div style="font-size: 10px; font-weight: 700; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">{{ $group->department }}</div>
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">{{ optional($group->uploaded_at)->format('M d, Y g:i A') }}</div>
                        </div>
                        <div style="padding: 18px 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; flex: 1;">
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px;">
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;">Records</div>
                                <div style="font-size: 28px; font-weight: 800; color: #1e3c72;">{{ $group->record_count }}</div>
                            </div>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px;">
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;">Subjects</div>
                                <div style="font-size: 28px; font-weight: 800; color: #1e3c72;">{{ $group->subject_count }}</div>
                            </div>
                        </div>
                        <div style="padding: 16px 22px; border-top: 1px solid #edf2f7; background: #f8fafc;">
                            <a href="{{ route('dean.class-records.view', ['user_id' => $group->user_id ?? $group->id, 'file_name' => $group->file_name]) }}" 
                               style="display: block; width: 100%; padding: 12px; text-align: center; background: #1e3c72; color: white; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 6px rgba(30, 60, 114, 0.2);"
                               onmouseover="this.style.background='#2a5298'; this.style.transform='translateY(-2px)';"
                               onmouseout="this.style.background='#1e3c72'; this.style.transform='translateY(0)';"
                               >
                               View Class Record Content
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px solid #edf2f7;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📁</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">No uploaded class records found</h3>
                <p style="font-size: 14px; color: #64748b;">No faculty members have uploaded class record files yet.</p>
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

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #edf2f7;
        }

        .class-record-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .class-record-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.12) !important;
            border-color: #cbd5e1 !important;
        }
    </style>

    <script>
        document.getElementById('recordSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.class-record-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
@endsection
