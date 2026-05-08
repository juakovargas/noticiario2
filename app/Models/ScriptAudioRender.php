<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScriptAudioRender extends Model
{
    protected $fillable = [
        'script_id',
        'status',
        'provider',
        'voice_id',
        'model_id',
        'output_format',
        'source_text',
        'audio_path',
        'audio_disk',
        'duration_seconds',
        'character_count',
        'error_message',
        'requested_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'character_count' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
