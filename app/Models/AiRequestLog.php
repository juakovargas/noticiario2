<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_provider_id',
        'bulletin_prompt_run_id',
        'user_id',
        'model',
        'status',
        'limit_blocked',
        'request_type',
        'prompt_hash',
        'prompt_preview',
        'response_preview',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost',
        'duration_ms',
        'error_message',
        'error_code',
        'provider_status_code',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:6',
            'limit_blocked' => 'boolean',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function bulletinPromptRun(): BelongsTo
    {
        return $this->belongsTo(BulletinPromptRun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
