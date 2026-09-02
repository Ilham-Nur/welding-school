<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_kinds', function (Blueprint $table): void {
            $table->id();
            $table->string('category_code', 3)->index();
            $table->string('code', 3);
            $table->string('name', 120);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['category_code', 'code']);
        });

        Schema::create('asset_kind_number_sequences', function (Blueprint $table): void {
            $table->foreignId('asset_kind_id')->primary()->constrained('asset_kinds')->cascadeOnDelete();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('asset_kind_id')
                ->nullable()
                ->after('category_code')
                ->constrained('asset_kinds')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('asset_kind_id');
        });

        Schema::dropIfExists('asset_kind_number_sequences');
        Schema::dropIfExists('asset_kinds');
    }
};
