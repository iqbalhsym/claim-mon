<?php

namespace App\Imports;

use App\Models\PatientGeography;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class MasterPasienImport implements WithMultipleSheets
{
    public int $inserted = 0;
    public int $skipped  = 0;

    public function sheets(): array
    {
        return [
            '2025 - 03,2026' => new MasterPasienSheetImport($this),
        ];
    }
}

class MasterPasienSheetImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private MasterPasienImport $parent;

    public function __construct(MasterPasienImport $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Kolom yang dipakai dari master_pasien.xlsx:
     *  - medical_record_no  (A) → no_rm
     *  - patient_name       (B) → nama_pasien
     *  - kat_guarantor      (X) → kat_guarantor (JKN / Non JKN)
     *  - guarantor          (Y) → guarantor
     *  - kat_edited         (AL) → kabupaten_kota (kategori wilayah)
     *  - provinces          (AJ) → provinsi
     *  - created_date       (BD) → tanggal_kunjungan
     */
    public function model(array $row): ?PatientGeography
    {
        $noRm = trim((string) ($row['medical_record_no'] ?? ''));
        if (empty($noRm)) return null;

        // Skip duplikat
        if (PatientGeography::where('no_rm', $noRm)->exists()) {
            $this->parent->skipped++;
            return null;
        }

        // Kategori wilayah dari KAT EDITED
        $katEdited = trim((string) ($row['kat_edited'] ?? ''));
        if (empty($katEdited)) {
            $this->parent->skipped++;
            return null;
        }

        // Provinsi dari Provinces
        $provinsi = trim((string) ($row['provinces'] ?? ''));
        if (empty($provinsi)) $provinsi = $this->inferProvinsi($katEdited);

        // KAT Guarantor (JKN / Non JKN)
        $katGuarantor = strtoupper(trim((string) ($row['kat_guarantor'] ?? '')));
        $katGuarantor = ($katGuarantor === 'JKN') ? 'JKN' : 'Non JKN';

        // Guarantor detail
        $guarantor = trim((string) ($row['guarantor'] ?? ''));

        // Created Date — Excel serial number → Carbon date
        $tanggal = $this->parseExcelDate($row['created_date'] ?? null);

        // Filter periode: Januari 2025 s/d Maret 2026
        if ($tanggal) {
            $start = Carbon::create(2025, 1, 1);
            $end   = Carbon::create(2026, 3, 31);
            if ($tanggal->lt($start) || $tanggal->gt($end)) {
                $this->parent->skipped++;
                return null;
            }
        }

        $this->parent->inserted++;

        return new PatientGeography([
            'no_rm'             => $noRm,
            'nama_pasien'       => trim((string) ($row['patient_name'] ?? '')),
            'alamat'            => '-',
            'provinsi'          => $provinsi,
            'kabupaten_kota'    => $katEdited,
            'guarantor'         => $guarantor,
            'kat_guarantor'     => $katGuarantor,
            'tanggal_kunjungan' => $tanggal?->toDateString(),
        ]);
    }

    /**
     * Parse Excel serial date number to Carbon
     */
    private function parseExcelDate($value): ?Carbon
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt);
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Infer provinsi dari KAT EDITED jika Provinces kosong
     */
    private function inferProvinsi(string $katEdited): string
    {
        $kat = strtolower($katEdited);
        if (str_contains($kat, 'jakarta') || str_contains($kat, 'dki')) return 'DKI Jakarta';
        if (str_contains($kat, 'bekasi') || str_contains($kat, 'bogor') || str_contains($kat, 'depok') || str_contains($kat, 'bandung')) return 'Jawa Barat';
        if (str_contains($kat, 'tangerang') || str_contains($kat, 'serang') || str_contains($kat, 'cilegon') || str_contains($kat, 'banten')) return 'Banten';
        if (str_contains($kat, 'jawa barat')) return 'Jawa Barat';
        if (str_contains($kat, 'jawa tengah')) return 'Jawa Tengah';
        if (str_contains($kat, 'jawa timur')) return 'Jawa Timur';
        if (str_contains($kat, 'yogya')) return 'DI Yogyakarta';
        return 'Lainnya';
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
