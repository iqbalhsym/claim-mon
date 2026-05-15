<?php

namespace App\Imports;

use App\Models\PatientGeography;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class PatientGeographyImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $namaP    = trim($row['nama_pasien'] ?? '');
        $noRm     = trim($row['no_rm'] ?? '');
        $provinsi = trim($row['provinsi'] ?? '');
        $kota     = trim($row['kabupaten_kota'] ?? '');

        if (!$namaP || !$provinsi || !$kota) {
            return null;
        }

        // Filter duplikat berdasarkan no_rm jika ada
        if ($noRm !== '') {
            $exists = PatientGeography::where('no_rm', $noRm)->exists();
            if ($exists) {
                return null;
            }
        }

        // KAT Guarantor — support kolom baru maupun format lama
        $katGuarantor = strtoupper(trim($row['kat_guarantor'] ?? ''));
        if (!in_array($katGuarantor, ['JKN', 'NON JKN'])) {
            // Infer dari guarantor jika kat_guarantor tidak ada
            $guarantor = strtoupper(trim($row['guarantor'] ?? ''));
            $katGuarantor = str_contains($guarantor, 'BPJS') ? 'JKN' : 'Non JKN';
        } else {
            $katGuarantor = $katGuarantor === 'JKN' ? 'JKN' : 'Non JKN';
        }

        // Tanggal kunjungan
        $tanggal = null;
        $tglRaw  = $row['tanggal_kunjungan'] ?? null;
        if ($tglRaw) {
            try {
                $tanggal = Carbon::parse((string) $tglRaw)->toDateString();
            } catch (\Exception $e) {
                $tanggal = null;
            }
        }

        return new PatientGeography([
            'nama_pasien'       => $namaP,
            'no_rm'             => $noRm ?: null,
            'alamat'            => trim($row['alamat'] ?? '-'),
            'provinsi'          => $provinsi,
            'kabupaten_kota'    => $kota,
            'guarantor'         => trim($row['guarantor'] ?? ''),
            'kat_guarantor'     => $katGuarantor,
            'tanggal_kunjungan' => $tanggal,
        ]);
    }
}
