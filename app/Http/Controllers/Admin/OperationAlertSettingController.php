<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\OperationAlertSetting; use Illuminate\Http\Request; use Inertia\Inertia;
class OperationAlertSettingController extends Controller {
 public function index(){ return Inertia::render('Admin/OperationAlertSettings/Index',['settings'=>OperationAlertSetting::query()->paginate(20)]); }
 public function edit(OperationAlertSetting $operationAlertSetting){ return Inertia::render('Admin/OperationAlertSettings/Edit',['setting'=>$operationAlertSetting]); }
 public function update(Request $request, OperationAlertSetting $operationAlertSetting){ $data=$request->validate(['name'=>'required|string','is_active'=>'boolean','email_recipients'=>'nullable|string','threshold_minutes'=>'nullable|integer|min:1','threshold_count'=>'nullable|integer|min:1','cooldown_minutes'=>'required|integer|min:5']);
 $emails = collect(explode(',',(string)($data['email_recipients'] ?? '')))->map(fn($v)=>trim($v))->filter()->values(); foreach($emails as $email){ if(!filter_var($email,FILTER_VALIDATE_EMAIL)){ return back()->withErrors(['email_recipients'=>'Invalid email: '.$email]); }}
 $operationAlertSetting->update([...$data,'email_recipients'=>$emails->all()]); return redirect()->route('admin.operation-alert-settings.index'); }
}
