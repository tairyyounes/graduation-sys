<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalVersion extends Model
{
    protected $primaryKey = 'version_id';

    protected $fillable = [
        'proposal_id',
        'version_number',
        'title',
        'problem',
        'solution',
        'functions',
        'objectives',
        'tags',
        'technologies_used',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id', 'proposal_id');
    }
}
