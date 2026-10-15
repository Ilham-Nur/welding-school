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
        Schema::create('training_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->unsignedInteger('revision')->default(1);
            $table->json('inputs');
            $table->json('approved_snapshot')->nullable();
            $table->unsignedBigInteger('approved_price')->nullable();
            $table->unsignedInteger('approved_revision')->nullable();
            $table->timestamps();
        });
        Schema::create('training_budget_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_budget_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });
        Schema::table('training_batches', function (Blueprint $table): void {
            $table->foreignId('training_budget_id')->nullable()->constrained()->restrictOnDelete();
            // Legacy records retain their pricing. The batch creation endpoint requires a budget.
            $table->boolean('budget_required')->default(false);
        });
        Schema::table('training_applications', function (Blueprint $table): void {
            $table->unsignedBigInteger('quoted_price')->nullable();
            $table->unsignedBigInteger('quoted_administration_fee')->nullable();
            $table->json('pricing_snapshot')->nullable();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['budgets.view', 'budgets.manage', 'budgets.approve'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::whereIn('name', ['admin', 'super-admin'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo(['budgets.view', 'budgets.manage', 'budgets.approve']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('training_applications', fn (Blueprint $table) => $table->dropColumn(['quoted_price', 'quoted_administration_fee', 'pricing_snapshot']));
        Schema::table('training_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('training_budget_id');
            $table->dropColumn('budget_required');
        });
        Schema::dropIfExists('training_budget_events');
        Schema::dropIfExists('training_budgets');
        Permission::whereIn('name', ['budgets.view', 'budgets.manage', 'budgets.approve'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
