<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Student;
use App\Models\TeamLeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\{actingAs, deleteJson, getJson, postJson};

beforeEach(function () {
    $this->department = Department::create(['department_name' => 'Networks']);

    $this->makeStudent = function (string $number) {
        $email = "s{$number}@test.com";
        $user = User::create([
            'full_name' => "Student {$number}",
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'student',
            'department_id' => $this->department->department_id,
            'is_active' => true,
        ]);
        $student = Student::create([
            'student_number' => $number,
            'full_name' => "Student {$number}",
            'official_email' => $email,
            'department_id' => $this->department->department_id,
            'semester' => 8,
            'is_active' => true,
        ]);

        return [$user, $student];
    };

    // A team of Ahmed (owner) and Mohamed on a proposal with the given statuses.
    $this->makeTeamProposal = function (string $submission, string $review) {
        $proposal = Proposal::create([
            'department_id' => $this->department->department_id,
            'submission_status' => $submission,
            'review_status' => $review,
        ]);
        ProposalVersion::create([
            'proposal_id' => $proposal->proposal_id,
            'version_number' => 1,
            'title' => 'Team project',
        ]);
        $proposal->students()->attach($this->ahmed->student_id, [
            'member_role' => 'owner',
            'invitation_status' => 'accepted',
            'joined_at' => now()->subDay(),
        ]);
        $proposal->students()->attach($this->mohamed->student_id, [
            'member_role' => 'member',
            'invitation_status' => 'accepted',
            'joined_at' => now(),
        ]);

        return $proposal;
    };

    $this->makeStaff = function (string $role, ?int $departmentId = null) {
        return User::create([
            'full_name' => "Staff {$role}",
            'email' => uniqid($role) . '@test.com',
            'password' => Hash::make('password'),
            'role' => $role,
            'department_id' => $departmentId ?? $this->department->department_id,
            'is_active' => true,
        ]);
    };

    [$this->ahmedUser, $this->ahmed] = ($this->makeStudent)('111111');
    [$this->mohamedUser, $this->mohamed] = ($this->makeStudent)('222222');
    $this->head = ($this->makeStaff)('department_head');
});

// ---- Scenario 1: no active proposal -------------------------------------

it('lets a student leave a draft team directly without the department', function () {
    $proposal = ($this->makeTeamProposal)('draft', 'pending');

    actingAs($this->ahmedUser);
    getJson("/student/proposals/{$proposal->proposal_id}/team")->assertJsonPath('leave.mode', 'direct');

    postJson("/student/proposals/{$proposal->proposal_id}/leave")
        ->assertOk()
        ->assertJsonPath('message', __('messages.leave.left'));

    // Mohamed stays alone and now owns the draft; no request was created.
    expect($proposal->students()->pluck('students.student_id')->all())->toBe([$this->mohamed->student_id]);
    expect($proposal->students()->first()->pivot->member_role)->toBe('owner');
    expect(TeamLeaveRequest::count())->toBe(0);

    // Ahmed is free: no team and he can start a new proposal.
    expect($this->ahmed->fresh()->teamProposal())->toBeNull();
    getJson("/student/proposals/{$proposal->proposal_id}/team")->assertForbidden();
    postJson('/student/proposals', ['title' => 'A brand new graduation project idea'])->assertCreated();
});

it('blocks a direct leave when the proposal is active', function (string $submission, string $review) {
    $proposal = ($this->makeTeamProposal)($submission, $review);

    actingAs($this->ahmedUser);
    getJson("/student/proposals/{$proposal->proposal_id}/team")->assertJsonPath('leave.mode', 'request');
    postJson("/student/proposals/{$proposal->proposal_id}/leave")
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.leave.needs_request'));

    expect($proposal->students()->count())->toBe(2);
})->with([
    'submitted, pending review' => ['submitted', 'pending'],
    'submitted, revision requested' => ['submitted', 'revision_requested'],
    'accepted' => ['submitted', 'accepted'],
]);

it('does not let a student leave a team they are not in, or a team of one', function () {
    $proposal = ($this->makeTeamProposal)('draft', 'pending');
    [$otherUser] = ($this->makeStudent)('333333');

    actingAs($otherUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave")->assertForbidden();

    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave")->assertOk();

    actingAs($this->mohamedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave")
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.leave.alone'));
});

// ---- Scenario 2: active proposal ----------------------------------------

it('requires a reason and only allows a leave request on an active proposal', function () {
    $draft = ($this->makeTeamProposal)('draft', 'pending');
    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$draft->proposal_id}/leave-request", ['reason' => 'x'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.leave.use_direct'));

    $draft->update(['submission_status' => 'submitted']);
    postJson("/student/proposals/{$draft->proposal_id}/leave-request", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');
    postJson("/student/proposals/{$draft->proposal_id}/leave-request", ['reason' => '   '])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reason');

    expect(TeamLeaveRequest::count())->toBe(0);
});

it('sends a leave request that teammates can see, once per student', function () {
    $proposal = ($this->makeTeamProposal)('submitted', 'pending');

    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Moving to another city'])
        ->assertCreated();
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Again'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.leave.already_requested'));

    expect(TeamLeaveRequest::where('status', 'pending')->count())->toBe(1);
    // Still on the team while the department decides.
    expect($proposal->students()->count())->toBe(2);

    actingAs($this->mohamedUser);
    getJson("/student/proposals/{$proposal->proposal_id}/team")
        ->assertJsonCount(1, 'leave.pending')
        ->assertJsonPath('leave.pending.0.regNumber', '111111')
        ->assertJsonPath('leave.pending.0.reason', 'Moving to another city')
        ->assertJsonPath('leave.pending.0.mine', false);
    getJson('/student/proposals')->assertJsonPath('active.has_pending_leave', true);
});

it('lets the student withdraw a pending leave request', function () {
    $proposal = ($this->makeTeamProposal)('submitted', 'pending');

    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Changed my mind later'])->assertCreated();
    deleteJson("/student/proposals/{$proposal->proposal_id}/leave-request")->assertOk();
    deleteJson("/student/proposals/{$proposal->proposal_id}/leave-request")->assertNotFound();

    expect(TeamLeaveRequest::count())->toBe(0);
});

it('shows leave requests only to the department that owns the proposal and only lets staff decide', function () {
    $proposal = ($this->makeTeamProposal)('submitted', 'pending');
    $leave = TeamLeaveRequest::create([
        'proposal_id' => $proposal->proposal_id,
        'student_id' => $this->ahmed->student_id,
        'reason' => 'Personal reasons',
    ]);

    actingAs($this->head);
    getJson('/department/leave-requests')
        ->assertOk()
        ->assertJsonCount(1, 'requests')
        ->assertJsonPath('requests.0.reason', 'Personal reasons')
        ->assertJsonPath('requests.0.status', 'pending')
        ->assertJsonCount(2, 'requests.0.team');

    $otherDepartment = Department::create(['department_name' => 'Software']);
    $otherHead = ($this->makeStaff)('department_head', $otherDepartment->department_id);
    actingAs($otherHead);
    getJson('/department/leave-requests')->assertJsonCount(0, 'requests');
    postJson("/department/leave-requests/{$leave->id}/decide", ['decision' => 'approve'])->assertForbidden();

    // Students cannot decide their own request.
    actingAs($this->ahmedUser);
    postJson("/department/leave-requests/{$leave->id}/decide", ['decision' => 'approve'])->assertForbidden();

    expect($leave->fresh()->status)->toBe('pending');
});

it('keeps the team and proposal as they are when the department rejects', function () {
    $proposal = ($this->makeTeamProposal)('submitted', 'accepted');
    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Not motivated'])->assertCreated();
    $leave = TeamLeaveRequest::first();

    actingAs($this->head);
    postJson("/department/leave-requests/{$leave->id}/decide", ['decision' => 'reject'])
        ->assertOk()
        ->assertJsonPath('message', __('messages.leave.rejected'));
    postJson("/department/leave-requests/{$leave->id}/decide", ['decision' => 'approve'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.leave.already_decided'));

    $leave->refresh();
    expect($leave->status)->toBe('rejected')
        ->and($leave->decided_by)->toBe($this->head->id)
        ->and($leave->decided_at)->not->toBeNull();

    $proposal->refresh();
    expect($proposal->submission_status)->toBe('submitted')
        ->and($proposal->review_status)->toBe('accepted')
        ->and($proposal->students()->count())->toBe(2);

    // A new request is possible after the rejection.
    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Trying again'])->assertCreated();
});

it('cancels the proposal for the whole team when the department approves', function () {
    $proposal = ($this->makeTeamProposal)('submitted', 'accepted');
    actingAs($this->ahmedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Health reasons'])->assertCreated();
    actingAs($this->mohamedUser);
    postJson("/student/proposals/{$proposal->proposal_id}/leave-request", ['reason' => 'Me too'])->assertCreated();
    $leave = TeamLeaveRequest::where('student_id', $this->ahmed->student_id)->first();

    actingAs($this->head);
    postJson("/department/leave-requests/{$leave->id}/decide", ['decision' => 'approve'])
        ->assertOk()
        ->assertJsonPath('message', __('messages.leave.approved'));

    $proposal->refresh();
    expect($proposal->submission_status)->toBe('archived')
        ->and($proposal->review_status)->toBe('rejected')
        ->and((bool) $proposal->is_locked)->toBeTrue()
        ->and($proposal->isCancelledByLeave())->toBeTrue();
    expect(TeamLeaveRequest::where('status', 'pending')->count())->toBe(0);

    // Neither member is in a team or has an active proposal any more...
    foreach ([[$this->ahmedUser, $this->ahmed], [$this->mohamedUser, $this->mohamed]] as [$user, $student]) {
        expect($student->fresh()->teamProposal())->toBeNull();

        actingAs($user);
        getJson('/student/proposals')
            ->assertJsonPath('active', null)
            ->assertJsonPath('archived.0.cancelled_by_leave', true);

        // ...the cancelled proposal cannot be brought back...
        $this->putJson("/student/proposals/{$proposal->proposal_id}/restore")->assertStatus(422);
    }

    // ...and each of them must (and can) start a new proposal.
    foreach ([$this->ahmedUser, $this->mohamedUser] as $user) {
        actingAs($user);
        postJson('/student/proposals', ['title' => 'A brand new graduation project idea'])->assertCreated();
    }
});

it('does not let a regular department member decide leave requests', function () {
    $proposal = ($this->makeTeamProposal)('submitted', 'pending');
    $leave = TeamLeaveRequest::create([
        'proposal_id' => $proposal->proposal_id,
        'student_id' => $this->ahmed->student_id,
        'reason' => 'Reason',
    ]);
    $member = ($this->makeStaff)('department_member');

    actingAs($member);
    postJson("/department/leave-requests/{$leave->id}/decide", ['decision' => 'approve'])->assertForbidden();
    expect($leave->fresh()->status)->toBe('pending');
});
