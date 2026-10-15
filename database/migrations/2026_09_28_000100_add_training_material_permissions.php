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
        $permissions = array_keys(config('training_materials.permissions'));
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['admin', 'super-admin'])->get() as $role) {
            $role->givePermissionTo($permissions);
        }
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['trainer', 'penanggung-jawab'])->get() as $role) {
            $role->givePermissionTo(['training-materials.view', 'operations.materials']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('guard_name', 'web')->whereIn('name', array_keys(config('training_materials.permissions')))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
