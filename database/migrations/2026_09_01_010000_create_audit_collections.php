<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('order_number')->default(0);
            $table->timestamps();
        });

        Schema::table('audit_documents', function (Blueprint $table): void {
            $table->foreignId('audit_collection_id')
                ->nullable()
                ->after('id')
                ->constrained('audit_collections')
                ->cascadeOnDelete();
        });

        $now = now();
        $defaultCollectionId = DB::table('audit_collections')->insertGetId([
            'name' => 'Data Audit',
            'slug' => 'data-audit',
            'order_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('audit_documents')
            ->whereNull('audit_collection_id')
            ->update(['audit_collection_id' => $defaultCollectionId]);
    }

    public function down(): void
    {
        Schema::table('audit_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('audit_collection_id');
        });

        Schema::dropIfExists('audit_collections');
    }
};
