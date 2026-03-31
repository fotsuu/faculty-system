<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Record;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;

class ImportExcelFile extends Command
{
    protected $signature = 'import:excel {filePath} {userId}';
    protected $description = 'Import Excel file data into database';

    public function handle()
    {
        $filePath = $this->argument('filePath');
        $userId = $this->argument('userId');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found: $userId");
            return 1;
        }
        
        $this->info("Importing file: $filePath for user: {$user->name}");
        
        // Parse XLSX file
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== TRUE) {
            $this->error("Cannot open ZIP file");
            return 1;
        }
        
        // Read shared strings
        $sharedStrings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
            $doc = new \SimpleXMLElement($xmlStrings);
            foreach ($doc->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        // Get subject code from filename or use default
        $subjectCode = 'IT633';
        $subjectName = 'System Analysis and Design';
        
        // Find or create subject
        $subject = Subject::firstOrCreate(
            ['code' => $subjectCode, 'user_id' => $user->id],
            [
                'name' => $subjectName,
                'user_id' => $user->id,
                'status' => 'active',
            ]
        );
        
        // Read class record worksheet (multi-sheet workbook support)
        $sheetPath = $this->resolveClassRecordSheetPath($zip) ?? 'xl/worksheets/sheet1.xml';
        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if (!$xml) {
            $this->error("Cannot read worksheet");
            return 1;
        }
        
        $doc = new \SimpleXMLElement($xml);
        $rowCount = 0;
        $importedCount = 0;
        
        $headerRowDetected = false;

        foreach ($doc->sheetData->row as $row) {
            $data = [];
            foreach ($row->c as $cell) {
                $cellValue = '';

                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    $stringIndex = (int)$cell->v;
                    $cellValue = $sharedStrings[$stringIndex] ?? '';
                } else {
                    $cellValue = (string)$cell->v;
                }

                $data[] = $cellValue;
            }

            if (!$headerRowDetected) {
                $lowerData = array_map(fn($v) => strtolower(trim((string)$v)), $data);
                $hasStudent = false;
                $hasName = false;
                $hasRemarks = false;

                foreach ($lowerData as $val) {
                    if ($val === '') continue;
                    if (str_contains($val, 'student')) $hasStudent = true;
                    if (str_contains($val, 'name')) $hasName = true;
                    if (str_contains($val, 'remark')) $hasRemarks = true;
                }

                if (($hasName && ($hasStudent || $hasRemarks)) || ($hasStudent && $hasRemarks)) {
                    $headerRowDetected = true;
                    $rowCount++;
                    continue;
                }

                $rowCount++;
                continue;
            }

            $firstCell = strtolower(trim($data[0] ?? ''));
            if (str_contains($firstCell, 'prepared by') || str_contains($firstCell, 'faculty') || str_contains($firstCell, 'total')) {
                break;
            }

            $trimmedData = array_map('trim', $data);
            $notEmpty = array_filter($trimmedData, fn($x) => $x !== '');
            if (count($notEmpty) < 2) {
                $rowCount++;
                continue;
            }

            $studentId = trim($data[0] ?? '');
            $studentName = trim($data[1] ?? 'Unknown');
            $course = trim($data[2] ?? 'BSIT');
            $grade = trim($data[count($data) - 1] ?? '');

            if ($studentId !== '') {
                $student = Student::firstOrCreate(
                    ['student_id' => $studentId],
                    [
                        'name' => $studentName,
                        'email' => strtolower(str_replace(' ', '.', $studentName)) . '@student.edu',
                        'program' => $course,
                    ]
                );

                Record::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'subject_id' => $subject->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'file_name' => basename($filePath),
                        'notes' => $grade ? "Grade: $grade" : 'Imported from upload',
                    ]
                );

                $this->line("✓ Student: {$studentName} ({$studentId}) - Grade: {$grade}");
                $importedCount++;
            }

            $rowCount++;
        }
        
        $this->info("✅ Import complete! Imported $importedCount records");
        
        // Show stats
        $studentCount = Student::count();
        $recordCount = Record::where('user_id', $user->id)->count();
        
        $this->info("\n=== Database Stats ===");
        $this->info("Total Students: $studentCount");
        $this->info("Records for user: $recordCount");
        
        return 0;
    }

    private function resolveClassRecordSheetPath(\ZipArchive $zip)
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if (!$workbookXml) {
            return null;
        }

        try {
            $wbDoc = new \SimpleXMLElement($workbookXml);
            $targetRid = null;
            foreach ($wbDoc->sheets->sheet as $sheet) {
                $sheetName = (string) $sheet['name'];
                if ($sheetName !== '' && stripos($sheetName, 'class record') !== false) {
                    $targetRid = (string) $sheet['r:id'];
                    break;
                }
            }

            if (!$targetRid) {
                foreach ($wbDoc->sheets->sheet as $sheet) {
                    $sheetName = (string) $sheet['name'];
                    if ($sheetName !== '' && (stripos($sheetName, 'record') !== false || stripos($sheetName, 'class') !== false)) {
                        $targetRid = (string) $sheet['r:id'];
                        break;
                    }
                }
            }

            if (!$targetRid) {
                return null;
            }

            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if (!$relsXml) {
                return null;
            }

            $relsDoc = new \SimpleXMLElement($relsXml);
            foreach ($relsDoc->Relationship as $rel) {
                if ((string) $rel['Id'] === $targetRid) {
                    $target = (string) $rel['Target'];
                    return 'xl/' . ltrim($target, '/');
                }
            }
        } catch (\Exception $e) {
            $this->error('Could not resolve class record sheet path: ' . $e->getMessage());
            return null;
        }

        return null;
    }
}
