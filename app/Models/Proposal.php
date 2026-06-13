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

    public function students()
    {
        return $this->belongsToMany(Student::class, 'project_members', 'proposal_id', 'student_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->withTimestamps();
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
}
