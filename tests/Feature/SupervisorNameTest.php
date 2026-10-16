<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\{actingAs, post, putJson};

function words(int $count, string $word = 'word'): string
{
    return implode(' ', array_fill(0, $count, $word));
}

function completeProposalFields(): array
{
    return [
        'title'      => 'Smart Library Management System Using NFC',
        'problem'    => words(35, 'problem'),
        'solution'   => words(35, 'solution'),
        'functions'  => words(25, 'function'),
        'objectives' => words(25, 'objective'),
        'tags'       => 'AI, NFC, Library',
        'tech'       => 'Laravel, Vue',
    ];
}

beforeEach(function () {
    Queue::fake();

    $this->department = Department::create(['department_name' => 'Software Engineering']);

    $this->user = User::create([
        'full_name' => 'Supervised Student',
        'email' => 'supervised@test.com',
        'password' => Hash::make('password'),
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $this->student = Student::create([
        'student_number' => '654321',
        'full_name' => 'Supervised Student',
        'official_email' => 'supervised@test.com',
        'department_id' => $this->department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);
});

it('saves a draft without a supervisor name', function () {
    actingAs($this->user);

    $response = post('/student/proposals', ['title' => 'My New Graduation Project Title'], ['Accept' => 'application/json']);

    $response->assertCreated();
    expect($response->json('proposal.supervisor_name'))->toBe('');
});

it('stores the supervisor name with a draft', function () {
    actingAs($this->user);

    $response = post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_name' => 'د. أحمد محمد',
    ], ['Accept' => 'application/json']);

    $response->assertCreated();
    expect(Proposal::find($response->json('proposal.id'))->supervisor_name)->toBe('د. أحمد محمد');
});

it('requires only the supervisor name to submit', function () {
    actingAs($this->user);

    $id = post('/student/proposals', completeProposalFields(), ['Accept' => 'application/json'])->json('proposal.id');

    $response = putJson("/student/proposals/{$id}/submit");
    $response->assertStatus(422)->assertJsonValidationErrors(['supervisor_name']);
    expect($response->json('errors'))->not->toHaveKey('supervisor_approval');
    expect(Proposal::find($id)->submission_status)->toBe('draft');

    post("/student/proposals/{$id}", completeProposalFields() + [
        '_method' => 'PUT',
        'supervisor_name' => 'Dr. Supervisor',
    ], ['Accept' => 'application/json'])->assertOk();

    // Only the supervisor name changed, so no edit was consumed.
    expect(ProposalVersion::where('proposal_id', $id)->count())->toBe(1);

    putJson("/student/proposals/{$id}/submit")->assertOk();
    expect(Proposal::find($id)->submission_status)->toBe('submitted');
});

it('keeps the supervisor name required while revising a submitted proposal', function () {
    actingAs($this->user);

    $id = post('/student/proposals', completeProposalFields() + [
        'supervisor_name' => 'Dr. Supervisor',
    ], ['Accept' => 'application/json'])->json('proposal.id');
    putJson("/student/proposals/{$id}/submit")->assertOk();
    Proposal::find($id)->update(['review_status' => 'revision_requested']);

    post("/student/proposals/{$id}", completeProposalFields() + [
        '_method' => 'PUT',
        'supervisor_name' => '',
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['supervisor_name']);
});

it('no longer exposes the supervisor approval file endpoints', function () {
    actingAs($this->user);

    $id = post('/student/proposals', ['title' => 'My New Graduation Project Title'], ['Accept' => 'application/json'])->json('proposal.id');

    $this->get('/student/supervisor-approval-template')->assertNotFound();
    $this->get("/student/proposals/{$id}/supervisor-approval")->assertNotFound();
});
