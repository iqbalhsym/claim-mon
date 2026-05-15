<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_geographies', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pasien');
            $table->string('no_rm')->nullable()->unique();
            $table->string('alamat')->nullable();
            $table->string('provinsi');
            $table->string('kabupaten_kota');
            $table->string('guarantor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_geographies');
    }
};
