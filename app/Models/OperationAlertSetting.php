<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class OperationAlertSetting extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name','alert_type','is_active','email_recipients','threshold_minutes','threshold_count','cooldown_minutes','metadata'];
    protected function casts(): array { return ['is_active'=>'boolean','email_recipients'=>'array','metadata'=>'array']; }
}
