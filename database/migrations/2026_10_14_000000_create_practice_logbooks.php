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
        Schema::create('session_practice_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_session_id')->unique()->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->boolean('photo_required')->default(true);
            $table->unsignedInteger('effective_minutes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('practice_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_practice_task_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->string('outcome')->nullable();
            $table->string('obstacle')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_mime')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->uuid('last_request_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('verified_minutes')->nullable();
            $table->timestamps();
            $table->unique(['session_practice_task_id', 'enrollment_id'], 'practice_task_enrollment_unique');
        });
        Schema::create('practice_submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_submission_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->json('snapshot');
            $table->timestamp('created_at');
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = array_keys(config('practice.permissions'));
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::where('guard_name', 'web')->whereIn('name', ['admin', 'super-admin', 'trainer'])->get() as $role) {
            $role->givePermissionTo($permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_submission_events');
        Schema::dropIfExists('practice_submissions');
        Schema::dropIfExists('session_practice_tasks');
        Permission::where('guard_name', 'web')->whereIn('name', array_keys(config('practice.permissions')))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
