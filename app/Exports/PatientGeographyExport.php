<?php

namespace App\Exports;

use App\Models\PatientGeography;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PatientGeographyExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return PatientGeography::select(
            'nama_pasien',
            'no_rm',
            'provinsi',
            'kabupaten_kota',
            'guarantor',
            'kat_guarantor',
            'tanggal_kunjungan'
        )->get()->map(function ($row) {
            return [
                'nama_pasien'       => $row->nama_pasien,
                'no_rm'             => $row->no_rm,
                'provinsi'          => $row->provinsi,
                'kabupaten_kota'    => $row->kabupaten_kota,
                'guarantor'         => $row->guarantor,
                'kat_guarantor'     => $row->kat_guarantor,
                'tanggal_kunjungan' => $row->tanggal_kunjungan?->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'nama_pasien',
            'no_rm',
            'provinsi',
            'kabupaten_kota',
            'guarantor',
            'kat_guarantor',
            'tanggal_kunjungan',
        ];
    }
}
