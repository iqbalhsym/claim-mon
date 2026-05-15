<?php

namespace App\Imports;

use App\Models\MedicalRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MedicalRecordsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if (!isset($row['billing_no']) || $row['billing_no'] === null) {
            return null;
        }

        // Cek duplikat data No RM sesuai permintaan: jika No RM sudah ada, skip (tidak import)
        if (!empty($row['no_rm'])) {
            $exists = MedicalRecord::where('no_rm', $row['no_rm'])->exists();
            if ($exists) {
                return null;
            }
        }

        $isLengkap = isset($row['status_berkas_is_rm_lengkap']) 
                     ? (strtoupper(trim($row['status_berkas_is_rm_lengkap'])) === 'LENGKAP' ? true : false)
                     : false;
        
        $kembaliRM = isset($row['kembali_ke_rm_yatidak'])
                     ? (strtoupper(trim($row['kembali_ke_rm_yatidak'])) === 'YA' ? true : false)
                     : false;
        
        $analisa = isset($row['analisa_yatidak'])
                     ? (strtoupper(trim($row['analisa_yatidak'])) === 'YA' ? true : false)
                     : false;

        return new MedicalRecord([
            'billing_no'           => $row['billing_no'] ?? null,
            'no_rm'                => $row['no_rm'] ?? null,
            'nama_pasien'          => $row['nama_pasien'] ?? null,
            'guarantor'            => $row['guarantor'] ?? null,
            'tanggal_masuk'        => $this->transformDate($row['tanggal_masuk'] ?? null),
            'tanggal_pulang'       => $this->transformDate($row['tanggal_pulang'] ?? null),
            'ruangan_afya'         => $row['ruangan_afya'] ?? null,
            'ruangan'              => $row['ruangan'] ?? null,
            'status_kembali_rm'    => $kembaliRM,
            'tanggal_kembali_rm'   => $this->transformDate($row['tgl_kembali_rm'] ?? null),
            'status_analisa'       => $analisa,
            'tanggal_analisa'      => $this->transformDate($row['tgl_analisa'] ?? null),
            'is_rm_lengkap'        => $isLengkap,
            'laporan_pembedahan'   => $row['laporan_pembedahan'] ?? null,
            'persetujuan_tindakan' => $row['persetujuan_tindakan'] ?? null,
            // Bungkus data flat ke format Grouped JSON
            'formulir_rawat_inap'  => $this->wrapInDefaultGroup($row['rawat_inap'] ?? '', $row['ruangan'] ?? 'Ruangan Utama'),
            'kelengkapan_dokter'   => $this->wrapInDefaultGroup($row['kelengkapan_dokter'] ?? '', $row['nama_dokter_ksm'] ?? 'Dokter Utama'),
            
            'formulir_lain'        => !empty($row['formulir_lain_lain'])
                ? array_map(fn($n) => ['nama' => trim($n), 'status' => 'belum_selesai'], explode(',', $row['formulir_lain_lain']))
                : [],
            'nama_dokter'          => $row['nama_dokter_ksm'] ?? null,
            'keterangan_formulir'  => $row['keterangan_formulir_lain_lain'] ?? null,
        ]);
    }

    /**
     * Bungkus list formulir (koma) ke dalam satu grup default
     */
    private function wrapInDefaultGroup($value, $groupName)
    {
        if (empty($value)) return [];
        
        $items = array_map(function($item) {
            return [
                'nama' => trim($item),
                'is_kembali' => false,
                'tanggal_kembali' => null
            ];
        }, explode(',', $value));
        
        return [
            [
                'group_name' => $groupName,
                'items' => array_values(array_filter($items, fn($i) => !empty($i['nama'])))
            ]
        ];
    }

    /**
     * Ubah format tanggal Excel (serial number) ke format Carbon/DateTime.
     */
    private function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Jika numeric (format Excel serial), konversi menggunakan PHPSpreadsheet
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        // Jika sudah string date, biarkan Laravel/Carbon yang menangani
        return $value;
    }
}
