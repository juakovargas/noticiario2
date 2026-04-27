<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uploaded_by',
        'disk',
        'directory',
        'filename',
        'original_name',
        'path',
        'url',
        'mime_type',
        'extension',
        'size_bytes',
        'media_type',
        'collection',
        'title',
        'alt_text',
        'caption',
        'description',
        'width',
        'height',
        'duration_seconds',
        'checksum',
        'visibility',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (filled($this->url)) {
            return $this->url;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function humanSize(): string
    {
        $bytes = max(0, (int) $this->size_bytes);

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $unitIndex = 0;

        while ($value >= 1024 && isset($units[$unitIndex + 1])) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, 1).' '.$units[$unitIndex];
    }
}
