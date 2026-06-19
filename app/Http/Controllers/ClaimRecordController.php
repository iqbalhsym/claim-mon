<?php

namespace App\Http\Controllers;

use App\Models\ClaimRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ClaimRecordController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $severity = $request->query('severity');
        $sortBy = $request->query('sort_by', 'discharge_date');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'no_rm' => 'no_rm',
            'nama_pasien' => 'nama_pasien',
            'discharge_date' => 'discharge_date',
            'inacbg' => 'inacbg',
            'severity' => 'severity',
            'tarif_rs' => 'tarif_rs',
            'total_tarif' => 'total_tarif',
            'selisih' => 'selisih',
        ];

        $query = ClaimRecord::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pasien', 'ilike', "%{$search}%")
                  ->orWhere('no_rm', 'ilike', "%{$search}%")
                  ->orWhere('inacbg', 'ilike', "%{$search}%")
                  ->orWhere('dpjp', 'ilike', "%{$search}%");
            });
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        $totalFiltered = $query->count();

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortDir);
        } else {
            $query->orderBy('discharge_date', 'desc');
        }

        $records = $query->paginate(50);
        $totalRecords = ClaimRecord::count();

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $availableMonths = ClaimRecord::selectRaw("$monthExpr as month_key")
            ->whereNotNull('discharge_date')
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->pluck('month_key')
            ->filter()
            ->values();

        return view('claim_records.index', compact('records', 'totalRecords', 'totalFiltered', 'search', 'severity', 'availableMonths', 'sortBy', 'sortDir'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file_excel');
        $filePath = $file->getRealPath();

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestRow();

            $batch = [];
            $batchSize = 250;
            $totalInserted = 0;
            $now = now()->toDateTimeString();

            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
            $headers = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $headers[$colLetter] = trim($sheet->getCell($colLetter . '1')->getValue() ?? '');
            }

            for ($row = 2; $row <= $highestRow; $row++) {
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

                // Build raw data
                $rawData = [];
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $headerName = $headers[$colLetter] ?? $colLetter;
                    if (!empty($headerName)) {
                        $rawData[$headerName] = $sheet->getCell($colLetter . $row)->getValue();
                    }
                }

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
                    'raw_data' => json_encode($rawData),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    ClaimRecord::insert($batch);
                    $totalInserted += count($batch);
                    $batch = [];
                }
            }

            if (count($batch) > 0) {
                ClaimRecord::insert($batch);
                $totalInserted += count($batch);
            }

            return redirect()->route('claim-records.index')->with('success', "Berhasil mengimpor {$totalInserted} data klaim.");
        } catch (\Exception $e) {
            return redirect()->route('claim-records.index')->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function truncate(Request $request)
    {
        $deleteMonth = $request->input('delete_month', 'all');

        if ($deleteMonth === 'all') {
            ClaimRecord::truncate();
            return redirect()->route('claim-records.index')->with('success', 'Semua data klaim berhasil dihapus.');
        } else {
            $driver = DB::connection()->getDriverName();
            $monthExpr = $driver === 'pgsql'
                ? "to_char(discharge_date, 'YYYY-MM')"
                : "strftime('%Y-%m', discharge_date)";

            $deletedCount = ClaimRecord::whereRaw("$monthExpr = ?", [$deleteMonth])->delete();

            try {
                $carbon = Carbon::createFromFormat('Y-m', $deleteMonth);
                $monthLabel = $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthLabel = $deleteMonth;
            }

            return redirect()->route('claim-records.index')->with('success', "Berhasil menghapus {$deletedCount} data klaim untuk bulan {$monthLabel}.");
        }
    }

    public function dpjpReport(Request $request)
    {
        $selectedMonth = $request->query('month');

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        // Get available months for dropdown filter
        $availableMonths = ClaimRecord::selectRaw("$monthExpr as month_key")
            ->whereNotNull('discharge_date')
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->pluck('month_key')
            ->filter()
            ->values();

        $query = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            dpjp,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ");

        if ($selectedMonth) {
            $query->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $stats = $query->groupBy('month_key', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('dpjp', 'asc')
            ->get();

        // Query statistik per KSM
        $queryKsm = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            ksm,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ");

        if ($selectedMonth) {
            $queryKsm->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $ksmStats = $queryKsm->groupBy('month_key', 'ksm')
            ->orderBy('month_key', 'desc')
            ->orderBy('ksm', 'asc')
            ->get();

        // Calculate grand totals
        $grandTotalPatients = $stats->sum('patient_count');
        $grandTotalTarif = $stats->sum('total_total_tarif');
        $grandTotalRs = $stats->sum('total_tarif_rs');
        $grandTotalSelisih = $stats->sum('total_selisih');

        // Query detail dokter per KSM untuk modal detail
        $queryKsmDetails = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            ksm,
            dpjp,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ");

        if ($selectedMonth) {
            $queryKsmDetails->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $ksmDetails = $queryKsmDetails->groupBy('month_key', 'ksm', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('ksm', 'asc')
            ->orderBy('dpjp', 'asc')
            ->get();

        $ksmDetailsGrouped = [];
        foreach ($ksmDetails as $detail) {
            $mKey = $detail->month_key;
            $kKey = $detail->ksm ?: 'Tidak Terdaftar/Lain-lain';
            $ksmDetailsGrouped[$mKey][$kKey][] = [
                'dpjp' => $detail->dpjp ?: 'Tanpa Nama Dokter',
                'patient_count' => (int)$detail->patient_count,
                'total_tarif' => (float)$detail->total_total_tarif,
                'tarif_rs' => (float)$detail->total_tarif_rs,
                'selisih' => (float)$detail->total_selisih,
            ];
        }
        $ksmDetailsJson = json_encode($ksmDetailsGrouped);

        return view('claim_records.dpjp', compact(
            'stats',
            'ksmStats',
            'grandTotalPatients',
            'grandTotalTarif',
            'grandTotalRs',
            'grandTotalSelisih',
            'availableMonths',
            'selectedMonth',
            'ksmDetailsJson'
        ));
    }

    public function ksmReport(Request $request, $ksm)
    {
        $selectedMonth = $request->query('month');

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        // Get available months for dropdown filter
        $availableMonths = ClaimRecord::selectRaw("$monthExpr as month_key")
            ->whereNotNull('discharge_date')
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->pluck('month_key')
            ->filter()
            ->values();

        $query = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            ksm,
            dpjp,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ")
        ->where(function($q) use ($ksm) {
            if ($ksm === 'Tidak Terdaftar/Lain-lain' || $ksm === 'Lain-lain') {
                $q->whereNull('ksm')->orWhere('ksm', '')->orWhere('ksm', 'Lain-lain');
            } else {
                $q->where('ksm', $ksm);
            }
        });

        if ($selectedMonth) {
            $query->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $stats = $query->groupBy('month_key', 'ksm', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('patient_count', 'desc')
            ->get();

        $totalPatients = $stats->sum('patient_count');
        $totalTarif = $stats->sum('total_total_tarif');
        $totalRs = $stats->sum('total_tarif_rs');
        $totalBalance = $stats->sum('total_selisih');

        // Top 5 by Patient Count
        $top5Patients = $stats->sortByDesc('patient_count')->take(5)->values();

        // Top 5 by INACBG Tariff
        $top5Finance = $stats->sortByDesc('total_total_tarif')->take(5)->values();

        return view('claim_records.ksm_detail', compact(
            'ksm',
            'stats',
            'totalPatients',
            'totalTarif',
            'totalRs',
            'totalBalance',
            'top5Patients',
            'top5Finance',
            'availableMonths',
            'selectedMonth'
        ));
    }

    public function exportDpjp(Request $request)
    {
        $selectedMonth = $request->query('month');

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $query = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            dpjp,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ");

        if ($selectedMonth) {
            $query->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $stats = $query->groupBy('month_key', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('dpjp', 'asc')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan DPJP');

        // Title Block
        $sheet->setCellValue('A1', 'LAPORAN KINERJA DPJP (DOKTER UTAMA)');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);

        $periodeText = 'Semua Bulan/Tahun';
        if ($selectedMonth) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $selectedMonth);
                $periodeText = 'Bulan: ' . $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $periodeText = 'Bulan: ' . $selectedMonth;
            }
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Headers
        $headers = [
            'A4' => 'No',
            'B4' => 'Bulan',
            'C4' => 'Nama Dokter (DPJP)',
            'D4' => 'Jumlah Pasien',
            'E4' => 'Total Tarif INACBG',
            'F4' => 'Tarif RS (Rp)',
            'G4' => 'Balance Positif/Negatif'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EAEAEA');
        }

        $rowIdx = 5;
        $no = 1;

        $grandTotalPatients = 0;
        $grandTotalTarif = 0;
        $grandTotalRs = 0;
        $grandTotalSelisih = 0;

        foreach ($stats as $row) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $row->month_key);
                $monthName = $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthName = $row->month_key;
            }

            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValue('B' . $rowIdx, $monthName);
            $sheet->setCellValue('C' . $rowIdx, $row->dpjp ?: 'Tanpa Nama Dokter');
            $sheet->setCellValue('D' . $rowIdx, $row->patient_count);
            $sheet->setCellValue('E' . $rowIdx, $row->total_total_tarif);
            $sheet->setCellValue('F' . $rowIdx, $row->total_tarif_rs);
            $sheet->setCellValue('G' . $rowIdx, $row->total_selisih);

            // Alignments & formats
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            $grandTotalPatients += $row->patient_count;
            $grandTotalTarif += $row->total_total_tarif;
            $grandTotalRs += $row->total_tarif_rs;
            $grandTotalSelisih += $row->total_selisih;

            $rowIdx++;
        }

        // Grand Total row
        $sheet->setCellValue('A' . $rowIdx, '');
        $sheet->setCellValue('B' . $rowIdx, '');
        $sheet->setCellValue('C' . $rowIdx, 'Grand Total');
        $sheet->setCellValue('D' . $rowIdx, $grandTotalPatients);
        $sheet->setCellValue('E' . $rowIdx, $grandTotalTarif);
        $sheet->setCellValue('F' . $rowIdx, $grandTotalRs);
        $sheet->setCellValue('G' . $rowIdx, $grandTotalSelisih);

        $sheet->getStyle('C' . $rowIdx . ':G' . $rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

        // Auto column width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ================= SHEET 2: LAPORAN KSM =================
        $sheetKsm = $spreadsheet->createSheet();
        $sheetKsm->setTitle('Laporan KSM');

        // Query KSM stats untuk diekspor
        $queryKsm = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            ksm,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ");

        if ($selectedMonth) {
            $queryKsm->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $ksmStats = $queryKsm->groupBy('month_key', 'ksm')
            ->orderBy('month_key', 'desc')
            ->orderBy('ksm', 'asc')
            ->get();

        // Title Block
        $sheetKsm->setCellValue('A1', 'LAPORAN KINERJA PER KSM (SPESIALIS)');
        $sheetKsm->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        $sheetKsm->setCellValue('A2', $periodeText);
        $sheetKsm->getStyle('A2')->getFont()->setItalic(true);

        // Headers
        $ksmHeaders = [
            'A4' => 'No',
            'B4' => 'Bulan',
            'C4' => 'KSM / Spesialis',
            'D4' => 'Jumlah Pasien',
            'E4' => 'Total Tarif INACBG',
            'F4' => 'Tarif RS (Rp)',
            'G4' => 'Balance Positif/Negatif'
        ];

        foreach ($ksmHeaders as $cell => $text) {
            $sheetKsm->setCellValue($cell, $text);
            $sheetKsm->getStyle($cell)->getFont()->setBold(true);
            $sheetKsm->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheetKsm->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EAEAEA');
        }

        $rowIdxKsm = 5;
        $noKsm = 1;

        $grandTotalPatientsKsm = 0;
        $grandTotalTarifKsm = 0;
        $grandTotalRsKsm = 0;
        $grandTotalSelisihKsm = 0;

        foreach ($ksmStats as $row) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $row->month_key);
                $monthName = $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthName = $row->month_key;
            }

            $sheetKsm->setCellValue('A' . $rowIdxKsm, $noKsm++);
            $sheetKsm->setCellValue('B' . $rowIdxKsm, $monthName);
            $sheetKsm->setCellValue('C' . $rowIdxKsm, $row->ksm ?: 'Tidak Terdaftar/Lain-lain');
            $sheetKsm->setCellValue('D' . $rowIdxKsm, $row->patient_count);
            $sheetKsm->setCellValue('E' . $rowIdxKsm, $row->total_total_tarif);
            $sheetKsm->setCellValue('F' . $rowIdxKsm, $row->total_tarif_rs);
            $sheetKsm->setCellValue('G' . $rowIdxKsm, $row->total_selisih);

            // Alignments & formats
            $sheetKsm->getStyle('A' . $rowIdxKsm)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheetKsm->getStyle('D' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');
            $sheetKsm->getStyle('E' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');
            $sheetKsm->getStyle('F' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');
            $sheetKsm->getStyle('G' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');

            $grandTotalPatientsKsm += $row->patient_count;
            $grandTotalTarifKsm += $row->total_total_tarif;
            $grandTotalRsKsm += $row->total_tarif_rs;
            $grandTotalSelisihKsm += $row->total_selisih;

            $rowIdxKsm++;
        }

        // Grand Total row
        $sheetKsm->setCellValue('A' . $rowIdxKsm, '');
        $sheetKsm->setCellValue('B' . $rowIdxKsm, '');
        $sheetKsm->setCellValue('C' . $rowIdxKsm, 'Grand Total');
        $sheetKsm->setCellValue('D' . $rowIdxKsm, $grandTotalPatientsKsm);
        $sheetKsm->setCellValue('E' . $rowIdxKsm, $grandTotalTarifKsm);
        $sheetKsm->setCellValue('F' . $rowIdxKsm, $grandTotalRsKsm);
        $sheetKsm->setCellValue('G' . $rowIdxKsm, $grandTotalSelisihKsm);

        $sheetKsm->getStyle('C' . $rowIdxKsm . ':G' . $rowIdxKsm)->getFont()->setBold(true);
        $sheetKsm->getStyle('D' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');
        $sheetKsm->getStyle('E' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');
        $sheetKsm->getStyle('F' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');
        $sheetKsm->getStyle('G' . $rowIdxKsm)->getNumberFormat()->setFormatCode('#,##0');

        // Auto column width
        foreach (range('A', 'G') as $col) {
            $sheetKsm->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        
        $monthStr = 'semua_bulan';
        if ($selectedMonth) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $selectedMonth);
                $monthStr = strtolower($carbon->translatedFormat('F_Y'));
            } catch (\Exception $e) {
                $monthStr = strtolower(str_replace('-', '_', $selectedMonth));
            }
        }
        $fileName = 'laporan_dpjp_' . $monthStr . '.xlsx';

        return response()->stream(
            function() use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function export(Request $request)
    {
        $search = $request->query('search');
        $severity = $request->query('severity');

        $query = ClaimRecord::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pasien', 'ilike', "%{$search}%")
                  ->orWhere('no_rm', 'ilike', "%{$search}%")
                  ->orWhere('inacbg', 'ilike', "%{$search}%")
                  ->orWhere('dpjp', 'ilike', "%{$search}%");
            });
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        $records = $query->orderBy('discharge_date', 'desc')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Klaim');

        // Headers
        $headers = [
            'A1' => 'No. RM',
            'B1' => 'Nama Pasien',
            'C1' => 'Tanggal Pulang',
            'D1' => 'INACBG',
            'E1' => 'Severity',
            'F1' => 'DPJP (Dokter)',
            'G1' => 'Tarif RS',
            'H1' => 'Total Tarif INACBG',
            'I1' => 'Balance Positif/Negatif'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($records as $rec) {
            $sheet->setCellValue('A' . $row, $rec->no_rm);
            $sheet->setCellValue('B' . $row, $rec->nama_pasien);
            $sheet->setCellValue('C' . $row, $rec->discharge_date ? $rec->discharge_date->format('Y-m-d') : '-');
            $sheet->setCellValue('D' . $row, $rec->inacbg);
            $sheet->setCellValue('E' . $row, $rec->severity);
            $sheet->setCellValue('F' . $row, $rec->dpjp);
            $sheet->setCellValue('G' . $row, $rec->tarif_rs);
            $sheet->setCellValue('H' . $row, $rec->total_tarif);
            $sheet->setCellValue('I' . $row, $rec->selisih);

            // Format numeric columns
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        // Auto width
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        $severityStr = $severity ? '_severity_' . strtolower($severity) : '';
        $fileName = 'data_klaim_export' . $severityStr . '_' . date('Ymd_His') . '.xlsx';

        return response()->stream(
            function() use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function show($id)
    {
        $record = ClaimRecord::findOrFail($id);

        return response()->json([
            'id' => $record->id,
            'no_rm' => $record->no_rm,
            'nama_pasien' => $record->nama_pasien,
            'admission_date' => $record->admission_date ? $record->admission_date->format('Y-m-d') : '-',
            'discharge_date' => $record->discharge_date ? $record->discharge_date->format('Y-m-d') : '-',
            'inacbg' => $record->inacbg,
            'severity' => $record->severity,
            'dpjp' => $record->dpjp ?: 'Tanpa Nama Dokter',
            'ksm' => $record->ksm ?: 'Tidak Terdaftar/Lain-lain',
            'total_tarif' => (float)$record->total_tarif,
            'tarif_rs' => (float)$record->tarif_rs,
            'selisih' => (float)$record->selisih,
            'total_tarif_formatted' => 'Rp ' . number_format($record->total_tarif, 0, ',', '.'),
            'tarif_rs_formatted' => 'Rp ' . number_format($record->tarif_rs, 0, ',', '.'),
            'selisih_formatted' => 'Rp ' . number_format($record->selisih, 0, ',', '.'),
            'raw_data' => $record->raw_data,
        ]);
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
