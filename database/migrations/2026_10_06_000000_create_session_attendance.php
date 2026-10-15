<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_sessions', function (Blueprint $table) {
            $table->string('execution_status')->default('scheduled');
            $table->timestamp('actual_started_at')->nullable();
            $table->timestamp('actual_ended_at')->nullable();
            $table->text('execution_notes')->nullable();
            $table->decimal('attendance_latitude', 10, 7)->nullable();
            $table->decimal('attendance_longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_meters')->nullable();
            $table->unsignedInteger('late_grace_minutes')->default(15);
            $table->timestamp('attendance_finalized_at')->nullable();
        });
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('unrecorded');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('attended_minutes')->default(0);
            foreach (['in', 'out'] as $kind) {
                $table->string($kind.'_photo_path')->nullable();
                $table->decimal($kind.'_latitude', 10, 7)->nullable();
                $table->decimal($kind.'_longitude', 10, 7)->nullable();
                $table->decimal($kind.'_accuracy', 8, 2)->nullable();
                $table->decimal($kind.'_distance', 8, 2)->nullable();
            }
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['batch_session_id', 'enrollment_id']);
        });
        Schema::create('attendance_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_attendance_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unique(['session_attendance_id', 'kind']);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = array_keys(config('attendance.permissions'));
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['admin', 'super-admin'])->get() as $role) {
            $role->givePermissionTo($permissions);
        }
        foreach (Role::where('guard_name', 'web')->where('name', 'trainer')->get() as $role) {
            $role->givePermissionTo(config('attendance.trainer_permissions'));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_challenges');
        Schema::dropIfExists('session_attendances');
        Schema::table('batch_sessions', function (Blueprint $table) {
            $table->dropColumn(['execution_status', 'actual_started_at', 'actual_ended_at', 'execution_notes', 'attendance_latitude', 'attendance_longitude', 'attendance_radius_meters', 'late_grace_minutes', 'attendance_finalized_at']);
        });
        Permission::where('guard_name', 'web')->whereIn('name', array_keys(config('attendance.permissions')))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
