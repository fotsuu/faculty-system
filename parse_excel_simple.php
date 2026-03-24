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
    
    // Extract metadata
    $subject = '';
    $section = '';
    $instructor = '';
    
    foreach ($rows as $row) {
        $firstCol = isset($row[0]) ? trim($row[0]) : '';
        if (stripos($firstCol, 'Subject:') === 0) {
            $subject = trim(substr($firstCol, 8));
        }
        if (stripos($firstCol, 'Section:') === 0) {
            $section = trim(substr($firstCol, 8));
        }
        if (stripos($firstCol, 'Instructor:') === 0) {
            $instructor = trim(substr($firstCol, 11));
        }
    }
    
    echo "Subject: $subject\n";
    echo "Section: $section\n";
    echo "Instructor: $instructor\n\n";
    
    // Find student data rows (starting from row 10 onwards, skipping headers)
    $studentRows = array_slice($rows, 10);
    
    echo "Student Records Found: " . count($studentRows) . "\n";
    echo "First 5 student rows:\n";
    
    foreach (array_slice($studentRows, 0, 5) as $i => $row) {
        echo "Row $i: ";
        // Extract ID (col 1), Name (col 2), Course (col 3), Grade (last col)
        $id = isset($row[1]) ? trim($row[1]) : '';
        $name = isset($row[2]) ? trim($row[2]) : '';
        $course = isset($row[3]) ? trim($row[3]) : '';
        $grade = isset($row[count($row)-1]) ? trim($row[count($row)-1]) : '';
        echo "ID=$id | Name=$name | Course=$course | Grade=$grade\n";
    }
} else {
    echo "Failed to open ZIP file\n";
}
