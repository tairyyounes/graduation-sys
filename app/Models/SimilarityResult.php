<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarityResult extends Model
{
    protected $fillable = [
        'proposal_version_id',
        'compared_version_id',
        'similarity_score',
    ];

    public function proposalVersion(): BelongsTo
    {
        return $this->belongsTo(ProposalVersion::class, 'proposal_version_id', 'version_id');
    }

    public function comparedVersion(): BelongsTo
    {
        return $this->belongsTo(ProposalVersion::class, 'compared_version_id', 'version_id');
    }
}
