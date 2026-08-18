<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'role',
        'department_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Map 'name' dynamically to 'full_name' for compatibility.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->full_name,
            set: fn ($value) => ['full_name' => $value],
        );
    }

    /**
     * Configure the activity logging options for the Spatie Activitylog package.
     * This defines which attributes are tracked and how the log is described.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Log all attributes inside $fillable automatically when they are changed
            ->logFillable()
            // Only log an event if an attribute actually changed
            ->logOnlyDirty()
            // Prevent logging the password field directly for security reasons
            ->dontLogIfAttributesChangedOnly(['password', 'remember_token'])
            // Specify a human-readable log name to easily filter later
            ->useLogName('system');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'official_email', 'email');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    /**
     * The committees that the user belongs to.
     */
    public function committees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ReviewCommittee::class, 'committee_user');
    }
}
