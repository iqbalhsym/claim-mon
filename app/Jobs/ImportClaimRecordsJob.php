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

        if (!file_exists($this->filePath)) {
            $this->cleanup();
            return;
        }

        $headers = []; // index => headerName
        $colMap = []; // letter => index
        for ($i = 1; $i <= 150; $i++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $colMap[$letter] = $i - 1;
        }

        $batch = [];
        $batchSize = 250;
        $totalInserted = 0;
        $now = now()->toDateTimeString();

        // Pre-load KSM lookup map to prevent N+1 query overhead
        \App\Models\Doctor::resolveKsm('');

        \App\Services\FastXlsxReader::readRows($this->filePath, function(array $cells, int $rowNumber) use (
            &$headers, $colMap, &$batch, $batchSize, &$totalInserted, $now
        ) {
            if ($rowNumber === 1) {
                // Header row
                foreach ($cells as $idx => $val) {
                    $headerVal = trim((string)$val);
                    if ($headerVal !== '') {
                        $headers[$idx] = $headerVal;
                    }
                }
                return;
            }

            // Get values using mapped letters/indices
            $noRm = isset($colMap['AU']) && isset($cells[$colMap['AU']]) ? trim((string)$cells[$colMap['AU']]) : '';
            $namaPasien = isset($colMap['AT']) && isset($cells[$colMap['AT']]) ? trim((string)$cells[$colMap['AT']]) : '';

            if (empty($noRm) && empty($namaPasien)) {
                return;
            }

            $rawAdmission = isset($colMap['F']) && isset($cells[$colMap['F']]) ? $cells[$colMap['F']] : null;
            $rawDischarge = isset($colMap['G']) && isset($cells[$colMap['G']]) ? $cells[$colMap['G']] : null;

            $admissionDate = $this->parseExcelDate($rawAdmission);
            $dischargeDate = $this->parseExcelDate($rawDischarge);

            $inacbg = isset($colMap['T']) && isset($cells[$colMap['T']]) ? trim((string)$cells[$colMap['T']]) : '';
            $severity = ClaimRecord::parseSeverity($inacbg);
            $dpjp = isset($colMap['AX']) && isset($cells[$colMap['AX']]) ? trim((string)$cells[$colMap['AX']]) : '';
            $ksm = \App\Models\Doctor::resolveKsm($dpjp);

            $totalTarif = isset($colMap['AM']) && isset($cells[$colMap['AM']]) ? (float)$cells[$colMap['AM']] : 0.0;
            $tarifRs = isset($colMap['AN']) && isset($cells[$colMap['AN']]) ? (float)$cells[$colMap['AN']] : 0.0;
            $selisih = $totalTarif - $tarifRs;

            // Build raw data using only columns up to actual highest header column
            $rawData = [];
            foreach ($headers as $idx => $headerName) {
                $rawData[$headerName] = $cells[$idx] ?? null;
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
        });

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

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
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
