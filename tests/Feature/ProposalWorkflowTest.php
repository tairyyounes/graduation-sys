<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\{actingAs, postJson, putJson};

beforeEach(function () {
    // Create department
    $this->department = Department::create([
        'department_name' => 'Networks',
    ]);

    // Create user and student
    $this->user = User::create([
        'full_name' => 'Test Student',
        'email' => 'student@test.com',
        'password' => Hash::make('password'),
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $this->student = Student::create([
        'student_number' => '123456',
        'full_name' => 'Test Student',
        'official_email' => 'student@test.com',
        'department_id' => $this->department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);
});

it('validates proposal fields on creation', function () {
    actingAs($this->user);

    // Creating proposal with missing title should fail
    $response = postJson('/student/proposals', [
        'title' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
});

it('allows draft creation with only title but validates word counts if fields are filled', function () {
    actingAs($this->user);

    // Creating proposal draft with short problem statement
    $response = postJson('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'problem' => 'Too short.', // Should fail problem statement count (min 30 words)
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['problem']);
});

it('blocks updating a proposal more than 3 times', function () {
    actingAs($this->user);

    $proposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'draft',
        'review_status' => 'pending',
    ]);

    // Create 4 versions (initial version + 3 updates)
    for ($i = 1; $i <= 4; $i++) {
        ProposalVersion::create([
            'proposal_id' => $proposal->proposal_id,
            'version_number' => $i,
            'title' => 'Proposal Title ' . $i,
        ]);
    }

    $proposal->students()->attach($this->student->student_id, [
        'member_role' => 'owner',
        'invitation_status' => 'accepted',
        'joined_at' => now(),
    ]);

    // The 4th update (which would create the 5th version) should be blocked
    $response = putJson('/student/proposals/' . $proposal->proposal_id, [
        'title' => 'Proposal Title 5',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'You can only update your proposal three times.'
    ]);
});

it('enforces strict validation on submission', function () {
    actingAs($this->user);

    $proposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'draft',
        'review_status' => 'pending',
    ]);

    $proposal->students()->attach($this->student->student_id, [
        'member_role' => 'owner',
        'invitation_status' => 'accepted',
        'joined_at' => now(),
    ]);

    // Create incomplete version (missing other required fields like problem, solution, etc.)
    ProposalVersion::create([
        'proposal_id' => $proposal->proposal_id,
        'version_number' => 1,
        'title' => 'Smart Networks Project Title',
    ]);

    // Submitting incomplete proposal should fail validation
    $response = putJson('/student/proposals/' . $proposal->proposal_id . '/submit');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['problem', 'solution', 'functions', 'objectives', 'tags', 'tech']);
});

it('validates team member invitations', function () {
    actingAs($this->user);

    $proposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'draft',
        'review_status' => 'pending',
    ]);

    $proposal->students()->attach($this->student->student_id, [
        'member_role' => 'owner',
        'invitation_status' => 'accepted',
        'joined_at' => now(),
    ]);

    // Non-numeric invite code
    $response = postJson("/student/proposals/{$proposal->proposal_id}/invite", [
        'reg_number' => 'ABC',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['reg_number']);
    expect($response->json('message'))->toBe('Student number must contain numbers only.');

    // Long invite code (> 6 digits)
    $response = postJson("/student/proposals/{$proposal->proposal_id}/invite", [
        'reg_number' => '1234567',
    ]);
    $response->assertStatus(422);
    expect($response->json('message'))->toBe('Student number must not be more than 6 digits.');

    // Non-existent student invite
    $response = postJson("/student/proposals/{$proposal->proposal_id}/invite", [
        'reg_number' => '999999',
    ]);
    $response->assertStatus(422);
    expect($response->json('message'))->toBe('This student does not exist in the system.');

    // Logged-in student (owner) invite
    $response = postJson("/student/proposals/{$proposal->proposal_id}/invite", [
        'reg_number' => '123456',
    ]);
    $response->assertStatus(422);
    expect($response->json('message'))->toBe('This student is already added to this team.');
});

it('provides shared proposal repository functionality', function () {
    actingAs($this->user);

    // Create a submitted proposal to show in repository
    $repoProposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'accepted',
    ]);
    $repoProposal->created_at = now()->subYears(1);
    $repoProposal->save();

    $repoVersion = ProposalVersion::create([
        'proposal_id' => $repoProposal->proposal_id,
        'version_number' => 1,
        'title' => 'Historical Android Mobile App',
        'problem' => 'Some descriptive problem text for networks...',
        'solution' => 'A custom solution text for networks...',
    ]);
    $repoVersion->created_at = now()->subYears(1);
    $repoVersion->save();

    // Test GET /repository index
    $response = $this->getJson('/repository');
    $response->assertStatus(200);
    $response->assertJsonStructure(['proposals', 'years']);
    expect($response->json('proposals'))->toHaveCount(1);
    expect($response->json('proposals.0.title'))->toBe('Historical Android Mobile App');

    // Test search filter
    $responseSearch = $this->getJson('/repository?search=Android');
    $responseSearch->assertStatus(200);
    expect($responseSearch->json('proposals'))->toHaveCount(1);

    // Test show details
    $responseShow = $this->getJson("/repository/{$repoProposal->proposal_id}");
    $responseShow->assertStatus(200);
    expect($responseShow->json('proposal.title'))->toBe('Historical Android Mobile App');
});
