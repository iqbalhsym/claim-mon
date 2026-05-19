<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_no',
        'tanggal_masuk',
        'tanggal_pulang',
        'status_kembali_rm',
        'tanggal_kembali_rm',
        'status_analisa',
        'tanggal_analisa',
        'no_rm',
        'nama_pasien',
        'guarantor',
        'ruangan_afya',
        'is_rm_lengkap',
        'laporan_pembedahan',
        'persetujuan_tindakan',
        'ruangan',
        'nama_dokter',
        'formulir_igd',
        'formulir_rawat_inap',
        'formulir_lain',
        'kelengkapan_dokter',
        'keterangan_formulir',
    ];

    protected $casts = [
        'formulir_rawat_inap' => 'array',
        'formulir_lain'       => 'array',
        'kelengkapan_dokter'  => 'array',
    ];

    /**
     * Helper: Normalisasi data formulir bersarang (Grup -> Items)
     * Struktur target: [ { group_name: "...", items: [ {nama, is_kembali, tanggal_kembali} ] } ]
     */
    public static function normalizeGroupedFormulir(?array $data, string $defaultGroupName = ''): array
    {
        if (empty($data)) return [];

        // Cek apakah format lama (langsung array of items tanpa 'items' key)
        $isOldFormat = isset($data[0]) && !isset($data[0]['items']);

        if ($isOldFormat) {
            $items = collect($data)->map(function ($item) {
                if (is_array($item)) {
                    // Cek status lama 'sudah_selesai' -> is_kembali true
                    $status = $item['status'] ?? '';
                    return [
                        'nama' => $item['nama'] ?? '',
                        'is_kembali' => $status === 'sudah_selesai',
                        'tanggal_kembali' => null
                    ];
                }
                // Jika cuma string
                return [
                    'nama' => $item,
                    'is_kembali' => false,
                    'tanggal_kembali' => null
                ];
            })->all();

            return [
                [
                    'group_name' => $defaultGroupName,
                    'items' => $items
                ]
            ];
        }

        // Format baru: pastikan setiap grup punya key 'items' yang valid
        return collect($data)->map(function ($group) {
            return [
                'group_name' => $group['group_name'] ?? '',
                'items' => collect($group['items'] ?? [])->map(function ($item) {
                    return [
                        'nama'           => $item['nama'] ?? '',
                        'is_kembali'     => filter_var($item['is_kembali'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'tanggal_kembali'=> $item['tanggal_kembali'] ?? null,
                        'is_lengkap'     => filter_var($item['is_lengkap'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ];
                })->all()
            ];
        })->all();
    }

    /**
     * Helper lama (tetap dipertahankan untuk formulir_lain yang mungkin masih flat)
     */
    public static function normalizeFormulirItems(?array $items): array
    {
        if (!$items) return [];
        return collect($items)->map(function ($item) {
            if (is_array($item)) {
                return [
                    'nama'   => $item['nama'] ?? '',
                    'status' => $item['status'] ?? ($item['is_kembali'] ? 'sudah_selesai' : 'belum_selesai')
                ];
            }
            return ['nama' => $item, 'status' => 'belum_selesai'];
        })->values()->all();
    }
}
