<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
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

    public function proposals()
    {
        return $this->belongsToMany(Proposal::class, 'project_members', 'student_id', 'proposal_id')
                    ->withPivot('member_role', 'invitation_status', 'joined_at')
                    ->withTimestamps();
    }
}
