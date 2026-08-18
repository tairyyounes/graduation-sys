<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarityResult extends Model
{
    protected $fillable = [
        'proposal_version_id',
        'compared_version_id',

        // AI status tracking
        'ai_status',               // 'pending' | 'success' | 'failed'

        // Legacy score (kept for compatibility)
        'similarity_score',

        // Per-dimension breakdown scores from the AI engine
        'problem_similarity',
        'solution_similarity',
        'objectives_similarity',
        'functions_similarity',
        'tags_similarity',
        'technologies_similarity',

        // Legacy: whole-proposal embedding score from the pre-breakdown
        // architecture. Kept for backward compatibility with old raw
        // responses; not populated or displayed by the current pipeline.
        'semantic_similarity',

        // Final weighted reranked score from the AI
        'final_score',

        // AI-generated verdict and explanation
        'verdict',
        'explanation',

        // Full raw JSON response for auditing
        'ai_raw_response',
    ];

    protected $casts = [
        'ai_raw_response'         => 'array',
        'similarity_score'        => 'float',
        'problem_similarity'      => 'float',
        'solution_similarity'     => 'float',
        'semantic_similarity'     => 'float',
        'functions_similarity'    => 'float',
        'objectives_similarity'   => 'float',
        'tags_similarity'         => 'float',
        'technologies_similarity' => 'float',
        'final_score'             => 'float',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function proposalVersion(): BelongsTo
    {
        return $this->belongsTo(ProposalVersion::class, 'proposal_version_id', 'version_id');
    }

    public function comparedVersion(): BelongsTo
    {
        return $this->belongsTo(ProposalVersion::class, 'compared_version_id', 'version_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Return the best available display score as a percentage string.
     * Prefers final_score (from AI), falls back to similarity_score.
     */
    public function displayScorePercent(): string
    {
        $score = $this->final_score ?? ($this->similarity_score / 100);
        return round($score * 100, 1) . '%';
    }
}
