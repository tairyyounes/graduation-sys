<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReviewCommittee extends Model
{
    protected $fillable = [
        'name',
        'department_id',
    ];

    /**
     * Get the department that owns the review committee.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The users that belong to the review committee.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'committee_user');
    }
}
