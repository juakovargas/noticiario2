<?php
namespace App\Services\Operations;
use App\Models\OperationalEvent;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class OperationalEventService {
 public function record(string $eventType, string $title, array $data = []): OperationalEvent {
  $metadata = (array)($data['metadata'] ?? []); unset($metadata['api_key'],$metadata['token'],$metadata['secret']);
  $related = $data['related'] ?? null; $user = $data['user'] ?? null;
  return OperationalEvent::query()->create([
   'event_type'=>$eventType,'severity'=>$data['severity'] ?? 'info','status'=>$data['status'] ?? null,'title'=>$title,'message'=>$data['message'] ?? null,
   'related_type'=>$related instanceof Model ? $related::class : null,'related_id'=>$related instanceof Model ? $related->getKey() : null,
   'user_id'=>$user instanceof User ? $user->id : null,'occurred_at'=>$data['occurred_at'] ?? now(),'metadata'=>$metadata,
  ]);
 }
 public function info(string $t,string $ti,array $d=[]): OperationalEvent { return $this->record($t,$ti,[...$d,'severity'=>'info']); }
 public function success(string $t,string $ti,array $d=[]): OperationalEvent { return $this->record($t,$ti,[...$d,'severity'=>'success']); }
 public function warning(string $t,string $ti,array $d=[]): OperationalEvent { return $this->record($t,$ti,[...$d,'severity'=>'warning']); }
 public function error(string $t,string $ti,array $d=[]): OperationalEvent { return $this->record($t,$ti,[...$d,'severity'=>'error']); }
 public function critical(string $t,string $ti,array $d=[]): OperationalEvent { return $this->record($t,$ti,[...$d,'severity'=>'critical']); }
}
