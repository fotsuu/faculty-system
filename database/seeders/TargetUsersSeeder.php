<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TargetUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default credentials for auto-detected accounts:
        // nel@dssc.edu.ph       | password123
        // rhea@dssc.edu.ph      | password123
        // felomino@dssc.edu.ph  | password123

        $users = [
            [
                'name' => 'Nel Panaligan',
                'email' => 'nel@dssc.edu.ph',
                'password' => Hash::make('password123'),
                'role' => 'program_head',
                'department' => 'BSIT',
                'status' => 'active',
            ],
            [
                'name' => 'Rhea Mae Perito',
                'email' => 'rhea@dssc.edu.ph',
                'password' => Hash::make('password123'),
                'role' => 'program_head',
                'department' => 'BSIS',
                'status' => 'active',
            ],
            [
                'name' => 'Felomino Alba',
                'email' => 'felomino@dssc.edu.ph',
                'password' => Hash::make('password123'),
                'role' => 'dean',
                'department' => 'All',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
