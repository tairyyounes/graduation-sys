<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * This seeder is fully idempotent — safe to run multiple times.
     */
    public function run(): void
    {
        $this->call(InitialDataSeeder::class);

        // Seed a test student user for development.
        // We use DB::table() directly to avoid any Eloquent accessor interference.
        $testEmail = 'test@example.com';
        $departmentId = DB::table('departments')->value('department_id') ?? 1;

        // 1. Ensure the student record exists first (users.email FK's students.official_email)
        DB::table('students')->insertOrIgnore([
            'student_number' => 'STU-00001',
            'full_name'      => 'Test Student',
            'official_email' => $testEmail,
            'department_id'  => $departmentId,
            'semester'       => 8,
            'is_active'      => true,
        ]);

        // 2. Ensure the user account exists and is verified
        DB::table('users')->insertOrIgnore([
            'full_name'         => 'Test Student',
            'email'             => $testEmail,
            'password'          => Hash::make('password'),
            'role'              => 'student',
            'department_id'     => $departmentId,
            'is_active'         => true,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
