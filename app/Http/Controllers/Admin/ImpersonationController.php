<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if (! $currentUser || ! $currentUser->can('admin.access')) {
            abort(403);
        }

        if ((int) $currentUser->id === (int) $user->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Nested impersonation is not allowed.');
        }

        $request->session()->put('impersonator_id', $currentUser->id);

        Auth::login($user);
        $request->session()->regenerate();

        $routeName = AuthRedirect::routeNameFor($user);

        return $routeName === 'dashboard'
            ? redirect('/')->with('success', 'You are now impersonating '.$user->name.'.')
            : to_route($routeName)->with('success', 'You are now impersonating '.$user->name.'.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return back()->with('error', 'No active impersonation session.');
        }

        $impersonator = User::query()->find($impersonatorId);

        if (! $impersonator) {
            Auth::logout();
            $request->session()->forget('impersonator_id');

            return to_route('login')->with('error', 'Original admin account no longer exists.');
        }

        Auth::login($impersonator);
        $request->session()->forget('impersonator_id');
        $request->session()->regenerate();

        return to_route('admin.users.index')->with('success', 'Returned to admin account.');
    }
}
