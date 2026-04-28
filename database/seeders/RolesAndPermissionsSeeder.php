<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
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
            'editor.access',
            'editor.dashboard.view',
            'viewer.access',
            'viewer.dashboard.view',
            'home-page.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [],
            );
        }

        $superAdminRole = Role::query()->updateOrCreate(['name' => 'superadmin', 'guard_name' => 'web'], []);
        Role::query()->updateOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], []);
        $adminRole = Role::query()->updateOrCreate(['name' => 'admin', 'guard_name' => 'web'], []);
        $editorRole = Role::query()->updateOrCreate(['name' => 'editor', 'guard_name' => 'web'], []);
        $viewerRole = Role::query()->updateOrCreate(['name' => 'viewer', 'guard_name' => 'web'], []);

        $allPermissionNames = Permission::query()->pluck('name')->all();

        $superAdminRole->syncPermissions($allPermissionNames);
        $adminRole->syncPermissions([
            'admin.access',
            'editor.access',
            'viewer.access',
            'dashboard.view',
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
            'home-page.manage',
        ]);
        $editorRole->syncPermissions([
            'editor.access',
            'editor.dashboard.view',
        ]);
        $viewerRole->syncPermissions([
            'viewer.access',
            'viewer.dashboard.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
