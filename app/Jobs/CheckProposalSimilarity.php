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
                // The AI engine's /search_proposals now returns "score" as an
                // already-calibrated 0-1 overall similarity (Dense+FAISS+
                // Cross-Encoder, temperature-scaled sigmoid applied server-side
                // in dense.py — see calibrate_ce_score()) plus a nested
                // "similarity" breakdown, "verdict", and "explanation". This is
                // the ONE authoritative score — no re-derivation here.
                $sim = $match['similarity'] ?? [];
                $score = isset($match['score']) ? $this->clampScore((float) $match['score']) : null;

                SimilarityResult::create([
                    'proposal_version_id'   => $versionId,
                    // The AI engine's comparison corpus is an external
                    // dataset, not this application's own proposals — most
                    // matches have no real proposal_versions row to point
                    // to, so this is null unless a genuine one is found.
                    'compared_version_id'   => $this->resolveComparedVersionId($match['project_id'] ?? null),
                    'ai_status'             => 'success',

                    // Legacy field — store final score × 100 for backwards-compat
                    'similarity_score'      => round(($score ?? 0) * 100, 2),

                    // Breakdown dimensions (0–1 range). null = not evaluated
                    // (a field was empty on one side), 0.0 = evaluated with no
                    // overlap found — never conflate the two.
                    'problem_similarity'      => $sim['problem_similarity']      ?? null,
                    'solution_similarity'     => $sim['solution_similarity']     ?? null,
                    'objectives_similarity'   => $sim['objectives_similarity']   ?? null,
                    'functions_similarity'    => $sim['functions_similarity']    ?? null,
                    'tags_similarity'         => $sim['tags_similarity']         ?? null,
                    'technologies_similarity' => $sim['technologies_similarity'] ?? null,
                    'final_score'             => $score,

                    // AI-generated verdict + explanation (backend, not Vue-invented)
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
     * Defensive safety clamp — the AI engine now returns an already-
     * calibrated 0-1 overall score (see dense.py's calibrate_ce_score()),
     * so this is not a re-derivation, just a guard against float edge cases
     * (e.g. 1.0000000002) before it's persisted or multiplied for display.
     */
    private function clampScore(float $score): float
    {
        return max(0.0, min(1.0, $score));
    }

    /**
     * Resolve the compared ProposalVersion for an AI match, if one genuinely
     * exists in this system.
     *
     * VERIFIED (see AI similarity investigation): the AI engine's comparison
     * corpus is loaded entirely from static research CSVs (data_prep.py /
     * load_all()) — it never contains this application's own proposals. Its
     * project_id is an independent sequence (1..~3000) that numerically
     * collides with real Laravel proposal_id values purely by coincidence
     * (confirmed empirically: 3 of 10 matches in one real test resolved to
     * an unrelated real proposal this way). Matching on project_id ==
     * proposal_id would therefore attribute a synthetic corpus entry's
     * similarity to a real, unrelated proposal and display its title.
     *
     * Until the AI engine can tag a match as "this really is one of our own
     * proposals" (e.g. a dedicated external_id column it doesn't currently
     * have), there is no reliable way to distinguish a genuine match from a
     * coincidental ID collision — so this always returns null, and the
     * frontend correctly falls back to the AI engine's own raw title/domain.
     */
    private function resolveComparedVersionId(?string $aiProjectId): ?int
    {
        return null;
    }
}
