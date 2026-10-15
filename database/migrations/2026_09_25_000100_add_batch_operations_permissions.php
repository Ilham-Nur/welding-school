<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = array_keys(config('batch_operations.permissions'));
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::whereIn('name', ['admin', 'super-admin'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($permissions);
        }
        foreach (config('batch_operations.roles') as $name => $grants) {
            // Existing custom roles keep their permissions; only new roles get defaults.
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                foreach ($grants as $grant) {
                    Permission::findOrCreate($grant, 'web');
                }
                Role::findOrCreate($name, 'web')->givePermissionTo($grants);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(config('batch_operations.permissions')))->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
