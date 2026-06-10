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
        'total_tarif',
        'tarif_rs',
        'selisih',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'total_tarif' => 'decimal:2',
        'tarif_rs' => 'decimal:2',
        'selisih' => 'decimal:2',
    ];

    /**
     * Ekstrak severity level (I, II, III) dari kode INACBG (contoh: N-1-40-I).
     */
    public static function parseSeverity(?string $inacbg): string
    {
        if (empty($inacbg)) {
            return 'Unknown';
        }

        $parts = explode('-', $inacbg);
        $last = strtoupper(trim(end($parts)));

        if (in_array($last, ['I', 'II', 'III'])) {
            return $last;
        }

        return 'Unknown';
    }
}
