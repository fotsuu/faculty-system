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
            $sheetPaths = $this->resolveClassRecordSheetPaths($zip);
            if (empty($sheetPaths)) {
                $sheetPaths = ['xl/worksheets/sheet1.xml'];
            }

            $datasets = [];
            foreach ($sheetPaths as $sheetPath) {
                $xml = $zip->getFromName($sheetPath);
                if (!$xml) {
                    continue;
                }

                $parsed = $this->parseXMLSheet($xml, $sharedStrings, $user, $fileName);
                $headers = $parsed['headers'] ?? [];
                $rows = $parsed['rows'] ?? [];

                if (!empty($headers) || !empty($rows)) {
                    $datasets[] = [
                        'headers' => $headers,
                        'rows' => $rows,
                        'raw_rows' => $parsed['raw_rows'] ?? $rows,
                        'meta' => $parsed['meta'] ?? [],
                        'filename' => $fileName,
                    ];
                }
            }
            $zip->close();

            if (!empty($datasets)) {
                // Keep the first dataset for preview compatibility while importing all datasets later.
                $first = $datasets[0];
                return [
                    'headers' => $first['headers'],
                    'rows' => $first['rows'],
                    'raw_rows' => $first['raw_rows'],
                    'meta' => $first['meta'],
                    'filename' => $fileName,
                    'datasets' => $datasets,
                ];
            }

            $this->createGenericRecord($user, $fileName);
            return ['headers' => [], 'rows' => []];
        } catch (\Exception $e) {
            \Log::error('XLSX Parse Error: ' . $e->getMessage());
            $this->createGenericRecord($user, $fileName);
            return ['headers' => [], 'rows' => []];
        }
    }

    private function resolveClassRecordSheetPaths(\ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if (!$workbookXml) {
            return [];
        }

        try {
            $wbDoc = new \SimpleXMLElement($workbookXml);
            $targetRids = [];
            
            // Register namespaces for workbook
            $namespaces = $wbDoc->getNamespaces(true);
            $rNs = $namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

            foreach ($wbDoc->sheets->sheet as $sheet) {
                $sheetName = (string)$sheet['name'];
                if ($sheetName !== '' && (
                    stripos($sheetName, 'class record') !== false ||
                    stripos($sheetName, 'classrecord') !== false ||
                    stripos($sheetName, 'classrec') !== false
                )) {
                    // Try with namespace first, then fallback
                    $rid = (string)$sheet->attributes($rNs)->id;
                    if (!$rid) {
                        $rid = (string)$sheet['r:id'];
                    }
                    if ($rid) {
                        $targetRids[] = $rid;
                    }
                }
            }

            if (empty($targetRids)) {
                // fallback: all sheet names containing class/record keywords
                foreach ($wbDoc->sheets->sheet as $sheet) {
                    $sheetName = (string)$sheet['name'];
                    if ($sheetName !== '' && (
                        stripos($sheetName, 'record') !== false ||
                        stripos($sheetName, 'class') !== false ||
                        stripos($sheetName, 'classrec') !== false ||
                        stripos($sheetName, 'classrecord') !== false
                    )) {
                        $rid = (string)$sheet->attributes($rNs)->id;
                        if (!$rid) {
                            $rid = (string)$sheet['r:id'];
                        }
                        if ($rid) {
                            $targetRids[] = $rid;
                        }
                    }
                }
            }

            if (empty($targetRids)) {
                return [];
            }

            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if (!$relsXml) {
                return [];
            }

            $relsDoc = new \SimpleXMLElement($relsXml);
            $sheetPaths = [];
            foreach ($relsDoc->Relationship as $rel) {
                if (in_array((string)$rel['Id'], $targetRids, true)) {
                    $target = (string)$rel['Target'];
                    // Normalize relative path
                    $sheetPaths[] = 'xl/' . ltrim($target, '/');
                }
            }
            return array_values(array_unique($sheetPaths));
        } catch (\Exception $e) {
            \Log::warning('Could not resolve class record sheet path: ' . $e->getMessage());
            return [];
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

            // Apply merged-cell values so merged headers are seen across all merged columns
            if (isset($doc->mergeCells)) {
                foreach ($doc->mergeCells->mergeCell as $mergeCell) {
                    $ref = (string)$mergeCell['ref'];
                    if (!str_contains($ref, ':')) continue;
                    [$start, $end] = explode(':', $ref);
                    if (!preg_match('/^([A-Z]+)(\d+)$/', $start, $startMatch) || !preg_match('/^([A-Z]+)(\d+)$/', $end, $endMatch)) {
                        continue;
                    }

                    $startCol = $colToIndex($startMatch[1]);
                    $startRow = (int)$startMatch[2];
                    $endCol = $colToIndex($endMatch[1]);
                    $endRow = (int)$endMatch[2];

                    $masterValue = $sheetData[$startRow][$startCol] ?? '';
                    for ($rr = $startRow; $rr <= $endRow; $rr++) {
                        for ($cc = $startCol; $cc <= $endCol; $cc++) {
                            if (($sheetData[$rr][$cc] ?? '') === '') {
                                $sheetData[$rr][$cc] = $masterValue;
                            }
                        }
                    }
                }
            }

            // Extract metadata from first 10 rows
            $meta = ['subject' => null, 'section' => null, 'instructor' => null, 'subject_code' => null, 'subject_name' => null];
            for ($r = 1; $r < 11; $r++) {
                $rowData = $sheetData[$r] ?? [];
                foreach ($rowData as $val) {
                    if (empty($val)) continue;
                    if (stripos($val, 'Subject:') === 0) $meta['subject'] = trim(substr($val, strlen('Subject:')));
                    if (stripos($val, 'Section:') === 0) $meta['section'] = trim(substr($val, strlen('Section:')));
                    if (stripos($val, 'Instructor:') === 0) $meta['instructor'] = trim(substr($val, strlen('Instructor:')));
                }
            }

            // Auto-detect subject code/name from row 2/3 if present
            $row2 = $sheetData[2] ?? [];
            $row3 = $sheetData[3] ?? [];
            $row4 = $sheetData[4] ?? [];
            $detectedSubjectCode = null;
            $detectedSubjectName = null;
            $detectedSection = null;

            foreach ($row2 as $val) {
                $candidate = trim((string)$val);
                if ($candidate === '') {
                    continue;
                }
                $normalized = preg_replace('/\s+/', '', $candidate);
                if (preg_match('/^[A-Za-z]{2,}\d{1,}$/', $normalized)) {
                    $detectedSubjectCode = strtoupper($normalized);
                    break;
                }
            }

            foreach ($row3 as $val) {
                $candidate = trim((string)$val);
                if ($candidate === '') {
                    continue;
                }
                if (preg_match('/\d/', $candidate)) {
                    continue;
                }
                $wordCount = str_word_count($candidate);
                if ($wordCount >= 2 && mb_strlen($candidate) >= 10) {
                    $detectedSubjectName = $candidate;
                    break;
                }
            }

            // Auto-detect section from row 4 (e.g., "BSIT-3A", "BS-IT-2B")
            foreach ($row4 as $val) {
                $candidate = trim((string)$val);
                if ($candidate === '') {
                    continue;
                }
                // Section patterns: letter(s) + dash + number + letter, e.g., BSIT-3A, BS-2C, IT-4D
                if (preg_match('/^[A-Za-z\-]+\d+[A-Za-z]?$/', $candidate)) {
                    $detectedSection = strtoupper($candidate);
                    break;
                }
            }

            if ($meta['subject_code'] === null && $detectedSubjectCode) {
                $meta['subject_code'] = $detectedSubjectCode;
            }
            if ($meta['subject'] === null && $detectedSubjectCode) {
                $meta['subject'] = $detectedSubjectCode;
            }
            if ($meta['subject_name'] === null && $detectedSubjectName) {
                $meta['subject_name'] = $detectedSubjectName;
            }
            if ($meta['section'] === null && $detectedSection) {
                $meta['section'] = $detectedSection;
            }
            if ($meta['subject_name'] === null && $detectedSubjectCode && $meta['subject'] === $detectedSubjectCode) {
                // subject_code prepopulated but name may still be from Subject: if provided.
            }

            // Find headers row using new format (column names like Student, Name, Remarks)
            $maxCol = 0;
            foreach ($sheetData as $r => $cols) {
                if (!empty($cols)) {
                    $maxCol = max($maxCol, ...array_keys($cols));
                }
            }

            $headerRowIndex = null;
            $bestScore = 0;

            foreach ($sheetData as $r => $cols) {
                if ($r > 50) break; // header should be very near top

                $values = array_map(fn($v) => strtolower(trim((string)$v)), $cols);
                if (empty($values)) continue;

                $rowScore = 0;
                foreach ($values as $val) {
                    if ($val === '') continue;
                    if (str_contains($val, 'name of student') || str_contains($val, 'student name')) $rowScore += 10;
                    else if (str_contains($val, 'student')) $rowScore += 5;
                    
                    if (str_contains($val, 'name')) $rowScore += 2;
                    if (str_contains($val, 'remark')) $rowScore += 10;
                    if (str_contains($val, 'id') || str_contains($val, 'no')) $rowScore += 2;
                    if (str_contains($val, 'grade') || str_contains($val, 'total')) $rowScore += 1;
                }

                if ($rowScore > $bestScore) {
                    $bestScore = $rowScore;
                    $headerRowIndex = $r;
                }
            }

            if ($bestScore < 10) {
                $headerRowIndex = null;
            }

            // Additional fallback: match explicit classrec header row (Name of Students -> Remarks)
            if ($headerRowIndex === null) {
                for ($r = 1; $r <= 50; $r++) {
                    $rowData = $sheetData[$r] ?? [];
                    if (empty($rowData)) continue;

                    $lowerVals = array_map(fn($v) => strtolower(trim((string)$v)), $rowData);
                    $hasNameHeading = false;
                    $hasRemarksHeading = false;

                    foreach ($lowerVals as $val) {
                        if ($val === '') continue;
                        if (str_contains($val, 'name of student') || str_contains($val, 'student name') || str_contains($val, 'name')) {
                            $hasNameHeading = true;
                        }
                        if (str_contains($val, 'remark')) {
                            $hasRemarksHeading = true;
                        }
                    }

                    if ($hasNameHeading && $hasRemarksHeading) {
                        $headerRowIndex = $r;
                        break;
                    }
                }
            }

            $nameCol = null;
            $remarksCol = null;

            if ($headerRowIndex !== null) {
                $headerRow = $sheetData[$headerRowIndex];
                
                // First pass for priority headings
                foreach ($headerRow as $c => $val) {
                    $lower = strtolower(trim((string)$val));
                    if ($nameCol === null && (str_contains($lower, 'name of student') || str_contains($lower, 'student name'))) {
                        $nameCol = $c;
                    }
                    if ($remarksCol === null && str_contains($lower, 'remark')) {
                        $remarksCol = $c;
                    }
                }

                // Second pass if still missing
                if ($nameCol === null || $remarksCol === null) {
                    foreach ($headerRow as $c => $val) {
                        $lower = strtolower(trim((string)$val));
                        if ($nameCol === null && (str_contains($lower, 'name') || str_contains($lower, 'student'))) {
                            $nameCol = $c;
                        }
                        if ($remarksCol === null && str_contains($lower, 'remark')) {
                            $remarksCol = $c;
                        }
                    }
                }

                if ($nameCol === null) {
                    // if name column still missing, pick first non-empty col index
                    foreach ($headerRow as $c => $val) {
                        if (trim((string)$val) !== '') {
                            $nameCol = $c;
                            break;
                        }
                    }
                }
                if ($nameCol === null) {
                    $nameCol = 0;
                }

                if ($remarksCol === null) {
                    // try to set remarks to the last non-empty in row if no remarks tag found
                    $lastNonEmpty = null;
                    foreach ($headerRow as $c => $val) {
                        if (trim((string)$val) !== '') {
                            $lastNonEmpty = $c;
                        }
                    }
                    $remarksCol = $lastNonEmpty ?? $maxCol;
                }

                if ($nameCol > $remarksCol) {
                    $nameCol = 0;
                    $remarksCol = $maxCol;
                }

                $headers = [];
                for ($i = $nameCol; $i <= $remarksCol; $i++) {
                    $headers[] = trim((string)($headerRow[$i] ?? "Col_$i"));
                }

                // Ensure first column is clearly labeled as student name when this is the name column.
                if (isset($headers[0]) && !$this->isStudentNameHeader($headers[0])) {
                    // If first header was something like Q1/Q2, set it to student labels.
                    $headers[0] = 'Name of Student';
                }

                // If we forced name header, shift existing label sequence to include Q1 if missing.
                if (isset($headers[0]) && $this->isStudentNameHeader($headers[0])) {
                    $nextLabel = strtolower(trim($headers[1] ?? ''));
                    // Check if next column is Q2 or higher (not Q1)
                    if ($nextLabel !== '' && preg_match('/^q+\s*([2-9]|[1-9]\d+)/', $nextLabel) && !$this->isQuizLabel($headers[1], '1')) {
                        array_splice($headers, 1, 0, 'Q1');
                    }
                }

                $previewRows = [];
                $dataRows = [];

                // Identify row where student listing begins (e.g., numeric index + name present)
                $startDataRow = null;
                for ($r = $headerRowIndex + 1; $r <= 1000; $r++) {
                    $rowData = $sheetData[$r] ?? [];
                    if (empty($rowData)) continue;

                    $nameVal = trim((string)($rowData[$nameCol] ?? ''));
                    if ($nameVal === '') continue;
                    
                    // Skip if this row looks like another header row (contains "name" or "student" or "subject" etc)
                    $lowerName = strtolower($nameVal);
                    if ($lowerName === 'name of student' || $lowerName === 'student name' || $lowerName === 'name') {
                        continue;
                    }

                    // If first column is numeric, it's very likely a data row (index 1, 2, 3...)
                    $firstCol = trim((string)($rowData[0] ?? ''));
                    if (is_numeric(str_replace('.', '', $firstCol)) || preg_match('/^[0-9]+$/', $firstCol)) {
                        $startDataRow = $r;
                        break;
                    }

                    // Fallback: if we have a name and it doesn't look like a header, and we've skipped at least one row after header
                    // (or if it's been a few rows), assume it's data
                    if ($r > $headerRowIndex + 1) {
                         $startDataRow = $r;
                         break;
                    }
                }

                if (is_null($startDataRow)) {
                    $startDataRow = $headerRowIndex + 1;
                }

                // If detected name column does not look like student names, re-evaluate the best candidate from row content.
                $nameCol = $this->inferStudentNameColumn($sheetData, $startDataRow, $maxCol, $nameCol);

                // Ensure comments/remark bound includes name column.
                if ($remarksCol !== null && $nameCol > $remarksCol) {
                    $remarksCol = $nameCol;
                }

                for ($r = $startDataRow; $r <= 1000; $r++) {
                    $rowData = $sheetData[$r] ?? [];
                    if (empty($rowData)) continue;

                    $footerCheck = strtolower(trim((string)($rowData[0] ?? '')));
                    if (str_contains($footerCheck, 'prepared by') || str_contains($footerCheck, 'faculty') || str_contains($footerCheck, 'total')) {
                        break;
                    }

                    $studentName = trim((string)($rowData[$nameCol] ?? ''));
                    if ($studentName === '') continue;

                    // Slice row to match headers (from nameCol to remarksCol) for both preview and import
                    $sliceRow = [];
                    for ($i = $nameCol; $i <= $remarksCol; $i++) {
                        $sliceRow[] = $rowData[$i] ?? '';
                    }
                    
                    $previewRows[] = $sliceRow;
                    $dataRows[] = $sliceRow;  // Use sliced row for import to match headers
                }

                return ['headers' => $headers, 'rows' => $previewRows, 'raw_rows' => $dataRows, 'meta' => $meta, 'filename' => $fileName];
            }

            // Fallback to old fixed layout if a header row isn't detected
            $row11 = $sheetData[11] ?? [];
            $row12 = $sheetData[12] ?? [];
            $row13 = $sheetData[13] ?? [];

            $headers = [];
            $lastMainHeader = '';
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

            // Normalize name column in fallback layout if present
            $assigned = false;
            foreach ($headers as $i => $h) {
                if ($this->isStudentNameHeader($h)) {
                    $headers[$i] = 'Name of Student';
                    $assigned = true;
                    break;
                }
            }

            if (!$assigned) {
                $firstSampleRow = $sheetData[14] ?? [];
                foreach ($firstSampleRow as $i => $v) {
                    $val = trim((string)$v);
                    if ($val === '' || is_numeric($val)) {
                        continue;
                    }
                    // assign text-like first non-numeric column from data to name if heuristics suggest.
                    $headers[$i] = 'Name of Student';
                    $assigned = true;
                    break;
                }
            }

            if ($assigned && isset($headers[0]) && $this->isStudentNameHeader($headers[0])) {
                $nextLabel = strtolower(trim($headers[1] ?? ''));
                // Check if next column is Q2 or higher (not Q1)
                if ($nextLabel !== '' && preg_match('/^q+\s*([2-9]|[1-9]\d+)/', $nextLabel) && !$this->isQuizLabel($headers[1], '1')) {
                    array_splice($headers, 1, 0, 'Q1');
                }
            }

            $previewRows = [];
            $dataRows = [];

            for ($r = 14; $r <= 1000; $r++) {
                $rowData = $sheetData[$r] ?? [];
                if (empty($rowData)) continue;
                if (isset($rowData[2]) && (stripos($rowData[2], 'Prepared by') !== false || stripos($rowData[2], 'FACULTY') !== false)) {
                    break;
                }

                $studentId = $rowData[1] ?? null;
                $studentName = $rowData[2] ?? null;
                if (empty($studentId) || empty($studentName) || !preg_match('/\d/', $studentId)) {
                    continue;
                }

                $rowFinal = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $rowFinal[$i] = $rowData[$i] ?? '';
                }

                $dataRows[] = $rowFinal;
                $previewRows[] = $rowFinal;
            }

            // If fallback rule found no rows, show everything from raw sheet so users can still inspect Name..Remarks area.
            if (empty($dataRows)) {
                $headers = [];
                for ($i = 0; $i <= $maxCol; $i++) {
                    $headers[] = 'Col_' . $i;
                }
                $previewRows = [];
                $dataRows = [];

                foreach ($sheetData as $r => $rowData) {
                    $fullRow = [];
                    for ($i = 0; $i <= $maxCol; $i++) {
                        $fullRow[$i] = $rowData[$i] ?? '';
                    }
                    if (count(array_filter($fullRow, fn($v) => trim((string)$v) !== '')) === 0) {
                        continue;
                    }
                    $previewRows[] = $fullRow;
                    $dataRows[] = $fullRow;
                }
            }

            return ['headers' => $headers, 'rows' => $previewRows, 'raw_rows' => $dataRows, 'meta' => $meta, 'filename' => $fileName];
        } catch (\Exception $e) {
            \Log::error('XML Parse Error: ' . $e->getMessage());
            return ['headers' => [], 'rows' => [], 'meta' => [], 'filename' => $fileName];
        }
    }

    private function inferStudentNameColumn(array $sheetData, int $startDataRow, int $maxCol, ?int $currentNameCol = null): int
    {
        // Keep current if it already looks like a name column.
        $bestCol = $currentNameCol ?? 0;
        $bestScore = $currentNameCol !== null ? $this->studentNameColumnScore($sheetData, $startDataRow, $currentNameCol) : 0.0;

        // Evaluate every column, pick the one with most text-like (non-numeric) row values.
        for ($c = 0; $c <= $maxCol; $c++) {
            $score = $this->studentNameColumnScore($sheetData, $startDataRow, $c);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCol = $c;
            }
        }

        // If best candidate is at least 30% text-like, pick it. Otherwise keep current candidate.
        if ($bestScore >= 0.3) {
            return $bestCol;
        }

        return $currentNameCol ?? $bestCol;
    }

    private function studentNameColumnScore(array $sheetData, int $startDataRow, int $col): float
    {
        $total = 0;
        $textLike = 0;

        // Analyze up to 20 rows of data, starting from the first data row.
        for ($r = $startDataRow; $r < $startDataRow + 20; $r++) {
            $rowData = $sheetData[$r] ?? null;
            if (!is_array($rowData)) {
                continue;
            }

            $value = trim((string)($rowData[$col] ?? ''));
            if ($value === '') {
                continue;
            }

            $total++;
            if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $value)) {
                continue;
            }

            // Treat names and text as indicative of a student name column.
            if (preg_match('/^[A-Za-z\s\-\.\',]+$/', $value)) {
                $textLike++;
            } else {
                // also accept mixed values (e.g. "Smith, Juan") as name-like.
                $textLike++;
            }
        }

        if ($total === 0) {
            return 0.0;
        }

        return $textLike / $total;
    }

    private function isStudentNameHeader(string $header): bool
    {
        $lower = strtolower(trim($header));
        if ($lower === 'name of student' || $lower === 'student name' || $lower === 'name of students') {
            return true;
        }

        return str_contains($lower, 'name') && str_contains($lower, 'student');
    }

    private function isQuizLabel(string $label, string $quizNumber): bool
    {
        $lower = strtolower(trim($label));
        // Check if label matches pattern like "Q1", "Q 1", "QUIZ 1", etc.
        if (preg_match('/^(q|quiz)\s*' . preg_quote($quizNumber) . '$/i', $label)) {
            return true;
        }
        return false;
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
            $keywords = ['student', 'student id', 'name of student', 'name', 'no.', 'no', 'id', 'grade', 'average', 'total', 'remarks', 'remark', 'scores', 'midterm', 'final', 'quiz', 'section', 'instructor'];
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
                    'section' => !empty($meta['section']) ? (string) $meta['section'] : null,
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

    public function cancelPreview()
    {
        // Clear the excel preview data from session
        session()->forget('excel_preview_data');
        
        return response()->json(['success' => true, 'message' => 'Preview cancelled']);
    }
}
