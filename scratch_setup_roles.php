<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

$dept = Department::first();
if (!$dept) {
    $dept = Department::create([
        'name' => 'Computer Science Department',
        'code' => 'CS'
    ]);
}

// 1. Admin
$admin = User::firstOrCreate(
    ['email' => 'testadmin@example.com'],
    [
        'name' => 'API Test Admin',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'email_verified_at' => now()
    ]
);
$admin->password = Hash::make('password123');
$admin->role = 'admin';
$admin->save();

// 2. Department Head
$head = User::firstOrCreate(
    ['email' => 'testhead@example.com'],
    [
        'name' => 'API Test Dept Head',
        'password' => Hash::make('password123'),
        'role' => 'department_head',
        'email_verified_at' => now()
    ]
);
$head->password = Hash::make('password123');
$head->role = 'department_head';
$head->department_id = $dept->id ?? 1;
$head->save();

// 3. Department Member
$member = User::firstOrCreate(
    ['email' => 'testmember@example.com'],
    [
        'name' => 'API Test Dept Member',
        'password' => Hash::make('password123'),
        'role' => 'department_member',
        'email_verified_at' => now()
    ]
);
$member->password = Hash::make('password123');
$member->role = 'department_member';
$member->department_id = $dept->id ?? 1;
$member->save();

echo "Roles configured:\n";
echo "Admin: testadmin@example.com / password123\n";
echo "Head: testhead@example.com / password123\n";
echo "Member: testmember@example.com / password123\n";
