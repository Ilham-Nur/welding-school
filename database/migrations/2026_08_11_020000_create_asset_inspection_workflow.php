<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn('pic');
            $table->unsignedTinyInteger('inspection_interval_months')->default(3)->after('condition');
        });

        Schema::create('asset_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('asset_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->index();
            $table->string('condition', 20);
            $table->string('status', 30);
            $table->date('next_inspection_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_inspection_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->nullable()->constrained('asset_checklist_items')->nullOnDelete();
            $table->string('item_label');
            $table->boolean('is_ok');
            $table->timestamps();
        });

        $now = now();
        DB::table('assets')->pluck('id')->each(function (int $assetId) use ($now): void {
            DB::table('asset_checklist_items')->insert([
                [
                    'asset_id' => $assetId,
                    'label' => 'Periksa kondisi fisik alat',
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'asset_id' => $assetId,
                    'label' => 'Pastikan fungsi alat berjalan normal',
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inspection_results');
        Schema::dropIfExists('asset_inspections');
        Schema::dropIfExists('asset_checklist_items');

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn('inspection_interval_months');
            $table->string('pic', 120)->nullable()->after('condition');
        });
    }
};
