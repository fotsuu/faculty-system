@extends('layouts.faculty_new', ['activePage' => 'students'])

@section('title', 'Students - DSSC Faculty System')
@section('page_title', 'My Students')

@section('content')
    <div class="section">
        <div class="section-header">
            <div>
                <div style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 4px;">Student List</div>
                <div style="font-size: 13px; color: #64748b;">Total: {{ $totalStudents }} students</div>
            </div>
        </div>

        @if(isset($studentGroups) && count($studentGroups) > 0)
            @foreach($studentGroups as $group)
                <div style="background: white; border-radius: 12px; padding: 18px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #edf2f7; margin-bottom: 18px;">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 16px; font-weight: 800; color: #1e3c72;">
                                {{ $group['subject_code'] }} — {{ $group['section'] }}
                            </div>
                            <div style="font-size: 12px; color: #64748b;">
                                {{ $group['subject_name'] }}
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #64748b;">
                            {{ count($group['students']) }} students
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #edf2f7;">
                                    <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Name</th>
                                    <th style="padding: 16px; text-align: left; font-weight: 700; color: #1e3c72;">Program</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">Records</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">GPA</th>
                                    <th style="padding: 16px; text-align: center; font-weight: 700; color: #1e3c72;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['students'] as $student)
                                    <tr style="border-bottom: 1px solid #edf2f7; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 14px 16px; color: #334155; font-weight: 600;">{{ $student['name'] }}</td>
                                        <td style="padding: 14px 16px; color: #64748b;">{{ $student['program'] }}</td>
                                        <td style="padding: 14px 16px; text-align: center; color: #64748b;">{{ $student['recordCount'] }}</td>
                                        <td style="padding: 14px 16px; text-align: center; font-weight: 700; color: #1e3c72;">{{ $student['gpa'] ?? '-' }}</td>
                                        <td style="padding: 14px 16px; text-align: center;">
                                            <button
                                                onclick="viewStudentDetails({{ json_encode($student['name']) }}, {{ (int)$student['id'] }}, {{ json_encode($group['subject_id']) }}, {{ json_encode($group['section']) }})"
                                                style="background: #1e3c72; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s ease;"
                                            >
                                                View Records
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @else
        <div style="text-align: center; padding: 40px; color: #64748b;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 64px; height: 64px; margin: 0 auto;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <p>No students found in your records.</p>
        </div>
        @endif
    </div>

    <!-- Additional Content Below -->
    <div class="section" style="margin-top: 20px; margin-bottom: 30px;">
        <div class="section-header">
            <h3 class="section-title">Additional Information</h3>
            <div style="font-size: 13px; color: #64748b;">This section shows the content that is rendered below the student list.</div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #edf2f7;">
            <p style="margin: 0; color: #334155;">Use this area to add contextual notes, reminders, or additional data outside the student table.</p>
        </div>
    </div>

    <!-- Student Grades Modal -->
    <div id="studentGradesModal" class="modal-overlay" onclick="if(event.target.id === 'studentGradesModal') closeGradesModal()">
        <div class="modal-box" style="max-width: 1000px; width: 95%; max-height: 90vh; overflow-y: auto; text-align: left; padding: 40px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f1f5f9;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; color: #1e3c72; margin: 0;">Academic Record</h2>
                    <p style="font-size: 14px; color: #64748b; margin-top: 4px;">
                        <span id="modalStudentName" style="font-weight: 700; color: #334155;"></span> 
                        <span style="margin: 0 8px; opacity: 0.3;">•</span>
                        <span style="color:#64748b;">Student</span>
                    </p>
                </div>
                <button onclick="closeGradesModal()" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b;">✕</button>
            </div>

            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 30px; border-left: 4px solid #1e3c72;">
                <p style="font-size: 14px; color: #334155;"><strong>Summary</strong> - Complete record of attendance, quizzes, and exams</p>
            </div>

            <!-- Grade Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <!-- Attendance Card -->
                <div class="grade-card" onclick="showRawRecords('attendance')" style="cursor: pointer; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s ease;">
                    <div style="font-size: 12px; font-weight: 700; color: #1e3c72; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Attendance</div>
                    <div id="attendanceTotal" style="font-size: 36px; font-weight: 800; color: #1e3c72; margin-bottom: 4px;">0</div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 16px;">Total Sessions</div>
                    <div style="display: flex; justify-content: space-around; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                        <div>
                            <div style="font-size: 10px; color: #64748b; font-weight: 600;">Present</div>
                            <div id="attendancePresent" style="font-size: 14px; color: #059669; font-weight: 700;">0</div>
                        </div>
                        <div>
                            <div style="font-size: 10px; color: #64748b; font-weight: 600;">Absent</div>
                            <div id="attendanceAbsent" style="font-size: 14px; color: #dc2626; font-weight: 700;">0</div>
                        </div>
                    </div>
                </div>

                <!-- Quizzes Card -->
                <div class="grade-card" onclick="showRawRecords('quizzes')" style="cursor: pointer; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s ease;">
                    <div style="font-size: 12px; font-weight: 700; color: #1e3c72; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Quizzes (incl. Midterm/Final)</div>
                    <div id="quizzesAverage" style="font-size: 36px; font-weight: 800; color: #1e3c72; margin-bottom: 4px;">0.00</div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 16px;">Average Score</div>
                    <div style="padding-top: 16px; border-top: 1px solid #f1f5f9;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">Records</div>
                        <div id="quizzesTotal" style="font-size: 14px; color: #1e3c72; font-weight: 700;">0</div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 11px; color: #64748b;">
                        <div>
                            <div style="font-weight: 700; color: #0ea5e9;">Lab</div>
                            <div><span id="labQuizCount">0</span> / <span id="labQuizAverage">0.00</span></div>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #16a34a;">Non-Lab</div>
                            <div><span id="nonLabQuizCount">0</span> / <span id="nonLabQuizAverage">0.00</span></div>
                        </div>
                    </div>
                </div>

                <!-- Midterm Card -->
                <div class="grade-card" onclick="showRawRecords('midterm')" style="cursor: pointer; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s ease;">
                    <div style="font-size: 12px; font-weight: 700; color: #1e3c72; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Midterm</div>
                    <div id="midtermAverage" style="font-size: 36px; font-weight: 800; color: #1e3c72; margin-bottom: 4px;">0.00</div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 16px;">Average Score</div>
                    <div style="padding-top: 16px; border-top: 1px solid #f1f5f9;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">Records</div>
                        <div id="midtermCount" style="font-size: 14px; color: #1e3c72; font-weight: 700;">0</div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 6px;">Lab: <span id="midtermLabScore">0.00</span>, Non-Lab: <span id="midtermNonLabScore">0.00</span></div>
                    </div>
                </div>

                <!-- Final Card -->
                <div class="grade-card" onclick="showRawRecords('final')" style="cursor: pointer; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s ease;">
                    <div style="font-size: 12px; font-weight: 700; color: #1e3c72; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Final Exam</div>
                    <div id="finalAverage" style="font-size: 36px; font-weight: 800; color: #1e3c72; margin-bottom: 4px;">0.00</div>
                    <div style="font-size: 11px; color: #64748b; margin-bottom: 16px;">Average Score</div>
                    <div style="padding-top: 16px; border-top: 1px solid #f1f5f9;">
                        <div style="font-size: 10px; color: #64748b; font-weight: 600;">Records</div>
                        <div id="finalCount" style="font-size: 14px; color: #1e3c72; font-weight: 700;">0</div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 6px;">Lab: <span id="finalLabScore">0.00</span>, Non-Lab: <span id="finalNonLabScore">0.00</span></div>
                    </div>
                </div>
            </div>

            <!-- Raw Records Detail Section -->
            <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
                <button onclick="showRawRecords('midtermLecture')" style="padding: 8px 14px; background: #1e3c72; color: white; border: none; border-radius: 6px; cursor: pointer;">Midterm Lecture Quizzes</button>
                <button onclick="showRawRecords('midtermLab')" style="padding: 8px 14px; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer;">Midterm Lab Quizzes</button>
                <button onclick="showRawRecords('finalLab')" style="padding: 8px 14px; background: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer;">Final Laboratory Activities</button>
                <button onclick="showRawRecords('finalNonLab')" style="padding: 8px 14px; background: #f59e0b; color: white; border: none; border-radius: 6px; cursor: pointer;">Final Non-Laboratory Activities</button>
            </div>
            <div id="rawRecordsSection" style="display: none; margin-top: 20px; animation: fadeIn 0.3s ease;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9;">
                    <h3 id="rawRecordsTitle" style="color: #1e3c72; font-size: 18px; font-weight: 700;">Raw Records</h3>
                    <button onclick="hideRawRecords()" style="background: #f1f5f9; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 12px; color: #64748b; font-weight: 600;">Hide Details</button>
                </div>
                <div id="rawRecordsTableContainer" style="overflow-x: auto; max-height: 400px; border: 1px solid #f1f5f9; border-radius: 8px;">
                    <!-- Table will be injected here -->
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                <button onclick="closeGradesModal()" style="padding: 10px 24px; background: #f1f5f9; border: none; border-radius: 8px; font-weight: 600; color: #64748b; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .grade-card:hover {
            transform: translateY(-5px);
            border-color: #1e3c72 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endpush

@section('scripts')
    <script>
        function viewStudentDetails(name, studentDbId, subjectId = null, section = null) {
            const modal = document.getElementById('studentGradesModal');
            document.getElementById('modalStudentName').textContent = name;
            modal.classList.add('show');
            loadStudentGrades(studentDbId, subjectId, section);
        }

        function closeGradesModal() {
            document.getElementById('studentGradesModal').classList.remove('show');
        }

        let currentStudentGrades = null;

        async function loadStudentGrades(studentDbId, subjectId = null, section = null) {
            try {
                const params = new URLSearchParams();
                if (subjectId) params.set('subject_id', subjectId);
                if (section) params.set('section', section);

                const qs = params.toString();
                const url = qs ? `/faculty/students/${studentDbId}/grades?${qs}` : `/faculty/students/${studentDbId}/grades`;

                const response = await fetch(url);
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                currentStudentGrades = data;
                
                hideRawRecords();

                document.getElementById('attendanceTotal').textContent = data.attendance_total || 0;
                document.getElementById('attendancePresent').textContent = data.attendance_present || 0;
                document.getElementById('attendanceAbsent').textContent = data.attendance_absent || 0;
                
                document.getElementById('quizzesTotal').textContent = data.all_quizzes_with_exams_total || data.quizzes_total || 0;
                document.getElementById('quizzesAverage').textContent = (data.all_quizzes_with_exams_average || data.quizzes_average || 0).toFixed(2);
                document.getElementById('labQuizCount').textContent = data.lab_quizzes_total || 0;
                document.getElementById('labQuizAverage').textContent = (data.lab_quizzes_average || 0).toFixed(2);
                document.getElementById('nonLabQuizCount').textContent = data.non_lab_quizzes_total || 0;
                document.getElementById('nonLabQuizAverage').textContent = (data.non_lab_quizzes_average || 0).toFixed(2);
                
                document.getElementById('midtermCount').textContent = data.midterm_count || 0;
                document.getElementById('midtermAverage').textContent = (data.midterm_average || 0).toFixed(2);
                document.getElementById('midtermLabScore').textContent = (data.lab_midterm_score || 0).toFixed(2);
                document.getElementById('midtermNonLabScore').textContent = (data.non_lab_midterm_score || 0).toFixed(2);

                document.getElementById('finalCount').textContent = data.final_count || 0;
                document.getElementById('finalAverage').textContent = (data.final_average || 0).toFixed(2);
                document.getElementById('finalLabScore').textContent = (data.lab_final_score || 0).toFixed(2);
                document.getElementById('finalNonLabScore').textContent = (data.non_lab_final_score || 0).toFixed(2);
            } catch (error) {
                console.error('Error loading student grades:', error);
                alert('Error loading student grades. Please try again.');
            }
        }

        function showRawRecords(type) {
            if (!currentStudentGrades) return;
            
            const container = document.getElementById('rawRecordsTableContainer');
            const title = document.getElementById('rawRecordsTitle');
            const section = document.getElementById('rawRecordsSection');
            
            let records = [];
            let html = '';
            
            switch(type) {
                case 'attendance':
                    title.textContent = 'Attendance Records';
                    records = currentStudentGrades.attendance_records || [];
                    html = createTable(['Subject', 'Date', 'Status'], records.map(r => [r.subject, r.date, r.status]));
                    break;
                case 'quizzes':
                    title.textContent = 'Quiz Records (including Midterm/Final)';
                    records = currentStudentGrades.quiz_records || [];
                    html = createTable(
                        ['Subject', 'Type', 'Assessment', 'Score'],
                        records.map(r => [
                            r.subject,
                            r.type ? r.type.replace('_', ' ').toUpperCase() : 'Quiz',
                            r.name,
                            r.score
                        ])
                    );
                    break;
                case 'midtermLecture':
                    title.textContent = 'Midterm Lecture Quizzes';
                    records = currentStudentGrades.midterm_lecture_quiz_records || [];
                    html = createTable(['Subject', 'Assessment', 'Score'], records.map(r => [r.subject, r.name, r.score]));
                    break;
                case 'midtermLab':
                    title.textContent = 'Midterm Laboratory Quizzes';
                    records = currentStudentGrades.midterm_lab_quiz_records || [];
                    html = createTable(['Subject', 'Assessment', 'Score'], records.map(r => [r.subject, r.name, r.score]));
                    break;
                case 'finalLab':
                    title.textContent = 'Final Laboratory Activities';
                    records = currentStudentGrades.final_lab_activity_records || [];
                    html = createTable(['Subject', 'Assessment', 'Score'], records.map(r => [r.subject, r.name, r.score]));
                    break;
                case 'finalNonLab':
                    title.textContent = 'Final Non-Laboratory Activities';
                    records = currentStudentGrades.final_non_lab_activity_records || [];
                    html = createTable(['Subject', 'Assessment', 'Score'], records.map(r => [r.subject, r.name, r.score]));
                    break;
                case 'midterm':
                    title.textContent = 'Midterm Records';
                    records = currentStudentGrades.midterm_records || [];
                    html = createTable(['Subject', 'Exam Name', 'Score'], records.map(r => [r.subject, r.name, r.score]));
                    break;
                case 'final':
                    title.textContent = 'Final Exam Records';
                    records = currentStudentGrades.final_records || [];
                    html = createTable(['Subject', 'Exam Name', 'Score'], records.map(r => [r.subject, r.name, r.score]));
                    break;
            }
            
            container.innerHTML = html;
            section.style.display = 'block';
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function hideRawRecords() {
            document.getElementById('rawRecordsSection').style.display = 'none';
        }

        function createTable(headers, rows) {
            if (rows.length === 0) {
                return '<div style="text-align: center; padding: 24px; color: #64748b; font-size: 14px;">No records found for this category.</div>';
            }
            
            let html = '<table style="width: 100%; border-collapse: collapse; font-size: 13px;">';
            
            html += '<thead style="background: #f8fafc; position: sticky; top: 0;"><tr>';
            headers.forEach(h => {
                html += `<th style="padding: 12px; text-align: left; font-weight: 700; color: #1e3c72; border-bottom: 2px solid #edf2f7;">${h}</th>`;
            });
            html += '</tr></thead>';
            
            html += '<tbody>';
            rows.forEach(row => {
                html += '<tr style="border-bottom: 1px solid #edf2f7;">';
                row.forEach((cell, index) => {
                    let cellStyle = 'padding: 10px 12px; color: #334155;';
                    if (cell === 'Present') cellStyle += 'color: #059669; font-weight: 600;';
                    if (cell === 'Absent') cellStyle += 'color: #dc2626; font-weight: 600;';
                    html += `<td style="${cellStyle}">${cell}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            return html;
        }
    </script>
@endsection
