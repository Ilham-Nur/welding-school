<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = array_keys(config('admin.permission_labels'));

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $admin = Role::findOrCreate('admin', 'web');
        $participant = Role::findOrCreate('participant', 'web');

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions([
            'admin.access',
            'users.view',
            'roles.view',
            'applications.view',
            'applications.approve',
            'programs.view',
            'programs.manage',
            'batches.view',
            'batches.manage',
            'activities.view',
            'activities.manage',
        ]);
        $participant->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
