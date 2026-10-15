<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_batches', function (Blueprint $table): void {
            $table->string('operational_status', 24)->default('preparation')->index();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->text('operational_notes')->nullable();
        });
        Schema::create('batch_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 24);
            $table->timestamps();
            $table->unique(['training_batch_id', 'user_id']);
        });
        Schema::create('batch_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_batch_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 24);
            $table->foreignId('trainer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['trainer_id', 'starts_at', 'ends_at']);
            $table->index(['location_id', 'starts_at', 'ends_at']);
        });
        Schema::create('batch_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_batch_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('title');
            $table->boolean('is_required')->default(true);
            $table->string('status', 24)->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['training_batch_id', 'key']);
        });
        Schema::create('batch_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('description');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        // Existing completed batches remain historical, not new preparation work.
        DB::table('training_batches')->where('status', 'completed')->update(['operational_status' => 'completed']);
        DB::table('training_batches')->orderBy('id')->chunkById(100, function ($batches): void {
            foreach ($batches as $batch) {
                foreach (['materials' => 'Materi pelatihan siap', 'equipment' => 'Alat dan material praktik siap', 'venue' => 'Lokasi pelatihan siap'] as $key => $title) {
                    DB::table('batch_checklist_items')->insert([
                        'training_batch_id' => $batch->id, 'key' => $key, 'title' => $title,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_activity_logs');
        Schema::dropIfExists('batch_checklist_items');
        Schema::dropIfExists('batch_sessions');
        Schema::dropIfExists('batch_assignments');
        Schema::table('training_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['operational_status', 'operational_notes']);
        });
    }
};
