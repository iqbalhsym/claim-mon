<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PatientGeography;
use App\Http\Controllers\PatientGeographyController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if ($row >= $this->startRow && $row < $this->endRow) {
            if (in_array($columnAddress, ['G', 'P'])) { // G=Kategori Alamat, P=Guarantor
                return true;
            }
        }
        return false;
    }
}

class PatientGeographySeeder extends Seeder
{
    public function run(): void
    {
        PatientGeography::truncate();

        $inputFileName = 'database/seeders/data_pasien.xlsx';
        
        $reader = IOFactory::createReaderForFile($inputFileName);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['BPJS RI']);
        
        $chunkSize = 1000;
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);

        $startRow = 2; // skip header
        $totalInserted = 0;
        $maxRows = 15000; // safe limit

        $this->command->info("Membaca file Excel kolom G dan P...");

        while ($startRow < $maxRows) {
            $chunkFilter->setRows($startRow, $chunkSize);
            
            try {
                $spreadsheet = $reader->load($inputFileName);
            } catch (\Exception $e) {
                break;
            }
            
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            
            if ($highestRow < $startRow) {
                break; // No more data in this chunk
            }

            $insertData = [];
            
            for ($row = $startRow; $row < $startRow + $chunkSize && $row <= $highestRow; $row++) {
                $kategoriAlamat = trim($sheet->getCell('G' . $row)->getValue() ?? '');
                $guarantor = trim($sheet->getCell('P' . $row)->getValue() ?? '');

                if (empty($kategoriAlamat) && empty($guarantor)) continue;

                $parsed = $this->normalizeCityAndProvince($kategoriAlamat);

                $insertData[] = [
                    'nama_pasien'    => 'Pasien ' . uniqid(),
                    'no_rm'          => 'RM-' . str_pad($row, 6, '0', STR_PAD_LEFT),
                    'alamat'         => '-',
                    'provinsi'       => $parsed['provinsi'],
                    'kabupaten_kota' => $parsed['kota'],
                    'guarantor'      => strtoupper($guarantor) === 'BPJS KESEHATAN' ? 'BPJS KESEHATAN' : 'NON BPJS',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }

            if (!empty($insertData)) {
                PatientGeography::insert($insertData);
                $totalInserted += count($insertData);
                $this->command->info("Telah memproses $totalInserted baris...");
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            
            $startRow += $chunkSize;
        }

        $this->command->info("Seeding Excel berhasil! Total data: $totalInserted");
    }

    private function normalizeCityAndProvince($rawKota)
    {
        $raw = strtoupper(trim($rawKota));
        
        // Exact literal mappings from actual KATEGORI ALAMAT
        $map = [
            'DKI JAKARTA'     => ['provinsi' => 'DKI Jakarta', 'kota' => 'DKI Jakarta'],
            'DEPOK'           => ['provinsi' => 'Jawa Barat',  'kota' => 'Kota Depok'],
            'JAWA BARAT'      => ['provinsi' => 'Jawa Barat',  'kota' => 'Jawa Barat'],
            'BANTEN'          => ['provinsi' => 'Banten',      'kota' => 'Banten'],
            'NON DKI JAKARTA' => ['provinsi' => 'Luar DKI',    'kota' => 'Non DKI Jakarta'],
            'LAINNYA'         => ['provinsi' => 'Lainnya',     'kota' => 'Lainnya'],
            'TIDAK DIKETAHUI' => ['provinsi' => 'Lainnya',     'kota' => 'Tidak Diketahui'],
        ];

        if (isset($map[$raw])) {
            return $map[$raw];
        }

        // Fallback generic mapping based on words
        if (strpos($raw, 'NON DKI') !== false) return ['provinsi' => 'Luar DKI', 'kota' => 'Non DKI Jakarta'];
        if (strpos($raw, 'JAKARTA') !== false) return ['provinsi' => 'DKI Jakarta', 'kota' => 'DKI Jakarta'];
        if (strpos($raw, 'BOGOR') !== false) return ['provinsi' => 'Jawa Barat', 'kota' => ucwords(strtolower($raw))];
        if (strpos($raw, 'DEPOK') !== false) return ['provinsi' => 'Jawa Barat', 'kota' => 'Kota Depok'];
        if (strpos($raw, 'TANGERANG') !== false) return ['provinsi' => 'Banten', 'kota' => ucwords(strtolower($raw))];
        if (strpos($raw, 'BEKASI') !== false) return ['provinsi' => 'Jawa Barat', 'kota' => ucwords(strtolower($raw))];
        if (strpos($raw, 'BANTEN') !== false) return ['provinsi' => 'Banten', 'kota' => 'Banten'];
        if (strpos($raw, 'JAWA BARAT') !== false) return ['provinsi' => 'Jawa Barat', 'kota' => 'Jawa Barat'];

        // Extreme fallback
        return [
            'provinsi' => 'Lainnya',
            'kota' => ucwords(strtolower($raw)) ?: 'Tidak Diketahui'
        ];
    }
}
