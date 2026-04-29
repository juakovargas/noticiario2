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
            'script' => $script->load('bulletinPromptRun.createdBy:id,name,email'),
        ]);
    }
}
