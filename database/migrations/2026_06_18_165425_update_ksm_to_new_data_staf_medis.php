<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eksekusi DoctorSeeder baru untuk memuat master data staf medis terbaru
        $seeder = new \Database\Seeders\DoctorSeeder();
        try {
            $seeder->run();
        } catch (\Exception $e) {
            // Silently catch seeder errors if any
        }

        // 2. Perbarui kolom ksm di tabel claim_records berdasarkan data master baru secara chunk
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
        // Rollback data KSM lama tidak diperlukan karena ini adalah pembaharuan data internal
    }
};
