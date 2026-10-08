<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'student_id';
    public $timestamps = false;

    protected $fillable = [
        'student_number',
        'full_name',
        'official_email',
        'department_id',
        'semester',
        'is_active',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'email', 'official_email');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /** Proposals this student is a member of (invitation accepted). */
    public function proposals()
    {
        return $this->belongsToMany(Proposal::class, 'project_members', 'student_id', 'proposal_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->wherePivot('invitation_status', 'accepted')
                    ->withTimestamps();
    }

    /** Team requests sent to this student that are still waiting for an answer. */
    public function pendingInvitations()
    {
        return $this->belongsToMany(Proposal::class, 'project_members', 'student_id', 'proposal_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->wherePivot('invitation_status', 'pending')
                    ->withTimestamps();
    }

    /** The open (non-archived) proposal where this student already has a teammate, if any. */
    public function pairedProposal(): ?Proposal
    {
        return $this->proposals()
            ->where('submission_status', '!=', 'archived')
            ->has('students', '>=', 2)
            ->first();
    }

    /** The proposal on which this student sent a team request that is still pending, if any. */
    public function proposalWithPendingRequest(): ?Proposal
    {
        return $this->proposals()
            ->where('submission_status', '!=', 'archived')
            ->whereHas('pendingInvitees')
            ->first();
    }
}
