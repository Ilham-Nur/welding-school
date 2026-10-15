<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->string('department')->nullable();
        });
        Schema::create('employee_position_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_position_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 30);
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('has_expiry')->default(false);
            $table->boolean('requires_verification')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
        Schema::table('employee_documents', function (Blueprint $table): void {
            $table->foreignId('requirement_id')->nullable()->constrained('employee_position_requirements')->nullOnDelete();
            $table->date('expires_at')->nullable();
            $table->string('verification_status', 30)->default('pending');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('requirement_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('requirement_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['expires_at', 'verification_status', 'verification_notes', 'verified_at', 'requirement_version']);
        });
        Schema::dropIfExists('employee_position_requirements');
        Schema::table('employee_positions', fn (Blueprint $table) => $table->dropColumn('department'));
    }
};
