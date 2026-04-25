<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $editorRole = Role::findOrCreate('editor', 'web');
        $viewerRole = Role::findOrCreate('viewer', 'web');

        $allPermissionNames = Permission::query()->pluck('name')->all();

        $superAdminRole->syncPermissions($allPermissionNames);
        $adminRole->syncPermissions($allPermissionNames);
        $editorRole->syncPermissions([
            'admin.access',
            'dashboard.view',
            'users.view',
            'users.update',
            'roles.view',
            'permissions.view',
        ]);
        $viewerRole->syncPermissions([
            'admin.access',
            'dashboard.view',
            'users.view',
            'roles.view',
            'permissions.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
