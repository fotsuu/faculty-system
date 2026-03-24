<?php
require __DIR__ . '/vendor/autoload.php';

// Direct importer using the updated header-detection logic
$host = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_DATABASE') ?: 'faculty_system';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbName",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Find uploaded XLSX files
    $uploadDir = __DIR__ . '/storage/app/private/uploads';
    $files = glob($uploadDir . '/*SAD_3DSample*.xlsx');
    
    if (empty($files)) {
        echo "No sample file found\n";
        exit(1);
    }
    
    $filePath = $files[0];
    $fileName = basename($filePath);
    
    echo "Importing: " . $fileName . "\n\n";
    
    // Parse XLSX
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        echo "Failed to open XLSX\n";
        exit(1);
    }
    
    // Read shared strings
    $sharedStrings = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
        $doc = new SimpleXMLElement($xmlStrings);
        foreach ($doc->si as $si) {
            $sharedStrings[] = (string)$si->t;
        }
    }
    
    // Read worksheet
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    
    if (!$xml) {
        echo "No sheet found\n";
        exit(1);
    }
    
    $doc = new SimpleXMLElement($xml);
    
    // Process rows
    $rowCount = 0;
    $importedCount = 0;
    $skippedCount = 0;
    
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
            $data[] = trim($cellValue);
        }
        
        $rowCount++;
        
        // Use the same header detection logic
        if (isHeaderRow($data)) {
            echo "Row $rowCount: SKIP (Header/Artifact)\n";
            $skippedCount++;
            continue;
        }
        
        // Skip mostly empty rows
        $nonEmpty = count(array_filter($data, fn($v) => $v !== ''));
        if ($nonEmpty < 2) {
            echo "Row $rowCount: SKIP (Empty row)\n";
            $skippedCount++;
            continue;
        }
        
        // Detect student ID and name
        $studentId = null;
        $studentName = null;
        
        if (isset($data[0]) && is_numeric($data[0]) && isset($data[1]) && preg_match('/^\d{4}-\d+$/', $data[1])) {
            $studentId = $data[1];
            $studentName = $data[2] ?? null;
        } else {
            $studentId = $data[0] ?? null;
            $studentName = $data[1] ?? null;
        }
        
        if (!$studentId || !preg_match('/^\d{4}-\d+$/', $studentId)) {
            $skippedCount++;
            continue;
        }
        
        // Extract grade (scan backwards)
        $grade = null;
        for ($i = count($data) - 1; $i >= 2; $i--) {
            $val = $data[$i] ?? '';
            if ($val === '' || in_array(strtolower($val), ['inc', 'dr', 'u'])) continue;
            if (is_numeric(str_replace(',', '.', $val))) {
                $grade = str_replace(',', '.', $val);
                break;
            }
            if (preg_match('/^[A-DF]$/i', $val)) {
                $grade = strtoupper($val);
                break;
            }
        }
        
        echo "Row $rowCount: IMPORT - Student: $studentId | $studentName | Grade: $grade\n";
        $importedCount++;
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Summary:\n";
    echo "  Total Rows: $rowCount\n";
    echo "  Imported: $importedCount\n";
    echo "  Skipped: $skippedCount\n";
    echo str_repeat("=", 80) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Header detection function (copied from controller)
function isHeaderRow(array $data): bool
{
    $vals = array_map(fn($v) => strtolower(trim((string)$v)), $data);
    $origVals = array_map(fn($v) => trim((string)$v), $data);

    $nonEmpty = count(array_filter($vals, fn($v) => $v !== ''));
    if ($nonEmpty === 0) return true;

    // Check for dates
    foreach ($origVals as $v) {
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', $v)) {
            return true;
        }
    }

    // Check for purely numeric rows
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

    // Check for real student ID
    $hasStudentId = false;
    foreach ($vals as $v) {
        if (preg_match('/^\d{4}-\d+$/', $v)) {
            $hasStudentId = true;
            break;
        }
    }

    if (!$hasStudentId) {
        $keywords = ['student', 'student id', 'name', 'no.', 'no', 'id', 'grade', 'average', 'total', 'remarks', 'scores', 'midterm', 'final', 'quiz', 'section', 'instructor'];
        foreach ($vals as $v) {
            if ($v === '') continue;
            foreach ($keywords as $k) {
                if (strpos($v, $k) !== false) return true;
            }
        }

        $alphaCount = 0;
        foreach ($vals as $v) {
            if ($v === '') continue;
            if (preg_match('/^[a-z\s\.\-]+$/', $v)) $alphaCount++;
        }
        if ($nonEmpty > 0 && $alphaCount / $nonEmpty > 0.75) {
            return true;
        }

        $first = $vals[0] ?? '';
        if ($first !== '' && (strpos($first, 'total') === 0 || strpos($first, 'average') === 0)) {
            return true;
        }
    }

    return false;
}
