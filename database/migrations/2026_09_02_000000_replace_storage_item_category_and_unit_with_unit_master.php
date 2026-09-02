<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_units', function (Blueprint $table): void {
            $table->id();
            $table->string('symbol', 30)->unique();
            $table->string('name', 80)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        $symbols = collect(['pcs', 'kg', 'box', 'roll', 'unit', 'tabung'])
            ->merge(DB::table('storage_items')->pluck('unit'))
            ->map(fn ($symbol) => trim((string) $symbol))
            ->filter()
            ->unique(fn (string $symbol) => mb_strtolower($symbol))
            ->values();

        DB::table('storage_units')->insert($symbols->map(fn (string $symbol): array => [
            'symbol' => $symbol,
            'name' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        Schema::table('storage_items', function (Blueprint $table): void {
            $table->foreignId('storage_unit_id')->nullable()->after('name')->constrained('storage_units')->restrictOnDelete();
        });

        DB::table('storage_items')->orderBy('id')->each(function (object $item): void {
            $symbol = mb_strtolower(trim((string) $item->unit));
            $unitId = DB::table('storage_units')->whereRaw('LOWER(symbol) = ?', [$symbol])->value('id');

            DB::table('storage_items')->where('id', $item->id)->update(['storage_unit_id' => $unitId]);
        });

        Schema::table('storage_items', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::table('storage_items', function (Blueprint $table): void {
            $table->string('category', 80)->default('Consumable')->index();
            $table->string('unit', 30)->nullable();
        });

        DB::table('storage_items')->orderBy('id')->each(function (object $item): void {
            $symbol = DB::table('storage_units')->where('id', $item->storage_unit_id)->value('symbol');
            DB::table('storage_items')->where('id', $item->id)->update(['unit' => $symbol]);
        });

        Schema::table('storage_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('storage_unit_id');
        });

        Schema::dropIfExists('storage_units');
    }
};
