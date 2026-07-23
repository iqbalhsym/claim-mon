<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ClaimRecord;
use App\Services\ChunkReadFilter;
use Carbon\Carbon;

class ImportClaimRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Max seconds before the job is considered failed */
    public int $timeout = 1800; // 30 minutes

    /** Don't auto-retry on failure — import is not idempotent without truncate */
    public int $tries = 1;

    public function __construct(
        private string $filePath,
        private string $jenisRawatSource,
        private string $originalFileName
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        $reader = IOFactory::createReaderForFile($this->filePath);
        $reader->setReadDataOnly(true);

        $sheetInfo = $reader->listWorksheetInfo($this->filePath);
        $highestRow = $sheetInfo[0]['totalRows'] ?? 0;

        if ($highestRow <= 1) {
            $this->cleanup();
            return;
        }

        $batch = [];
        $batchSize = 100;
        $chunkSize = 500;
        $totalInserted = 0;
        $now = now()->toDateTimeString();

        // Pre-load KSM lookup map to prevent N+1 query overhead
        \App\Models\Doctor::resolveKsm('');

        for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
            $filter = new ChunkReadFilter($startRow, $chunkSize);
            $reader->setReadFilter($filter);

            $spreadsheet = $reader->load($this->filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $endRow = min($startRow + $chunkSize - 1, $highestRow);

            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

            // Only track columns that actually have a header label
            $actualHighestColIndex = 0;
            $headers = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $headerVal = trim($sheet->getCell($colLetter . '1')->getValue() ?? '');
                if ($headerVal !== '') {
                    $headers[$colLetter] = $headerVal;
                    $actualHighestColIndex = $col;
                }
            }

            for ($row = $startRow; $row <= $endRow; $row++) {
                $noRm = trim($sheet->getCell('AU' . $row)->getValue() ?? '');
                $namaPasien = trim($sheet->getCell('AT' . $row)->getValue() ?? '');

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

                // Build raw data using only columns up to actual highest header column
                $rawData = [];
                for ($col = 1; $col <= $actualHighestColIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $headerName = $headers[$colLetter] ?? '';
                    if ($headerName !== '') {
                        $rawData[$headerName] = $sheet->getCell($colLetter . $row)->getValue();
                    }
                }

                $batch[] = [
                    'no_rm'               => $noRm,
                    'nama_pasien'         => $namaPasien,
                    'admission_date'      => $admissionDate?->toDateString(),
                    'discharge_date'      => $dischargeDate?->toDateString(),
                    'inacbg'              => $inacbg,
                    'severity'            => $severity,
                    'dpjp'                => $dpjp,
                    'ksm'                 => $ksm,
                    'total_tarif'         => $totalTarif,
                    'tarif_rs'            => $tarifRs,
                    'selisih'             => $selisih,
                    'jenis_rawat'         => ClaimRecord::parseJenisRawat($inacbg),
                    'raw_data'            => json_encode($rawData),
                    'prosedur_non_bedah'  => (float)($rawData['PROSEDUR_NON_BEDAH'] ?? 0),
                    'prosedur_bedah'      => (float)($rawData['PROSEDUR_BEDAH'] ?? 0),
                    'konsultasi'          => (float)($rawData['KONSULTASI'] ?? 0),
                    'tenaga_ahli'         => (float)($rawData['TENAGA_AHLI'] ?? 0),
                    'keperawatan'         => (float)($rawData['KEPERAWATAN'] ?? 0),
                    'penunjang'           => (float)($rawData['PENUNJANG'] ?? 0),
                    'radiologi'           => (float)($rawData['RADIOLOGI'] ?? 0),
                    'laboratorium'        => (float)($rawData['LABORATORIUM'] ?? 0),
                    'pelayanan_darah'     => (float)($rawData['PELAYANAN_DARAH'] ?? 0),
                    'rehabilitasi'        => (float)($rawData['REHABILITASI'] ?? 0),
                    'kamar_akomodasi'     => (float)($rawData['KAMAR_AKOMODASI'] ?? 0),
                    'rawat_intensif'      => (float)($rawData['RAWAT_INTENSIF'] ?? 0),
                    'obat'                => (float)($rawData['OBAT'] ?? 0),
                    'alkes'               => (float)($rawData['ALKES'] ?? 0),
                    'bmhp'                => (float)($rawData['BMHP'] ?? 0),
                    'sewa_alat'           => (float)($rawData['SEWA_ALAT'] ?? 0),
                    'obat_kronis'         => (float)($rawData['OBAT_KRONIS'] ?? 0),
                    'obat_kemo'           => (float)($rawData['OBAT_KEMO'] ?? 0),
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];

                if (count($batch) >= $batchSize) {
                    ClaimRecord::insert($batch);
                    $totalInserted += count($batch);
                    $batch = [];
                }
            }

            // Free worksheet memory per chunk
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
            gc_collect_cycles();
        }

        if (count($batch) > 0) {
            ClaimRecord::insert($batch);
            $totalInserted += count($batch);
        }

        // Invalidate cached cost report so next load is fresh
        Cache::forget('cost_report_data_ranap');
        Cache::forget('cost_report_data_rajal');

        // Store completion status for the user to see
        Cache::put(
            "import_status_{$this->jenisRawatSource}",
            ['status' => 'done', 'total' => $totalInserted, 'file' => $this->originalFileName],
            now()->addMinutes(30)
        );

        $this->cleanup();
    }

    public function failed(\Throwable $e): void
    {
        Cache::put(
            "import_status_{$this->jenisRawatSource}",
            ['status' => 'failed', 'error' => $e->getMessage()],
            now()->addMinutes(30)
        );

        $this->cleanup();
    }

    private function cleanup(): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }

    private function parseExcelDate(mixed $value): ?Carbon
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
