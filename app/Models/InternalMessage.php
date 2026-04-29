<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['sender_id','recipient_id','related_type','related_id','message_type','subject','body','read_at','archived_at','metadata'];
    protected function casts(): array { return ['read_at'=>'datetime','archived_at'=>'datetime','metadata'=>'array']; }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }
    public function related(): MorphTo { return $this->morphTo(__FUNCTION__, 'related_type', 'related_id'); }
    public function scopeUnread(Builder $q): Builder { return $q->whereNull('read_at'); }
    public function scopeRead(Builder $q): Builder { return $q->whereNotNull('read_at'); }
    public function scopeArchived(Builder $q): Builder { return $q->whereNotNull('archived_at'); }
    public function scopeInboxFor(Builder $q, User $user): Builder { return $q->where('recipient_id', $user->id)->whereNull('archived_at'); }
}
