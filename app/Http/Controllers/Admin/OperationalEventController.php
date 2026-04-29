<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\OperationalEvent; use Illuminate\Http\Request; use Inertia\Inertia;
class OperationalEventController extends Controller {
 public function index(Request $request){ $q=OperationalEvent::query()->latest('occurred_at'); foreach(['severity','event_type','status'] as $f){ if($request->filled($f)) $q->where($f,$request->string($f)); }
 if($request->filled('search')) $q->where('title','like','%'.$request->string('search').'%'); return Inertia::render('Admin/Operations/Events/Index',['events'=>$q->paginate(20)->withQueryString(),'filters'=>$request->all()]); }
 public function show(OperationalEvent $operationalEvent){ return Inertia::render('Admin/Operations/Events/Show',['event'=>$operationalEvent]); }
}
