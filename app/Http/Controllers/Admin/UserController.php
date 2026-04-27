<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with('roles:id,name')
                ->latest()
                ->paginate(10)
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at?->toDateTimeString(),
                    'roles' => $user->roles->pluck('name')->values(),
                    'preferred_locale' => $user->preferred_locale,
                    'timezone' => $user->timezone,
                    'avatar_url' => $user->avatar_url,
                    'initials' => $user->initials,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::query()->select('id', 'name')->orderBy('name')->get(),
            'permissions' => Permission::query()->select('id', 'name')->orderBy('name')->get(),
            'localeOptions' => $this->localeOptions(),
            'dateFormatOptions' => ['locale_default', 'dd/mm/yyyy', 'yyyy-mm-dd', 'mm/dd/yyyy'],
            'timeFormatOptions' => ['24h', '12h'],
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'],
            'preferred_locale' => $data['preferred_locale'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'date_format' => $data['date_format'] ?? null,
            'time_format' => $data['time_format'] ?? null,
            'avatar_path' => $data['avatar_path'] ?? null,
        ]);

        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['permissions'] ?? []);

        return to_route('admin.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user): Response
    {
        $user->load(['roles:id,name', 'permissions:id,name']);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->toDateTimeString(),
                'updated_at' => $user->updated_at?->toDateTimeString(),
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->permissions->pluck('name')->values(),
                'preferred_locale' => $user->preferred_locale,
                'timezone' => $user->timezone,
                'date_format' => $user->date_format,
                'time_format' => $user->time_format,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load(['roles:id,name', 'permissions:id,name']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->values(),
                'permissions' => $user->permissions->pluck('name')->values(),
                'preferred_locale' => $user->preferred_locale,
                'timezone' => $user->timezone,
                'date_format' => $user->date_format,
                'time_format' => $user->time_format,
                'avatar_url' => $user->avatar_url,
                'initials' => $user->initials,
            ],
            'roles' => Role::query()->select('id', 'name')->orderBy('name')->get(),
            'permissions' => Permission::query()->select('id', 'name')->orderBy('name')->get(),
            'localeOptions' => $this->localeOptions(),
            'dateFormatOptions' => ['locale_default', 'dd/mm/yyyy', 'yyyy-mm-dd', 'mm/dd/yyyy'],
            'timeFormatOptions' => ['24h', '12h'],
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($this->wouldRemoveLastAdminLikeAccess($user, $data['roles'] ?? [])) {
            return back()->with('error', 'Cannot remove last admin');
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
            'preferred_locale' => $data['preferred_locale'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'date_format' => $data['date_format'] ?? null,
            'time_format' => $data['time_format'] ?? null,
        ];

        if (! empty($data['avatar_path'])) {
            $payload['avatar_path'] = $data['avatar_path'];
        }

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['permissions'] ?? []);

        return to_route('admin.users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->hasAnyRole(['super-admin', 'superadmin'])) {
            return to_route('admin.users.index')->with('error', 'Super admin user cannot be deleted.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    private function localeOptions(): array
    {
        $locales = [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'es', 'name' => 'Español'],
        ];

        if (Schema::hasTable('languages')) {
            $dbLocales = Language::query()->where('is_active', true)->orderBy('sort_order')->get(['code', 'name']);

            if ($dbLocales->isNotEmpty()) {
                return $dbLocales->map(fn (Language $language) => ['code' => $language->code, 'name' => $language->name])->values()->all();
            }
        }

        return $locales;
    }

    private function wouldRemoveLastAdminLikeAccess(User $user, array $newRoles): bool
    {
        $adminLikeRoles = ['admin', 'superadmin', 'super-admin'];
        $userIsCurrentlyAdminLike = $user->hasAnyRole($adminLikeRoles);

        if (! $userIsCurrentlyAdminLike) {
            return false;
        }

        $newHasAdminLikeRole = collect($newRoles)->intersect($adminLikeRoles)->isNotEmpty();

        if ($newHasAdminLikeRole) {
            return false;
        }

        $adminLikeUsersCount = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $adminLikeRoles))
            ->count();

        return $adminLikeUsersCount <= 1;
    }
}
