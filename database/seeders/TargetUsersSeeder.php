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
        // Default credentials:
        // nel.panaligan@dssc.edu.ph   | nel12345
        // rhea.perito@dssc.edu.ph     | rhea12345
        // felomino.alba@dssc.edu.ph   | felomino123

        $users = [
            [
                'name' => 'Nel Panaligan',
                'email' => 'nel.panaligan@dssc.edu.ph',
                'password' => Hash::make('nel12345'),
                'role' => 'program_head',
                'department' => 'BSIT',
                'status' => 'active',
            ],
            [
                'name' => 'Rhea Mae Perito',
                'email' => 'rhea.perito@dssc.edu.ph',
                'password' => Hash::make('rhea12345'),
                'role' => 'program_head',
                'department' => 'BSIS',
                'status' => 'active',
            ],
            [
                'name' => 'Felomino Alba',
                'email' => 'felomino.alba@dssc.edu.ph',
                'password' => Hash::make('felomino123'),
                'role' => 'dean',
                'department' => 'General Studies',
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
