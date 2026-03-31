<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subject;

class DebugSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n=== Debug Seeder ===\n";
        
        // Get BSIT faculty
        $bsitFaculty = User::where('role', 'faculty')->where('department', 'BSIT')->first();
        echo "BSIT Faculty Found: " . ($bsitFaculty ? $bsitFaculty->name : "None") . "\n";
        echo "Faculty ID: " . ($bsitFaculty ? $bsitFaculty->id : "N/A") . "\n";
        
        if ($bsitFaculty) {
            echo "\nAttempting to create subject...\n";
            try {
                $subject = Subject::create([
                    'code' => 'CS101',
                    'name' => 'Introduction to Programming',
                    'user_id' => $bsitFaculty->id,
                    'status' => 'active',
                ]);
                echo "Subject created successfully! ID: " . $subject->id . "\n";
            } catch (\Exception $e) {
                echo "Error creating subject: " . $e->getMessage() . "\n";
                echo "Error Code: " . $e->getCode() . "\n";
            }
        }
        
        echo "\n=== End Debug ===\n";
    }
}
