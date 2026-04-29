<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackgroundTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackgroundTaskController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search','task_type','status','user','date_from','date_to']);
        $tasks = BackgroundTask::query()->with('user:id,name')
            ->when($filters['search'] ?? null, fn(Builder $q,$v)=>$q->where('message','like',"%$v%"))
            ->when($filters['task_type'] ?? null, fn(Builder $q,$v)=>$q->where('task_type',$v))
            ->when($filters['status'] ?? null, fn(Builder $q,$v)=>$q->where('status',$v))
            ->when($filters['user'] ?? null, fn(Builder $q,$v)=>$q->where('user_id',$v))
            ->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/BackgroundTasks/Index', ['tasks'=>$tasks,'filters'=>$filters,'users'=>User::query()->orderBy('name')->get(['id','name'])]);
    }

    public function show(BackgroundTask $backgroundTask): Response
    {
        return Inertia::render('Admin/BackgroundTasks/Show', ['task'=>$backgroundTask->load('user:id,name')]);
    }
}
