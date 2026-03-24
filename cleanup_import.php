<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fileName = '1769790142_SAD_3DSample.xlsx';
$deleted = \App\Models\Record::where('file_name', $fileName)->delete();
echo "✅ Cleaned records for " . $fileName . " (Deleted: " . $deleted . ")\n";
echo "Remaining records: " . \App\Models\Record::count() . "\n";
