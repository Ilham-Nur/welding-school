<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_standards', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('order_number')->default(0);
            $table->timestamps();
        });

        Schema::create('document_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('document_categories')->nullOnDelete();
            $table->unsignedInteger('order_number')->default(0);
            $table->timestamps();
        });

        Schema::create('document_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('standard_id')->constrained('document_standards')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('document_sections')->nullOnDelete();
            $table->string('chapter_number', 50);
            $table->string('title');
            $table->unsignedBigInteger('order_number')->default(0);
            $table->timestamps();

            $table->unique(['standard_id', 'chapter_number']);
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('standard_id')->constrained('document_standards')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('document_categories')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('document_sections')->nullOnDelete();
            $table->string('document_code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('revision_number')->default(0);
            $table->date('effective_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('original_file_path');
            $table->string('original_file_name');
            $table->string('original_file_type', 20)->nullable();
            $table->unsignedBigInteger('original_file_size')->nullable();
            $table->string('preview_file_path')->nullable();
            $table->string('conversion_status', 30)->default('not_required');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['standard_id', 'category_id', 'document_code'], 'documents_standard_category_code_unique');
        });

        Schema::create('document_document_section', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_section_id')->constrained('document_sections')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'document_section_id'], 'document_section_unique');
        });

        Schema::create('document_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('document_code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20);
            $table->json('section_ids')->nullable();
            $table->unsignedInteger('revision_number');
            $table->date('effective_date')->nullable();
            $table->string('original_file_path');
            $table->string('original_file_name');
            $table->string('original_file_type', 20)->nullable();
            $table->unsignedBigInteger('original_file_size')->nullable();
            $table->string('preview_file_path')->nullable();
            $table->string('conversion_status', 30)->default('not_required');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_revisions');
        Schema::dropIfExists('document_document_section');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_sections');
        Schema::dropIfExists('document_categories');
        Schema::dropIfExists('document_standards');
    }
};
