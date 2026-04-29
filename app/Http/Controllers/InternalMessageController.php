<?php
namespace App\Http\Controllers;
use App\Models\InternalMessage; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Inertia\Inertia; use Inertia\Response;
class InternalMessageController extends Controller {
 public function index(Request $r): Response { $u=$r->user(); $q=InternalMessage::query()->inboxFor($u)->with(['sender:id,name,email']); if($r->query('status')==='unread') $q->unread(); if($r->query('status')==='read') $q->read(); if($t=$r->query('message_type')) $q->where('message_type',$t); return Inertia::render('Messages/Index',['messages'=>$q->latest()->paginate(20)->withQueryString()]); }
 public function show(Request $r, InternalMessage $internalMessage): Response { abort_unless($internalMessage->recipient_id===$r->user()->id,403); if(!$internalMessage->read_at)$internalMessage->update(['read_at'=>now()]); $internalMessage->load('sender:id,name,email'); return Inertia::render('Messages/Show',['message'=>$internalMessage]); }
 public function markRead(Request $r, InternalMessage $internalMessage): RedirectResponse { abort_unless($internalMessage->recipient_id===$r->user()->id,403); if(!$internalMessage->read_at)$internalMessage->update(['read_at'=>now()]); return back()->with('success','Message marked as read.'); }
 public function archive(Request $r, InternalMessage $internalMessage): RedirectResponse { abort_unless($internalMessage->recipient_id===$r->user()->id,403); $internalMessage->update(['archived_at'=>now()]); return back()->with('success','Message archived.'); }
}
