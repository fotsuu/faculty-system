<?php

// Load .env file manually
$env = [];
$lines = file('.env');
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim($value);
}

// Create PDO connection
$dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
$password = !empty($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
$db = new PDO($dsn, $env['DB_USERNAME'], $password);

echo "Connected to database: {$env['DB_DATABASE']}\n\n";

// Get first user
$stmt = $db->query("SELECT id, name, email FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "No users found in database\n";
    exit(1);
}

$userId = $user['id'];
$userName = $user['name'];
echo "User: {$userName} (ID: {$userId})\n\n";

$filePath = 'storage/app/private/uploads/1769790142_SAD_3DSample.xlsx';

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

echo "Processing file: $filePath\n\n";

// Parse XLSX
$zip = new \ZipArchive();
if ($zip->open($filePath) !== TRUE) {
    echo "Cannot open ZIP file\n";
    exit(1);
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

// Get subject
$subjectCode = 'IT633';
$subjectName = 'System Analysis and Design';

// Check if subject exists
$stmt = $db->prepare("SELECT id FROM subjects WHERE code = ? AND user_id = ?");
$stmt->execute([$subjectCode, $userId]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    // Create subject
    $stmt = $db->prepare("INSERT INTO subjects (code, name, user_id, status, created_at, updated_at) VALUES (?, ?, ?, 'active', NOW(), NOW())");
    $stmt->execute([$subjectCode, $subjectName, $userId]);
    $subjectId = $db->lastInsertId();
    echo "Created Subject: {$subjectCode} - {$subjectName}\n\n";
} else {
    $subjectId = $subject['id'];
    echo "Using existing Subject: {$subjectCode}\n\n";
}

// Read worksheet
$xml = $zip->getFromName('xl/worksheets/sheet1.xml');
$zip->close();

if (!$xml) {
    echo "Cannot read worksheet\n";
    exit(1);
}

$doc = new \SimpleXMLElement($xml);
$rowCount = 0;
$importedCount = 0;

echo "Importing student records...\n";
echo str_repeat("-", 80) . "\n";

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
    if ($rowCount >= 10 && count($data) > 1) {
        $data = array_map('trim', $data);
        $data = array_filter($data, fn($x) => !is_null($x) && $x !== '');
        $data = array_values($data);
        
        if (count($data) >= 3) {
            // Data format: [count, student_id, student_name, ..., grade]
            $studentId = $data[1] ?? null;
            $studentName = $data[2] ?? 'Unknown';
            
            // Validate studentId is a student ID (numeric or in format 2022-xxxxx)
            if (!$studentId || !preg_match('/^\d{4}-\d+$/', $studentId)) {
                $rowCount++;
                continue; // Skip non-student rows
            }
            
            $course = 'BSIT'; // Default course
            $grade = null;
            
            // Extract grade - look for numeric value in the last columns
            for ($i = count($data) - 1; $i >= 3; $i--) {
                $val = trim($data[$i]);
                // Check if it's a numeric grade value
                if (is_numeric($val)) {
                    $grade = $val;
                    break;
                }
            }
            
            if ($studentId && strlen((string)$studentId) > 0) {
                // Find or create student
                $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ?");
                $stmt->execute([$studentId]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$student) {
                    $email = strtolower(str_replace(' ', '.', $studentName)) . '@student.edu';
                    $stmt = $db->prepare("INSERT INTO students (student_id, name, email, program, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$studentId, $studentName, $email, $course]);
                    $studentDbId = $db->lastInsertId();
                } else {
                    $studentDbId = $student['id'];
                }
                
                // Create structured scores array
                $scores = [];
                for ($i = 3; $i < count($data); $i++) {
                    $scores['c' . $i] = $data[$i];
                }

                // Prepare grade fields
                $rawGrade = $grade;
                $numericGrade = null;
                if (!is_null($rawGrade) && is_numeric(str_replace(',', '.', $rawGrade))) {
                    $numericGrade = (float) str_replace(',', '.', $rawGrade);
                }

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

                $notes = $rawGrade ? "Grade: {$rawGrade}" : "Imported from upload";
                $stmt = $db->prepare("INSERT INTO records (user_id, subject_id, student_id, file_name, notes, raw_grade, numeric_grade, grade_point, scores, row_index, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE notes = VALUES(notes), raw_grade = VALUES(raw_grade), numeric_grade = VALUES(numeric_grade), grade_point = VALUES(grade_point), scores = VALUES(scores), row_index = VALUES(row_index)");
                $stmt->execute([$userId, $subjectId, $studentDbId, basename($filePath), $notes, $rawGrade, $numericGrade, $gradePoint, !empty($scores) ? json_encode($scores) : null, $rowCount]);
                
                echo "✓ {$studentName} ({$studentId}) | Grade: {$grade}\n";
                $importedCount++;
            }
        }
    }
    
    $rowCount++;
}

echo str_repeat("-", 80) . "\n";
echo "\n✅ Import complete! Imported $importedCount records\n";

// Show stats
$stmt = $db->query("SELECT COUNT(*) as count FROM students");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$studentCount = $result['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM records WHERE user_id = ?");
$stmt->execute([$userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$recordCount = $result['count'];

echo "\n=== Database Stats ===\n";
echo "Total Students: $studentCount\n";
echo "Records for {$userName}: $recordCount\n";
