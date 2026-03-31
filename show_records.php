<?php
// show some records with new columns
$env = [];
$lines = file('.env');
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#') continue;
    if (strpos($line, '=') === false) continue;
    list($key, $value) = explode('=', $line, 2);
    $env[trim($key)] = trim($value);
}
$dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
$password = !empty($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
$db = new PDO($dsn, $env['DB_USERNAME'], $password);

$stmt = $db->prepare("SELECT r.id as record_id, s.student_id, r.file_name, r.notes, r.raw_grade, r.numeric_grade, r.grade_point, r.scores, r.row_index FROM records r JOIN students s ON r.student_id = s.id WHERE r.file_name LIKE ? LIMIT 20");
$stmt->execute(['%SAD_3DSample%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo json_encode($row) . PHP_EOL;
}
