<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $roles = ['super-admin', 'admin', 'participant'];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->pluck('id', 'name');

        DB::table('users')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->each(function (object $user) use ($roleIds): void {
                $role = $user->role === 'admin' ? 'admin' : 'participant';

                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleIds[$role],
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]);
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 30)->default('participant')->index();
        });

        $adminRoleIds = DB::table('roles')
            ->whereIn('name', ['super-admin', 'admin'])
            ->pluck('id');

        $adminUserIds = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('role_id', $adminRoleIds)
            ->pluck('model_id');

        DB::table('users')
            ->whereIn('id', $adminUserIds)
            ->update(['role' => 'admin']);
    }
};
