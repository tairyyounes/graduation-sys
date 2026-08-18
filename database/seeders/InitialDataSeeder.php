<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Programming', 'Networks', 'Control'] as $dept) {
            DB::table('departments')->updateOrInsert(
                ['department_name' => $dept],
                ['department_name' => $dept]
            );
        }

        DB::table('users')->insertOrIgnore([
            'full_name' => 'System Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department_id' => null,
            'is_active' => true,
            'email_verified_at' => now(), // Admin is system-created, auto-verified
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}