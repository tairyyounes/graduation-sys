<?php

use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->department = Department::create([
        'department_name' => 'Computer Science',
    ]);

    $this->admin = User::create([
        'full_name' => 'System Admin',
        'email' => 'admin@system.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'is_active' => true,
    ]);
});

test('admin can fetch users list and departments', function () {
    $response = $this->actingAs($this->admin)->getJson('/admin/users');

    $response->assertOk();
    $response->assertJsonStructure([
        'users' => [
            '*' => [
                'id',
                'name',
                'email',
                'role',
                'departmentId',
                'department',
                'status',
                'isActive',
                'studentNumber',
            ],
        ],
        'departments' => [
            '*' => [
                'department_id',
                'department_name',
            ],
        ],
    ]);
});

test('admin can create a new student user and linked student record', function () {
    $payload = [
        'full_name' => 'Ali Ahmed',
        'email' => 'ali123@cctt.edu.ly',
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'student_number' => '221001',
        'is_active' => true,
        'password' => 'password123',
    ];

    $response = $this->actingAs($this->admin)->postJson('/admin/users', $payload);

    $response->assertStatus(201);
    $response->assertJson([
        'message' => 'User created successfully.',
        'user' => [
            'name' => 'Ali Ahmed',
            'email' => 'ali123@cctt.edu.ly',
            'role' => 'student',
            'departmentId' => $this->department->department_id,
            'department' => 'Computer Science',
            'status' => 'Active',
            'isActive' => true,
            'studentNumber' => '221001',
        ],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'ali123@cctt.edu.ly',
        'role' => 'student',
    ]);

    $this->assertDatabaseHas('students', [
        'official_email' => 'ali123@cctt.edu.ly',
        'student_number' => '221001',
    ]);
});

test('admin can create a department member and admin user', function () {
    $memberPayload = [
        'full_name' => 'Dr Salem',
        'email' => 'salem@cctt.edu.ly',
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
        'password' => 'password123',
    ];

    $response = $this->actingAs($this->admin)->postJson('/admin/users', $memberPayload);
    $response->assertStatus(201);

    $adminPayload = [
        'full_name' => 'Super Admin',
        'email' => 'super@admin.com',
        'role' => 'admin',
        'is_active' => true,
        'password' => 'password123',
    ];

    $response2 = $this->actingAs($this->admin)->postJson('/admin/users', $adminPayload);
    $response2->assertStatus(201);
    $this->assertNull($response2->json('user.departmentId'));
});

test('validation rejects invalid email domains for student or member', function () {
    $payload = [
        'full_name' => 'Ali Ahmed',
        'email' => 'ali123@gmail.com',
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'student_number' => '221002',
        'is_active' => true,
        'password' => 'password123',
    ];

    $response = $this->actingAs($this->admin)->postJson('/admin/users', $payload);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('admin can update an existing user', function () {
    $user = User::create([
        'full_name' => 'Omar Salem',
        'email' => 'omar@cctt.edu.ly',
        'password' => Hash::make('password123'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $updatePayload = [
        'full_name' => 'Omar Salem Updated',
        'email' => 'omar.updated@cctt.edu.ly',
        'role' => 'department_head',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ];

    $response = $this->actingAs($this->admin)->putJson("/admin/users/{$user->id}", $updatePayload);

    $response->assertOk();
    $response->assertJson([
        'message' => 'User updated successfully.',
        'user' => [
            'name' => 'Omar Salem Updated',
            'email' => 'omar.updated@cctt.edu.ly',
            'role' => 'department_head',
        ],
    ]);
});

test('admin cannot delete their own account', function () {
    $response = $this->actingAs($this->admin)->deleteJson("/admin/users/{$this->admin->id}");

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'You cannot delete your own account.',
    ]);
});

test('admin can delete another user and soft deletes properly', function () {
    $user = User::create([
        'full_name' => 'Tariq Student',
        'email' => 'tariq@cctt.edu.ly',
        'password' => Hash::make('password123'),
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    Student::create([
        'student_number' => '221003',
        'full_name' => 'Tariq Student',
        'official_email' => 'tariq@cctt.edu.ly',
        'department_id' => $this->department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/admin/users/{$user->id}");

    $response->assertOk();
    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertSoftDeleted('students', ['official_email' => 'tariq@cctt.edu.ly']);
});
