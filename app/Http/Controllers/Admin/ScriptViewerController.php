<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Script;
use Inertia\Inertia;
use Inertia\Response;

class ScriptViewerController extends Controller
{
    public function show(Script $script): Response
    {
        return Inertia::render('Admin/Scripts/Show', [
            'script' => $script->load([
                'edition:id,name,language',
                'bulletinPromptRun:id,title,status,created_by',
                'bulletinPromptRun.createdBy:id,name,email',
                'reviewedBy:id,name,email',
            ]),
            'returnToMessages' => request()->query('from') === 'messages',
        ]);
    }
}
