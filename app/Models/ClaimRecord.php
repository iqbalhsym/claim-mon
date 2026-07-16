<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimRecord extends Model
{
    use HasFactory;

    protected $table = 'claim_records';

    protected $fillable = [
        'no_rm',
        'nama_pasien',
        'admission_date',
        'discharge_date',
        'inacbg',
        'severity',
        'dpjp',
        'ksm',
        'total_tarif',
        'tarif_rs',
        'selisih',
        'jenis_rawat',
        'raw_data',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'total_tarif' => 'decimal:2',
        'tarif_rs' => 'decimal:2',
        'selisih' => 'decimal:2',
        'raw_data' => 'array',
    ];

    /**
     * Ekstrak severity level (I, II, III, 0) dari kode INACBG (contoh: N-1-40-I atau Q-5-44-0).
     */
    public static function parseSeverity(?string $inacbg): string
    {
        if (empty($inacbg)) {
            return 'Unknown';
        }

        $parts = explode('-', $inacbg);
        $last = strtoupper(trim(end($parts)));

        if (in_array($last, ['I', 'II', 'III', '0'])) {
            return $last;
        }

        return 'Unknown';
    }

    /**
     * Ekstrak jenis pelayanan rawat inap ('ranap') atau rawat jalan ('rajal') dari kode INACBG.
     * Rawat jalan (rajal) memiliki digit terakhir keparahan (severity level) = '0'.
     */
    public static function parseJenisRawat(?string $inacbg): string
    {
        if (empty($inacbg)) {
            return 'ranap';
        }

        $parts = explode('-', $inacbg);
        $last = strtoupper(trim(end($parts)));

        if ($last === '0') {
            return 'rajal';
        }

        return 'ranap';
    }
}
