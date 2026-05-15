<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_geographies', function (Blueprint $table) {
            $table->date('tanggal_kunjungan')->nullable()->after('guarantor');
            $table->string('kat_guarantor', 20)->nullable()->after('tanggal_kunjungan'); // JKN / Non JKN
        });
    }

    public function down(): void
    {
        Schema::table('patient_geographies', function (Blueprint $table) {
            $table->dropColumn(['tanggal_kunjungan', 'kat_guarantor']);
        });
    }
};
