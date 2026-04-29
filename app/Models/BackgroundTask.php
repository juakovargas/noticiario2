<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackgroundTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uuid','task_type','queue_name','status','related_type','related_id','user_id','started_at','finished_at','failed_at','attempts','progress','message','error_message','metadata'];

    protected function casts(): array
    {
        return ['started_at'=>'datetime','finished_at'=>'datetime','failed_at'=>'datetime','metadata'=>'array','progress'=>'integer'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function scopePending(Builder $q): Builder { return $q->where('status','pending'); }
    public function scopeRunning(Builder $q): Builder { return $q->where('status','running'); }
    public function scopeCompleted(Builder $q): Builder { return $q->where('status','completed'); }
    public function scopeFailed(Builder $q): Builder { return $q->where('status','failed'); }
    public function scopeRecent(Builder $q): Builder { return $q->latest('created_at'); }
}
