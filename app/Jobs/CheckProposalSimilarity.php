<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Models\ProposalVersion;
use App\Models\SimilarityResult;
use App\Services\AiSimilarityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckProposalSimilarity implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait between retries (backoff).
     */
    public array $backoff = [10, 30];

    // ─── Constructor ────────────────────────────────────────────────────────

    public function __construct(
        public readonly Proposal        $proposal,
        public readonly ProposalVersion $version
    ) {}

    // ─── Handle ─────────────────────────────────────────────────────────────

    /**
     * Execute the job.
     *
     * 1. Upserts a "pending" SimilarityResult placeholder for audit visibility.
     * 2. Calls the AI API via AiSimilarityService.
     * 3. Persists each MatchResult returned by the API.
     * 4. On any failure: marks existing records as "failed" and logs — the
     *    proposal itself is never affected.
     */
    public function handle(AiSimilarityService $service): void
    {
        $departmentName = optional($this->proposal->department)->department_name ?? 'General';
        $versionId      = $this->version->version_id;
        $currentProposalId = $this->proposal->proposal_id;

        // No pre-flight check is needed because the FastAPI similarity service
        // has its own pre-loaded historical dataset to compare against.

        // ── 1. Mark existing results as pending (clean slate) ──────────────
        SimilarityResult::where('proposal_version_id', $versionId)->update(['ai_status' => 'pending']);

        try {
            // ── 2. Call the AI API ─────────────────────────────────────────
            // Pass the current proposal_id as excludeId so the AI engine
            // cannot return the same proposal as a match.
            $apiResponse = $service->checkSimilarity(
                version:        $this->version,
                departmentName: $departmentName,
                excludeId:      (string) $currentProposalId,
                topK:           10,
            );

            // ── 3. Delete old results and store fresh ones ─────────────────
            SimilarityResult::where('proposal_version_id', $versionId)->delete();

            $results = $apiResponse['results'] ?? [];

            foreach ($results as $match) {
                $sim = $match['similarity'] ?? [];

                SimilarityResult::create([
                    'proposal_version_id'   => $versionId,
                    'compared_version_id'   => $this->resolveComparedVersionId($match['project_id'] ?? null),
                    'ai_status'             => 'success',

                    // Legacy field — store final score × 100 for backwards-compat
                    'similarity_score'      => round(($sim['final_similarity'] ?? 0) * 100, 2),

                    // Breakdown dimensions (0–1 range)
                    'semantic_similarity'   => $sim['semantic_similarity']   ?? null,
                    'functions_similarity'  => $sim['functions_similarity']  ?? null,
                    'objectives_similarity' => $sim['objectives_similarity'] ?? null,
                    'tags_similarity'       => $sim['tags_similarity']       ?? null,
                    'technologies_similarity' => $sim['technologies_similarity'] ?? null,
                    'final_score'           => $sim['final_similarity']      ?? null,

                    // AI verdict + explanation
                    'verdict'               => $match['verdict']     ?? null,
                    'explanation'           => $match['explanation']  ?? null,

                    // Raw JSON for auditing
                    'ai_raw_response'       => $match,
                ]);
            }

            // If no results were returned (e.g. no other proposals in DB), insert a
            // single sentinel row so the frontend knows the check ran successfully.
            if (empty($results)) {
                SimilarityResult::create([
                    'proposal_version_id' => $versionId,
                    'compared_version_id' => $versionId, // self-reference as sentinel
                    'ai_status'           => 'success',
                    'similarity_score'    => 0,
                    'final_score'         => 0,
                    'verdict'             => 'No Matches',
                    'explanation'         => 'No similar proposals found in the database.',
                    'ai_raw_response'     => $apiResponse,
                ]);
            }

            Log::info("CheckProposalSimilarity: completed for version {$versionId}", [
                'matches' => count($results),
            ]);

        } catch (\Throwable $e) {
            // ── 4. Graceful failure — proposal stays intact ────────────────
            Log::error("CheckProposalSimilarity: failed for version {$versionId}", [
                'error' => $e->getMessage(),
            ]);

            // Mark or create a failed sentinel row
            SimilarityResult::updateOrCreate(
                ['proposal_version_id' => $versionId, 'compared_version_id' => $versionId],
                [
                    'ai_status'       => 'failed',
                    'similarity_score' => 0,
                    'ai_raw_response'  => ['error' => $e->getMessage()],
                ]
            );

            // Re-throw so the queue marks this attempt as failed (respects $tries)
            throw $e;
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Attempt to look up the compared ProposalVersion by the AI project_id.
     *
     * The AI engine uses string project IDs from its own dataset, which may not
     * directly map to our database primary keys. We try a best-effort lookup;
     * if nothing is found we fall back to the current version's own ID so the
     * FK constraint is satisfied (the row is still useful for score storage).
     */
    private function resolveComparedVersionId(?string $aiProjectId): int
    {
        if ($aiProjectId === null) {
            return $this->version->version_id;
        }

        // Try matching proposal_id or version_id in our database
        $match = ProposalVersion::whereHas('proposal', function ($q) use ($aiProjectId) {
            $q->where('proposal_id', $aiProjectId);
        })->first();

        if ($match) {
            return $match->version_id;
        }

        // Fallback: self-reference (won't break FK, still stores AI data)
        return $this->version->version_id;
    }
}
