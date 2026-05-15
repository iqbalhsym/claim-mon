<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            // Kolom baru: kelengkapan dokter (JSON array of {nama, status})
            $table->text('kelengkapan_dokter')->nullable()->after('formulir_lain');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn('kelengkapan_dokter');
        });
    }
};
