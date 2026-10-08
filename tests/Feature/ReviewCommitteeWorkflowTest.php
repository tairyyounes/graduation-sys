<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\ReviewCommittee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\{actingAs, getJson, postJson};

beforeEach(function () {
    // Create department
    $this->department = Department::create([
        'department_name' => 'Computer Science',
    ]);

    // Create student & user
    $this->studentUser = User::create([
        'full_name' => 'Student User',
        'email' => 'student.committee@test.com',
        'password' => Hash::make('password'),
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $this->student = Student::create([
        'student_number' => '223344',
        'full_name' => 'Student User',
        'official_email' => 'student.committee@test.com',
        'department_id' => $this->department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);

    // Create a submitted proposal
    $this->proposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'pending',
    ]);

    $this->proposal->students()->attach($this->student->student_id, [
        'member_role' => 'owner',
        'invitation_status' => 'accepted',
        'joined_at' => now(),
    ]);

    $this->version = ProposalVersion::create([
        'proposal_id' => $this->proposal->proposal_id,
        'version_number' => 1,
        'title' => 'AI Automated Graduation Review System',
        'problem' => 'Manual review of graduation project proposals causes delays and inconsistencies across academic departments.',
        'solution' => 'Build an AI-powered portal that enforces multi-member committee reviews and semantic similarity checks.',
        'objectives' => 'Streamline proposal submission, enhance originality check, and enforce unanimous review committee approval.',
        'functions' => 'Proposal submission, team invitations, AI similarity check, and committee consensus tracking.',
        'tags' => 'AI, Laravel, Vue, Education',
        'technologies_used' => 'PHP, Laravel, Vue.js, Tailwind, SQLite',
    ]);

    // Create 3 committee members
    $this->member1 = User::create([
        'full_name' => 'Dr. Member One',
        'email' => 'member1@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $this->member2 = User::create([
        'full_name' => 'Dr. Member Two',
        'email' => 'member2@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $this->member3 = User::create([
        'full_name' => 'Dr. Member Three',
        'email' => 'member3@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    // Create a faculty member who is NOT in the committee
    $this->nonCommitteeMember = User::create([
        'full_name' => 'Dr. Outside Committee',
        'email' => 'outsider@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    // Create Review Committee and assign members 1, 2, and 3
    $this->committee = ReviewCommittee::create([
        'name' => 'Department Review Committee 2026',
        'department_id' => $this->department->department_id,
    ]);
    $this->committee->users()->attach([
        $this->member1->id,
        $this->member2->id,
        $this->member3->id,
    ]);
});

it('allows only review committee members to view proposals in queue', function () {
    // Non-committee member tries to get queue
    actingAs($this->nonCommitteeMember);
    $responseOutsider = getJson('/department/proposals?status=submitted');
    $responseOutsider->assertStatus(200);
    expect($responseOutsider->json('is_committee_member'))->toBeFalse();
    expect($responseOutsider->json('proposals'))->toHaveCount(0);

    // Committee member gets queue
    actingAs($this->member1);
    $responseMember = getJson('/department/proposals?status=submitted');
    $responseMember->assertStatus(200);
    expect($responseMember->json('is_committee_member'))->toBeTrue();
    expect($responseMember->json('proposals'))->toHaveCount(1);
    expect($responseMember->json('proposals.0.id'))->toBe($this->proposal->proposal_id);
});

it('blocks non-committee members from accessing proposal details and reviewing', function () {
    actingAs($this->nonCommitteeMember);

    // Show details
    $responseShow = getJson("/department/proposals/{$this->proposal->proposal_id}");
    $responseShow->assertStatus(403);
    expect($responseShow->json('message'))->toContain('Only review committee members');

    // Review proposal
    $responseReview = postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'accepted',
        'note' => 'Unauthorized review attempt',
    ]);
    $responseReview->assertStatus(403);
});

it('requires ALL review committee members to approve before proposal status becomes accepted', function () {
    // Initial status is pending
    expect($this->proposal->fresh()->review_status)->toBe('pending');
    expect($this->proposal->fresh()->is_locked)->toBeFalsy();

    // 1st member approves
    actingAs($this->member1);
    $res1 = postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'accepted',
        'note' => 'Approved by Dr. Member One',
    ]);
    $res1->assertStatus(200);
    expect($res1->json('all_approved'))->toBeFalse();
    expect($this->proposal->fresh()->review_status)->toBe('pending');
    expect($this->proposal->fresh()->is_locked)->toBeFalsy();

    // 2nd member approves
    actingAs($this->member2);
    $res2 = postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'accepted',
        'note' => 'Approved by Dr. Member Two',
    ]);
    $res2->assertStatus(200);
    expect($res2->json('all_approved'))->toBeFalse();
    expect($this->proposal->fresh()->review_status)->toBe('pending');
    expect($this->proposal->fresh()->is_locked)->toBeFalsy();

    // 3rd member (final committee member) approves
    actingAs($this->member3);
    $res3 = postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'accepted',
        'note' => 'Approved by Dr. Member Three',
    ]);
    $res3->assertStatus(200);
    expect($res3->json('all_approved'))->toBeTrue();
    
    // Now the proposal is accepted and locked!
    $freshProposal = $this->proposal->fresh();
    expect($freshProposal->review_status)->toBe('accepted');
    expect((bool) $freshProposal->is_locked)->toBeTrue();
});

it('returns full committee review status and individual decisions in show endpoint', function () {
    // Dr. 1 approves
    actingAs($this->member1);
    postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'accepted',
        'note' => 'Looks great!',
    ]);

    // Check show endpoint as Dr. 2
    actingAs($this->member2);
    $response = getJson("/department/proposals/{$this->proposal->proposal_id}");
    $response->assertStatus(200);

    $committeeReview = $response->json('committee_review');
    expect($committeeReview)->not->toBeNull();
    expect($committeeReview['total_members'])->toBe(3);
    expect($committeeReview['approvals_count'])->toBe(1);
    expect($committeeReview['all_approved'])->toBeFalse();
    expect($committeeReview['members'])->toHaveCount(3);

    // Dr. 1's decision is accepted
    $member1Status = collect($committeeReview['members'])->firstWhere('id', $this->member1->id);
    expect($member1Status['decision'])->toBe('accepted');
    expect($member1Status['decision_note'])->toBe('Looks great!');

    // Dr. 2's decision is pending
    $member2Status = collect($committeeReview['members'])->firstWhere('id', $this->member2->id);
    expect($member2Status['decision'])->toBe('pending');
});

it('handles revision requested by a committee member', function () {
    actingAs($this->member1);
    $res = postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'revision_requested',
        'note' => 'Please expand on the problem statement.',
    ]);
    $res->assertStatus(200);
    expect($this->proposal->fresh()->review_status)->toBe('revision_requested');
});

it('handles rejection by a committee member', function () {
    actingAs($this->member2);
    $res = postJson("/department/proposals/{$this->proposal->proposal_id}/review", [
        'decision' => 'rejected',
        'note' => 'Topic is out of department scope.',
    ]);
    $res->assertStatus(200);
    expect($this->proposal->fresh()->review_status)->toBe('rejected');
});
