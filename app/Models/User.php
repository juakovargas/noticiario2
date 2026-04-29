<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'is_active',
        'preferred_locale',
        'timezone',
        'avatar_path',
        'profile_image_id',
        'manager_id',
        'date_format',
        'time_format',
        'appearance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
        'initials',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function profileImage(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'profile_image_id');
    }

    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'uploaded_by');
    }

    public function bulletinPromptRuns(): HasMany
    {
        return $this->hasMany(BulletinPromptRun::class, 'created_by');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function checkedSourceReferences(): HasMany
    {
        return $this->hasMany(SourceReference::class, 'checked_by');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $profileImage = null;

        if ($this->relationLoaded('profileImage')) {
            $profileImage = $this->getRelation('profileImage');
        } elseif ($this->profile_image_id) {
            $profileImage = $this->profileImage()->first();
        }

        if ($profileImage instanceof MediaFile && filled($profileImage->public_url)) {
            return $profileImage->public_url;
        }

        if (filled($this->avatar_path)) {
            return asset(Storage::disk('public')->url($this->avatar_path));
        }

        return null;
    }

    public function getInitialsAttribute(): string
    {
        $parts = Str::of((string) $this->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2);

        if ($parts->isEmpty()) {
            return 'U';
        }

        return $parts
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
