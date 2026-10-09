<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\ReviewCommittee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{actingAs, get, getJson, post, putJson};

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
    Storage::fake('local');
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

it('saves a draft without supervisor details', function () {
    actingAs($this->user);

    $response = post('/student/proposals', ['title' => 'My New Graduation Project Title'], ['Accept' => 'application/json']);

    $response->assertCreated();
    expect($response->json('proposal.supervisor_name'))->toBe('');
    expect($response->json('proposal.supervisor_approval'))->toBeNull();
});

it('stores the supervisor name and approval document with a draft', function () {
    actingAs($this->user);

    $response = post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_name' => 'د. أحمد محمد',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 200, 'application/pdf'),
    ], ['Accept' => 'application/json']);

    $response->assertCreated();
    $proposal = Proposal::find($response->json('proposal.id'));

    expect($proposal->supervisor_name)->toBe('د. أحمد محمد');
    expect($proposal->supervisor_approval_original_name)->toBe('approval.pdf');
    Storage::disk('local')->assertExists($proposal->supervisor_approval_path);
    expect($proposal->supervisor_approval_path)->toStartWith("supervisor-approvals/{$proposal->proposal_id}/");
    expect($response->json('proposal.supervisor_approval.name'))->toBe('approval.pdf');
});

it('rejects approval documents of the wrong type or over 5 MB', function () {
    actingAs($this->user);

    post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->create('approval.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['supervisor_approval']);

    post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 5121, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['supervisor_approval']);

    // A script renamed to .pdf is rejected by content sniffing.
    $path = tempnam(sys_get_temp_dir(), 'evil');
    file_put_contents($path, '<?php echo "pwned";');
    post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => new UploadedFile($path, 'evil.pdf', null, null, true),
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['supervisor_approval']);

    expect(Proposal::count())->toBe(0);
});

it('accepts image approval documents', function () {
    actingAs($this->user);

    post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->image('signed.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated();

    post('/student/proposals', [
        'title' => 'Another New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->image('signed.png'),
    ], ['Accept' => 'application/json'])->assertCreated();
});

it('requires supervisor name and approval to submit, then allows submission', function () {
    actingAs($this->user);

    $id = post('/student/proposals', completeProposalFields(), ['Accept' => 'application/json'])->json('proposal.id');

    $response = putJson("/student/proposals/{$id}/submit");
    $response->assertStatus(422)->assertJsonValidationErrors(['supervisor_name', 'supervisor_approval']);
    expect(Proposal::find($id)->submission_status)->toBe('draft');

    // Add the supervisor details to the draft (multipart update via method spoofing).
    post("/student/proposals/{$id}", completeProposalFields() + [
        '_method' => 'PUT',
        'supervisor_name' => 'Dr. Supervisor',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertOk();

    // Only supervisor details changed, so no edit was consumed.
    expect(ProposalVersion::where('proposal_id', $id)->count())->toBe(1);

    putJson("/student/proposals/{$id}/submit")->assertOk();
    expect(Proposal::find($id)->submission_status)->toBe('submitted');
});

it('replaces and removes the approval document on a draft', function () {
    actingAs($this->user);

    $id = post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->create('first.pdf', 50, 'application/pdf'),
    ], ['Accept' => 'application/json'])->json('proposal.id');
    $firstPath = Proposal::find($id)->supervisor_approval_path;

    post("/student/proposals/{$id}", [
        '_method' => 'PUT',
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->image('second.png'),
    ], ['Accept' => 'application/json'])->assertOk();

    $proposal = Proposal::find($id);
    expect($proposal->supervisor_approval_original_name)->toBe('second.png');
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($proposal->supervisor_approval_path);

    post("/student/proposals/{$id}", [
        '_method' => 'PUT',
        'title' => 'My New Graduation Project Title',
        'remove_supervisor_approval' => '1',
    ], ['Accept' => 'application/json'])->assertOk();

    expect(Proposal::find($id)->supervisor_approval_path)->toBeNull();
    Storage::disk('local')->assertMissing($proposal->supervisor_approval_path);
});

it('keeps the approval required while revising a submitted proposal', function () {
    actingAs($this->user);

    $id = post('/student/proposals', completeProposalFields() + [
        'supervisor_name' => 'Dr. Supervisor',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json'])->json('proposal.id');
    putJson("/student/proposals/{$id}/submit")->assertOk();
    Proposal::find($id)->update(['review_status' => 'revision_requested']);

    // Removing it without a replacement is refused.
    post("/student/proposals/{$id}", completeProposalFields() + [
        '_method' => 'PUT',
        'supervisor_name' => 'Dr. Supervisor',
        'remove_supervisor_approval' => '1',
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['supervisor_approval']);

    // Revising content while keeping the stored document works.
    $fields = completeProposalFields();
    $fields['title'] = 'Smart Library Management System Using RFID';
    post("/student/proposals/{$id}", $fields + [
        '_method' => 'PUT',
        'supervisor_name' => 'Dr. Supervisor',
    ], ['Accept' => 'application/json'])->assertOk();

    expect(ProposalVersion::where('proposal_id', $id)->count())->toBe(2);
    expect(Proposal::find($id)->hasSupervisorApproval())->toBeTrue();
});

it('lets team members preview and download the approval but not other students', function () {
    actingAs($this->user);

    $id = post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 20, 'application/pdf'),
    ], ['Accept' => 'application/json'])->json('proposal.id');

    $preview = get("/student/proposals/{$id}/supervisor-approval");
    $preview->assertOk();
    expect($preview->headers->get('content-disposition'))->toStartWith('inline');

    $download = get("/student/proposals/{$id}/supervisor-approval?download=1");
    $download->assertOk();
    expect($download->headers->get('content-disposition'))->toStartWith('attachment');

    $other = User::create([
        'full_name' => 'Other Student',
        'email' => 'other@test.com',
        'password' => Hash::make('password'),
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);
    Student::create([
        'student_number' => '111222',
        'full_name' => 'Other Student',
        'official_email' => 'other@test.com',
        'department_id' => $this->department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);

    actingAs($other);
    getJson("/student/proposals/{$id}/supervisor-approval")->assertForbidden();
});

it('lets review committee members view the approval, but not outsiders', function () {
    actingAs($this->user);
    $id = post('/student/proposals', completeProposalFields() + [
        'supervisor_name' => 'Dr. Supervisor',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 20, 'application/pdf'),
    ], ['Accept' => 'application/json'])->json('proposal.id');
    putJson("/student/proposals/{$id}/submit")->assertOk();

    $reviewer = User::create([
        'full_name' => 'Dr. Reviewer',
        'email' => 'reviewer@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);
    $outsider = User::create([
        'full_name' => 'Dr. Outsider',
        'email' => 'outsider.sup@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);
    $committee = ReviewCommittee::create([
        'name' => 'Committee',
        'department_id' => $this->department->department_id,
    ]);
    $committee->users()->attach([$reviewer->id]);

    actingAs($reviewer);
    $show = getJson("/department/proposals/{$id}");
    $show->assertOk();
    expect($show->json('proposal.supervisor_name'))->toBe('Dr. Supervisor');
    expect($show->json('proposal.supervisor_approval.download_url'))->toContain("/department/proposals/{$id}/supervisor-approval");
    get("/department/proposals/{$id}/supervisor-approval?download=1")->assertOk();

    actingAs($outsider);
    getJson("/department/proposals/{$id}/supervisor-approval")->assertForbidden();

    // Students cannot use the department endpoint.
    actingAs($this->user);
    get("/department/proposals/{$id}/supervisor-approval")->assertForbidden();
});

it('serves the blank supervisor approval template as a PDF', function () {
    actingAs($this->user);

    $response = get('/student/supervisor-approval-template');
    $response->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

it('deletes the stored approval document when the proposal is deleted', function () {
    actingAs($this->user);

    $id = post('/student/proposals', [
        'title' => 'My New Graduation Project Title',
        'supervisor_approval' => UploadedFile::fake()->create('approval.pdf', 20, 'application/pdf'),
    ], ['Accept' => 'application/json'])->json('proposal.id');
    $path = Proposal::find($id)->supervisor_approval_path;

    $this->deleteJson("/student/proposals/{$id}")->assertOk();
    Storage::disk('local')->assertMissing($path);
});
