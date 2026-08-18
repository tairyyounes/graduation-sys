<?php

use App\Models\Department;
use App\Models\Student;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $department = Department::create([
        'department_name' => 'Programming',
    ]);

    Student::create([
        'student_number' => '123456',
        'full_name' => 'Test User',
        'official_email' => 'test@example.com',
        'department_id' => $department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
