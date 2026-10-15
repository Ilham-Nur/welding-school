<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->timestamps();
        });
        Schema::create('training_material_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_material_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('status', 20)->default('draft');
            $table->string('title');
            $table->string('kind', 30);
            $table->text('objectives');
            $table->text('topics')->nullable();
            $table->string('method', 50);
            $table->text('guidance')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->text('change_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['training_material_id', 'number']);
        });
        Schema::create('training_material_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_material_revision_id')->constrained()->restrictOnDelete();
            $table->string('label');
            $table->string('audience', 20)->default('internal');
            $table->string('path');
            $table->string('original_name');
            $table->string('extension', 12);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        Schema::create('training_material_program', function (Blueprint $table) {
            $table->foreignId('training_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->primary(['training_material_id', 'training_program_id']);
        });
        Schema::create('program_training_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_material_revision_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->string('method', 50);
            $table->unsignedInteger('duration_minutes');
            $table->timestamps();
        });
        Schema::create('batch_training_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_material_revision_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->string('method', 50);
            $table->unsignedInteger('duration_minutes');
            $table->boolean('participant_visible')->default(false);
            $table->timestamps();
        });
        Schema::table('training_batches', fn (Blueprint $table) => $table->boolean('materials_managed')->default(false));
    }

    public function down(): void
    {
        Schema::table('training_batches', fn (Blueprint $table) => $table->dropColumn('materials_managed'));
        foreach (['batch_training_materials', 'program_training_materials', 'training_material_program', 'training_material_files', 'training_material_revisions', 'training_materials'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
