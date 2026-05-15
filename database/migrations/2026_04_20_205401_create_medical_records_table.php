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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->string('billing_no')->nullable();
            $table->date('tanggal_pulang')->nullable();
            $table->boolean('status_kembali_rm')->default(false);
            $table->date('tanggal_kembali_rm')->nullable();
            $table->boolean('status_analisa')->default(false);
            $table->date('tanggal_analisa')->nullable();
            
            $table->string('no_rm')->nullable();
            $table->string('nama_pasien')->nullable();
            $table->string('guarantor')->nullable(); // Penjamin, e.g. BPJS KESEHATAN
            $table->string('ruangan_afya')->nullable();
            
            $table->boolean('is_rm_lengkap')->default(false);
            $table->string('laporan_pembedahan')->nullable(); // LENGKAP / TIDAK LENGKAP / KOSONG
            $table->string('persetujuan_tindakan')->nullable(); // LENGKAP / TIDAK LENGKAP / KOSONG
            
            $table->string('ruangan')->nullable();
            $table->string('nama_dokter')->nullable();
            
            $table->string('formulir_igd')->nullable();
            $table->string('formulir_rawat_inap')->nullable();
            $table->string('formulir_lain')->nullable();
            $table->text('keterangan_formulir')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
