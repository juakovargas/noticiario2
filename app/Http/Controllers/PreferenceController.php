<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function updateAppearance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'appearance' => ['required', 'in:light,dark,system'],
        ]);

        $request->user()?->update([
            'appearance' => $data['appearance'],
        ]);

        return back()->with('success', 'Theme updated');
    }
}
