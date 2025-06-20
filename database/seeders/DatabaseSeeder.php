<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Agent;
use App\Models\CallerId;
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
        $admin = User::create([
            "name" => "HaydarAdmin",
            "email" => "haydarAdmin@example.com",
            "role" => UserRole::Admin,
            'password' => Hash::make("haydar123"), 
        ]);

        // Create SuperAdmin User
        $superAdmin = User::create([
            "name" => "Haydar SuperAdmin",
            "email" => "haydarSuperAdmin@example.com",
            "role" => UserRole::SuperAdmin,
            'password' => Hash::make("haydar123"), 
        ]);

        // Create Agent Users
        for ($i = 1; $i <= 5; $i++) {
            $extension = 100 + $i;
            
            $user = User::create([
                'name' => "Agent {$i}",
                'email' => "agent{$i}@example.com",
                'role' => UserRole::Agent,
                'password' => Hash::make('password'),
            ]);

            $agent = Agent::create([
                'user_id' => $user->id,
                'name' => "Agent {$i}",
                'extension' => $extension,
                'status' => 'idle',
            ]);

            $user->update([
                'agent_id' => $agent->id,
            ]);
        }

        // Create Caller IDs
        $callerIds = [
            '02112345678',
            '02187654321',
            '02155555555',
            '02199999999',
            '02177777777',
        ];

        foreach ($callerIds as $number) {
            CallerId::create([
                'number' => $number,
                'is_active' => true,
            ]);
        }
    }
}