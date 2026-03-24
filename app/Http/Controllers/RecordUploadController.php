<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ExcelGradeImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $user = Auth::user();
        $name = time() . '_' . preg_replace('/[^A-Za-z0-9_\-.]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs('uploads', $name);

        // Parse file to build preview (do not import yet)
        $previewData = $this->parseAndExtractPreview($file, $user, $name, $path);
        // save preview (and metadata) in session for later import when analytics generated
        session([ 'excel_preview_data' => $previewData ]);

        return redirect()->route('dashboard')->with('status', 'File uploaded successfully: ' . $name);
    }

    private function parseAndExtractPreview($file, $user, $fileName, $filePath)
    {
        $fileExtension = strtolower($file->getClientOriginalExtension());
        
        if ($fileExtension === 'csv') {
            return $this->previewFromCSV($file, $user, $fileName, $filePath);
        }
        if (in_array($fileExtension, ['xlsx', 'xls'])) {
            return $this->previewFromExcel($file, $user, $fileName, $filePath);
        }
        return ['headers' => [], 'rows' => [], 'meta' => [], 'filename' => $fileName];
    }

    private function previewFromCSV($file, $user, $fileName, $filePath)
    {
        $filePath = $file->getRealPath();
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        if (!is_array($header)) {
            $header = [];
        }
        $previewRows = [];
        
        while ($data = fgetcsv($handle)) {
            if (!is_array($data) || count($data) === 0) continue;
            if ($this->isHeaderRow($data)) continue;
            if (count(array_filter($data, fn($v) => $v !== null && trim((string)$v) !== '')) === 0) continue;
            // store raw cell string values (no trimming) so import matches file exactly
            $previewRows[] = array_map(function ($v) { return $v === null ? '' : (string) $v; }, $data);
        }
        fclose($handle);
        return ['headers' => array_map(function ($v) { return $v === null ? '' : (string) $v; }, $header), 'rows' => $previewRows, 'raw_rows' => $previewRows /* CSV preview and raw are same since we already kept original values */, 'meta'=>[], 'filename'=>$fileName];
    }

    private function previewFromExcel($file, $user, $fileName, $filePath)
    {
        $filePath = $file->getRealPath();
        if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            $data = $this->parseXLSX($filePath, $user, $fileName);
            // append filename to metadata
            if (!isset($data['meta'])) {
                $data['meta'] = [];
            }
            $data['filename'] = $fileName;
            return $data;
        }
        $this->createGenericRecord($user, $fileName);
        return ['headers' => [], 'rows' => [], 'meta'=>[], 'filename'=>$fileName];
    }

    private function parseXLSX($filePath, $user, $fileName)
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                $this->createGenericRecord($user, $fileName);
                return ['headers' => [], 'rows' => []];
            }
            $sharedStrings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
                $doc = new \SimpleXMLElement($xmlStrings);
                foreach ($doc->si as $si) {
                    $sharedStrings[] = (string)$si->t;
                }
            }
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            if ($xml) {
                return $this->parseXMLSheet($xml, $sharedStrings, $user, $fileName);
            }
            $this->createGenericRecord($user, $fileName);
            return ['headers' => [], 'rows' => []];
        } catch (\Exception $e) {
            \Log::error('XLSX Parse Error: ' . $e->getMessage());
            $this->createGenericRecord($user, $fileName);
            return ['headers' => [], 'rows' => []];
        }
    }

    private function parseXMLSheet($xml, $sharedStrings, $user, $fileName)
    {
        try {
            $doc = new \SimpleXMLElement($xml);
            $sheetData = [];
            
            // Helper to convert column letter to index
            $colToIndex = function($col) {
                $index = 0;
                for ($i = 0; $i < strlen($col); $i++) {
                    $index = $index * 26 + ord($col[$i]) - 0x40;
                }
                return $index - 1;
            };

            // Helper to format Excel values (especially dates)
            $formatExcelValue = function($val) {
                if (is_numeric($val) && $val > 40000 && $val < 50000) {
                    return date('m/d/Y', ($val - 25569) * 86400);
                }
                return $val;
            };

            // First pass: Load all data into 2D array by cell reference
            foreach ($doc->sheetData->row as $row) {
                $rowIndex = (int)$row['r'];
                foreach ($row->c as $cell) {
                    $cellRef = (string)$cell['r'];
                    if (preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $matches)) {
                        $col = $matches[1];
                        $cIdx = $colToIndex($col);
                        
                        $value = '';
                        if (isset($cell->v)) {
                            $value = (string)$cell->v;
                            if (isset($cell['t']) && (string)$cell['t'] === 's') {
                                $value = $sharedStrings[(int)$value] ?? '';
                            }
                        }
                        $sheetData[$rowIndex][$cIdx] = trim($value);
                    }
                }
            }

            // Extract metadata from first 10 rows
            $meta = ['subject' => null, 'section' => null, 'instructor' => null];
            for ($r = 1; $r < 11; $r++) {
                $rowData = $sheetData[$r] ?? [];
                foreach ($rowData as $val) {
                    if (empty($val)) continue;
                    if (stripos($val, 'Subject:') === 0) $meta['subject'] = trim(substr($val, strlen('Subject:')));
                    if (stripos($val, 'Section:') === 0) $meta['section'] = trim(substr($val, strlen('Section:')));
                    if (stripos($val, 'Instructor:') === 0) $meta['instructor'] = trim(substr($val, strlen('Instructor:')));
                }
            }

            // Row 11-13 are headers
            $row11 = $sheetData[11] ?? [];
            $row12 = $sheetData[12] ?? [];
            $row13 = $sheetData[13] ?? [];

            // Combine headers for descriptive keys
            $headers = [];
            $lastMainHeader = '';
            $maxCol = 0;
            foreach ($sheetData as $r => $cols) {
                if (!empty($cols)) $maxCol = max($maxCol, ...array_keys($cols));
            }

            for ($i = 0; $i <= $maxCol; $i++) {
                $rawMain = trim($row11[$i] ?? '');
                if ($rawMain !== '') {
                    $lastMainHeader = $rawMain;
                }
                $main = $lastMainHeader;
                
                $sub = $formatExcelValue($row12[$i] ?? '');
                $max = $row13[$i] ?? '';
                
                $headerName = trim($main . ($sub ? " ($sub)" : "") . ($max ? " [Max: $max]" : ""));
                $headers[$i] = $headerName ?: "Col_$i";
            }

            $previewRows = [];
            $dataRows = [];

            // Rows 14+ are student data
            for ($r = 14; $r <= 1000; $r++) {
                $rowData = $sheetData[$r] ?? [];
                if (empty($rowData)) continue;
                
                // Check for footer markers
                if (isset($rowData[2]) && (stripos($rowData[2], 'Prepared by') !== false || stripos($rowData[2], 'FACULTY') !== false)) {
                    break;
                }
                
                $studentId = $rowData[1] ?? null;
                $studentName = $rowData[2] ?? null;
                
                // Skip if no student ID or Name
                if (empty($studentId) || empty($studentName) || !preg_match('/\d/', $studentId)) {
                    continue;
                }

                // Normalize row to maxCol
                $rowFinal = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $rowFinal[$i] = $rowData[$i] ?? '';
                }

                $dataRows[] = $rowFinal;
                $previewRows[] = $rowFinal;
            }

            return ['headers' => $headers, 'rows' => $previewRows, 'raw_rows' => $dataRows, 'meta' => $meta, 'filename' => $fileName];
        } catch (\Exception $e) {
            \Log::error('XML Parse Error: ' . $e->getMessage());
            return ['headers' => [], 'rows' => [], 'meta' => [], 'filename' => $fileName];
        }
    }

    /**
     * Detect header-like or non-data rows and skip them.
     */
    private function isHeaderRow(array $data): bool
    {
        // Normalize values
        $vals = array_map(fn($v) => strtolower(trim((string)$v)), $data);
        $origVals = array_map(fn($v) => trim((string)$v), $data);

        // Skip rows that are mostly empty
        $nonEmpty = count(array_filter($vals, fn($v) => $v !== ''));
        if ($nonEmpty === 0) return true;

        // Skip if the row contains a date anywhere (MM/DD/YY or similar) — common in Excel as metadata/timestamps
        foreach ($origVals as $v) {
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', $v)) {
                return true;
            }
        }

        // Skip rows that are purely numeric or mostly numeric (often Excel artifacts)
        $numericCount = 0;
        foreach ($origVals as $v) {
            if ($v === '') continue;
            if (is_numeric($v) || preg_match('/^\d+(\.\d+)?$/', $v)) {
                $numericCount++;
            }
        }
        if ($nonEmpty > 0 && $numericCount === $nonEmpty) {
            return true;
        }

        // Look for real student ID (format: YYYY-NNNNN or similar numeric ID)
        $hasStudentId = false;
        foreach ($vals as $v) {
            if (preg_match('/^\d{4}-\d+$/', $v)) {
                $hasStudentId = true;
                break;
            }
        }

        // If no valid student ID found and row looks like a header, skip it
        if (!$hasStudentId) {
            // Common header/summary keywords
            $keywords = ['student', 'student id', 'name', 'no.', 'no', 'id', 'grade', 'average', 'total', 'remarks', 'scores', 'midterm', 'final', 'quiz', 'section', 'instructor'];
            foreach ($vals as $v) {
                if ($v === '') continue;
                foreach ($keywords as $k) {
                    if (strpos($v, $k) !== false) return true;
                }
            }

            // Rows that are mainly alphabetic (likely a header row)
            $alphaCount = 0;
            foreach ($vals as $v) {
                if ($v === '') continue;
                if (preg_match('/^[a-z\s\.\-]+$/', $v)) $alphaCount++;
            }
            if ($nonEmpty > 0 && $alphaCount / $nonEmpty > 0.75) {
                return true;
            }

            // Skip rows that start with totals/averages explicitly
            $first = $vals[0] ?? '';
            if ($first !== '' && (strpos($first, 'total') === 0 || strpos($first, 'average') === 0)) {
                return true;
            }
        }

        return false;
    }

    private function createRecordFromRow($data, $header, $user, $fileName, $rowIndex = null, $meta = [])
    {
        // Handle flexible data format
        // Format can be: student_id, name, subject_code or variations
        
        // Preserve original cell values for accurate storage
        $rawOriginal = array_map(fn($v) => $v === null ? '' : (string)$v, $data);
        // use a trimmed copy for processing and detection logic
        $raw = array_map(fn($v) => trim((string)$v), $data);
        
        if (count($raw) < 2) {
            return; // Need at least student ID and name
        }
        
        // Determine mapping variants. Many sheets have a leading index column.
        // Common formats observed: [idx, student_id, name, program, ...scores..., grade]
        $studentId = null;
        $studentName = null;
        $subjectCode = null;
        $grade = null;

        // If first column is a small integer index and second column looks like student id, shift indices
        if (isset($raw[0]) && is_numeric($raw[0]) && isset($raw[1]) && preg_match('/^\d{4}-\d+$/', $raw[1])) {
            $studentId = $raw[1];
            $studentName = $raw[2] ?? null;
            $startIndex = 3; // scores start here usually
        } else {
            // fallback: assume first column is student id
            $studentId = $raw[0] ?? null;
            $studentName = $raw[1] ?? null;
            $startIndex = 2;
        }

        // If meta contains subject info, prefer that
        if (!empty($meta['subject'])) {
            // attempt to parse subject code and name from meta (do NOT overwrite $raw array)
            $metaSubject = trim((string)$meta['subject']);
            // split on - to get name
            $parts = preg_split('/\s*-\s*/', $metaSubject, 2);
            $codePart = $parts[0] ?? $metaSubject;
            $namePart = $parts[1] ?? null;
            // normalize code (e.g., "IT 633" -> "IT633")
            $code = preg_replace('/\s+/', '', $codePart);
            $subjectCode = strtoupper($code);
            $subjectName = $namePart ? trim($namePart) : $metaSubject;
        }

        // If there's an explicit code column, detect it
        if (isset($data[$startIndex]) && preg_match('/[A-Za-z]+\s*\d+/', $data[$startIndex])) {
            $subjectCode = preg_replace('/\s+/', '', $data[$startIndex]);
        }

        // Extract grade by scanning from end backwards for a numeric value or common grade token
        for ($i = count($data) - 1; $i >= $startIndex; $i--) {
            $val = trim($data[$i]);
            if ($val === '' || strtolower($val) === 'inc' || strtolower($val) === 'dr' || strtolower($val) === 'u') continue;
            // numeric grade
            if (is_numeric(str_replace(',', '.', $val))) {
                $grade = str_replace(',', '.', $val);
                break;
            }
            // letter grade
            if (preg_match('/^[A-DF]$/i', $val)) {
                $grade = strtoupper($val);
                break;
            }
        }
        
        $subjectCode = $subjectCode ?? 'GEN001';
        
        if (!$studentId) {
            return;
        }

        try {
            // Find or create student
            $student = Student::firstOrCreate(
                ['student_id' => $studentId],
                [
                    'name' => $studentName,
                    'email' => strtolower(str_replace(' ', '.', $studentName)) . '@student.edu',
                    'program' => 'General Studies',
                ]
            );

            // Find or create subject
            $subject = Subject::firstOrCreate(
                ['code' => $subjectCode, 'user_id' => $user->id],
                [
                    'name' => ucwords(str_replace('_', ' ', $subjectCode)),
                    'user_id' => $user->id,
                    'status' => 'active',
                ]
            );

            // Update subject metadata if available from this upload
            if (!empty($meta['section']) || !empty($meta['instructor'])) {
                $subject->update([
                    'section' => $meta['section'] ?? $subject->section,
                    'instructor' => $meta['instructor'] ?? $subject->instructor,
                ]);
            }

            // Prepare scores array: use provided header names (preserve original Excel headers) when available
            $scores = [];
            $len = count($rawOriginal);
            if (is_array($header) && count($header) > 0) {
                // Use header entries as keys — keep original header text (trimmed)
                for ($i = $startIndex; $i < $len; $i++) {
                    $key = isset($header[$i]) ? trim((string)$header[$i]) : 'col' . $i;
                    $scores[$key] = $rawOriginal[$i] ?? null;
                }
            } else {
                // Fallback to generic column keys preserving position
                for ($i = $startIndex; $i < $len; $i++) {
                    $scores['c' . $i] = $rawOriginal[$i] ?? null;
                }
            }

            // Raw grade and numeric grade detection
            $rawGrade = $grade;
            $numericGrade = null;
            if (!is_null($rawGrade) && is_numeric(str_replace(',', '.', $rawGrade))) {
                $numericGrade = (float) str_replace(',', '.', $rawGrade);
            }

            // Compute grade point (0-4) using the same logic as Record model
            $gradePoint = null;
            if (!is_null($numericGrade)) {
                $num = $numericGrade;
                if ($num >= 0 && $num <= 5) {
                    $gradePoint = round(($num / 5.0) * 4.0, 4);
                } elseif ($num > 5 && $num <= 100) {
                    $gradePoint = round(($num / 100.0) * 4.0, 4);
                } elseif ($num >= 0 && $num <= 4) {
                    $gradePoint = round($num, 4);
                }
            }

            // Create or update record with structured fields
            Record::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                ],
                [
                    'file_name' => $fileName,
                    'notes' => $grade ? 'Grade: ' . $grade : 'Imported from upload',
                    'raw_grade' => $rawGrade,
                    'numeric_grade' => $numericGrade,
                    'grade_point' => $gradePoint,
                    'scores' => !empty($scores) ? $scores : null,
                    'row_index' => $rowIndex,
                ]
            );
            
            \Log::info("Record created: Student=$studentId, Subject=$subjectCode, Grade=$grade");
        } catch (\Exception $e) {
            \Log::error("Error creating record: " . $e->getMessage());
        }
    }

    private function createGenericRecord($user, $fileName)
    {
        // Create a generic record for the upload
        $subject = Subject::where('user_id', $user->id)->first();
        if (!$subject) {
            $subject = Subject::create([
                'code' => 'GEN001',
                'name' => 'General Records',
                'user_id' => $user->id,
                'status' => 'active',
            ]);
        }

        $student = Student::first();
        if (!$student) {
            $student = Student::create([
                'student_id' => 'STU00001',
                'name' => 'Sample Student',
                'email' => 'sample@student.edu',
                'program' => 'General Studies',
            ]);
        }

        Record::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'file_name' => $fileName,
            'notes' => 'Uploaded from file',
        ]);
    }

    /**
     * Get statistics on imported grades (quizzes, attendance, midterm, final)
     */
    public function getImportStats(Request $request)
    {
        $user = Auth::user();
        
        // Get counts from assessment tables
        $stats = [
            'records' => Record::where('user_id', $user->id)->count(),
            'quizzes' => \App\Models\StudentQuiz::where('user_id', $user->id)->count(),
            'attendance' => \App\Models\StudentAttendance::where('user_id', $user->id)->count(),
            'midterm' => \App\Models\StudentMidtermExam::where('user_id', $user->id)->count(),
            'final' => \App\Models\StudentFinalExam::where('user_id', $user->id)->count(),
            'students' => Student::whereHas('records', function ($q) {
                $q->where('user_id', Auth::id());
            })->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all grades summary for display
     */
    public function getGradesSummary(Request $request)
    {
        $user = Auth::user();

        $summary = [
            'total_quizzes' => \App\Models\StudentQuiz::where('user_id', $user->id)->count(),
            'lab_quizzes' => \App\Models\StudentQuiz::where('user_id', $user->id)->where('quiz_type', 'laboratory')->count(),
            'non_lab_quizzes' => \App\Models\StudentQuiz::where('user_id', $user->id)->where('quiz_type', 'non_laboratory')->count(),
            'total_attendance' => \App\Models\StudentAttendance::where('user_id', $user->id)->count(),
            'present_count' => \App\Models\StudentAttendance::where('user_id', $user->id)->where('status', 'present')->count(),
            'absent_count' => \App\Models\StudentAttendance::where('user_id', $user->id)->where('status', 'absent')->count(),
            'total_midterm' => \App\Models\StudentMidtermExam::where('user_id', $user->id)->count(),
            'total_final' => \App\Models\StudentFinalExam::where('user_id', $user->id)->count(),
            'unique_students' => Student::whereHas('records', function ($q) {
                $q->where('user_id', Auth::id());
            })->distinct()->count(),
        ];

        return response()->json($summary);
    }
}
