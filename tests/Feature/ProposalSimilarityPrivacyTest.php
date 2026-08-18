<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Student;
use App\Models\User;
use App\Models\SimilarityResult;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\{actingAs, getJson, postJson, putJson};

beforeEach(function () {
    // Create department
    $this->department = Department::create([
        'department_name' => 'Software Engineering',
    ]);

    // Create student user & student record
    $this->studentUser = User::create([
        'full_name' => 'Alice Student',
        'email' => 'alice@test.com',
        'password' => Hash::make('password'),
        'role' => 'student',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);

    $this->student = Student::create([
        'student_number' => '111111',
        'full_name' => 'Alice Student',
        'official_email' => 'alice@test.com',
        'department_id' => $this->department->department_id,
        'semester' => 8,
        'is_active' => true,
    ]);

    // Create department user
    $this->deptUser = User::create([
        'full_name' => 'Dr. Bob Reviewer',
        'email' => 'bob@test.com',
        'password' => Hash::make('password'),
        'role' => 'department_member',
        'department_id' => $this->department->department_id,
        'is_active' => true,
    ]);
});

it('anonymizes current-year confirmed proposals for students but shows full details to department members', function () {
    // 1. Create a proposal from another team that is accepted in the current year
    $otherProposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'accepted', // Confirmed/Accepted
        'created_at' => now(), // Current Year
    ]);

    $otherVersion = ProposalVersion::create([
        'proposal_id' => $otherProposal->proposal_id,
        'version_number' => 1,
        'title' => 'Secret Current Year Accepted Project',
        'problem' => 'Some secret problem statement description.',
        'solution' => 'A secret proposed solution.',
        'created_at' => now(),
    ]);

    // 2. Create a proposal from a previous year
    $pastProposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'accepted',
    ]);
    $pastProposal->created_at = now()->subYears(1);
    $pastProposal->save();

    $pastVersion = ProposalVersion::create([
        'proposal_id' => $pastProposal->proposal_id,
        'version_number' => 1,
        'title' => 'Old Public Project Title',
        'problem' => 'Old problem description.',
        'solution' => 'Old solution description.',
    ]);
    $pastVersion->created_at = now()->subYears(1);
    $pastVersion->save();

    // 3. Create the current student's proposal
    $myProposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'draft',
        'review_status' => 'pending',
    ]);
    
    $myProposal->students()->attach($this->student->student_id, [
        'member_role' => 'owner',
        'invitation_status' => 'accepted',
        'joined_at' => now(),
    ]);

    $myVersion = ProposalVersion::create([
        'proposal_id' => $myProposal->proposal_id,
        'version_number' => 1,
        'title' => 'My Draft Proposal Title',
    ]);

    // 4. Inject similarity results in DB
    SimilarityResult::create([
        'proposal_version_id' => $myVersion->version_id,
        'compared_version_id' => $otherVersion->version_id,
        'ai_status' => 'success',
        'similarity_score' => 85,
        'final_score' => 0.85,
        'problem_similarity' => 0.90,
        'verdict' => 'High Similarity',
        'explanation' => 'Both projects have high overlap.',
    ]);

    SimilarityResult::create([
        'proposal_version_id' => $myVersion->version_id,
        'compared_version_id' => $pastVersion->version_id,
        'ai_status' => 'success',
        'similarity_score' => 75,
        'final_score' => 0.75,
        'problem_similarity' => 0.80,
        'verdict' => 'Moderate Similarity',
        'explanation' => 'Some overlap with past projects.',
    ]);

    // Test as STUDENT: Should mask the current-year accepted proposal, but show past proposal normally
    actingAs($this->studentUser);
    $studentResponse = getJson("/student/proposals/{$myProposal->proposal_id}/similarity");
    
    $studentResponse->assertStatus(200);
    $studentData = $studentResponse->json();
    
    // Check results list
    $matches = $studentData['results'];
    expect($matches)->toHaveCount(2);

    // Find the current-year masked result
    $maskedResult = collect($matches)->firstWhere('details_hidden', true);
    expect($maskedResult)->not->toBeNull();
    expect($maskedResult['title'])->toBe('Hidden for Privacy');
    expect($maskedResult['domain'])->toBe('Active Confirmed Proposal');
    expect($maskedResult['id'])->toBeNull();
    expect($maskedResult['problem_similarity'])->toBeNull();
    expect($maskedResult['explanation'])->toContain('details are hidden for privacy');

    // Find the past-year normal result
    $normalResult = collect($matches)->firstWhere('details_hidden', false);
    expect($normalResult)->not->toBeNull();
    expect($normalResult['title'])->toBe('Old Public Project Title');
    expect($normalResult['id'])->toBe($pastProposal->proposal_id);
    expect($normalResult['problem_similarity'])->toBe(80);

    // Test as DEPARTMENT MEMBER: Should have full visibility for both
    actingAs($this->deptUser);
    $deptResponse = getJson("/department/proposals/{$myProposal->proposal_id}/similarity");
    
    $deptResponse->assertStatus(200);
    $deptData = $deptResponse->json();
    
    $deptMatches = $deptData['results'];
    expect($deptMatches)->toHaveCount(2);
 
    $conflictingMatch = collect($deptMatches)->firstWhere('id', $otherProposal->proposal_id);
    expect($conflictingMatch)->not->toBeNull();
    expect($conflictingMatch['title'])->toBe('Secret Current Year Accepted Project');
    expect($conflictingMatch['problem_similarity'])->toBe(90);
});
