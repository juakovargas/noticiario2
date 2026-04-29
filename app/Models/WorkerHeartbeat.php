<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class WorkerHeartbeat extends Model
{
    use HasFactory;
    protected $fillable = ['worker_name','queue_connection','queue_name','hostname','process_id','last_seen_at','metadata'];
    protected function casts(): array { return ['last_seen_at'=>'datetime','metadata'=>'array']; }
}
