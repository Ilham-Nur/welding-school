<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->string('enrollment_number', 30)->unique();
            $table->foreignId('training_application_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_program_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('training_batch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['training_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
