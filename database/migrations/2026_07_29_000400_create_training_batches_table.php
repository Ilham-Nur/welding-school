<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_program_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->date('registration_deadline')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();

            $table->index(['training_program_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_batches');
    }
};
