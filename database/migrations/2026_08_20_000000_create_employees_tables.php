<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 50)->nullable()->unique();
            $table->string('full_name', 255);
            $table->string('gender', 30)->nullable();
            $table->string('birth_place', 255)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('position', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->text('full_address')->nullable();
            $table->string('identity_number', 50)->nullable();
            $table->string('bpjs_ketenagakerjaan_number', 50)->nullable();
            $table->string('bpjs_kesehatan_number', 50)->nullable();
            $table->string('marital_status', 50)->nullable();
            $table->string('nationality', 100)->nullable()->default('Indonesia');
            $table->string('religion', 50)->nullable();
            $table->text('important_information')->nullable();
            $table->string('last_education', 100)->nullable();
            $table->string('last_education_file_path', 255)->nullable();
            $table->string('last_education_file_name', 255)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('original_photo_path', 255)->nullable();
            $table->date('hire_date')->nullable();
            $table->string('employment_status', 50)->nullable()->default('kontrak');
            $table->timestamps();

            $table->index(['full_name', 'position']);
            $table->index('employment_status');
        });

        Schema::create('employee_educations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('education_level', 100)->nullable();
            $table->string('institution_name', 255)->nullable();
            $table->string('major', 255)->nullable();
            $table->string('start_year', 10)->nullable();
            $table->string('end_year', 10)->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('grade', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();

            $table->index('employee_id');
        });

        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('document_label', 255);
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_educations');
        Schema::dropIfExists('employees');
    }
};
