<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fields = [
            'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi', 'tenaga_ahli',
            'keperawatan', 'penunjang', 'radiologi', 'laboratorium', 'pelayanan_darah',
            'rehabilitasi', 'kamar_akomodasi', 'rawat_intensif', 'obat', 'alkes',
            'bmhp', 'sewa_alat', 'obat_kronis', 'obat_kemo'
        ];

        Schema::table('claim_records', function (Blueprint $table) use ($fields) {
            foreach ($fields as $field) {
                $table->decimal($field, 15, 2)->default(0);
            }
        });

        // Backfill existing records from raw_data JSON
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $sets = [];
            foreach ($fields as $field) {
                $upperField = strtoupper($field);
                $sets[] = "$field = COALESCE(CAST(raw_data->>'$upperField' AS NUMERIC), 0)";
            }
            DB::statement("UPDATE claim_records SET " . implode(', ', $sets) . " WHERE raw_data IS NOT NULL");
        } elseif ($driver === 'sqlite') {
            $sets = [];
            foreach ($fields as $field) {
                $upperField = strtoupper($field);
                $sets[] = "$field = COALESCE(CAST(json_extract(raw_data, '$.$upperField') AS NUMERIC), 0)";
            }
            DB::statement("UPDATE claim_records SET " . implode(', ', $sets) . " WHERE raw_data IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fields = [
            'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi', 'tenaga_ahli',
            'keperawatan', 'penunjang', 'radiologi', 'laboratorium', 'pelayanan_darah',
            'rehabilitasi', 'kamar_akomodasi', 'rawat_intensif', 'obat', 'alkes',
            'bmhp', 'sewa_alat', 'obat_kronis', 'obat_kemo'
        ];

        Schema::table('claim_records', function (Blueprint $table) use ($fields) {
            $table->dropColumn($fields);
        });
    }
};
