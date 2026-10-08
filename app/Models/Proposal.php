<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Proposal extends Model
{
    use LogsActivity;

    protected $primaryKey = 'proposal_id';

    protected $fillable = [
        'department_id',
        'review_committee_id',
        'submission_status',
        'review_status',
        'is_locked',
        'extra_revisions_allowed',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('proposal');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function reviewCommittee(): BelongsTo
    {
        return $this->belongsTo(ReviewCommittee::class, 'review_committee_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'project_members', 'proposal_id', 'student_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->withTimestamps();
    }

    /** Edits allowed after the initial submission: 2 by default, plus any extras granted by the department. */
    public function maxEdits(): int
    {
        return 2 + (int) $this->extra_revisions_allowed;
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProposalVersion::class, 'proposal_id', 'proposal_id');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProposalVersion::class, 'proposal_id', 'proposal_id')->latestOfMany('version_number');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class, 'proposal_id', 'proposal_id');
    }

    /**
     * Get all active committee members assigned to review this proposal.
     */
    public function getReviewCommitteeMembers()
    {
        if ($this->review_committee_id && $this->reviewCommittee) {
            return $this->reviewCommittee->users()->where('users.is_active', true)->get();
        }

        return User::whereHas('committees', function ($q) {
            $q->where('review_committees.department_id', $this->department_id);
        })->where('is_active', true)->get();
    }

    /**
     * Check if a specific user is authorized to review this proposal.
     */
    public function isUserInReviewCommittee(User $user): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->department_id !== $this->department_id) {
            return false;
        }

        $committeeMembers = $this->getReviewCommitteeMembers();
        return $committeeMembers->contains('id', $user->id);
    }
}
