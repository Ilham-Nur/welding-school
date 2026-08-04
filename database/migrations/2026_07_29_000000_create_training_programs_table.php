<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('title');
            $table->string('category', 80);
            $table->unsignedSmallInteger('duration_hours');
            $table->unsignedBigInteger('price');
            $table->string('status', 20)->default('draft')->index();
            $table->date('start_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};
