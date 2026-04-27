<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EditorialScheduleRun extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'editorial_schedule_id',
        'edition_id',
        'editorial_request_id',
        'script_id',
        'scheduled_for',
        'status',
        'generated_prompt',
        'ai_response_text',
        'prompt_generated_at',
        'response_received_at',
        'script_created_at',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'prompt_generated_at' => 'datetime',
            'response_received_at' => 'datetime',
            'script_created_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EditorialSchedule::class, 'editorial_schedule_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function editorialRequest(): BelongsTo
    {
        return $this->belongsTo(EditorialRequest::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }
}
