<?php

$filePath = 'storage/app/private/uploads/1769790142_SAD_3DSample.xlsx';

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

echo "Parsing file: $filePath\n\n";

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

// Read worksheet
$xml = $zip->getFromName('xl/worksheets/sheet1.xml');
$zip->close();

if (!$xml) {
    echo "Cannot read worksheet\n";
    exit(1);
}

$doc = new \SimpleXMLElement($xml);
$rowCount = 0;

echo "Row Analysis:\n";
echo str_repeat("-", 100) . "\n";

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
    
    if ($rowCount >= 10 && count($data) > 0) {
        $data = array_map('trim', $data);
        $data = array_filter($data, fn($x) => !is_null($x) && $x !== '');
        $data = array_values($data);
        
        if ($rowCount <= 20) { // Show first 10 data rows
            echo "Row $rowCount: ";
            echo "Col0='{$data[0]}' | Col1='" . (isset($data[1]) ? $data[1] : '') . "' | Col2='" . (isset($data[2]) ? $data[2] : '') . "' | Last='" . (isset($data[count($data)-1]) ? $data[count($data)-1] : '') . "'\n";
        }
    }
    
    $rowCount++;
}
