<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         User::Create([
            "name" => "HaydarAdmin",
            "email" => "haydarAdmin@example",
            "role" => UserRole::Admin,
            'password' => Hash::make("haydar123"), 
        ]);

         User::Create([
            "name" => "Haydar SuperAdmin",
            "email" => "haydarSuperAdmin@example",
            "role" => UserRole::SuperAdmin,
            'password' => Hash::make("haydar123"), 
        ]);

    }
}
