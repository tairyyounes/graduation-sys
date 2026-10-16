<?php

use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->department = Department::create(['department_name' => 'Computer Science']);

    $this->head = User::create([
        'full_name' => 'Department Head',
        'email' => 'head@cctt.edu.ly',
        'password' => Hash::make('password123'),
        'role' => 'department_head',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);
});

function addStudent(array $overrides = []): \Illuminate\Testing\TestResponse
{
    return test()->actingAs(test()->head)->postJson('/department/students', $overrides + [
        'student_number' => '221001',
        'full_name' => 'Ali Ahmed',
        'semester' => 8,
        'is_active' => true,
        'role' => 'student',
    ]);
}

test('a new student gets an email and password derived from the student number', function () {
    addStudent()->assertOk();

    $user = User::where('email', '221001@cctt.edu.ly')->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('221001', $user->password))->toBeTrue();
    $this->assertDatabaseHas('students', ['student_number' => '221001', 'official_email' => '221001@cctt.edu.ly']);
});

test('a password sent with a new student is ignored', function () {
    addStudent(['password' => 'something-else'])->assertOk();

    expect(Hash::check('221001', User::where('email', '221001@cctt.edu.ly')->first()->password))->toBeTrue();
});

test('creating a student with an email that does not match the student number is rejected', function () {
    addStudent(['email' => '221002@cctt.edu.ly'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertDatabaseMissing('students', ['student_number' => '221001']);
});

test('editing the student number moves the email but keeps the password', function () {
    addStudent()->assertOk();
    $student = Student::where('student_number', '221001')->first();

    $this->actingAs($this->head)->putJson("/department/students/{$student->student_id}", [
        'student_number' => '221009',
        'full_name' => 'Ali Ahmed',
        'semester' => 8,
        'is_active' => true,
        'password' => 'new-password',
    ])->assertOk();

    $this->assertDatabaseHas('students', ['student_id' => $student->student_id, 'official_email' => '221009@cctt.edu.ly']);
    $user = User::where('email', '221009@cctt.edu.ly')->first();
    expect($user)->not->toBeNull();
    // Still the original password: neither the new number nor the submitted value.
    expect(Hash::check('221001', $user->password))->toBeTrue();
});

test('editing a student with a mismatched email is rejected', function () {
    addStudent()->assertOk();
    $student = Student::where('student_number', '221001')->first();

    $this->actingAs($this->head)->putJson("/department/students/{$student->student_id}", [
        'student_number' => '221001',
        'full_name' => 'Ali Ahmed',
        'email' => '999999@cctt.edu.ly',
        'semester' => 8,
        'is_active' => true,
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('the CSV preview flags rows whose email does not match the student number', function () {
    $csv = "student_number,full_name,email\n221001,Ali Ahmed,221001@cctt.edu.ly\n221002,Omar Salem,221999@cctt.edu.ly\n221003,Sara Ali,\n";
    $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

    $rows = $this->actingAs($this->head)
        ->post('/department/students/import', ['file' => $file], ['Accept' => 'application/json'])
        ->assertOk()
        ->json('staged_students');

    expect($rows[0]['errors'])->toBe([]);
    expect($rows[1]['errors'])->toContain(__('validation.custom.email.student_number_mismatch'));
    expect($rows[2]['email'])->toBe('221003@cctt.edu.ly');
    expect($rows[2])->not->toHaveKey('password');
});

test('confirming a CSV import rejects mismatched emails and sets the default password', function () {
    $this->actingAs($this->head)->postJson('/department/students/import-confirm', [
        'students' => [
            ['student_number' => '221001', 'full_name' => 'Ali Ahmed', 'email' => '221001@cctt.edu.ly'],
            ['student_number' => '221002', 'full_name' => 'Omar Salem', 'email' => '221999@cctt.edu.ly'],
        ],
    ])->assertStatus(422)->assertJsonStructure(['errors' => ['row_1']]);

    $this->assertDatabaseCount('students', 0);

    $this->actingAs($this->head)->postJson('/department/students/import-confirm', [
        'students' => [
            ['student_number' => '221001', 'full_name' => 'Ali Ahmed', 'email' => '221001@cctt.edu.ly', 'password' => 'ignored'],
            ['student_number' => '221002', 'full_name' => 'Omar Salem'],
        ],
    ])->assertOk();

    expect(Hash::check('221001', User::where('email', '221001@cctt.edu.ly')->first()->password))->toBeTrue();
    expect(Hash::check('221002', User::where('email', '221002@cctt.edu.ly')->first()->password))->toBeTrue();
});

test('the CSV template no longer asks for an email or password', function () {
    $response = $this->actingAs($this->head)->get('/department/students/template');

    $response->assertOk();
    expect(trim($response->streamedContent()))->toBe('student_number,full_name,semester,is_active');
});
