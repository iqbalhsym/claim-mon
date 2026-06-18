<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel dokter terlebih dahulu
        Doctor::truncate();

        $file = database_path('seeders/DATA DOKTER.xlsx');

        if (!file_exists($file)) {
            if ($this->command) {
                $this->command->error("File master dokter tidak ditemukan: $file");
            }
            return;
        }

        if ($this->command) {
            $this->command->info("Membuka file Excel master dokter: " . basename($file));
        }

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        if ($this->command) {
            $this->command->info("Total baris terdeteksi: $highestRow");
        }

        $batch = [];
        $batchSize = 100;
        $totalInserted = 0;
        $now = now()->toDateTimeString();

        for ($row = 2; $row <= $highestRow; $row++) {
            $nama = trim($sheet->getCell('A' . $row)->getValue() ?? '');
            $namaGelar = trim($sheet->getCell('B' . $row)->getValue() ?? '');
            $spesialis = trim($sheet->getCell('C' . $row)->getValue() ?? '');
            $ksm = trim($sheet->getCell('D' . $row)->getValue() ?? '');
            $status = trim($sheet->getCell('F' . $row)->getValue() ?? '');

            if (empty($nama)) {
                continue;
            }

            $batch[] = [
                'nama' => $nama,
                'nama_gelar' => $namaGelar ?: $nama,
                'spesialis' => $spesialis ?: null,
                'ksm' => $ksm ?: 'Lain-lain',
                'status' => $status ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                Doctor::insert($batch);
                $totalInserted += count($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            Doctor::insert($batch);
            $totalInserted += count($batch);
        }

        if ($this->command) {
            $this->command->info("Sukses mengimpor $totalInserted data dokter ke master tabel.");
        }
    }
}
