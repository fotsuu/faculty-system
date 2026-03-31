<?php
// Test header detection logic

function isHeaderRow(array $data): bool
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

// Test data rows
$testRows = [
    ['45605', '09/16/24'], // Date artifact - should skip
    ['10', '10'], // Pure numeric - should skip
    ['2022-00809', 'Student 1', 'IT 633', '1'], // Valid student - should NOT skip
    ['2022-00577', 'Student 2', 'IT 633', '2'], // Valid student - should NOT skip
    ['Student', 'ID', 'Name', 'Grade'], // Header row - should skip
];

echo "Header Detection Test Results:\n";
echo str_repeat("=", 80) . "\n";

foreach ($testRows as $idx => $row) {
    $isHeader = isHeaderRow($row);
    $display = implode(" | ", $row);
    $result = $isHeader ? "SKIP (Header)" : "IMPORT (Data)";
    echo sprintf("Row %d: %-60s -> %s\n", $idx + 1, $display, $result);
}

echo str_repeat("=", 80) . "\n";
