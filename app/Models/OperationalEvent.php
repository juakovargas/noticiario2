<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalEvent extends Model
{
    use HasFactory;

    protected $fillable = ['event_type','severity','status','title','message','related_type','related_id','user_id','occurred_at','metadata'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'metadata' => 'array'];
    }

    public function scopeRecent(Builder $query): Builder { return $query->latest('occurred_at'); }
    public function scopeToday(Builder $query): Builder { return $query->whereDate('occurred_at', now()->toDateString()); }
    public function scopeSeverity(Builder $query, string $severity): Builder { return $query->where('severity', $severity); }
    public function scopeType(Builder $query, string $type): Builder { return $query->where('event_type', $type); }
}
