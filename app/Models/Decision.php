<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    protected $primaryKey = 'decision_id';

    protected $fillable = [
        'proposal_id',
        'version_id',
        'reviewer_id',
        'decision_type',
        'decision_note',
        'decision_date',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id', 'proposal_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProposalVersion::class, 'version_id', 'version_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id', 'id');
    }
}
