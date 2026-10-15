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
            ...array_keys(config('practice.permissions')),
            ...array_keys(config('attendance.permissions')),
            ...array_keys(config('training_materials.permissions')),
            'budgets.view', 'budgets.manage', 'budgets.approve',
            ...array_keys(config('batch_operations.permissions')),
            'admin.access',
            'users.view',
            'roles.view',
            'locations.view',
            'locations.manage',
            'applications.view',
            'applications.approve',
            'programs.view',
            'programs.manage',
            'batches.view',
            'batches.manage',
            'activities.view',
            'activities.manage',
            'quality-documents.view',
            'quality-documents.manage',
            'quality-documents.structure.manage',
            'assets.view',
            'assets.manage',
            'assets.inspect',
            'storage.view',
            'storage.items.manage',
            'storage.transactions.manage',
            'storage.loans.manage',
            'storage.stocktakes.manage',
            'storage.reports.view',
            'employees.view',
            'employees.manage',
            'employees.verify',
        ]);
        $participant->syncPermissions([]);

        foreach (config('batch_operations.roles') as $name => $grants) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                Role::findOrCreate($name, 'web')->givePermissionTo($grants);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
