<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        'supervisor_name',
        'supervisor_approval_path',
        'supervisor_approval_original_name',
        'supervisor_approval_mime',
        'supervisor_approval_size',
        'supervisor_approval_uploaded_at',
    ];

    /** Private disk holding the signed supervisor approval forms. */
    public const SUPERVISOR_APPROVAL_DISK = 'local';

    protected $casts = [
        'supervisor_approval_uploaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Don't leave orphaned approval documents behind when a proposal is deleted.
        static::deleted(function (Proposal $proposal) {
            $proposal->deleteSupervisorApprovalFile();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['supervisor_approval_path'])
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

    /** Team members who are actually on the team (invitation accepted). */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'project_members', 'proposal_id', 'student_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->wherePivot('invitation_status', 'accepted')
                    ->withTimestamps();
    }

    /** Students invited to the team who have not answered yet. */
    public function pendingInvitees()
    {
        return $this->belongsToMany(Student::class, 'project_members', 'proposal_id', 'student_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->wherePivot('invitation_status', 'pending')
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

    public function hasSupervisorApproval(): bool
    {
        return $this->supervisor_approval_path !== null
            && Storage::disk(self::SUPERVISOR_APPROVAL_DISK)->exists($this->supervisor_approval_path);
    }

    /**
     * Store (or replace) the signed supervisor approval document on the private disk.
     */
    public function storeSupervisorApproval(UploadedFile $file): void
    {
        $previous = $this->supervisor_approval_path;

        $path = $file->store('supervisor-approvals/' . $this->proposal_id, self::SUPERVISOR_APPROVAL_DISK);

        $this->forceFill([
            'supervisor_approval_path'          => $path,
            'supervisor_approval_original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'supervisor_approval_mime'          => $file->getMimeType(),
            'supervisor_approval_size'          => $file->getSize(),
            'supervisor_approval_uploaded_at'   => now(),
        ])->save();

        if ($previous && $previous !== $path) {
            Storage::disk(self::SUPERVISOR_APPROVAL_DISK)->delete($previous);
        }
    }

    public function removeSupervisorApproval(): void
    {
        $this->deleteSupervisorApprovalFile();

        $this->forceFill([
            'supervisor_approval_path'          => null,
            'supervisor_approval_original_name' => null,
            'supervisor_approval_mime'          => null,
            'supervisor_approval_size'          => null,
            'supervisor_approval_uploaded_at'   => null,
        ])->save();
    }

    private function deleteSupervisorApprovalFile(): void
    {
        if ($this->supervisor_approval_path) {
            Storage::disk(self::SUPERVISOR_APPROVAL_DISK)->delete($this->supervisor_approval_path);
        }
    }

    /**
     * Stream the approval document inline (preview) or as a download.
     */
    public function supervisorApprovalResponse(bool $download)
    {
        $disk = Storage::disk(self::SUPERVISOR_APPROVAL_DISK);
        $name = $this->supervisor_approval_original_name ?: basename($this->supervisor_approval_path);
        $headers = [
            'Content-Type'           => $this->supervisor_approval_mime ?: $disk->mimeType($this->supervisor_approval_path),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store',
        ];

        return $download
            ? $disk->download($this->supervisor_approval_path, $name, $headers)
            : $disk->response($this->supervisor_approval_path, $name, $headers, 'inline');
    }

    /**
     * Frontend-friendly description of the uploaded document (null when none).
     * $routePrefix is the API prefix the viewer uses, e.g. "/student/proposals".
     */
    public function supervisorApprovalMeta(string $routePrefix): ?array
    {
        if (!$this->supervisor_approval_path) {
            return null;
        }

        $url = url("{$routePrefix}/{$this->proposal_id}/supervisor-approval");

        return [
            'name'         => $this->supervisor_approval_original_name,
            'mime'         => $this->supervisor_approval_mime,
            'size'         => $this->supervisor_approval_size,
            'uploaded_at'  => $this->supervisor_approval_uploaded_at?->toIso8601String(),
            'preview_url'  => $url,
            'download_url' => $url . '?download=1',
        ];
    }
}
