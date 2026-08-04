<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_program_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('training_batch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->json('personal_data_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['training_program_id', 'status']);
            $table->index(['training_batch_id', 'status']);
        });

        Schema::create('application_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_application_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['training_application_id', 'document_type'],
                'application_document_type_unique'
            );
        });

        Schema::create('application_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_application_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['training_application_id', 'created_at'],
                'application_history_application_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('training_applications');
    }
};
