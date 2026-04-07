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

    <!-- All Subjects in Single Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        @foreach($subjects as $subject)
            <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #edf2f7; transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 12px; font-weight: 700; color: #1e3c72; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">{{ $subject->code }}</div>
                <h3 style="font-size: 18px; font-weight: 700; color: #1e3c72; margin-bottom: 12px;">{{ $subject->name }}</h3>
                <p style="font-size: 14px; color: #64748b; margin-bottom: 20px; line-height: 1.5; min-height: 42px;">{{ Str::limit($subject->description, 80) }}</p>
                
                @if(is_array($subject->section) && count($subject->section) > 0)
                    <div style="margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 8px;">
                        @foreach($subject->section as $sec)
                            <span
                                class="section-badge section-badge--view"
                                data-subject-name="{{ $subject->name }}"
                                data-subject-id="{{ $subject->id }}"
                                data-section="{{ $sec }}"
                                style="background: #dbeafe; color: #0369a1; padding: 4px 10px; border-radius: 16px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.background='#bfdbfe'; this.style.boxShadow='0 0 8px rgba(3, 105, 161, 0.3)';"
                                onmouseout="this.style.background='#dbeafe'; this.style.boxShadow='none';"
                                title="Click to view records for this section"
                            >{{ $sec }}</span>
                        @endforeach
                    </div>
                @else
                    <div style="margin-bottom: 16px;">
                        <span
                            class="section-badge section-badge--view"
                            data-subject-name="{{ $subject->name }}"
                            data-subject-id="{{ $subject->id }}"
                            data-section="Unassigned"
                            style="background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 16px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                            onmouseover="this.style.background='#e5e7eb';"
                            onmouseout="this.style.background='#f3f4f6';"
                            title="Click to view records (no section)"
                        >Unassigned</span>
                    </div>
                @endif
                
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

                <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <button onclick="openEditSubjectModal('{{ $subject->id }}', '{{ $subject->code }}', '{{ $subject->name }}', '{{ $subject->description }}', {{ json_encode($subject->section) }})" style="flex:1; background: white; color: #1e3c72; border: 1px solid #1e3c72; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Edit Info</button>
                    <button type="button" onclick="confirmDeleteSubject('{{ $subject->id }}', '{{ $subject->name }}')" style="flex:1; background: white; color: #dc2626; border: 1px solid #dc2626; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">Delete Subject</button>
                </div>
                <form id="delete-subject-form-{{ $subject->id }}" action="{{ route('faculty.subjects.delete', $subject->id) }}" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
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
                            <th style="padding:12px; text-align:left; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:180px;">Loading...</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Subject Confirmation Modal -->
    <div id="deleteSubjectModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 16px; width: 90%; max-width: 400px; padding: 32px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <div style="width: 64px; height: 64px; background: #fef2f2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 style="font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Delete Subject?</h3>
            <p style="font-size: 14px; color: #64748b; margin-bottom: 28px; line-height: 1.5;">Are you sure you want to delete <strong id="deleteSubjectName" style="color: #1e3c72;"></strong>? This action will permanently remove all associated student records and cannot be undone.</p>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeDeleteSubjectModal()" style="flex: 1; padding: 12px; background: #f1f5f9; color: #475569; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer;">Cancel</button>
                <button type="button" id="confirmDeleteButton" style="flex: 1; padding: 12px; background: #dc2626; color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer;">Delete</button>
            </div>
        </div>
    </div>

    <!-- Create/Edit Subject Modal -->
    <div id="subjectModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
        <div style="background:white; border-radius:12px; width:95%; max-width:560px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,.1);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 id="modalTitle" style="margin:0; color:#1e3c72; font-size:20px; font-weight:700;">Add Subject</h3>
                <button type="button" onclick="closeSubjectModal()" style="background:none; border:none; font-size:22px; color:#64748b; cursor:pointer;">&times;</button>
            </div>

            <form id="subjectForm" method="POST" action="{{ route('faculty.subjects.store') }}">
                @csrf
                <span id="methodField"></span>

                <div style="margin-bottom:12px;">
                    <label for="subjectCode" style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Subject Code</label>
                    <input id="subjectCode" name="code" type="text" required style="width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;">
                </div>

                <div style="margin-bottom:12px;">
                    <label for="subjectName" style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Subject Name</label>
                    <input id="subjectName" name="name" type="text" required style="width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px;">
                </div>

                <div style="margin-bottom:12px;">
                    <label for="subjectDescription" style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Description</label>
                    <textarea id="subjectDescription" name="description" rows="3" style="width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; resize:vertical;"></textarea>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:6px;">Sections</label>
                    <div id="sectionsCheckboxContainer" style="max-height:180px; overflow:auto; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px;"></div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeSubjectModal()" style="padding:10px 14px; border:1px solid #cbd5e1; background:white; color:#334155; border-radius:8px; font-weight:600; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 16px; border:none; background:#1e3c72; color:white; border-radius:8px; font-weight:600; cursor:pointer;">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function confirmDeleteSubject(subjectId, subjectName) {
        document.getElementById('deleteSubjectName').textContent = subjectName;
        document.getElementById('deleteSubjectModal').style.display = 'flex';
        document.getElementById('confirmDeleteButton').onclick = function() {
            document.getElementById('delete-subject-form-' + subjectId).submit();
        };
    }

    function closeDeleteSubjectModal() {
        document.getElementById('deleteSubjectModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const studentModal = document.getElementById('subjectStudentsModal');
        const modalTitle = document.getElementById('subjectStudentsModalTitle');
        const modalClose = document.getElementById('subjectStudentsModalClose');
        const tableBody = document.querySelector('#subjectStudentsTable tbody');
        const tableHead = document.getElementById('subjectStudentsTableHead');
        const bulkDeleteBtn = document.getElementById('btn-bulk-delete-records');
        
        let currentSubjectId = null;

        function buildHeaders(scoreKeys) {
            let headerHtml = '';
            scoreKeys.forEach(k => {
                // Keep original header text from Excel preview format.
                const displayLabel = String(k ?? '').trim();
                headerHtml += `<th style="padding:12px; text-align:left; font-weight:700; color:#1e3c72; border-bottom:2px solid #edf2f7; min-width:90px;">${displayLabel}</th>`;
            });
            return headerHtml;
        }

        async function openSubjectModal(subjectName, subjectId) {
            return openSubjectModalForSection(subjectName, subjectId, null);
        }

        async function openSubjectModalForSection(subjectName, subjectId, section) {
            currentSubjectId = subjectId;
            modalTitle.textContent = section ? `${subjectName} — ${section}` : subjectName;
            tableBody.innerHTML = '<tr><td colspan="4" style="padding:40px; text-align:center; color:#64748b;">Loading records...</td></tr>';
            studentModal.style.display = 'flex';

            try {
                const sectionParam = section ? `?section=${encodeURIComponent(section)}` : '';
                const response = await fetch(`{{ url('faculty/subjects') }}/${subjectId}/students${sectionParam}`);
                if (!response.ok) throw new Error('Failed to fetch');
                const data = await response.json();
                const students = data.students;
                const scoreHeaders = data.scoreHeaders ?? [];

                tableBody.innerHTML = '';
                
                if (!students || students.length === 0) {
                    tableHead.innerHTML = buildHeaders([]);
                    tableBody.innerHTML = '<tr><td colspan="1" style="padding:40px; text-align:center; color:#64748b;">No student records available.</td></tr>';
                } else {
                    // Use server-provided header order to match Excel preview columns.
                    const orderedKeys = Array.isArray(scoreHeaders) && scoreHeaders.length > 0 ? scoreHeaders : (() => {
                        const fallbackKeys = [];
                        students.forEach(s => {
                            if (s.scores && typeof s.scores === 'object') {
                                Object.keys(s.scores).forEach(k => {
                                    if (!fallbackKeys.includes(k)) fallbackKeys.push(k);
                                });
                            }
                        });
                        return fallbackKeys;
                    })();

                    tableHead.innerHTML = buildHeaders(orderedKeys);
                    const colCount = Math.max(orderedKeys.length, 1);

                    students.forEach(s => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid #edf2f7';
                        
                        let rowHtml = '';
                        
                        orderedKeys.forEach(k => {
                            let cellValue = '—';
                            if (s.scores && s.scores[k] !== undefined && s.scores[k] !== null) {
                                const raw = s.scores[k];
                                const numericRegex = /^-?\d+(\.\d+)?$/;
                                // Preserve non-numeric tokens (e.g. DR, U) as-is.
                                const rawStr = (typeof raw === 'string') ? raw.trim() : String(raw);

                                const isNumeric = (typeof raw === 'number' && !Number.isNaN(raw))
                                    || (typeof rawStr === 'string' && numericRegex.test(rawStr));

                                if (!isNumeric) {
                                    cellValue = raw ?? '—';
                                } else {
                                    // Mirror dashboard preview formatting:
                                    // - if decimals > 2 => round to 2 decimals
                                    // - else => preserve, but trim trailing zeros and optional trailing dot
                                    if (String(rawStr).includes('.')) {
                                        const parts = String(rawStr).split('.');
                                        const decimals = (parts[1] ?? '').length;
                                        if (decimals > 2) {
                                            cellValue = parseFloat(rawStr).toFixed(2);
                                        } else {
                                            let trimmed = String(rawStr);
                                            trimmed = trimmed.replace(/0+$/,'').replace(/\.$/,'');
                                            if (trimmed === '' || trimmed === '-') trimmed = String(rawStr);
                                            cellValue = trimmed;
                                        }
                                    } else {
                                        cellValue = String(rawStr);
                                    }
                                }
                            }
                            rowHtml += `<td style="padding:12px; text-align:left; color:#64748b;">${cellValue}</td>`;
                        });
                        tr.innerHTML = rowHtml;
                        tableBody.appendChild(tr);
                    });

                    if (orderedKeys.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="${colCount}" style="padding:40px; text-align:center; color:#64748b;">No Excel score columns found for this section.</td></tr>`;
                    }
                }
            } catch (err) {
                tableBody.innerHTML = `<tr><td colspan="1" style="padding:40px; text-align:center; color:#dc2626;">Error: ${err.message}</td></tr>`;
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

        document.querySelectorAll('.section-badge--view').forEach(badge => {
            badge.addEventListener('click', () => {
                openSubjectModalForSection(
                    badge.getAttribute('data-subject-name'),
                    badge.getAttribute('data-subject-id'),
                    badge.getAttribute('data-section')
                );
            });
        });
    });

    const subjectModal = document.getElementById('subjectModal');
    const subjectForm = document.getElementById('subjectForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodField = document.getElementById('methodField');
    const sectionsContainer = document.getElementById('sectionsCheckboxContainer');

    // Available sections from the server
    const availableSections = @json($availableSections ?? []);

    function renderSectionCheckboxes(selectedSections = []) {
        sectionsContainer.innerHTML = '';
        
        if (availableSections.length === 0) {
            sectionsContainer.innerHTML = '<p style="color:#64748b; font-size:12px; margin:0;">No sections available yet. Create a subject with a file upload first.</p>';
            return;
        }

        availableSections.forEach(section => {
            const isChecked = selectedSections && selectedSections.includes(section);
            const checkbox = document.createElement('label');
            checkbox.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 8px 0; cursor: pointer; font-size: 13px; color: #334155;';
            checkbox.innerHTML = `
                <input type="checkbox" name="sections[]" value="${section}" ${isChecked ? 'checked' : ''} style="width: 16px; height: 16px; cursor: pointer;">
                <span>${section}</span>
            `;
            sectionsContainer.appendChild(checkbox);
        });

        // Add "New Section" option
        const newSectionDiv = document.createElement('div');
        newSectionDiv.style.cssText = 'margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;';
        newSectionDiv.innerHTML = `
            <input type="text" id="newSectionInput" placeholder="Add new section (e.g., BSIT-4A)" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px;">
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Type a new section and add it to selections above</div>
        `;
        sectionsContainer.appendChild(newSectionDiv);
    }

    function openCreateSubjectModal() {
        modalTitle.textContent = 'Add Subject';
        subjectForm.action = "{{ route('faculty.subjects.store') }}";
        methodField.innerHTML = '';
        document.getElementById('subjectCode').value = '';
        document.getElementById('subjectName').value = '';
        document.getElementById('subjectDescription').value = '';
        renderSectionCheckboxes([]);
        subjectModal.style.display = 'flex';
    }

    function openEditSubjectModal(id, code, name, description, sections = null) {
        modalTitle.textContent = 'Edit Subject';
        subjectForm.action = `{{ url('faculty/subjects') }}/${id}`;
        methodField.innerHTML = '@method("PUT")';
        document.getElementById('subjectCode').value = code;
        document.getElementById('subjectName').value = name;
        document.getElementById('subjectDescription').value = description;
        
        // Parse sections if it's a JSON string
        let selectedSections = [];
        if (sections) {
            selectedSections = typeof sections === 'string' ? JSON.parse(sections) : sections;
        }
        
        renderSectionCheckboxes(selectedSections);
        subjectModal.style.display = 'flex';
    }

    function closeSubjectModal() {
        subjectModal.style.display = 'none';
    }
</script>
@endsection
