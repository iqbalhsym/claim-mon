<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Models\BillingRecord;
use App\Services\ChunkReadFilter;
use Carbon\Carbon;

class ImportBillingRecordsJob implements ShouldQueue
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
        $fieldMap = [];
        $batch = [];
        $batchSize = 250;
        $totalInserted = 0;
        $now = now()->toDateTimeString();

        \App\Services\FastXlsxReader::readRows($this->filePath, function(array $cells, int $rowNumber) use (
            &$headers, &$fieldMap, &$batch, $batchSize, &$totalInserted, $now
        ) {
            if ($rowNumber === 1) {
                // Header row
                foreach ($cells as $idx => $val) {
                    $headerVal = trim((string)$val);
                    if ($headerVal !== '') {
                        $headers[$idx] = $headerVal;
                    }
                }

                // Build dynamic field mapping by matching header names
                $lookup = [
                    'no_rm'           => ['MRN', 'NO_RM', 'NO RM', 'NO. RM', 'MEDICAL RECORD NO.', 'MEDICAL RECORD NO', 'MEDICALRECORDNO'],
                    'nama_pasien'     => ['NAMA_PASIEN', 'NAMA PASIEN', 'PASIEN', 'NAME'],
                    'registered_date' => ['REGISTERED DATE', 'REGISTEREDDATE', 'ADMISSION_DATE', 'TGL_MASUK', 'TGL MASUK'],
                    'discharge_date'  => ['DISCHARGE DATE', 'DISCHARGEDATE', 'DISCHARGE_DATE', 'TGL_PULANG', 'TGL PULANG'],
                    'treatment_type'  => ['TREATMENT TYPE', 'TREATMENTTYPE', 'JENIS RAWAT', 'JENIS_RAWAT'],
                ];

                foreach ($lookup as $field => $candidates) {
                    foreach ($headers as $idx => $name) {
                        $normName = strtoupper(trim(str_replace(['_', '.', ' '], '', $name)));
                        foreach ($candidates as $cand) {
                            $normCand = strtoupper(trim(str_replace(['_', '.', ' '], '', $cand)));
                            if ($normName === $normCand) {
                                $fieldMap[$field] = $idx;
                                break 2;
                            }
                        }
                    }
                }
                return;
            }

            // Extract values using dynamic header index mapping
            $noRm = isset($fieldMap['no_rm']) && isset($cells[$fieldMap['no_rm']]) ? trim((string)$cells[$fieldMap['no_rm']]) : '';
            $namaPasien = isset($fieldMap['nama_pasien']) && isset($cells[$fieldMap['nama_pasien']]) ? trim((string)$cells[$fieldMap['nama_pasien']]) : '';

            if (empty($noRm) && empty($namaPasien)) {
                return;
            }

            $rawRegistered = isset($fieldMap['registered_date']) && isset($cells[$fieldMap['registered_date']]) ? $cells[$fieldMap['registered_date']] : null;
            $rawDischarge = isset($fieldMap['discharge_date']) && isset($cells[$fieldMap['discharge_date']]) ? $cells[$fieldMap['discharge_date']] : null;

            $registeredDate = $this->parseExcelDate($rawRegistered);
            $dischargeDate = $this->parseExcelDate($rawDischarge);

            $treatmentType = isset($fieldMap['treatment_type']) && isset($cells[$fieldMap['treatment_type']]) ? strtolower(trim((string)$cells[$fieldMap['treatment_type']])) : '';
            
            // Filter by Treatment Type if present in the Excel file
            if ($treatmentType !== '') {
                if ($this->jenisRawatSource === 'rajal' && !(str_contains($treatmentType, 'outpatient') || str_contains($treatmentType, 'rajal'))) {
                    return; // Skip row if importing for Rajal but Treatment Type is not Outpatient/Rajal
                }
                if ($this->jenisRawatSource === 'ranap' && !(str_contains($treatmentType, 'inpatient') || str_contains($treatmentType, 'ranap'))) {
                    return; // Skip row if importing for Ranap but Treatment Type is not Inpatient/Ranap
                }
            }
            
            // Keep records strictly separated by the menu tab (ranap/rajal) where the user uploaded the file
            $jenisRawat = $this->jenisRawatSource;

            // Build raw data using only columns up to actual highest header column
            $rawData = [];
            $rawDataUpper = [];
            foreach ($headers as $idx => $headerName) {
                $val = $cells[$idx] ?? null;
                $rawData[$headerName] = $val;
                $rawDataUpper[strtoupper($headerName)] = $val;
            }

            $batch[] = [
                'no_rm'                         => $noRm,
                'nama_pasien'                   => $namaPasien,
                'registered_date'               => $registeredDate?->toDateString(),
                'discharge_date'                => $dischargeDate?->toDateString(),
                'jenis_rawat'                   => $jenisRawat,
                'raw_data'                      => json_encode($rawData),
                'guarantor'                     => (string)($rawDataUpper['GUARANTOR'] ?? ''),
                'total_guarantee'               => (float)($rawDataUpper['TOTAL GUARANTEE'] ?? 0),
                'total_payment'                 => (float)($rawDataUpper['TOTAL PAYMENT'] ?? 0),
                'total_discount_per_item'       => (float)($rawDataUpper['TOTAL DISCOUNT PER ITEM'] ?? 0),
                'total_invoice_before_discount' => (float)($rawDataUpper['TOTAL INVOICE BEFORE DISCOUNT'] ?? 0),
                'hospital_guarantee'            => (float)($rawDataUpper['HOSPITAL GUARANTEE'] ?? 0),
                'total_must_be_paid'            => (float)($rawDataUpper['TOTAL MUST BE PAID'] ?? 0),
                'hospital_fee'                  => (float)($rawDataUpper['TOTAL MUST BE PAID - HOSPITAL FEE'] ?? ($rawDataUpper['HOSPITAL FEE'] ?? 0)),
                'doctor_guarantee'              => (float)($rawDataUpper['DOCTOR GUARANTEE'] ?? 0),
                'doctor_fee'                    => (float)($rawDataUpper['TOTAL MUST BE PAID - DOCTOR FEE'] ?? ($rawDataUpper['DOCTOR FEE'] ?? 0)),
                'created_at'                    => $now,
                'updated_at'                    => $now,
            ];

            if (count($batch) >= $batchSize) {
                BillingRecord::insert($batch);
                $totalInserted += count($batch);
                $batch = [];
            }
        });

        if (count($batch) > 0) {
            BillingRecord::insert($batch);
            $totalInserted += count($batch);
        }

        // Store completion status for the user to see
        Cache::put(
            "import_billing_status_{$this->jenisRawatSource}",
            ['status' => 'done', 'total' => $totalInserted, 'file' => $this->originalFileName],
            now()->addMinutes(30)
        );

        $this->cleanup();
    }

    public function failed(\Throwable $e): void
    {
        Cache::put(
            "import_billing_status_{$this->jenisRawatSource}",
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
