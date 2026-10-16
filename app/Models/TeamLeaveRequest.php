<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's request to leave a team whose proposal is already active
 * (submitted or accepted). The department decides: approving it cancels the
 * proposal for the whole team, rejecting it leaves everything as it was.
 */
class TeamLeaveRequest extends Model
{
    protected $fillable = [
        'proposal_id',
        'student_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id', 'proposal_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
