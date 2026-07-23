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
        'prosedur_non_bedah',
        'prosedur_bedah',
        'konsultasi',
        'tenaga_ahli',
        'keperawatan',
        'penunjang',
        'radiologi',
        'laboratorium',
        'pelayanan_darah',
        'rehabilitasi',
        'kamar_akomodasi',
        'rawat_intensif',
        'obat',
        'alkes',
        'bmhp',
        'sewa_alat',
        'obat_kronis',
        'obat_kemo',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'total_tarif' => 'decimal:2',
        'tarif_rs' => 'decimal:2',
        'selisih' => 'decimal:2',
        'raw_data' => 'array',
        'prosedur_non_bedah' => 'decimal:2',
        'prosedur_bedah' => 'decimal:2',
        'konsultasi' => 'decimal:2',
        'tenaga_ahli' => 'decimal:2',
        'keperawatan' => 'decimal:2',
        'penunjang' => 'decimal:2',
        'radiologi' => 'decimal:2',
        'laboratorium' => 'decimal:2',
        'pelayanan_darah' => 'decimal:2',
        'rehabilitasi' => 'decimal:2',
        'kamar_akomodasi' => 'decimal:2',
        'rawat_intensif' => 'decimal:2',
        'obat' => 'decimal:2',
        'alkes' => 'decimal:2',
        'bmhp' => 'decimal:2',
        'sewa_alat' => 'decimal:2',
        'obat_kronis' => 'decimal:2',
        'obat_kemo' => 'decimal:2',
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
