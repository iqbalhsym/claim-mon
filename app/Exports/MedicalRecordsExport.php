<?php

namespace App\Exports;

use App\Models\MedicalRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MedicalRecordsExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return MedicalRecord::all([
            'billing_no',
            'no_rm',
            'nama_pasien',
            'guarantor',
            'tanggal_masuk',
            'tanggal_pulang',
            'ruangan_afya',
            'ruangan',
            'status_kembali_rm',
            'tanggal_kembali_rm',
            'status_analisa',
            'tanggal_analisa',
            'is_rm_lengkap',
            'laporan_pembedahan',
            'persetujuan_tindakan',
            'formulir_rawat_inap',
            'formulir_lain',
            'kelengkapan_dokter',
            'nama_dokter',
            'keterangan_formulir',
        ])->map(function ($record) {
            $record->status_kembali_rm = $record->status_kembali_rm ? 'YA' : 'TIDAK';
            $record->status_analisa    = $record->status_analisa ? 'YA' : 'TIDAK';
            $record->is_rm_lengkap     = $record->is_rm_lengkap ? 'LENGKAP' : 'TIDAK LENGKAP';

            // Flatten Grouped JSON
            $record->formulir_rawat_inap = $this->flattenGrouped($record->formulir_rawat_inap);
            $record->kelengkapan_dokter  = $this->flattenGrouped($record->kelengkapan_dokter);

            $record->formulir_lain = is_array($record->formulir_lain)
                ? implode(', ', collect($record->formulir_lain)->pluck('nama')->all())
                : ($record->formulir_lain ?? '');

            return $record;
        });
    }

    /**
     * Ubah array grouped JSON menjadi string flat untuk export
     */
    private function flattenGrouped($data): string
    {
        if (empty($data) || !is_array($data)) return '';
        
        $groups = [];
        foreach ($data as $group) {
            $groupName = $group['group_name'] ?? '';
            $items = collect($group['items'] ?? [])->map(function($item) {
                $status = ($item['is_kembali'] ?? false) ? '✓' : 'x';
                $date = ($item['tanggal_kembali'] ?? null) ? " ($item[tanggal_kembali])" : "";
                return ($item['nama'] ?? '') . " [$status$date]";
            })->implode(', ');
            
            if ($groupName) {
                $groups[] = "[$groupName: $items]";
            } else {
                $groups[] = $items;
            }
        }
        return implode('; ', $groups);
    }

    public function headings(): array
    {
        return [
            'Billing No',
            'No RM',
            'Nama Pasien',
            'Guarantor',
            'Tanggal Masuk',
            'Tanggal Pulang',
            'Ruangan Afya',
            'Ruangan',
            'Kembali ke RM (YA/TIDAK)',
            'Tgl Kembali RM',
            'Analisa (YA/TIDAK)',
            'Tgl Analisa',
            'Status Berkas (is_rm_lengkap)',
            'Laporan Pembedahan',
            'Persetujuan Tindakan',
            'Rawat Inap',
            'Formulir Lain-lain',
            'Kelengkapan Dokter',
            'Nama Dokter / KSM',
            'Keterangan Formulir Lain-lain'
        ];
    }
}
