<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClaimRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ClaimRecordSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate claim records first
        ClaimRecord::truncate();

        $file = database_path('seeders/Ranap Jan 2026 txt import.xlsx');

        if (!file_exists($file)) {
            $this->command->error("File tidak ditemukan: $file");
            return;
        }

        $this->command->info("Membuka file Excel: " . basename($file));

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $this->command->info("Total baris terdeteksi: $highestRow");

        $batch = [];
        $batchSize = 250;
        $totalInserted = 0;
        $now = now()->toDateTimeString();

        for ($row = 2; $row <= $highestRow; $row++) {
            $noRm = trim($sheet->getCell('AU' . $row)->getValue() ?? '');
            $namaPasien = trim($sheet->getCell('AT' . $row)->getValue() ?? '');
            
            // Skip if no MRN (No RM)
            if (empty($noRm) && empty($namaPasien)) {
                continue;
            }

            $rawAdmission = $sheet->getCell('F' . $row)->getValue();
            $rawDischarge = $sheet->getCell('G' . $row)->getValue();
            
            $admissionDate = $this->parseExcelDate($rawAdmission);
            $dischargeDate = $this->parseExcelDate($rawDischarge);

            $inacbg = trim($sheet->getCell('T' . $row)->getValue() ?? '');
            $severity = ClaimRecord::parseSeverity($inacbg);
            $dpjp = trim($sheet->getCell('AX' . $row)->getValue() ?? '');
            $ksm = \App\Models\Doctor::resolveKsm($dpjp);

            $totalTarif = (float)($sheet->getCell('AM' . $row)->getValue() ?? 0);
            $tarifRs = (float)($sheet->getCell('AN' . $row)->getValue() ?? 0);
            $selisih = $totalTarif - $tarifRs;

            $batch[] = [
                'no_rm' => $noRm,
                'nama_pasien' => $namaPasien,
                'admission_date' => $admissionDate?->toDateString(),
                'discharge_date' => $dischargeDate?->toDateString(),
                'inacbg' => $inacbg,
                'severity' => $severity,
                'dpjp' => $dpjp,
                'ksm' => $ksm,
                'total_tarif' => $totalTarif,
                'tarif_rs' => $tarifRs,
                'selisih' => $selisih,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                ClaimRecord::insert($batch);
                $totalInserted += count($batch);
                $this->command->info("Telah memproses $totalInserted data...");
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            ClaimRecord::insert($batch);
            $totalInserted += count($batch);
        }

        $this->command->info("Sukses mengimpor $totalInserted data klaim.");
    }

    private function parseExcelDate($value): ?Carbon
    {
        if (empty($value) && $value !== '0') {
            return null;
        }

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
}
