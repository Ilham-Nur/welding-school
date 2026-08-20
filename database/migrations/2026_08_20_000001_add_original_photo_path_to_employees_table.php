<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'original_photo_path')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->string('original_photo_path', 255)->nullable()->after('photo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'original_photo_path')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropColumn('original_photo_path');
            });
        }
    }
};
