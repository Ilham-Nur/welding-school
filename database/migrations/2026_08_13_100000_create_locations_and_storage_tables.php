<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('type', 30)->default('area')->index();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_storage')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreignId('location_id')->nullable()->after('location')->constrained()->nullOnDelete();
        });

        $makeCode = function (string $name): string {
            $base = Str::upper(Str::slug($name, '-')) ?: 'LOKASI';
            $code = Str::limit($base, 24, '');
            $candidate = $code;
            $suffix = 1;

            while (DB::table('locations')->where('code', $candidate)->exists()) {
                $candidate = Str::limit($code, 20, '').'-'.(++$suffix);
            }

            return $candidate;
        };

        DB::table('assets')
            ->select('location')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->get()
            ->each(function (object $row) use ($makeCode): void {
                $rawLocation = trim((string) $row->location);
                $isStorage = (strcasecmp($rawLocation, 'Tool Storage Area') === 0) || str_contains(strtolower($rawLocation), 'storage');

                $parts = preg_split('/\s*[\x{2013}\x{2014}\-\/]\s*/u', $rawLocation, 2);

                if (is_array($parts) && count($parts) === 2 && ! empty(trim($parts[0])) && ! empty(trim($parts[1]))) {
                    $parentName = trim($parts[0]);
                    $childName = trim($parts[1]);

                    $parent = DB::table('locations')->whereNull('parent_id')->where('name', $parentName)->first();
                    if (! $parent) {
                        $parentId = DB::table('locations')->insertGetId([
                            'code' => $makeCode($parentName),
                            'name' => $parentName,
                            'type' => 'area',
                            'parent_id' => null,
                            'is_storage' => false,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $parentId = $parent->id;
                    }

                    $child = DB::table('locations')->where('parent_id', $parentId)->where('name', $childName)->first();
                    if (! $child) {
                        $childId = DB::table('locations')->insertGetId([
                            'code' => $makeCode($parentName.'-'.$childName),
                            'name' => $childName,
                            'type' => 'area',
                            'parent_id' => $parentId,
                            'is_storage' => false,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $childId = $child->id;
                    }

                    DB::table('assets')->where('location', $row->location)->update([
                        'location_id' => $childId,
                        'location' => $parentName.' / '.$childName,
                    ]);
                } else {
                    $loc = DB::table('locations')->whereNull('parent_id')->where('name', $rawLocation)->first();
                    if (! $loc) {
                        $locId = DB::table('locations')->insertGetId([
                            'code' => $makeCode($rawLocation),
                            'name' => $rawLocation,
                            'type' => 'area',
                            'parent_id' => null,
                            'is_storage' => $isStorage,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $locId = $loc->id;
                    }

                    DB::table('assets')->where('location', $row->location)->update([
                        'location_id' => $locId,
                        'location' => $rawLocation,
                    ]);
                }
            });

        Schema::create('storage_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('category', 80)->index();
            $table->string('unit', 30);
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('storage_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('storage_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->timestamps();
            $table->unique(['storage_item_id', 'location_id']);
        });

        Schema::create('storage_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->unique();
            $table->string('type', 20)->index();
            $table->date('transaction_date')->index();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier')->nullable();
            $table->string('reference')->nullable();
            $table->string('purpose')->nullable();
            $table->string('status', 20)->default('posted')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_transaction_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('storage_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('storage_item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 16, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_external_loans', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->string('borrower_name');
            $table->string('borrower_contact', 100)->nullable();
            $table->string('organization')->nullable();
            $table->string('destination');
            $table->text('purpose');
            $table->dateTime('loaned_at')->index();
            $table->dateTime('due_at')->index();
            $table->dateTime('returned_at')->nullable();
            $table->string('condition_out', 20)->default('good');
            $table->string('condition_in', 20)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('storage_stock_opnames', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->date('counted_at')->index();
            $table->string('status', 20)->default('counting')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_stock_opname_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('storage_stock_opnames')->cascadeOnDelete();
            $table->foreignId('storage_item_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 14, 3);
            $table->decimal('counted_quantity', 14, 3)->nullable();
            $table->decimal('difference', 14, 3)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'storage_item_id'], 'stock_opname_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_stock_opname_lines');
        Schema::dropIfExists('storage_stock_opnames');
        Schema::dropIfExists('asset_external_loans');
        Schema::dropIfExists('storage_transaction_lines');
        Schema::dropIfExists('storage_transactions');
        Schema::dropIfExists('storage_stocks');
        Schema::dropIfExists('storage_items');
        Schema::table('assets', fn (Blueprint $table) => $table->dropConstrainedForeignId('location_id'));
        Schema::dropIfExists('locations');
    }
};
