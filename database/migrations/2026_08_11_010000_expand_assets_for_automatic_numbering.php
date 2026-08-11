<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORIES = ['WLD', 'MSR', 'NDT', 'TOL', 'PPE', 'GAS', 'MAT', 'FAC'];

    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('category_code', 3)->nullable()->after('asset_code')->index();
            $table->string('brand', 120)->nullable()->after('equipment_name');
            $table->string('model', 120)->nullable()->after('brand');
            $table->unsignedInteger('quantity')->default(1)->after('serial_number');
            $table->unsignedSmallInteger('purchase_year')->nullable()->after('quantity');
            $table->string('condition', 20)->default('good')->after('location')->index();
            $table->string('pic', 120)->nullable()->after('condition');
            $table->date('last_inspected_at')->nullable()->after('pic');
            $table->date('next_inspection_at')->nullable()->after('last_inspected_at')->index();
            $table->boolean('requires_calibration')->default(false)->after('status')->index();
        });

        Schema::create('asset_number_sequences', function (Blueprint $table): void {
            $table->string('category_code', 3)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        $highestNumbers = array_fill_keys(self::CATEGORIES, 0);

        DB::table('assets')->orderBy('id')->get()->each(function (object $asset) use (&$highestNumbers): void {
            preg_match('/^AWA-([A-Z]{3})-(\d+)$/', strtoupper((string) $asset->asset_code), $matches);
            $category = in_array($matches[1] ?? null, self::CATEGORIES, true)
                ? $matches[1]
                : ($asset->asset_type === 'measuring' ? 'MSR' : 'FAC');
            $number = isset($matches[2]) ? (int) $matches[2] : 0;

            $highestNumbers[$category] = max($highestNumbers[$category], $number);

            DB::table('assets')->where('id', $asset->id)->update([
                'category_code' => $category,
                'requires_calibration' => $asset->asset_type === 'measuring',
                'status' => match ($asset->status) {
                    'serviceable', 'calibrated' => 'active',
                    'calibration_due', 'out_of_calibration' => 'out_of_service',
                    default => $asset->status,
                },
            ]);
        });

        $now = now();
        DB::table('asset_number_sequences')->insert(array_map(
            fn (string $category): array => [
                'category_code' => $category,
                'last_number' => $highestNumbers[$category],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::CATEGORIES,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_number_sequences');

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn([
                'category_code',
                'brand',
                'model',
                'quantity',
                'purchase_year',
                'condition',
                'pic',
                'last_inspected_at',
                'next_inspection_at',
                'requires_calibration',
            ]);
        });
    }
};
