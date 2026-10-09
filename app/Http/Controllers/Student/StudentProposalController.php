<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Jobs\CheckProposalSimilarity;
use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\Decision;
use App\Models\SimilarityResult;
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
        $active = $proposals->where('submission_status', 'submitted')
            ->where('review_status', '!=', 'rejected')
            ->map(fn($p) => $this->transformProposal($p))
            ->first();
        $archived = $proposals->where(function($p) {
            return $p->submission_status === 'archived' || ($p->submission_status === 'submitted' && $p->review_status === 'rejected');
        })->map(fn($p) => $this->transformProposal($p))->values();

        return response()->json([
            'drafts' => $drafts,
            'active' => $active,
            'archived' => $archived,
        ]);
    }

    public function store(StoreProposalRequest $request): JsonResponse
    {
        $student = $request->user()->student;

        // Security: Student cannot create a new proposal if they already have an active pending or accepted proposal
        $hasActiveOrAccepted = $student->proposals()
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('submission_status', 'submitted')
                        ->whereIn('review_status', ['pending', 'revision_requested']);
                })->orWhere('review_status', 'accepted');
            })->exists();

        if ($hasActiveOrAccepted) {
            return response()->json([
                'message' => 'You already have an active submitted or accepted proposal and cannot create a new one.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $student) {
            $proposal = Proposal::create([
                'department_id' => $student->department_id,
                'submission_status' => 'draft',
                'review_status' => 'pending',
                'supervisor_name' => $request->supervisor_name,
            ]);

            if ($request->hasFile('supervisor_approval')) {
                $proposal->storeSupervisorApproval($request->file('supervisor_approval'));
            }

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

            $this->syncSupervisorDetails($request, $proposal);

            $content = [
                'title' => $request->title,
                'problem' => $request->problem,
                'solution' => $request->solution,
                'functions' => $request->functions,
                'objectives' => $request->objectives,
                'tags' => $request->tags,
                'technologies_used' => $request->tech,
            ];

            // Changing only the supervisor details must not burn one of the
            // limited proposal edits, so a version is created only when the
            // proposal content itself changed.
            $contentChanged = collect($content)->contains(
                fn ($value, $field) => trim((string) $value) !== trim((string) $latestVersion->$field)
            );

            if (!$contentChanged) {
                activity()
                    ->performedOn($proposal)
                    ->causedBy($request->user())
                    ->log('supervisor details updated');

                return response()->json([
                    'message' => 'Supervisor details updated.',
                    'proposal' => $this->transformProposal($proposal->load('latestVersion')),
                ]);
            }

            // Create new version
            $newVersion = ProposalVersion::create($content + [
                'proposal_id' => $proposal->proposal_id,
                'version_number' => $latestVersion->version_number + 1,
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

        // Security: Cannot submit if student already has an active pending/revision or accepted proposal
        $hasActiveOrAccepted = $student->proposals()
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('submission_status', 'submitted')
                        ->whereIn('review_status', ['pending', 'revision_requested']);
                })->orWhere('review_status', 'accepted');
            })
            ->where('proposals.proposal_id', '!=', $proposal->proposal_id)
            ->exists();

        if ($hasActiveOrAccepted) {
            return response()->json(['message' => 'You already have an active submitted or accepted proposal.'], 422);
        }

        // Submitting makes this the active proposal of EVERY team member, so
        // none of them may already have an active submitted or accepted proposal.
        if ($proposal->submission_status !== 'submitted') {
            $busyMember = $proposal->students()
                ->where('students.student_id', '!=', $student->student_id)
                ->whereHas('proposals', fn ($q) => $q
                    ->where(function ($sub) {
                        $sub->where('submission_status', 'submitted')
                            ->whereIn('review_status', ['pending', 'revision_requested']);
                    })
                    ->orWhere('review_status', 'accepted')
                    ->where('proposals.proposal_id', '!=', $proposal->proposal_id))
                ->first();
            if ($busyMember) {
                return response()->json([
                    'message' => "Team member {$busyMember->full_name} already has another active submitted or accepted proposal.",
                ], 422);
            }
        }

        // Strict validation of the latest version of the proposal before allowing final submission
        $latestVersion = $proposal->latestVersion;
        if (!$latestVersion) {
            return response()->json(['message' => 'No proposal content found.'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($latestVersion->toArray() + [
            'tech' => $latestVersion->technologies_used,
            'supervisor_name' => $proposal->supervisor_name,
            'supervisor_approval' => $proposal->hasSupervisorApproval() ? $proposal->supervisor_approval_path : null,
        ], [
            'supervisor_name' => ['required', 'string', 'max:150'],
            'supervisor_approval' => ['required'],
            'title' => [
                'required',
                'string',
                $this->validateWordCountHelper(5, 20, 'proposal title', 'The proposal title must be clear and contain at least 5 words.'),
                'regex:/[\p{L}\p{N}]/u',
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
                    if ($count < 1) $fail('Please add at least 1 relevant tag.');
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
            'tags.required' => 'Please add at least 1 relevant tag.',
            'tags.string' => 'The tags must be a string.',
            'tech.required' => 'Please add at least 2 technologies that will be used in the project.',
            'tech.string' => 'The technologies must be a string.',
            'supervisor_name.required' => __('messages.supervisor.name_required'),
            'supervisor_name.max' => __('messages.supervisor.name_max'),
            'supervisor_approval.required' => __('messages.supervisor.approval_required'),
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
        // Skip the (slow) AI call when this exact version was already checked
        // successfully as a draft — the stored results are still valid.
        $latestVersion = $proposal->latestVersion;
        $alreadyChecked = $latestVersion && SimilarityResult::where('proposal_version_id', $latestVersion->version_id)
            ->where('ai_status', 'success')
            ->exists();
        if ($latestVersion && !$alreadyChecked) {
            try {
                CheckProposalSimilarity::enqueue($proposal->load('department'), $latestVersion);
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

        // Prevent restore if the student already has an active submitted or accepted proposal
        $hasActiveOrAccepted = $student->proposals()
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('submission_status', 'submitted')
                        ->whereIn('review_status', ['pending', 'revision_requested']);
                })->orWhere('review_status', 'accepted');
            })
            ->where('proposals.proposal_id', '!=', $proposal->proposal_id)
            ->exists();

        if ($hasActiveOrAccepted) {
            return response()->json(['message' => 'You already have an active submitted or accepted proposal. Archive or withdraw it before restoring another.'], 422);
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

    /**
     * Whether the authenticated student is a member of this proposal's team.
     */
    private function isMember(Request $request, Proposal $proposal): bool
    {
        $student = $request->user()->student;

        return $student !== null
            && $proposal->students()->where('project_members.student_id', $student->student_id)->exists();
    }

    /**
     * Preview (inline) or download (?download=1) the signed supervisor approval.
     */
    public function supervisorApproval(Request $request, Proposal $proposal)
    {
        if (!$this->isMember($request, $proposal)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!$proposal->hasSupervisorApproval()) {
            return response()->json(['message' => __('messages.supervisor.approval_missing')], 404);
        }

        return $proposal->supervisorApprovalResponse($request->boolean('download'));
    }

    /**
     * The blank, printable supervisor approval form (static A4 PDF).
     */
    public function supervisorApprovalTemplate()
    {
        return response()->download(
            resource_path('templates/supervisor-approval-form.pdf'),
            __('messages.supervisor.template_filename'),
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Apply the supervisor name and approval document changes from the form.
     */
    private function syncSupervisorDetails(Request $request, Proposal $proposal): void
    {
        if ($request->exists('supervisor_name')) {
            $proposal->update(['supervisor_name' => $request->supervisor_name]);
        }

        if ($request->hasFile('supervisor_approval')) {
            $proposal->storeSupervisorApproval($request->file('supervisor_approval'));
        } elseif ($request->boolean('remove_supervisor_approval')) {
            $proposal->removeSupervisorApproval();
        }
    }

    public function versions(Request $request, Proposal $proposal): JsonResponse
    {
        if (!$this->isMember($request, $proposal)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $fields = ['title', 'problem', 'solution', 'functions', 'objectives', 'tags', 'technologies_used'];

        $versions = $proposal->versions()
            ->withMax('similarityResults', 'final_score')
            ->orderBy('version_number')
            ->get();

        // Walk oldest → newest so each version can be diffed against its predecessor.
        $previous = null;
        $timeline = $versions->map(function ($version) use (&$previous, $fields) {
            $changed = $previous === null
                ? []
                : array_values(array_filter($fields, fn($f) => trim((string) $version->$f) !== trim((string) $previous->$f)));

            $topScore = $version->similarity_results_max_final_score;

            $entry = [
                'id'             => $version->version_id,
                'version_number' => $version->version_number,
                'title'          => $version->title,
                'created_at'     => $version->created_at?->toIso8601String(),
                'is_initial'     => $previous === null,
                'changed_fields' => $changed,
                'similarity'     => $topScore !== null ? round($topScore * 100, 1) : null,
                'content'        => collect($fields)->mapWithKeys(fn($f) => [$f => $version->$f])->all(),
            ];

            $previous = $version;
            return $entry;
        })->reverse()->values();

        return response()->json([
            'versions'  => $timeline,
            'max_edits' => $proposal->maxEdits(),
        ]);
    }

    public function decision(Request $request, Proposal $proposal): JsonResponse
    {
        if (!$this->isMember($request, $proposal)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $decisions = $proposal->decisions()
            ->with('reviewer')
            ->orderByDesc('decision_date')
            ->get();

        $latestDecision = $decisions->first();

        if (!$latestDecision) {
            return response()->json([
                'message' => 'Waiting for department review.',
                'decision' => null,
                'decisions' => [],
            ], 200);
        }

        $allFormatted = $decisions->map(function ($d) {
            return [
                'type'     => $d->decision_type,
                'note'     => $d->decision_note,
                'reviewer' => optional($d->reviewer)->full_name ?? 'Committee Member',
                'date'     => $d->decision_date,
            ];
        })->values();

        return response()->json([
            'decision' => [
                'type'     => $latestDecision->decision_type,
                'note'     => $latestDecision->decision_note,
                'reviewer' => optional($latestDecision->reviewer)->full_name ?? 'Committee Member',
                'date'     => $latestDecision->decision_date,
            ],
            'decisions' => $allFormatted,
        ]);
    }

    public function similarity(Request $request, Proposal $proposal): JsonResponse
    {
        if (!$this->isMember($request, $proposal)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

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
        $staleCutoff = now()->subMinutes(15);
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
                CheckProposalSimilarity::enqueue($proposal->load('department'), $latestVersion);
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
                    'problem'                 => optional($res->comparedVersion)->problem ?? ($raw['problem'] ?? ($raw['description'] ?? '')),
                    'solution'                => optional($res->comparedVersion)->solution ?? ($raw['solution'] ?? ''),
                    'objectives'              => optional($res->comparedVersion)->objectives ?? ($raw['objectives'] ?? ''),
                    'functions'               => optional($res->comparedVersion)->functions ?? ($raw['functions'] ?? ''),
                    'tags'                    => optional($res->comparedVersion)->tags ?? ($raw['tags'] ?? ''),
                    'tech'                    => optional($res->comparedVersion)->technologies_used ?? ($raw['technologies_used'] ?? ($raw['tech'] ?? '')),
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

        return response()->json([
            'ai_status' => $aiStatus,
            'summary'   => $summary,
            'results'   => $results,
            // The AI engine has no /recommend endpoint; kept empty so the
            // frontend's (hidden-when-empty) recommendations section still works.
            'recommendations' => [],
            'analyzed_at' => optional($topResult)->updated_at,
            'message'   => 'Similarity analysis retrieved successfully.',
        ]);
    }

    private function validateWordCountHelper(int $min, int $max, string $fieldName, string $customMinMessage)
    {
        return function ($attribute, $value, $fail) use ($min, $max, $fieldName, $customMinMessage) {
            if (empty($value)) return;
            $trimmed = trim($value);
            $words = empty($trimmed) ? 0 : count(preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY));
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
            'team_size' => $proposal->students()->count(),
            'has_pending_request' => $proposal->pendingInvitees()->exists(),
            // New flags for front‑end UI
            'can_edit' => $proposal->review_status === 'revision_requested',
            'supervisor_name' => $proposal->supervisor_name ?? '',
            'supervisor_approval' => $proposal->supervisorApprovalMeta('/student/proposals'),
            'approval_pdf_url' => $proposal->approval_pdf_path ? \Illuminate\Support\Facades\Storage::url($proposal->approval_pdf_path) : null,
        ];
    }
}
