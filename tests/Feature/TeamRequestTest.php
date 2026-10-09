<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Student;
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

    $this->makeDraft = function (Student $owner) {
        $proposal = Proposal::create([
            'department_id' => $this->department->department_id,
            'submission_status' => 'draft',
            'review_status' => 'pending',
        ]);
        ProposalVersion::create([
            'proposal_id' => $proposal->proposal_id,
            'version_number' => 1,
            'title' => "Project of {$owner->full_name}",
        ]);
        $proposal->students()->attach($owner->student_id, [
            'member_role' => 'owner',
            'invitation_status' => 'accepted',
            'joined_at' => now(),
        ]);

        return $proposal;
    };

    [$this->aliUser, $this->ali] = ($this->makeStudent)('111111');
    [$this->saraUser, $this->sara] = ($this->makeStudent)('222222');
    [$this->omarUser, $this->omar] = ($this->makeStudent)('333333');
    $this->proposal = ($this->makeDraft)($this->ali);
});

it('keeps the invited student pending until they accept', function () {
    actingAs($this->aliUser);
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();

    // Not a member yet: only the owner is on the team, and Sara cannot open the proposal.
    expect($this->proposal->students()->count())->toBe(1);
    getJson("/student/proposals/{$this->proposal->proposal_id}/team")
        ->assertJsonPath('request.status', 'pending')
        ->assertJsonCount(1, 'members');

    actingAs($this->saraUser);
    getJson("/student/proposals/{$this->proposal->proposal_id}/team")->assertForbidden();
    getJson('/student/invitations')
        ->assertJsonCount(1, 'invitations')
        ->assertJsonPath('invitations.0.from_reg_number', '111111');

    postJson("/student/invitations/{$this->proposal->proposal_id}/accept")->assertOk();

    expect($this->proposal->students()->count())->toBe(2);
    getJson("/student/proposals/{$this->proposal->proposal_id}/team")->assertJsonCount(2, 'members');
});

it('blocks a second request while one is pending, and allows it after a decline', function () {
    actingAs($this->aliUser);
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '333333'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.team.request_pending'));

    // Also blocked from another draft of the same student.
    $otherDraft = ($this->makeDraft)($this->ali);
    postJson("/student/proposals/{$otherDraft->proposal_id}/invite", ['reg_number' => '333333'])
        ->assertStatus(422);

    actingAs($this->saraUser);
    postJson("/student/invitations/{$this->proposal->proposal_id}/reject")->assertOk();

    actingAs($this->aliUser);
    getJson("/student/proposals/{$this->proposal->proposal_id}/team")->assertJsonPath('request.status', 'rejected');
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();
});

it('lets the sender cancel a pending request', function () {
    actingAs($this->aliUser);
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();
    deleteJson("/student/proposals/{$this->proposal->proposal_id}/invite")->assertOk();
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '333333'])->assertOk();
});

it('allows up to three members and blocks inviting a student who is already in a team', function () {
    [$hudaUser, $huda] = ($this->makeStudent)('444444');

    // Ali -> Sara (accepted), then Sara (as a member) -> Omar (accepted): team of 3.
    actingAs($this->aliUser);
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();
    actingAs($this->saraUser);
    postJson("/student/invitations/{$this->proposal->proposal_id}/accept")->assertOk();
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '333333'])->assertOk();
    actingAs($this->omarUser);
    postJson("/student/invitations/{$this->proposal->proposal_id}/accept")->assertOk();
    expect($this->proposal->students()->count())->toBe(3);

    // A fourth member is refused.
    actingAs($this->aliUser);
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '444444'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.team.team_full'));

    // Huda cannot invite Sara, who is already in a team.
    $hudaDraft = ($this->makeDraft)($huda);
    actingAs($hudaUser);
    postJson("/student/proposals/{$hudaDraft->proposal_id}/invite", ['reg_number' => '222222'])
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.team.invitee_paired'));
});

it('declines the other requests once a student accepts one', function () {
    $omarDraft = ($this->makeDraft)($this->omar);

    actingAs($this->aliUser);
    postJson("/student/proposals/{$this->proposal->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();
    actingAs($this->omarUser);
    postJson("/student/proposals/{$omarDraft->proposal_id}/invite", ['reg_number' => '222222'])->assertOk();

    actingAs($this->saraUser);
    postJson("/student/invitations/{$this->proposal->proposal_id}/accept")->assertOk();
    getJson('/student/invitations')->assertJsonCount(0, 'invitations');
    postJson("/student/invitations/{$omarDraft->proposal_id}/accept")->assertNotFound();

    // Omar's request was declined, so he may send a new one.
    actingAs($this->omarUser);
    getJson("/student/proposals/{$omarDraft->proposal_id}/team")->assertJsonPath('request.status', 'rejected');
});
