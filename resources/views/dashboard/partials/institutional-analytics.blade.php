@php
    $totalPass = $totalPass ?? 0;
    $totalFail = $totalFail ?? 0;
    $isPassFailEmpty = ($totalPass + $totalFail) == 0;
    $attendanceRaw = $attendanceTrends ?? [];
    $isAttendanceEmpty = empty($attendanceRaw) || (is_array($attendanceRaw) && count($attendanceRaw) === 0);
    $selectedSubjectId = $selectedSubjectId ?? null;
    $selectedSection = $selectedSection ?? null;
@endphp

@if(isset($filterSubjects) || isset($filterSections))
<div class="section" style="margin-bottom: 24px;">
    <div class="section-header" style="display:flex; align-items:flex-end; justify-content:space-between; gap: 16px;">
        <div>
            <h3 class="section-title">Analytics Filters</h3>
            <div class="section-subtitle">View overall analytics, or filter by subject/section</div>
        </div>
        <form method="GET" style="display:flex; gap: 10px; align-items:flex-end; flex-wrap: wrap;" id="institutionalFilterForm">
            <input type="hidden" name="subject_id" id="institutionalSubjectId" value="{{ $selectedSubjectId ?? '' }}">
            <input type="hidden" name="section" id="institutionalSection" value="{{ $selectedSection ?? '' }}">
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px;">Subject / Section</label>
                @php
                    $selectedKey = '';
                    if (!empty($selectedSubjectId) && !empty($selectedSection)) {
                        $selectedKey = $selectedSubjectId . '||' . $selectedSection;
                    }
                @endphp
                <select id="institutionalSubjectSectionSelect" style="min-width: 320px; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;">
                    <option value="">All Subjects (Overall)</option>
                    @foreach(($subjectSectionOptions ?? []) as $opt)
                        <option value="{{ $opt['value'] }}" {{ $selectedKey === $opt['value'] ? 'selected' : '' }}>
                            {{ $opt['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
<script>
    (function() {
        const form = document.getElementById('institutionalFilterForm');
        const select = document.getElementById('institutionalSubjectSectionSelect');
        const sid = document.getElementById('institutionalSubjectId');
        const sec = document.getElementById('institutionalSection');
        if (!form || !select || !sid || !sec) return;
        select.addEventListener('change', function() {
            const selected = select.value || '';
            if (!selected.includes('||')) {
                sid.value = '';
                sec.value = '';
            } else {
                const parts = selected.split('||');
                sid.value = parts[0] || '';
                sec.value = parts[1] || '';
            }
            form.submit();
        });
    })();
</script>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 25px;">
    <div class="section">
        <div class="section-header">
            <div>
                <h3 class="section-title">Institutional Performance</h3>
                <div class="section-subtitle">Overall Pass/Fail ratio across all departments</div>
            </div>
        </div>
        <div class="chart-container" style="position: relative;">
            @if($isPassFailEmpty)
                <div style="height: 300px; display:flex; align-items:center; justify-content:center; color:#64748b; font-weight:700; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">No pass/fail analytics data available yet.</div>
            @else
                <canvas id="passFailChart"></canvas>
            @endif
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 25px;">
    <div class="section">
        <div class="section-header">
            <div>
                <h3 class="section-title">Attendance Trends</h3>
                <div class="section-subtitle">Institutional student attendance for the current semester</div>
            </div>
        </div>
        <div class="chart-container">
            @if($isAttendanceEmpty)
                <div style="height: 300px; display:flex; align-items:center; justify-content:center; color:#64748b; font-weight:700; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">No attendance analytics data available yet.</div>
            @else
                <canvas id="attendanceChart"></canvas>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <div>
                <h3 class="section-title">Institutional Top Performers</h3>
                <div class="section-subtitle">Based on cumulative GPA across all programs</div>
            </div>
        </div>
        <div style="margin-top: 10px;">
            @forelse($topStudents as $index => $performer)
                <div class="performer-item">
                    <div class="performer-rank">#{{ $index + 1 }}</div>
                    <div class="performer-info">
                        <div class="performer-name">{{ $performer['name'] }}</div>
                        <div class="performer-id">{{ $performer['program'] ?? ($performer['subjects'] ?? 'N/A') }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="performer-gpa">{{ $performer['gpa'] }}</div>
                        <div class="performer-subject">{{ $performer['recordCount'] }} records</div>
                    </div>
                </div>
            @empty
                <div style="padding: 20px; text-align: center; color: #999;">No performance data available.</div>
            @endforelse
        </div>
    </div>
</div>