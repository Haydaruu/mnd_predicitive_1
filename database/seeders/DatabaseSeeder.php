<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'HaydarAdmin',
            'email' => 'haydarAdmin@example.com',
            'role' => UserRole::Admin,
            'password' => Hash::make('haydar123'),
        ]);

        // Create SuperAdmin User
        User::create([
            'name' => 'Haydar SuperAdmin',
            'email' => 'haydarSuperAdmin@example.com',
            'role' => UserRole::SuperAdmin,
            'password' => Hash::make('haydar123'),
        ]);
    }
}