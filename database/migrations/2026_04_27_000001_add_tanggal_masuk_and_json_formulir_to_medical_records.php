<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            // Tambah tanggal masuk rawat inap setelah tanggal_pulang
            $table->date('tanggal_masuk')->nullable()->after('tanggal_pulang');

            // Ubah tipe kolom formulir_rawat_inap dan formulir_lain menjadi text
            // agar bisa menyimpan JSON array (multiple values)
            $table->text('formulir_rawat_inap')->nullable()->change();
            $table->text('formulir_lain')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn('tanggal_masuk');
            $table->string('formulir_rawat_inap')->nullable()->change();
            $table->string('formulir_lain')->nullable()->change();
        });
    }
};
