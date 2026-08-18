<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Decision;
use App\Models\SimilarityResult;
use App\Jobs\CheckProposalSimilarity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentProposalController extends Controller
{
    /**
     * Get proposals for the authenticated department member's department.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $departmentId = $user->department_id;

        if (!$departmentId) {
            return response()->json(['message' => 'User not assigned to any department.'], 403);
        }

        $status = $request->query('status', 'submitted');

        $proposals = Proposal::where('department_id', $departmentId)
            ->where('submission_status', $status)
            ->with(['latestVersion', 'students'])
            ->latest()
            ->get();

        return response()->json([
            'proposals' => $proposals->map(fn($p) => $this->transformProposal($p)),
        ]);
    }

    /**
     * Get statistics for the department dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $departmentId = $request->user()->department_id;

        $stats = [
            'total' => Proposal::where('department_id', $departmentId)->count(),
            'pending' => Proposal::where('department_id', $departmentId)->where('review_status', 'pending')->count(),
            'accepted' => Proposal::where('department_id', $departmentId)->where('review_status', 'accepted')->count(),
            'rejected' => Proposal::where('department_id', $departmentId)->where('review_status', 'rejected')->count(),
            'revision' => Proposal::where('department_id', $departmentId)->where('review_status', 'revision_requested')->count(),
        ];

        return response()->json(['stats' => $stats]);
    }

    /**
     * Get details of a specific proposal.
     */
    public function show(Request $request, Proposal $proposal): JsonResponse
    {
        // Security: Check if proposal belongs to user's department
        if ($proposal->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Unauthorized access to this department\'s data.'], 403);
        }

        $proposal->load(['latestVersion', 'students', 'versions', 'decisions.reviewer']);

        return response()->json([
            'proposal' => $this->transformProposal($proposal),
            'history' => $proposal->versions,
            'decisions' => $proposal->decisions,
        ]);
    }

    /**
     * Store a review decision for a proposal.
     */
    public function review(Request $request, Proposal $proposal): JsonResponse
    {
        $user = $request->user();

        if ($proposal->department_id !== $user->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Department heads, department members, and admins may all review proposals.
        $allowedRoles = ['department_head', 'department_member', 'admin'];
        if (!in_array($user->role, $allowedRoles)) {
            return response()->json(['message' => 'You do not have permission to review proposals.'], 403);
        }

        $request->validate([
            'decision' => 'required|in:accepted,rejected,revision_requested',
            'note' => 'nullable|string',
        ]);

        if ($request->decision === 'revision_requested') {
            $revisionCount = $proposal->decisions()->where('decision_type', 'revision_requested')->count();
            $maxRevisions = 2 + $proposal->extra_revisions_allowed;
            if ($revisionCount >= $maxRevisions) {
                return response()->json(['message' => 'Maximum number of revisions reached for this proposal.'], 403);
            }
        }

        $latestVersion = $proposal->latestVersion;
        if (!$latestVersion) {
            return response()->json(['message' => 'Proposal has no versions.'], 422);
        }

        return DB::transaction(function () use ($request, $proposal, $latestVersion) {
            $decision = Decision::create([
                'proposal_id' => $proposal->proposal_id,
                'version_id' => $latestVersion->version_id,
                'reviewer_id' => $request->user()->id,
                'decision_type' => $request->decision,
                'decision_note' => $request->note,
                'decision_date' => now(),
            ]);

            $proposal->update([
                'review_status' => $request->decision,
                'is_locked' => ($request->decision === 'accepted'),
            ]);

            activity()
                ->performedOn($proposal)
                ->causedBy($request->user())
                ->log("proposal marked as {$request->decision}");

            return response()->json([
                'message' => 'Review submitted successfully.',
                'decision' => $decision,
            ]);
        });
    }

    /**
     * Get the full similarity breakdown for a proposal (department view).
     */
    public function similarity(Request $request, Proposal $proposal): JsonResponse
    {
        if ($proposal->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $latestVersion = $proposal->latestVersion;

        if (!$latestVersion) {
            return response()->json([
                'ai_status' => 'none',
                'summary'   => null,
                'results'   => [],
            ]);
        }

        $versionId  = $latestVersion->version_id;
        $allResults = SimilarityResult::where('proposal_version_id', $versionId)
            ->orderByDesc('final_score')
            ->get();

        $statuses = $allResults->pluck('ai_status')->unique()->values();
        $aiStatus = $statuses->contains('failed') ? 'failed'
                  : ($statuses->contains('pending') ? 'pending'
                  : ($statuses->contains('no_comparisons') ? 'no_comparisons'
                  : ($allResults->isEmpty() ? 'none' : 'success')));

        // If it failed or has never been checked, dispatch job
        // Do NOT re-dispatch for 'no_comparisons' — there is nothing to compare against.
        if ($aiStatus === 'failed' || $aiStatus === 'none') {
            try {
                CheckProposalSimilarity::dispatch($proposal->load('department'), $latestVersion);
            } catch (\Throwable $e) {
                // On a sync queue a failing AI call would bubble up as a 500 and
                // break the department view. Swallow it so the endpoint still
                // returns a graceful status the UI can render.
                \Illuminate\Support\Facades\Log::warning('Department similarity dispatch failed: ' . $e->getMessage());
            }

            // Reload results from the database
            $allResults = SimilarityResult::where('proposal_version_id', $versionId)
                ->orderByDesc('final_score')
                ->get();

            $statuses = $allResults->pluck('ai_status')->unique()->values();
            $aiStatus = $statuses->contains('failed') ? 'failed'
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

        if ($allResults->isEmpty()) {
            return response()->json([
                'ai_status' => 'pending',
                'summary'   => null,
                'results'   => [],
            ]);
        }

        $topResult = $allResults->where('ai_status', 'success')->first();
        $summary   = null;

        if ($topResult) {
            $finalPct = $topResult->final_score !== null
                ? round($topResult->final_score * 100, 1)
                : ($topResult->similarity_score ?? 0);

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
            ];
        }

        $results = $allResults
            ->where('ai_status', 'success')
            ->filter(fn($r) => !($r->compared_version_id === $r->proposal_version_id && ($r->final_score === null || $r->final_score == 0 || $r->verdict === 'No Matches' || $r->verdict === 'No Comparisons')))
            ->map(function ($res) {
                $raw    = $res->ai_raw_response ?? [];
                $title  = optional($res->comparedVersion)->title ?? ($raw['title'] ?? 'Unknown Project');
                $domain = optional(optional($res->comparedVersion)->proposal)->department->department_name
                        ?? ($raw['domain'] ?? 'N/A');

                $finalPct = $res->final_score !== null
                    ? round($res->final_score * 100, 1)
                    : ($res->similarity_score ?? 0);

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
        ]);
    }

    /**
     * Transform proposal model into a frontend-friendly array.
     */
    private function transformProposal(Proposal $proposal): array
    {
        $v          = $proposal->latestVersion;
        $similarity = $this->resolveDisplaySimilarity($proposal);

        return [
            'id'               => $proposal->proposal_id,
            'title'            => $v->title ?? 'No Title',
            'author'           => $proposal->students->first()->full_name ?? 'Unknown',
            'author_email'     => $proposal->students->first()->official_email ?? '',
            'department'       => $proposal->department->department_name ?? 'N/A',
            'problem'          => $v->problem ?? '',
            'solution'         => $v->solution ?? '',
            'functions'        => $v->functions ?? '',
            'objectives'       => $v->objectives ?? '',
            'tags'             => $v->tags ?? '',
            'tech'             => $v->technologies_used ?? '',
            'status'           => $proposal->review_status,
            'submission_status' => $proposal->submission_status,
            'is_locked'         => $proposal->is_locked,
            'revision_count'    => $proposal->decisions()->where('decision_type', 'revision_requested')->count(),
            'max_revisions'     => 2 + $proposal->extra_revisions_allowed,
            'date'              => $proposal->updated_at->format('Y-m-d'),
            'similarity'        => $similarity,
        ];
    }

    /**
     * Grant an extra revision for a proposal.
     */
    public function grantExtraRevision(Request $request, Proposal $proposal): JsonResponse
    {
        $user = $request->user();

        if ($proposal->department_id !== $user->department_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->role !== 'department_head') {
            return response()->json(['message' => 'Only Department Heads can grant extra revisions.'], 403);
        }

        $proposal->increment('extra_revisions_allowed');

        activity()
            ->performedOn($proposal)
            ->causedBy($user)
            ->log('granted 1 extra revision');

        return response()->json([
            'message' => 'Extra revision granted successfully.',
            'max_revisions' => 2 + $proposal->extra_revisions_allowed,
        ]);
    }

    /**
     * Resolve the best available similarity display string for a proposal.
     * Returns e.g. "34.5%" from real AI data, "Pending" if still running,
     * or "N/A" if no analysis has been triggered.
     */
    private function resolveDisplaySimilarity(Proposal $proposal): string
    {
        $version = $proposal->latestVersion;
        if (!$version) {
            return 'N/A';
        }

        $top = SimilarityResult::where('proposal_version_id', $version->version_id)
            ->orderByDesc('final_score')
            ->first();

        if (!$top) {
            return 'Pending';
        }

        if ($top->ai_status === 'failed') {
            return 'Error';
        }

        if ($top->ai_status === 'pending') {
            return 'Pending';
        }

        if ($top->ai_status === 'no_comparisons') {
            return 'N/A';
        }

        $score = $top->final_score !== null
            ? round($top->final_score * 100, 1)
            : ($top->similarity_score ?? 0);

        return $score . '%';
    }
}
