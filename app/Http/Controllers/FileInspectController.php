<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FileInspectController extends Controller
{
    public function inspectExcel($fileName)
    {
        try {
            $filePath = storage_path('app/uploads/' . $fileName);
            
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
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
                
                if ($xml) {
                    $data = $this->extractXMLData($xml, $sharedStrings);
                    return response()->json(['data' => $data], 200);
                }
            }
            
            return response()->json(['error' => 'Could not parse Excel file'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    
    private function extractXMLData($xml, $sharedStrings)
    {
        $rows = [];
        $doc = new \SimpleXMLElement($xml);
        
        foreach ($doc->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $cellValue = '';
                
                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                    $stringIndex = (int)$cell->v;
                    $cellValue = $sharedStrings[$stringIndex] ?? '';
                } else {
                    $cellValue = (string)$cell->v;
                }
                
                $rowData[] = $cellValue;
            }
            $rows[] = $rowData;
        }
        
        return $rows;
    }
}
