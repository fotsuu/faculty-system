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
        
        // Read worksheet
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        
        if (!$xml) {
            $this->error("Cannot read worksheet");
            return 1;
        }
        
        $doc = new \SimpleXMLElement($xml);
        $rowCount = 0;
        $importedCount = 0;
        
        foreach ($doc->sheetData->row as $row) {
            $data = [];
            foreach ($row->c as $cell) {
                $cellValue = '';
                
                // Check if cell references shared strings
                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    $stringIndex = (int)$cell->v;
                    $cellValue = $sharedStrings[$stringIndex] ?? '';
                } else {
                    $cellValue = (string)$cell->v;
                }
                
                $data[] = $cellValue;
            }
            
            // Skip header rows (first 10 rows based on the Excel structure)
            if ($rowCount >= 10 && count($data) > 0) {
                $data = array_map('trim', $data);
                $data = array_filter($data, fn($x) => !is_null($x) && $x !== '');
                $data = array_values($data);
                
                if (count($data) >= 2) {
                    $studentId = $data[0] ?? null;
                    $studentName = $data[1] ?? 'Unknown';
                    $course = $data[2] ?? 'BSIT';
                    $grade = $data[count($data) - 1] ?? null; // Last column is usually the grade
                    
                    if ($studentId) {
                        // Find or create student
                        $student = Student::firstOrCreate(
                            ['student_id' => $studentId],
                            [
                                'name' => $studentName,
                                'email' => strtolower(str_replace(' ', '.', $studentName)) . '@student.edu',
                                'program' => $course,
                            ]
                        );
                        
                        // Create record
                        $record = Record::firstOrCreate(
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
                        
                        $this->line("✓ Student: {$studentName} ($studentId) - Grade: {$grade}");
                        $importedCount++;
                    }
                }
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
}
