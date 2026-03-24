@extends('layouts.faculty_new', ['activePage' => 'subjects'])

@section('title', 'Subjects - DSSC CRMS')
@section('page_title', 'My Subjects')

@section('content')
    <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
        <button onclick="openCreateSubjectModal()" style="background: #1e3c72; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Subject
        </button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        @foreach($subjects as $subject)
            <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #edf2f7; transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 12px; font-weight: 700; color: #1e3c72; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">{{ $subject->code }}</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 12px;">{{ $subject->name }}</h3>
                <p style="font-size: 14px; color: #64748b; margin-bottom: 20px; line-height: 1.5; min-height: 42px;">{{ Str::limit($subject->description, 80) }}</p>
                
                <div style="display: flex; gap: 20px; margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div>
                        <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">Students</div>
                        <div style="font-size: 16px; font-weight: 700; color: #1e3c72;">{{ $subject->records->unique('student_id')->count() }}</div>
                    </div>
                    <div style="border-left: 1px solid #e2e8f0;"></div>
                    <div>
                        <div style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">Records</div>
                        <div style="font-size: 16px; font-weight: 700; color: #1e3c72;">{{ $subject->records->count() }}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <button class="subject-card-btn" data-subject-name="{{ $subject->name }}" data-subject-id="{{ $subject->id }}" style="background: #1e3c72; color: white; border: none; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">View Records</button>
                    <button onclick="openEditSubjectModal('{{ $subject->id }}', '{{ $subject->code }}', '{{ $subject->name }}', '{{ $subject->description }}')" style="background: white; color: #1e3c72; border: 1px solid #1e3c72; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Edit Info</button>
                </div>
                
                <form action="{{ route('faculty.subjects.delete', $subject->id) }}" method="POST" style="margin-top: 12px;" onsubmit="return confirm('Are you sure you want to delete this subject and all its records?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="width: 100%; background: none; border: none; color: #dc2626; font-size: 12px; font-weight: 600; cursor: pointer; opacity: 0.6; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">Delete Subject</button>
                </form>
            </div>
        @endforeach
    </div>

    @if($subjects->isEmpty())
        <div style="text-align: center; padding: 60px; background: white; border-radius: 12px; border: 2px dashed #e2e8f0;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;">📚</div>
            <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 8px;">No Subjects Added</h3>
            <p style="color: #64748b; margin-bottom: 24px;">Start by adding your assigned subjects to manage records.</p>
            <button onclick="openCreateSubjectModal()" style="background: #1e3c72; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">Add Your First Subject</button>
        </div>
    @endif

    <!-- Subject Class Records Modal -->
    <div id="subjectStudentsModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; padding:30px; width:95%; max-width:1200px; max-height:90vh; overflow:hidden; display:flex; flex-direction:column; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid #edf2f7;">
                <div>
                    <h3 id="subjectStudentsModalTitle" style="margin:0; font-size:20px; font-weight:800; color:#1e3c72;"></h3>
                    <p style="font-size:13px; color:#64748b; margin-top:4px;">Manage individual student records and grades for this subject.</p>
                </div>
                <div style="display:flex; gap:12px;">
                    <button id="btn-bulk-delete-records" style="background:#dc2626; color:white; border:0; padding:10px 16px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:8px;">
                        🗑️ Bulk Delete
                    </button>
                    <button id="subjectStudentsModalClose" style="background:#f1f5f9; border:0; padding:10px 16px; border-radius:8px; cursor:pointer; color:#64748b; font-weight:600;">Close</button>
                </div>
            </div>
            <div style="overflow:auto; border:1px solid #edf2f7; border-radius:8px; flex:1;">
                <table id="subjectStudentsTable" style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr id="subjectStudentsTableHead" style="background:#f8fafc; position:sticky; top:0; z-index:10;">
                            <th style="padding:12px; text-align:center; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:100px;">Student ID</th>
                            <th style="padding:12px; text-align:left; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:180px;">Name</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit Subject Modal -->
    <div id="subjectModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; padding:32px; width:100%; max-width:480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <h3 id="modalTitle" style="font-size:20px; font-weight:800; color:#1e3c72; margin-bottom:24px;">Add Subject</h3>
            <form id="subjectForm" action="{{ route('faculty.subjects.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:8px;">Subject Code</label>
                    <input type="text" name="code" id="subjectCode" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px;">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:8px;">Subject Name</label>
                    <input type="text" name="name" id="subjectName" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px;">
                </div>
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:8px;">Description</label>
                    <textarea name="description" id="subjectDescription" rows="3" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; font-family:inherit;"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" onclick="closeSubjectModal()" style="padding:10px 20px; background:#f1f5f9; border:none; border-radius:8px; font-weight:600; color:#64748b; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 20px; background:#1e3c72; border:none; border-radius:8px; font-weight:600; color:white; cursor:pointer;">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentModal = document.getElementById('subjectStudentsModal');
        const modalTitle = document.getElementById('subjectStudentsModalTitle');
        const modalClose = document.getElementById('subjectStudentsModalClose');
        const tableBody = document.querySelector('#subjectStudentsTable tbody');
        const tableHead = document.getElementById('subjectStudentsTableHead');
        const bulkDeleteBtn = document.getElementById('btn-bulk-delete-records');
        
        let currentSubjectId = null;

        function buildHeaders(scoreKeys) {
            let headerHtml = `
                <th style="padding:12px; text-align:center; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:100px;">Student ID</th>
                <th style="padding:12px; text-align:left; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:180px;">Name</th>
            `;
            
            scoreKeys.forEach(k => {
                const cleaned = String(k).replace(/_/g, ' ').trim();
                const displayLabel = cleaned.split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                headerHtml += `<th style="padding:12px; text-align:center; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:70px;">${displayLabel}</th>`;
            });
            
            headerHtml += `<th style="padding:12px; text-align:center; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:80px;">Grade</th>
                           <th style="padding:12px; text-align:center; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; width:60px;">Action</th>`;
            
            return headerHtml;
        }

        async function openSubjectModal(subjectName, subjectId) {
            currentSubjectId = subjectId;
            modalTitle.textContent = subjectName;
            tableBody.innerHTML = '<tr><td colspan="4" style="padding:40px; text-align:center; color:#64748b;">Loading records...</td></tr>';
            studentModal.style.display = 'flex';

            try {
                const response = await fetch(`{{ url('faculty/subjects') }}/${subjectId}/students`);
                if (!response.ok) throw new Error('Failed to fetch');
                const data = await response.json();
                const students = data.students;

                tableBody.innerHTML = '';
                
                if (!students || students.length === 0) {
                    tableHead.innerHTML = buildHeaders([]);
                    tableBody.innerHTML = '<tr><td colspan="4" style="padding:40px; text-align:center; color:#64748b;">No student records available.</td></tr>';
                } else {
                    const orderedKeys = [];
                    students.forEach(s => {
                        if (s.scores && typeof s.scores === 'object') {
                            Object.keys(s.scores).forEach(k => {
                                if (!orderedKeys.includes(k)) orderedKeys.push(k);
                            });
                        }
                    });

                    tableHead.innerHTML = buildHeaders(orderedKeys);

                    students.forEach(s => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid #edf2f7';
                        
                        let rowHtml = `
                            <td style="padding:12px; text-align:center; font-weight:500; color:#1e3c72;">${s.student_id ?? s.id ?? ''}</td>
                            <td style="padding:12px; text-align:left; color:#334155;">${s.name ?? ''}</td>
                        `;
                        
                        orderedKeys.forEach(k => {
                            const cellValue = (s.scores && s.scores[k] !== undefined && s.scores[k] !== null) ? s.scores[k] : '—';
                            rowHtml += `<td style="padding:12px; text-align:center; color:#64748b;">${cellValue}</td>`;
                        });
                        
                        const finalGrade = s.grade_point ?? s.numeric_grade ?? s.raw_grade ?? '—';
                        rowHtml += `
                            <td style="padding:12px; text-align:center; font-weight:700; color:#1e3c72;">${finalGrade}</td>
                            <td style="padding:12px; text-align:center;">
                                <button class="btn-delete-record" data-record-id="${s.id}" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:16px;" title="Delete Record">🗑️</button>
                            </td>
                        `;
                        tr.innerHTML = rowHtml;
                        tableBody.appendChild(tr);
                    });

                    document.querySelectorAll('.btn-delete-record').forEach(btn => {
                        btn.addEventListener('click', async function(e) {
                            const recordId = this.getAttribute('data-record-id');
                            if (confirm('Delete this student record?')) {
                                const res = await fetch(`{{ url('fire/api/record') }}/${recordId}`, {
                                    method: 'DELETE',
                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (res.ok) this.closest('tr').remove();
                            }
                        });
                    });
                }
            } catch (err) {
                tableBody.innerHTML = `<tr><td colspan="4" style="padding:40px; text-align:center; color:#dc2626;">Error: ${err.message}</td></tr>`;
            }
        }

        bulkDeleteBtn.addEventListener('click', async function() {
            if (!currentSubjectId) return;
            if (confirm('Delete ALL records for this subject? This is permanent.')) {
                const res = await fetch(`{{ url('fire/api/subject-records') }}/${currentSubjectId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    alert('All records deleted.');
                    openSubjectModal(modalTitle.textContent, currentSubjectId);
                }
            }
        });

        modalClose.addEventListener('click', () => studentModal.style.display = 'none');
        studentModal.addEventListener('click', (e) => { if (e.target === studentModal) studentModal.style.display = 'none'; });

        document.querySelectorAll('.subject-card-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                openSubjectModal(btn.getAttribute('data-subject-name'), btn.getAttribute('data-subject-id'));
            });
        });
    });

    const subjectModal = document.getElementById('subjectModal');
    const subjectForm = document.getElementById('subjectForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodField = document.getElementById('methodField');

    function openCreateSubjectModal() {
        modalTitle.textContent = 'Add Subject';
        subjectForm.action = "{{ route('faculty.subjects.store') }}";
        methodField.innerHTML = '';
        document.getElementById('subjectCode').value = '';
        document.getElementById('subjectName').value = '';
        document.getElementById('subjectDescription').value = '';
        subjectModal.style.display = 'flex';
    }

    function openEditSubjectModal(id, code, name, description) {
        modalTitle.textContent = 'Edit Subject';
        subjectForm.action = `{{ url('faculty/subjects') }}/${id}`;
        methodField.innerHTML = '@method("PUT")';
        document.getElementById('subjectCode').value = code;
        document.getElementById('subjectName').value = name;
        document.getElementById('subjectDescription').value = description;
        subjectModal.style.display = 'flex';
    }

    function closeSubjectModal() {
        subjectModal.style.display = 'none';
    }
</script>
@endsection
