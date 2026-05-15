<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterKKSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/master_kk.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan: $filePath");
            return;
        }

        $reader = IOFactory::load($filePath);

        // Pemetaan sheet name → type di master_data
        $sheetTypeMap = [
            'Master Dokter'                => 'dokter',
            'Master Kolom Rawat Inap'      => 'rawat_inap',
            'Master Kolom Ruangan'         => 'ruangan',
            'Master Kolom Form Lain-Lain'  => 'formulir_lain',
        ];

        $totalInserted = 0;
        $totalSkipped  = 0;

        foreach ($sheetTypeMap as $sheetName => $type) {
            $sheet = $reader->getSheetByName($sheetName);
            if (!$sheet) {
                $this->command->warn("Sheet '$sheetName' tidak ditemukan, dilewati.");
                continue;
            }

            $this->command->info("Memproses sheet: $sheetName → type: $type");

            $highestRow = $sheet->getHighestRow();
            $inserted   = 0;
            $skipped    = 0;

            for ($row = 2; $row <= $highestRow; $row++) { // Mulai baris 2 (baris 1 = header)
                $name = trim((string) $sheet->getCell('A' . $row)->getValue());

                if (empty($name)) continue;

                // Cek apakah sudah ada (hindari duplikat)
                $exists = MasterData::where('type', $type)
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $totalSkipped++;
                    continue;
                }

                MasterData::create([
                    'type' => $type,
                    'name' => $name,
                    'code' => null,
                ]);

                $inserted++;
                $totalInserted++;
            }

            $this->command->line("  ✓ Ditambahkan: $inserted | Dilewati (duplikat): $skipped");
        }

        $this->command->newLine();
        $this->command->info("Selesai! Total ditambahkan: $totalInserted | Total dilewati: $totalSkipped");
    }
}
