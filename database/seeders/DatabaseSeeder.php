<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed target program heads and dean
        $this->call([
            TargetUsersSeeder::class,
        ]);

        // Seed faculty for BSIT department (under Nel)
        $nelbsitFaculty = [
            [
                'name' => 'Prof. Antonio Santos',
                'email' => 'santos.antonio@dssc.edu.ph',
                'password' => bcrypt('password123'),
                'role' => 'faculty',
                'department' => 'BSIT',
                'status' => 'active',
            ],
            [
                'name' => 'Engr. Maria Torres',
                'email' => 'torres.maria@dssc.edu.ph',
                'password' => bcrypt('password123'),
                'role' => 'faculty',
                'department' => 'BSIT',
                'status' => 'active',
            ],
        ];

        // Seed faculty for BSIS department (under Rhea)
        $rheabsisFaculty = [
            [
                'name' => 'Prof. Robert Cruz',
                'email' => 'cruz.robert@dssc.edu.ph',
                'password' => bcrypt('password123'),
                'role' => 'faculty',
                'department' => 'BSIS',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Grace Raymundo',
                'email' => 'raymundo.grace@dssc.edu.ph',
                'password' => bcrypt('password123'),
                'role' => 'faculty',
                'department' => 'BSIS',
                'status' => 'active',
            ],
        ];

        foreach (array_merge($nelbsitFaculty, $rheabsisFaculty) as $facultyData) {
            User::updateOrCreate(
                ['email' => $facultyData['email']],
                $facultyData
            );
        }
    }
}
