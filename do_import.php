<?php
// Bootstrap Laravel
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\ExcelGradeImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

// Get first user
$user = User::find(1);
if (!$user) {
    echo "User not found\n";
    exit(1);
}
Auth::login($user);

echo "User: {$user->name} ({$user->email})\n\n";

$uploadDir = 'storage/app/private/uploads/';
$files = glob($uploadDir . '*_SAD_3DSample*.xlsx');
if (empty($files)) {
    echo "No matching Excel files found in $uploadDir\n";
    exit(1);
}
usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
$filePath = $files[0];

echo "Processing file: $filePath\n";

// We need to use the logic from RecordUploadController to parse it correctly first
// For testing, we'll manually invoke ZipArchive similar to RecordUploadController
$zip = new \ZipArchive();
if ($zip->open($filePath) === TRUE) {
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
    
    // Helper to convert column letter to index
    $colToIndex = function($col) {
        $index = 0;
        for ($i = 0; $i < strlen($col); $i++) {
            $index = $index * 26 + ord($col[$i]) - 0x40;
        }
        return $index - 1;
    };

    $doc = new \SimpleXMLElement($xml);
    $sheetData = [];
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

    // Row 11-13 headers
    $row11 = $sheetData[11] ?? [];
    $row12 = $sheetData[12] ?? [];
    $row13 = $sheetData[13] ?? [];
    $headers = [];
    $lastMainHeader = '';
    $maxCol = 0;
    foreach ($sheetData as $r => $cols) { if (!empty($cols)) $maxCol = max($maxCol, ...array_keys($cols)); }
    
    for ($i = 0; $i <= $maxCol; $i++) {
        $main = $row11[$i] ?? $lastMainHeader;
        if (!empty($row11[$i])) $lastMainHeader = $row11[$i];
        $sub = $row12[$i] ?? '';
        // Format sub header if numeric (date)
        if (is_numeric($sub) && $sub > 40000) $sub = date('m/d/Y', ($sub - 25569) * 86400);
        $max = $row13[$i] ?? '';
        $headerName = trim($main . ($sub ? " ($sub)" : "") . ($max ? " [Max: $max]" : ""));
        $headers[$i] = $headerName ?: "Col_$i";
    }

    $dataRows = [];
    for ($r = 14; $r <= 1000; $r++) {
        $rowData = $sheetData[$r] ?? [];
        if (empty($rowData) || (isset($rowData[2]) && stripos($rowData[2], 'Prepared by') !== false)) break;
        if (empty($rowData[1])) continue;
        
        $rowFinal = [];
        for ($i = 0; $i <= $maxCol; $i++) { $rowFinal[$i] = $rowData[$i] ?? ''; }
        $dataRows[] = $rowFinal;
    }

    $meta = ['filename' => basename($filePath), 'subject' => 'IT633 - System Analysis and Design'];
    $importer = new ExcelGradeImporter($user);
    $stats = $importer->importFromParsedData($headers, $dataRows, $meta);
    
    print_r($stats);
} else {
    echo "Failed to open Excel file\n";
}
