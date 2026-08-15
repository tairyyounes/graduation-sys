<?php

use App\Models\Department;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\SimilarityResult;
use App\Jobs\CheckProposalSimilarity;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Create the department first to avoid FK constraint failures
    $this->department = Department::create([
        'department_name' => 'Programming',
    ]);
});

it('dispatches the CheckProposalSimilarity job and registers results', function () {
    Http::fake([
        '*/search' => Http::response([
            'query_title' => 'Smart Clinic System',
            'department' => 'Programming',
            'threshold' => 0.5,
            'total_matches' => 1,
            'results' => [
                [
                    'project_id' => '100',
                    'title' => 'Another Smart Clinic',
                    'domain' => 'Programming',
                    'similarity' => [
                        'semantic_similarity' => 0.85,
                        'functions_similarity' => 0.60,
                        'objectives_similarity' => 0.70,
                        'tags_similarity' => 0.80,
                        'technologies_similarity' => 0.90,
                        'final_similarity' => 0.78,
                    ],
                    'verdict' => 'Very High Similarity',
                    'explanation' => 'Both projects target clinic queues and appointment systems.',
                ]
            ]
        ], 200),
    ]);

    $proposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'pending',
    ]);

    // Create a second proposal in the same department so the pre-flight
    // guard finds at least one valid comparison target.
    $otherProposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'accepted',
    ]);
    ProposalVersion::create([
        'proposal_id'    => $otherProposal->proposal_id,
        'version_number' => 1,
        'title'          => 'Baseline Project',
        'problem'        => 'Existing baseline problem.',
        'solution'       => 'Existing baseline solution.',
    ]);

    $version = ProposalVersion::create([
        'proposal_id' => $proposal->proposal_id,
        'version_number' => 1,
        'title' => 'Smart Clinic System',
        'problem' => 'Manual queueing causes delays.',
        'solution' => 'Automated queue management application.',
        'functions' => 'appointments, queues',
        'objectives' => 'improve flow',
        'tags' => 'health, queue',
        'technologies_used' => 'PHP, Vue',
    ]);

    // Dispatch job synchronously
    CheckProposalSimilarity::dispatchSync($proposal, $version);

    // Assert that the similarity result is created in DB
    $result = SimilarityResult::where('proposal_version_id', $version->version_id)->first();

    expect($result)->not->toBeNull();
    expect($result->ai_status)->toBe('success');
    expect($result->verdict)->toBe('Very High Similarity');
    expect((float)$result->final_score)->toBe(0.78);
    expect((float)$result->semantic_similarity)->toBe(0.85);
});

it('gracefully handles API failures by creating a failed status sentinel row', function () {
    Http::fake([
        '*/search' => Http::response('Internal Server Error', 500),
    ]);

    $proposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'pending',
    ]);

    // Create a second proposal in the same department so the pre-flight
    // guard finds at least one valid comparison target.
    $otherProposal = Proposal::create([
        'department_id' => $this->department->department_id,
        'submission_status' => 'submitted',
        'review_status' => 'accepted',
    ]);
    ProposalVersion::create([
        'proposal_id'    => $otherProposal->proposal_id,
        'version_number' => 1,
        'title'          => 'Baseline Project',
        'problem'        => 'Existing baseline problem.',
        'solution'       => 'Existing baseline solution.',
    ]);

    $version = ProposalVersion::create([
        'proposal_id' => $proposal->proposal_id,
        'version_number' => 1,
        'title' => 'Smart Clinic System',
        'problem' => 'Manual queueing causes delays.',
        'solution' => 'Automated queue management application.',
    ]);

    try {
        CheckProposalSimilarity::dispatchSync($proposal, $version);
    } catch (\Throwable $e) {
        // Expected exception
    }

    $result = SimilarityResult::where('proposal_version_id', $version->version_id)->first();

    expect($result)->not->toBeNull();
    expect($result->ai_status)->toBe('failed');
    expect($result->similarity_score)->toBe(0.0);
});
