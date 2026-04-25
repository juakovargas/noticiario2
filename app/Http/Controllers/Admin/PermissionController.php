<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionStoreRequest;
use App\Http\Requests\Admin\PermissionUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::query()
                ->orderBy('name')
                ->paginate(12)
                ->through(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Permissions/Create');
    }

    public function store(PermissionStoreRequest $request): RedirectResponse
    {
        Permission::query()->create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('admin.permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission): Response
    {
        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
            ],
        ]);
    }

    public function update(PermissionUpdateRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update([
            'name' => $request->validated('name'),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('admin.permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $systemPermissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'dashboard.view',
            'admin.access',
        ];

        if (in_array($permission->name, $systemPermissions, true)) {
            return to_route('admin.permissions.index')->with('error', 'Core permissions cannot be deleted.');
        }

        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return to_route('admin.permissions.index')->with('success', 'Permission deleted successfully.');
    }
}
