<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('asset_code', 40)->unique();
            $table->string('asset_type', 20)->index();
            $table->string('equipment_name');
            $table->string('serial_number', 100)->nullable()->index();
            $table->string('location');
            $table->string('status', 30)->index();
            $table->date('calibrated_at')->nullable();
            $table->date('calibration_due_at')->nullable()->index();
            $table->string('certificate_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
