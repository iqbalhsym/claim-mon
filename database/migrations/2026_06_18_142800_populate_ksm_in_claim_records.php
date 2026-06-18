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
        // Jalankan seeder master dokter jika masih kosong
        if (\App\Models\Doctor::count() === 0) {
            $seeder = new \Database\Seeders\DoctorSeeder();
            try {
                $seeder->run();
            } catch (\Exception $e) {
                // Silently ignore or fallback
            }
        }

        // Proses data klaim secara batch untuk mengisi kolom ksm
        \App\Models\ClaimRecord::chunk(500, function ($records) {
            foreach ($records as $record) {
                $record->ksm = \App\Models\Doctor::resolveKsm($record->dpjp);
                $record->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu membalikkan data karena rollback migrasi sebelumnya akan menghapus kolom ksm
    }
};
