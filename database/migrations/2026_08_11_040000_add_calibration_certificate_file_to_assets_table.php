<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('calibration_certificate_path')->nullable()->after('certificate_number');
            $table->string('calibration_certificate_name')->nullable()->after('calibration_certificate_path');
            $table->string('calibration_certificate_mime', 100)->nullable()->after('calibration_certificate_name');
            $table->unsignedBigInteger('calibration_certificate_size')->nullable()->after('calibration_certificate_mime');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn([
                'calibration_certificate_path',
                'calibration_certificate_name',
                'calibration_certificate_mime',
                'calibration_certificate_size',
            ]);
        });
    }
};
