<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_positions')) {
            Schema::create('employee_positions', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 255)->unique();
                $table->string('code', 50)->nullable()->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'position_id')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->foreignId('position_id')->nullable()->after('position')->constrained('employee_positions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'position_id')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            });
        }

        Schema::dropIfExists('employee_positions');
    }
};
