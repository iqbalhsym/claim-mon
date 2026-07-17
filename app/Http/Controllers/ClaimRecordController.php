<?php

namespace App\Http\Controllers;

use App\Models\ClaimRecord;
use Illuminate\Http\Request;
use App\Services\ChunkReadFilter;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ClaimRecordController extends Controller
{
    public function indexRanap(Request $request)
    {
        return $this->index($request, 'ranap');
    }

    public function indexRajal(Request $request)
    {
        return $this->index($request, 'rajal');
    }

    public function index(Request $request, $jenisRawat = 'ranap')
    {
        $search = $request->query('search');
        $severity = $request->query('severity');
        $month = $request->query('month');
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

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $query = ClaimRecord::where('jenis_rawat', $jenisRawat);

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

        if ($month) {
            $query->whereRaw("$monthExpr = ?", [$month]);
        }

        $totalFiltered = $query->count();

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortDir);
        } else {
            $query->orderBy('discharge_date', 'desc');
        }

        $records = $query->paginate(50);
        $totalRecords = ClaimRecord::where('jenis_rawat', $jenisRawat)->count();

        $availableMonths = ClaimRecord::selectRaw("$monthExpr as month_key")
            ->where('jenis_rawat', $jenisRawat)
            ->whereNotNull('discharge_date')
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->pluck('month_key')
            ->filter()
            ->values();

        return view('claim_records.index', compact('records', 'totalRecords', 'totalFiltered', 'search', 'severity', 'availableMonths', 'sortBy', 'sortDir', 'jenisRawat'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv'
        ]);

        $jenisRawatSource = $request->input('jenis_rawat', 'ranap');
        $file = $request->file('file_excel');
        $filePath = $file->getRealPath();

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);

            // Get total worksheet rows without reading the entire dataset
            $sheetInfo = $reader->listWorksheetInfo($filePath);
            $highestRow = $sheetInfo[0]['totalRows'] ?? 0;

            if ($highestRow <= 1) {
                return redirect()->route($jenisRawatSource === 'rajal' ? 'claim-records.rajal' : 'claim-records.ranap')
                    ->with('error', "File excel kosong atau hanya berisi header.");
            }

            $batch = [];
            $batchSize = 250;
            $chunkSize = 2000;
            $totalInserted = 0;
            $now = now()->toDateTimeString();

            // Populate static in-memory lookup map in Doctor model to prevent N+1 query overhead
            \App\Models\Doctor::resolveKsm('');

            for ($startRow = 2; $startRow <= $highestRow; $startRow += $chunkSize) {
                $filter = new ChunkReadFilter($startRow, $chunkSize);
                $reader->setReadFilter($filter);

                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                $endRow = min($startRow + $chunkSize - 1, $highestRow);

                $highestCol = $sheet->getHighestColumn();
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                $headers = [];
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $headers[$colLetter] = trim($sheet->getCell($colLetter . '1')->getValue() ?? '');
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
                        'jenis_rawat' => ClaimRecord::parseJenisRawat($inacbg),
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

                // Free worksheet memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet);
                gc_collect_cycles();
            }

            if (count($batch) > 0) {
                ClaimRecord::insert($batch);
                $totalInserted += count($batch);
            }

            return redirect()->route($jenisRawatSource === 'rajal' ? 'claim-records.rajal' : 'claim-records.ranap')
                ->with('success', "Berhasil mengimpor {$totalInserted} data klaim.");
        } catch (\Exception $e) {
            return redirect()->route($jenisRawatSource === 'rajal' ? 'claim-records.rajal' : 'claim-records.ranap')
                ->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function truncate(Request $request, $jenisRawat)
    {
        $deleteMonth = $request->input('delete_month', 'all');

        if ($deleteMonth === 'all') {
            ClaimRecord::where('jenis_rawat', $jenisRawat)->delete();
            return redirect()->route($jenisRawat === 'rajal' ? 'claim-records.rajal' : 'claim-records.ranap')
                ->with('success', 'Semua data klaim berhasil dihapus.');
        } else {
            $driver = DB::connection()->getDriverName();
            $monthExpr = $driver === 'pgsql'
                ? "to_char(discharge_date, 'YYYY-MM')"
                : "strftime('%Y-%m', discharge_date)";

            $deletedCount = ClaimRecord::where('jenis_rawat', $jenisRawat)
                ->whereRaw("$monthExpr = ?", [$deleteMonth])
                ->delete();

            try {
                $carbon = Carbon::createFromFormat('Y-m', $deleteMonth);
                $monthLabel = $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthLabel = $deleteMonth;
            }

            return redirect()->route($jenisRawat === 'rajal' ? 'claim-records.rajal' : 'claim-records.ranap')
                ->with('success', "Berhasil menghapus {$deletedCount} data klaim untuk bulan {$monthLabel}.");
        }
    }

    public function dpjpReportRanap(Request $request)
    {
        return $this->dpjpReport($request, 'ranap');
    }

    public function dpjpReportRajal(Request $request)
    {
        return $this->dpjpReport($request, 'rajal');
    }

    public function dpjpReport(Request $request, $jenisRawat = 'ranap')
    {
        $selectedMonth = $request->query('month');

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        // Get available months for dropdown filter
        $availableMonths = ClaimRecord::selectRaw("$monthExpr as month_key")
            ->where('jenis_rawat', $jenisRawat)
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
        ")->where('jenis_rawat', $jenisRawat);

        if ($selectedMonth) {
            $query->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $stats = $query->whereNotNull('dpjp')
            ->where('dpjp', '!=', '')
            ->groupBy('month_key', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('patient_count', 'desc')
            ->get();

        // Separate grouping by KSM for KSM list tab
        $ksmQuery = ClaimRecord::selectRaw("
            $monthExpr as month_key,
            ksm,
            count(*) as patient_count,
            sum(total_tarif) as total_total_tarif,
            sum(tarif_rs) as total_tarif_rs,
            sum(total_tarif - tarif_rs) as total_selisih
        ")->where('jenis_rawat', $jenisRawat);

        if ($selectedMonth) {
            $ksmQuery->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $ksmStats = $ksmQuery->groupBy('month_key', 'ksm')
            ->orderBy('month_key', 'desc')
            ->orderBy('patient_count', 'desc')
            ->get();

        // Calculate Grand Totals
        $grandTotalPatients = $stats->sum('patient_count');
        $grandTotalTarif = $stats->sum('total_total_tarif');
        $grandTotalRs = $stats->sum('total_tarif_rs');
        $grandTotalSelisih = $stats->sum('total_selisih');

        return view('claim_records.dpjp', compact(
            'stats',
            'ksmStats',
            'availableMonths',
            'selectedMonth',
            'grandTotalPatients',
            'grandTotalTarif',
            'grandTotalRs',
            'grandTotalSelisih',
            'jenisRawat'
        ));
    }

    public function ksmReport(Request $request, $jenisRawat, $ksm)
    {
        $selectedMonth = $request->query('month');

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        // Get available months for dropdown filter
        $availableMonths = ClaimRecord::selectRaw("$monthExpr as month_key")
            ->where('jenis_rawat', $jenisRawat)
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
        ->where('jenis_rawat', $jenisRawat)
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
            'selectedMonth',
            'jenisRawat'
        ));
    }

    public function exportDpjp(Request $request, $jenisRawat)
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
        ")->where('jenis_rawat', $jenisRawat);

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

        $rawatLabel = $jenisRawat === 'ranap' ? 'RAWAT INAP (RANAP)' : 'RAWAT JALAN (RAJAL)';

        // Title Block
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI KLAIM PER DPJP - ' . $rawatLabel);
        $sheet->getStyle('A1')->getFont()->setSize(13)->setBold(true);

        $periodeText = 'Semua Periode';
        if ($selectedMonth) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $selectedMonth);
                $periodeText = 'Bulan Pulang: ' . $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $periodeText = 'Bulan Pulang: ' . $selectedMonth;
            }
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Headers
        $headers = [
            'A4' => 'No',
            'B4' => 'Bulan Pulang',
            'C4' => 'Nama Dokter DPJP',
            'D4' => 'Jumlah Pasien',
            'E4' => 'Total Tarif INACBG (Rp)',
            'F4' => 'Total Tarif RS (Rp)',
            'G4' => 'Selisih (Rp)'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EAEAEA');
        }

        $rowIdx = 5;
        $no = 1;
        $totalPatients = 0;
        $totalTarif = 0;
        $totalRs = 0;
        $totalSelisih = 0;

        foreach ($stats as $row) {
            try {
                $carbonDate = Carbon::createFromFormat('Y-m', $row->month_key);
                $monthLabel = $carbonDate->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthLabel = $row->month_key;
            }

            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValue('B' . $rowIdx, $monthLabel);
            $sheet->setCellValue('C' . $rowIdx, $row->dpjp ?: 'Tanpa Nama Dokter');
            $sheet->setCellValue('D' . $rowIdx, $row->patient_count);
            $sheet->setCellValue('E' . $rowIdx, $row->total_total_tarif);
            $sheet->setCellValue('F' . $rowIdx, $row->total_tarif_rs);
            $sheet->setCellValue('G' . $rowIdx, $row->total_selisih);

            // Alignments & formats
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            // Selisih color-coding
            if ($row->total_selisih < 0) {
                $sheet->getStyle('G' . $rowIdx)->getFont()->getColor()->setARGB('FF3366');
            } else {
                $sheet->getStyle('G' . $rowIdx)->getFont()->getColor()->setARGB('05A34A');
            }

            $totalPatients += $row->patient_count;
            $totalTarif += $row->total_total_tarif;
            $totalRs += $row->total_tarif_rs;
            $totalSelisih += $row->total_selisih;

            $rowIdx++;
        }

        // Grand Total row
        $sheet->setCellValue('B' . $rowIdx, 'Grand Total');
        $sheet->setCellValue('D' . $rowIdx, $totalPatients);
        $sheet->setCellValue('E' . $rowIdx, $totalTarif);
        $sheet->setCellValue('F' . $rowIdx, $totalRs);
        $sheet->setCellValue('G' . $rowIdx, $totalSelisih);

        $sheet->getStyle('B' . $rowIdx . ':G' . $rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

        // Color coding for grand total selisih
        if ($totalSelisih < 0) {
            $sheet->getStyle('G' . $rowIdx)->getFont()->getColor()->setARGB('FF3366');
        } else {
            $sheet->getStyle('G' . $rowIdx)->getFont()->getColor()->setARGB('05A34A');
        }

        // Auto width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $monthStr = $selectedMonth ? '_' . $selectedMonth : '_semua_periode';
        $fileName = 'rekap_dpjp_' . $jenisRawat . $monthStr . '.xlsx';

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

    public function export(Request $request, $jenisRawat)
    {
        $search = $request->query('search');
        $severity = $request->query('severity');
        $month = $request->query('month');

        $query = ClaimRecord::where('jenis_rawat', $jenisRawat);

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

        if ($month) {
            $driver = DB::connection()->getDriverName();
            $monthExpr = $driver === 'pgsql'
                ? "to_char(discharge_date, 'YYYY-MM')"
                : "strftime('%Y-%m', discharge_date)";
            $query->whereRaw("$monthExpr = ?", [$month]);
        }

        // Optimize memory: Select only required columns and use lazy loading
        $records = $query->select([
            'no_rm',
            'nama_pasien',
            'discharge_date',
            'inacbg',
            'severity',
            'dpjp',
            'tarif_rs',
            'total_tarif',
            'selisih'
        ])->orderBy('discharge_date', 'desc')->lazy(1000);

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
        $fileName = 'data_klaim_export_' . $jenisRawat . $severityStr . '_' . date('Ymd_His') . '.xlsx';

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

    public function costReportRanap(Request $request)
    {
        return $this->costReport($request, 'ranap');
    }

    public function costReportRajal(Request $request)
    {
        return $this->costReport($request, 'rajal');
    }

    public function costReport(Request $request, $jenisRawat = 'ranap')
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $fields = [
            'PROSEDUR_NON_BEDAH', 'PROSEDUR_BEDAH', 'KONSULTASI', 'TENAGA_AHLI',
            'KEPERAWATAN', 'PENUNJANG', 'RADIOLOGI', 'LABORATORIUM', 'PELAYANAN_DARAH',
            'REHABILITASI', 'KAMAR_AKOMODASI', 'RAWAT_INTENSIF', 'OBAT', 'ALKES',
            'BMHP', 'SEWA_ALAT', 'OBAT_KRONIS', 'OBAT_KEMO'
        ];

        $selects = ["$monthExpr as month_key"];
        foreach ($fields as $field) {
            if ($driver === 'pgsql') {
                $selects[] = "SUM(COALESCE(CAST(raw_data->>'$field' AS NUMERIC), 0)) as " . strtolower($field);
            } else {
                $selects[] = "SUM(COALESCE(CAST(json_extract(raw_data, '$.$field') AS NUMERIC), 0)) as " . strtolower($field);
            }
        }

        $stats = ClaimRecord::where('jenis_rawat', $jenisRawat)
            ->whereNotNull('discharge_date')
            ->selectRaw(implode(', ', $selects))
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->get();

        $totals = [];
        foreach ($fields as $field) {
            $key = strtolower($field);
            $totals[$key] = $stats->sum($key);
        }

        return view('claim_records.cost_report', compact('stats', 'totals', 'fields', 'jenisRawat'));
    }

    public function exportCostReport(Request $request, $jenisRawat)
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $fields = [
            'PROSEDUR_NON_BEDAH', 'PROSEDUR_BEDAH', 'KONSULTASI', 'TENAGA_AHLI',
            'KEPERAWATAN', 'PENUNJANG', 'RADIOLOGI', 'LABORATORIUM', 'PELAYANAN_DARAH',
            'REHABILITASI', 'KAMAR_AKOMODASI', 'RAWAT_INTENSIF', 'OBAT', 'ALKES',
            'BMHP', 'SEWA_ALAT', 'OBAT_KRONIS', 'OBAT_KEMO'
        ];

        $selects = ["$monthExpr as month_key"];
        foreach ($fields as $field) {
            if ($driver === 'pgsql') {
                $selects[] = "SUM(COALESCE(CAST(raw_data->>'$field' AS NUMERIC), 0)) as " . strtolower($field);
            } else {
                $selects[] = "SUM(COALESCE(CAST(json_extract(raw_data, '$.$field') AS NUMERIC), 0)) as " . strtolower($field);
            }
        }

        $stats = ClaimRecord::where('jenis_rawat', $jenisRawat)
            ->whereNotNull('discharge_date')
            ->selectRaw(implode(', ', $selects))
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $titleText = 'Laporan Komponen Biaya ' . ($jenisRawat === 'ranap' ? 'Ranap' : 'Rajal');
        $sheet->setTitle('Komponen Biaya');

        // Headers: Row 1
        $sheet->setCellValue('A1', 'Komponen Biaya');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $colIdx = 2;
        foreach ($stats as $row) {
            try {
                $carbon = Carbon::createFromFormat('Y-m', $row->month_key);
                $monthLabel = $carbon->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthLabel = $row->month_key;
            }
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . '1', $monthLabel);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $colIdx++;
        }

        // Total Column at the end
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($colLetter . '1', 'Total');
        $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        $totalColIdx = $colIdx;

        // Initialize monthly sums
        $monthTotals = [];
        for ($c = 2; $c < $totalColIdx; $c++) {
            $monthTotals[$c] = 0;
        }
        $grandTotal = 0;

        // Write rows
        $rowNum = 2;
        foreach ($fields as $field) {
            $key = strtolower($field);
            $sheet->setCellValue('A' . $rowNum, ucwords(strtolower(str_replace('_', ' ', $field))));
            
            $colIdx = 2;
            $componentTotal = 0;
            foreach ($stats as $row) {
                $val = (float)($row->$key ?? 0);
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . $rowNum, $val);
                
                $componentTotal += $val;
                $monthTotals[$colIdx] += $val;
                $colIdx++;
            }

            // Total Column for this row
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColIdx);
            $sheet->setCellValue($colLetter . $rowNum, $componentTotal);
            $sheet->getStyle($colLetter . $rowNum)->getFont()->setBold(true);
            
            $grandTotal += $componentTotal;
            $rowNum++;
        }

        // Totals Row at the bottom
        $sheet->setCellValue('A' . $rowNum, 'Total');
        $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true);

        $colIdx = 2;
        foreach ($stats as $row) {
            $val = $monthTotals[$colIdx] ?? 0;
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . $rowNum, $val);
            $sheet->getStyle($colLetter . $rowNum)->getFont()->setBold(true);
            $colIdx++;
        }

        // Grand Total cell
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColIdx);
        $sheet->setCellValue($colLetter . $rowNum, $grandTotal);
        $sheet->getStyle($colLetter . $rowNum)->getFont()->setBold(true);

        // Auto-size columns
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $fileName = str_replace(' ', '_', strtolower($titleText)) . '_' . date('Ymd_His') . '.xlsx';

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
