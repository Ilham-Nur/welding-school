<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_external_loans', function (Blueprint $table): void {
            $table->foreignId('borrower_user_id')->nullable()->after('asset_id')->constrained('users')->nullOnDelete();
            $table->string('destination')->nullable()->change();
            $table->dateTime('due_at')->nullable()->change();
        });

        Schema::create('asset_external_loan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_external_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['asset_external_loan_id', 'asset_id'], 'asset_loan_item_unique');
            $table->index('asset_id');
        });

        DB::table('asset_external_loans')->orderBy('id')->each(function (object $loan): void {
            DB::table('asset_external_loan_items')->insert([
                'asset_external_loan_id' => $loan->id,
                'asset_id' => $loan->asset_id,
                'created_at' => $loan->created_at,
                'updated_at' => $loan->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_external_loan_items');

        Schema::table('asset_external_loans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('borrower_user_id');
            $table->string('destination')->nullable(false)->change();
            $table->dateTime('due_at')->nullable(false)->change();
        });
    }
};
