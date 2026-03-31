<?php

$filePath = 'storage/app/private/uploads/1769790142_SAD_3DSample.xlsx';

// Open ZIP file
$zip = new ZipArchive();
if ($zip->open($filePath) === TRUE) {
    // Read shared strings
    $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
    $strings = [];
    foreach ($xml->si as $si) {
        $strings[] = (string)$si->t;
    }
    
    // Read worksheet
    $worksheetXml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
    $rows = [];
    
    foreach ($worksheetXml->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $value = '';
            if (isset($cell->v)) {
                $value = (string)$cell->v;
                if ((string)$cell['t'] === 's') {
                    // String reference
                    $value = isset($strings[(int)$value]) ? $strings[(int)$value] : '';
                }
            }
            $rowData[] = $value;
        }
        if (!empty(array_filter($rowData))) {
            $rows[] = $rowData;
        }
    }
    
    $zip->close();
    
    // Display first 10 rows
    echo "Headers and Data Sample:\n";
    foreach (array_slice($rows, 0, 10) as $i => $row) {
        echo 'Row ' . $i . ': ' . implode(' | ', $row) . "\n";
    }
    echo "\nTotal Rows: " . count($rows) . "\n";
} else {
    echo "Failed to open ZIP file\n";
}
