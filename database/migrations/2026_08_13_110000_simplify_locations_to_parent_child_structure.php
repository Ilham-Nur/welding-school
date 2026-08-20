<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropUnique('locations_code_unique');
            $table->dropIndex('locations_type_index');
            $table->dropColumn(['code', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->string('code', 30)->nullable()->unique()->after('id');
            $table->string('type', 30)->default('area')->index()->after('name');
        });
    }
};
