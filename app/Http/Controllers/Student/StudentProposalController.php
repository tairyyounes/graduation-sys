<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\CheckProposalSimilarity;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Decision;
use App\Models\SimilarityResult;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;
        
        $proposals = $student->proposals()->with(['latestVersion', 'department'])->get();

        $drafts = $proposals->where('submission_status', 'draft')->map(fn($p) => $this->transformProposal($p))->values();
        $active = $proposals->where('submission_status', 'submitted')->map(fn($p) => $this->transformProposal($p))->first();
        $archived = $proposals->where('submission_status', 'archived')->map(fn($p) => $this->transformProposal($p))->values();

        return response()->json([
            'drafts' => $drafts,
            'active' => $active,
            'archived' => $archived,
        ]);
    }

    public function store(StoreProposalRequest $request): JsonResponse
    {
        $student = $request->user()->student;

        return DB::transaction(function () use ($request, $student) {
            $proposal = Proposal::create([
                'department_id' => $student->department_id,
                'submission_status' => 'draft',
                'review_status' => 'pending',
            ]);

            ProposalVersion::create([
                'proposal_id' => $proposal->proposal_id,
                'version_number' => 1,
                'title' => $request->title,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'functions' => $request->functions,
                'objectives' => $request->objectives,
                'tags' => $request->tags,
                'technologies_used' => $request->tech,
            ]);

            $proposal->students()->attach($student->student_id, [
                'member_role' => 'owner',
                'invitation_status' => 'accepted',
                'joined_at' => now(),
            ]);

            activity()
                ->performedOn($proposal)
                ->causedBy($request->user())
                ->log('draft created');

            return response()->json([
                'message' => 'Draft created successfully.',
                'proposal' => $this->transformProposal($proposal->load('latestVersion')),
            ], 201);
        });
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal): JsonResponse
    {
        // Security: Student can only access their own proposals
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Security: Cannot edit accepted or locked proposals
        if ($proposal->is_locked || $proposal->review_status === 'accepted') {
            return response()->json(['message' => 'Proposal is locked or already accepted.'], 403);
        }
        return DB::transaction(function () use ($request, $proposal) {
            $latestVersion = $proposal->latestVersion;
            
            // Create new version
            $newVersion = ProposalVersion::create([
                'proposal_id' => $proposal->proposal_id,
                'version_number' => $latestVersion->version_number + 1,
                'title' => $request->title,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'functions' => $request->functions,
                'objectives' => $request->objectives,
                'tags' => $request->tags,
                'technologies_used' => $request->tech,
            ]);

            activity()
                ->performedOn($proposal)
                ->causedBy($request->user())
                ->log('proposal edited');

            activity()
                ->performedOn($newVersion)
                ->causedBy($request->user())
                ->log('version created');

            return response()->json([
                'message' => 'Proposal updated (new version created).',
                'proposal' => $this->transformProposal($proposal->load('latestVersion')),
            ]);
        });
    }

    public function submit(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Security: Cannot submit more than one active proposal
        $hasActive = $student->proposals()->where('submission_status', 'submitted')->exists();
        if ($hasActive && $proposal->submission_status !== 'submitted') {
            return response()->json(['message' => 'You already have an active submitted proposal.'], 422);
        }

        // Strict validation of the latest version of the proposal before allowing final submission
        $latestVersion = $proposal->latestVersion;
        if (!$latestVersion) {
            return response()->json(['message' => 'No proposal content found.'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($latestVersion->toArray() + [
            'tech' => $latestVersion->technologies_used,
        ], [
            'title' => [
                'required',
                'string',
                $this->validateWordCountHelper(5, 20, 'proposal title', 'The proposal title must be clear and contain at least 5 words.'),
                'regex:/^(?![\W_]+$).+$/',
            ],
            'problem' => [
                'required',
                'string',
                $this->validateWordCountHelper(30, 250, 'problem statement', 'The problem statement must contain at least 30 words and clearly explain the issue.'),
            ],
            'solution' => [
                'required',
                'string',
                $this->validateWordCountHelper(30, 250, 'proposed solution', 'The proposed solution must contain at least 30 words and clearly explain how the system solves the problem.'),
            ],
            'functions' => [
                'required',
                'string',
                $this->validateWordCountHelper(20, 200, 'system functions', 'Please describe the main system functions in at least 20 words.'),
            ],
            'objectives' => [
                'required',
                'string',
                $this->validateWordCountHelper(20, 200, 'project objectives', 'Please write at least 20 words explaining the project objectives.'),
            ],
            'tags' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $items = array_filter(array_map('trim', explode(',', $value)));
                    $count = count($items);
                    if ($count < 3) $fail('Please add at least 3 relevant tags.');
                    if ($count > 10) $fail('The tags cannot exceed 10 items.');
                }
            ],
            'tech' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $items = array_filter(array_map('trim', explode(',', $value)));
                    $count = count($items);
                    if ($count < 2) $fail('Please add at least 2 technologies that will be used in the project.');
                    if ($count > 12) $fail('The technologies cannot exceed 12 items.');
                }
            ],
        ], [
            'title.required' => 'The proposal title is required.',
            'title.string' => 'The proposal title must be a string.',
            'title.regex' => 'The proposal title must not consist only of symbols.',
            'problem.required' => 'The problem statement is required.',
            'problem.string' => 'The problem statement must be a string.',
            'solution.required' => 'The proposed solution is required.',
            'solution.string' => 'The proposed solution must be a string.',
            'functions.required' => 'Please describe the main system functions in at least 20 words.',
            'functions.string' => 'The system functions must be a string.',
            'objectives.required' => 'Please write at least 20 words explaining the project objectives.',
            'objectives.string' => 'The project objectives must be a string.',
            'tags.required' => 'Please add at least 3 relevant tags.',
            'tags.string' => 'The tags must be a string.',
            'tech.required' => 'Please add at least 2 technologies that will be used in the project.',
            'tech.string' => 'The technologies must be a string.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $proposal->update([
            'submission_status' => 'submitted',
            'review_status' => 'pending',
        ]);

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('proposal submitted');

        // Dispatch AI similarity check — runs synchronously if QUEUE_CONNECTION=sync,
        // or in the background when using a real queue driver. This is a secondary
        // step: if the AI service is down it must not fail the submission itself.
        $latestVersion = $proposal->latestVersion;
        if ($latestVersion) {
            try {
                CheckProposalSimilarity::dispatch($proposal->load('department'), $latestVersion);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Similarity check on submit failed: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Proposal submitted for review.']);
    }

    public function archive(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $proposal->update(['submission_status' => 'archived']);

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('draft archived');

        return response()->json(['message' => 'Proposal archived.']);
    }

    public function restore(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Only archived proposals can be restored
        if ($proposal->submission_status !== 'archived') {
            return response()->json(['message' => 'Only archived proposals can be restored.'], 422);
        }

        // Prevent restore if the student already has an active submitted proposal
        $hasActive = $student->proposals()->where('submission_status', 'submitted')->exists();
        if ($hasActive) {
            return response()->json(['message' => 'You already have an active submitted proposal. Archive or withdraw it before restoring another.'], 422);
        }

        $proposal->update([
            'submission_status' => 'draft',
            'review_status'     => 'pending',
        ]);

        activity()
            ->performedOn($proposal)
            ->causedBy($request->user())
            ->log('proposal restored to draft');

        return response()->json(['message' => 'Proposal restored to draft.']);
    }

    public function destroy(Request $request, Proposal $proposal): JsonResponse
    {
        $student = $request->user()->student;
        if (!$proposal->students()->where('project_members.student_id', $student->student_id)->exists()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Security: Student cannot delete submitted proposals
        if ($proposal->submission_status === 'submitted') {
            return response()->json(['message' => 'Cannot delete a submitted proposal.'], 403);
        }

        $proposal->delete(); // This will cascade delete versions and members if foreign keys are set to cascade

        return response()->json(['message' => 'Proposal deleted successfully.']);
    }

    public function versions(Proposal $proposal): JsonResponse
    {
        $versions = $proposal->versions()->orderBy('version_number', 'desc')->get();
        return response()->json(['versions' => $versions]);
    }

    public function decision(Proposal $proposal): JsonResponse
    {
        $decision = $proposal->decisions()->with('reviewer')->latest()->first();

        if (!$decision) {
            return response()->json(['message' => 'Waiting for department review.'], 200);
        }

        return response()->json([
            'decision' => [
                'type' => $decision->decision_type,
                'note' => $decision->decision_note,
                'reviewer' => $decision->reviewer->full_name,
                'date' => $decision->decision_date,
            ]
        ]);
    }

    public function similarity(Request $request, Proposal $proposal): JsonResponse
    {
        $latestVersion = $proposal->latestVersion;

        if (!$latestVersion) {
            return response()->json([
                'ai_status' => 'none',
                'results'   => [],
                'message'   => 'No versions found.',
            ]);
        }

        $versionId = $latestVersion->version_id;

        // ── Determine overall AI status from stored results ────────────────
        $allResults = SimilarityResult::where('proposal_version_id', $versionId)
            ->orderByDesc('final_score')
            ->get();
 
        $statuses  = $allResults->pluck('ai_status')->unique()->values();

        // A 'pending' row means a check is in progress. If that check gets
        // interrupted — server restart, dropped connection, browser
        // navigated away mid-request — before it reaches success/failed,
        // the row is left stuck at 'pending' forever: nothing below
        // previously re-triggered for that status, and the frontend showed
        // an infinite "analysis running" spinner with no way out. Treat a
        // pending row older than this as stale so it can self-heal into a
        // retriable 'failed' state instead of hanging indefinitely.
        $staleCutoff = now()->subMinutes(3);
        $hasStalePending = $allResults->contains(
            fn($r) => $r->ai_status === 'pending' && $r->updated_at && $r->updated_at->lt($staleCutoff)
        );

        $aiStatus  = $hasStalePending ? 'failed'
                   : ($statuses->contains('failed') ? 'failed'
                   : ($statuses->contains('pending') ? 'pending'
                   : ($statuses->contains('no_comparisons') ? 'no_comparisons'
                   : ($allResults->isEmpty() ? 'none' : 'success'))));

        // If forced recheck OR if it failed OR if it was never checked:
        // Do NOT re-dispatch for 'no_comparisons' — there is nothing to compare against.
        $forceRecheck = $request->query('recheck') === 'true';
        if ($forceRecheck || $aiStatus === 'failed' || $aiStatus === 'none') {
            try {
                CheckProposalSimilarity::dispatch($proposal->load('department'), $latestVersion);
            } catch (\Throwable $e) {
                // On a sync queue a failing AI call would bubble up as a 500 and
                // leave the page blank. Swallow it so the endpoint still returns a
                // graceful status the UI can render (e.g. "analysis unavailable").
                \Illuminate\Support\Facades\Log::warning('Similarity dispatch failed: ' . $e->getMessage());
            }

            // Reload results from the database (in case of sync execution)
            $allResults = SimilarityResult::where('proposal_version_id', $versionId)
                ->orderByDesc('final_score')
                ->get();

            $statuses  = $allResults->pluck('ai_status')->unique()->values();
            $aiStatus  = $statuses->contains('failed') ? 'failed'
                       : ($statuses->contains('pending') ? 'pending'
                       : ($statuses->contains('no_comparisons') ? 'no_comparisons'
                       : ($allResults->isEmpty() ? 'none' : 'success')));
        }

        // Early return — no proposals existed to compare against
        if ($aiStatus === 'no_comparisons') {
            return response()->json([
                'ai_status' => 'no_comparisons',
                'summary'   => null,
                'results'   => [],
                'message'   => 'No previous proposals available for comparison.',
            ]);
        }

        // If no DB records exist at all (e.g. async queue hasn't run it yet)
        if ($allResults->isEmpty()) {
            return response()->json([
                'ai_status' => 'pending',
                'results'   => [],
                'message'   => 'Similarity analysis is running.',
            ]);
        }

        // ── Build the best-match summary for the top card ──────────────────
        $topResult = $allResults->where('ai_status', 'success')->first();

        $summary = null;
        if ($topResult) {
            $finalPct = $topResult->final_score !== null
                ? round($topResult->final_score * 100, 1)
                : ($topResult->similarity_score ?? 0);

            $comparedProposal = optional($topResult->comparedVersion)->proposal;
            $isCurrentYearConfirmed = false;
            if ($comparedProposal && $comparedProposal->proposal_id !== $proposal->proposal_id) {
                $isCurrentYear = optional($comparedProposal->created_at)->year === now()->year;
                $isAccepted = $comparedProposal->review_status === 'accepted';
                $isCurrentYearConfirmed = $isCurrentYear && $isAccepted;
            }

            if ($isCurrentYearConfirmed) {
                $summary = [
                    'final_score'             => $finalPct,
                    'problem_similarity'      => null,
                    'solution_similarity'     => null,
                    'objectives_similarity'   => null,
                    'functions_similarity'    => null,
                    'tags_similarity'         => null,
                    'technologies_similarity' => null,
                    'verdict'                 => $topResult->verdict,
                    'explanation'             => __('messages.similarity.hidden'),
                    'details_hidden'          => true,
                ];
            } else {
                $summary = [
                    'final_score'             => $finalPct,
                    'problem_similarity'      => $topResult->problem_similarity      !== null ? round($topResult->problem_similarity      * 100, 1) : null,
                    'solution_similarity'     => $topResult->solution_similarity     !== null ? round($topResult->solution_similarity     * 100, 1) : null,
                    'objectives_similarity'   => $topResult->objectives_similarity   !== null ? round($topResult->objectives_similarity   * 100, 1) : null,
                    'functions_similarity'    => $topResult->functions_similarity    !== null ? round($topResult->functions_similarity    * 100, 1) : null,
                    'tags_similarity'         => $topResult->tags_similarity         !== null ? round($topResult->tags_similarity         * 100, 1) : null,
                    'technologies_similarity' => $topResult->technologies_similarity !== null ? round($topResult->technologies_similarity * 100, 1) : null,
                    'verdict'                 => $topResult->verdict,
                    'explanation'             => $topResult->explanation,
                    'details_hidden'          => false,
                ];
            }
        }

        // ── Build the per-match list ───────────────────────────────────────
        $results = $allResults
            ->where('ai_status', 'success')
            ->filter(fn($r) => !($r->compared_version_id === $r->proposal_version_id && ($r->final_score === null || $r->final_score == 0 || $r->verdict === 'No Matches' || $r->verdict === 'No Comparisons')))
            ->map(function ($res) use ($proposal) {
                $comparedProposal = optional($res->comparedVersion)->proposal;
                $isCurrentYearConfirmed = false;
                if ($comparedProposal && $comparedProposal->proposal_id !== $proposal->proposal_id) {
                    $isCurrentYear = optional($comparedProposal->created_at)->year === now()->year;
                    $isAccepted = $comparedProposal->review_status === 'accepted';
                    $isCurrentYearConfirmed = $isCurrentYear && $isAccepted;
                }

                // Try to resolve compared project title from DB, fall back to raw response
                $raw   = $res->ai_raw_response ?? [];
                $title  = $isCurrentYearConfirmed ? __('messages.similarity.hidden_title') : (optional($res->comparedVersion)->title ?? ($raw['title'] ?? __('messages.similarity.unknown_project')));
                $domain = $isCurrentYearConfirmed ? __('messages.similarity.hidden_domain') : (optional(optional($res->comparedVersion)->proposal)->department->department_name ?? ($raw['domain'] ?? 'N/A'));

                $finalPct = $res->final_score !== null
                    ? round($res->final_score * 100, 1)
                    : ($res->similarity_score ?? 0);

                if ($isCurrentYearConfirmed) {
                    return [
                        'id'                      => null,
                        'title'                   => $title,
                        'domain'                  => $domain,
                        'score'                   => $finalPct . '%',
                        'final_score'             => $finalPct,
                        'problem_similarity'      => null,
                        'solution_similarity'     => null,
                        'objectives_similarity'   => null,
                        'functions_similarity'    => null,
                        'tags_similarity'         => null,
                        'technologies_similarity' => null,
                        'verdict'                 => $res->verdict,
                        'explanation'             => __('messages.similarity.hidden'),
                        'year'                    => optional(optional($res->comparedVersion)->created_at)->format('Y') ?? now()->year,
                        'details_hidden'          => true,
                    ];
                }

                return [
                    'id'                      => optional(optional($res->comparedVersion)->proposal)->proposal_id
                                                 ?? ($raw['project_id'] ?? null),
                    'title'                   => $title,
                    'domain'                  => $domain,
                    'score'                   => $finalPct . '%',
                    'final_score'             => $finalPct,
                    'problem_similarity'      => $res->problem_similarity      !== null ? round($res->problem_similarity      * 100, 1) : null,
                    'solution_similarity'     => $res->solution_similarity     !== null ? round($res->solution_similarity     * 100, 1) : null,
                    'objectives_similarity'   => $res->objectives_similarity   !== null ? round($res->objectives_similarity   * 100, 1) : null,
                    'functions_similarity'    => $res->functions_similarity    !== null ? round($res->functions_similarity    * 100, 1) : null,
                    'tags_similarity'         => $res->tags_similarity         !== null ? round($res->tags_similarity         * 100, 1) : null,
                    'technologies_similarity' => $res->technologies_similarity !== null ? round($res->technologies_similarity * 100, 1) : null,
                    'verdict'                 => $res->verdict,
                    'explanation'             => $res->explanation,
                    'year'                    => optional(optional($res->comparedVersion)->created_at)->format('Y') ?? now()->year,
                    'details_hidden'          => false,
                ];
            })->values();

        // AI Recommendations
        $recommendations = [];
        if ($aiStatus === 'success') {
            $recResults = app(\App\Services\AiSimilarityService::class)->getRecommendations(
                version:        $latestVersion,
                departmentName: $proposal->department->department_name ?? 'General',
                excludeId:      (string) $proposal->proposal_id
            );

            foreach ($recResults as $rec) {
                $sim = $rec['similarity'] ?? [];
                $recommendations[] = [
                    'title'       => $rec['title'] ?? 'Alternative Project',
                    'domain'      => $rec['domain'] ?? 'N/A',
                    'explanation' => $rec['explanation'] ?? '',
                    'relevance'   => round(($sim['final_similarity'] ?? 0) * 100, 1) . '%',
                ];
            }
        }

        return response()->json([
            'ai_status' => $aiStatus,
            'summary'   => $summary,
            'results'   => $results,
            'recommendations' => $recommendations,
            'analyzed_at' => optional($topResult)->updated_at,
            'message'   => 'Similarity analysis retrieved successfully.',
        ]);
    }

    private function validateWordCountHelper(int $min, int $max, string $fieldName, string $customMinMessage)
    {
        return function ($attribute, $value, $fail) use ($min, $max, $fieldName, $customMinMessage) {
            if (empty($value)) return;
            $trimmed = trim($value);
            $words = empty($trimmed) ? 0 : count(preg_split('/\s+/', $trimmed));
            if ($words < $min) {
                $fail($customMinMessage);
            }
            if ($words > $max) {
                $fail("The {$fieldName} cannot exceed {$max} words.");
            }
        };
    }

    private function transformProposal(Proposal $proposal): array
    {
        $v = $proposal->latestVersion;
        $similarity = null;

        if ($v) {
            $topResult = SimilarityResult::where('proposal_version_id', $v->version_id)
                ->where('ai_status', 'success')
                ->orderByDesc('final_score')
                ->first();

            if ($topResult) {
                $similarity = $topResult->final_score !== null
                    ? round($topResult->final_score * 100, 1)
                    : ($topResult->similarity_score ?? 0);
            }
        }

        return [
            'id' => $proposal->proposal_id,
            'title' => $v->title ?? 'No Title',
            'domain' => $proposal->department->department_name ?? 'N/A',
            'problem' => $v->problem ?? '',
            'solution' => $v->solution ?? '',
            'functions' => $v->functions ?? '',
            'objectives' => $v->objectives ?? '',
            'tags' => $v->tags ?? '',
            'tech' => $v->technologies_used ?? '',
            'status' => $proposal->review_status,
            'submission_status' => $proposal->submission_status,
            'date' => $proposal->updated_at->format('Y-m-d'),
            'version' => $v->version_number ?? 1,
            'similarity' => $similarity,
            // New flags for front‑end UI
            'can_edit' => $proposal->review_status === 'revision_requested',
            'approval_pdf_url' => $proposal->approval_pdf_path ? \Illuminate\Support\Facades\Storage::url($proposal->approval_pdf_path) : null,
        ];
    }
}
