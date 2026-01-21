<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'wu@bunnycommunications.com'],
            [
                'name' => 'Wu',
                'password' => Hash::make('4EarrGvUL2K4'),
                'role' => User::ROLE_EMPLOYEE,
            ]
        );
    }
}
