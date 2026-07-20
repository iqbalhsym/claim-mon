<?php

namespace App\Http\Controllers;

use App\Models\ClaimRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DashboardController extends Controller
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
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $baseQuery = ClaimRecord::where('jenis_rawat', $jenisRawat);

        if ($startDate) {
            $baseQuery->where('discharge_date', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->where('discharge_date', '<=', $endDate);
        }

        $totalRecord = (clone $baseQuery)->count();
        $totalTotalTarif = (clone $baseQuery)->sum('total_tarif');
        $totalTarifRs = (clone $baseQuery)->sum('tarif_rs');
        $totalSelisih = (clone $baseQuery)->sum('selisih');

        // Top 5 Doctors by patient count
        $topDoctors = (clone $baseQuery)->selectRaw('
            dpjp,
            count(*) as patient_count,
            sum(total_tarif) as total_tarif,
            sum(tarif_rs) as tarif_rs,
            sum(selisih) as total_selisih
        ')
        ->whereNotNull('dpjp')
        ->where('dpjp', '!=', '')
        ->groupBy('dpjp')
        ->orderByDesc('patient_count')
        ->limit(5)
        ->get();

        // Severity grouping by month
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $rawSeverity = (clone $baseQuery)->selectRaw("
            $monthExpr as month_key,
            severity,
            count(*) as total
        ")
        ->whereNotNull('discharge_date')
        ->groupBy('month_key', 'severity')
        ->orderBy('month_key', 'asc')
        ->get();

        // Process severity counts and percentages for Chart.js
        $months = [];
        $severityCounts = [
            'I' => [],
            'II' => [],
            'III' => [],
            '0' => [],
        ];
        $severityPercentages = [
            'I' => [],
            'II' => [],
            'III' => [],
            '0' => [],
        ];

        // Group raw data by month
        $groupedByMonth = [];
        foreach ($rawSeverity as $row) {
            $month = $row->month_key;
            if (!$month) continue;
            
            if (!isset($groupedByMonth[$month])) {
                $groupedByMonth[$month] = [
                    'I' => 0,
                    'II' => 0,
                    'III' => 0,
                    '0' => 0,
                    'total' => 0
                ];
            }
            if (isset($groupedByMonth[$month][$row->severity])) {
                $groupedByMonth[$month][$row->severity] = (int)$row->total;
                $groupedByMonth[$month]['total'] += (int)$row->total;
            }
        }

        // Generate final labels and data arrays
        foreach ($groupedByMonth as $month => $data) {
            // Convert 'YYYY-MM' to readable name e.g. "Januari 2026"
            try {
                $carbonDate = Carbon::createFromFormat('Y-m', $month);
                $months[] = $carbonDate->translatedFormat('F Y');
            } catch (\Exception $e) {
                $months[] = $month;
            }

            $total = $data['total'];

            $severityCounts['I'][] = $data['I'];
            $severityCounts['II'][] = $data['II'];
            $severityCounts['III'][] = $data['III'];
            $severityCounts['0'][] = $data['0'];

            if ($total > 0) {
                $severityPercentages['I'][] = round(($data['I'] / $total) * 100, 1);
                $severityPercentages['II'][] = round(($data['II'] / $total) * 100, 1);
                $severityPercentages['III'][] = round(($data['III'] / $total) * 100, 1);
                $severityPercentages['0'][] = round(($data['0'] / $total) * 100, 1);
            } else {
                $severityPercentages['I'][] = 0;
                $severityPercentages['II'][] = 0;
                $severityPercentages['III'][] = 0;
                $severityPercentages['0'][] = 0;
            }
        }

        // Recent 5 claims
        $recentRecords = (clone $baseQuery)->latest('discharge_date')->limit(5)->get();

        $topInacbgData = [];
        if ($jenisRawat === 'rajal') {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $inacbgExpr = "raw_data->>'DESKRIPSI_INACBG'";
            } else {
                $inacbgExpr = "json_extract(raw_data, '$.DESKRIPSI_INACBG')";
            }

            // 1. Get Top 10 cases overall
            $topCases = (clone $baseQuery)
                ->selectRaw("$inacbgExpr as deskripsi, count(*) as total")
                ->whereNotNull('inacbg')
                ->where('inacbg', '!=', '')
                ->groupBy('deskripsi')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('deskripsi')
                ->filter()
                ->values()
                ->toArray();

            // 2. Get monthly stats for these top cases
            $monthlyStats = (clone $baseQuery)
                ->selectRaw("$monthExpr as month_key, $inacbgExpr as deskripsi, count(*) as total")
                ->whereIn('raw_data->DESKRIPSI_INACBG', $topCases)
                ->groupBy('month_key', 'deskripsi')
                ->orderBy('month_key', 'asc')
                ->get();

            // 3. Get unique months
            $inacbgMonths = $monthlyStats->pluck('month_key')->unique()->sort()->values()->toArray();

            // 4. Pivot counts
            $pivotCounts = [];
            foreach ($monthlyStats as $row) {
                $pivotCounts[$row->month_key][$row->deskripsi] = (int)$row->total;
            }

            // 5. Build datasets for Chart.js
            $datasets = [];
            $colors = ['#0F5DA6', '#05a34a', '#fbbc06', '#ff3366', '#8a2be2', '#20b2aa', '#ff8c00', '#db7093', '#4682b4', '#d2691e'];

            foreach ($inacbgMonths as $idx => $mKey) {
                try {
                    $carbonDate = Carbon::createFromFormat('Y-m', $mKey);
                    $monthLabel = $carbonDate->translatedFormat('F Y');
                } catch (\Exception $e) {
                    $monthLabel = $mKey;
                }

                $dataPoints = [];
                foreach ($topCases as $case) {
                    $dataPoints[] = $pivotCounts[$mKey][$case] ?? 0;
                }

                $datasets[] = [
                    'label' => $monthLabel,
                    'data' => $dataPoints,
                    'backgroundColor' => $colors[$idx % count($colors)],
                    'borderRadius' => 4
                ];
            }

            $topInacbgData = [
                'labels' => $topCases,
                'datasets' => $datasets
            ];
        }

        return view('dashboard', compact(
            'totalRecord',
            'totalTotalTarif',
            'totalTarifRs',
            'totalSelisih',
            'topDoctors',
            'months',
            'severityCounts',
            'severityPercentages',
            'recentRecords',
            'startDate',
            'endDate',
            'jenisRawat',
            'topInacbgData'
        ));
    }

    public function exportExcel(Request $request, $jenisRawat)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $baseQuery = ClaimRecord::where('jenis_rawat', $jenisRawat);

        if ($startDate) {
            $baseQuery->where('discharge_date', '>=', $startDate);
        }
        if ($endDate) {
            $baseQuery->where('discharge_date', '<=', $endDate);
        }

        // Severity grouping by month
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'pgsql'
            ? "to_char(discharge_date, 'YYYY-MM')"
            : "strftime('%Y-%m', discharge_date)";

        $rawSeverity = (clone $baseQuery)->selectRaw("
            $monthExpr as month_key,
            severity,
            count(*) as total
        ")
        ->whereNotNull('discharge_date')
        ->groupBy('month_key', 'severity')
        ->orderBy('month_key', 'asc')
        ->get();

        // Group raw data by month
        $groupedByMonth = [];
        foreach ($rawSeverity as $row) {
            $month = $row->month_key;
            if (!$month) continue;
            
            if (!isset($groupedByMonth[$month])) {
                $groupedByMonth[$month] = [
                    'I' => 0,
                    'II' => 0,
                    'III' => 0,
                    '0' => 0,
                    'total' => 0
                ];
            }
            if (isset($groupedByMonth[$month][$row->severity])) {
                $groupedByMonth[$month][$row->severity] = (int)$row->total;
                $groupedByMonth[$month]['total'] += (int)$row->total;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Severity Bulanan');

        $rawatLabel = $jenisRawat === 'ranap' ? 'RAWAT INAP (RANAP)' : 'RAWAT JALAN (RAJAL)';

        // Title Block
        $sheet->setCellValue('A1', 'LAPORAN KASUS SEVERITY PER BULAN - ' . $rawatLabel);
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        
        $periodeText = 'Semua Periode';
        if ($startDate && $endDate) {
            $periodeText = 'Periode: ' . Carbon::parse($startDate)->format('d-m-Y') . ' s/d ' . Carbon::parse($endDate)->format('d-m-Y');
        } elseif ($startDate) {
            $periodeText = 'Periode Mulai: ' . Carbon::parse($startDate)->format('d-m-Y');
        } elseif ($endDate) {
            $periodeText = 'Periode Selesai: ' . Carbon::parse($endDate)->format('d-m-Y');
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Table Headers
        $headers = [
            'A4' => 'No',
            'B4' => 'Bulan',
            'C4' => 'Severity I (Jumlah)',
            'D4' => 'Severity I (%)',
            'E4' => 'Severity II (Jumlah)',
            'F4' => 'Severity II (%)',
            'G4' => 'Severity III (Jumlah)',
            'H4' => 'Severity III (%)',
            'I4' => 'Severity 0 (Jumlah)',
            'J4' => 'Severity 0 (%)',
            'K4' => 'Total Pasien',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EAEAEA');
        }

        $rowIdx = 5;
        $no = 1;

        $grandTotalI = 0;
        $grandTotalII = 0;
        $grandTotalIII = 0;
        $grandTotal0 = 0;
        $grandTotalAll = 0;

        foreach ($groupedByMonth as $month => $data) {
            try {
                $carbonDate = Carbon::createFromFormat('Y-m', $month);
                $monthName = $carbonDate->translatedFormat('F Y');
            } catch (\Exception $e) {
                $monthName = $month;
            }

            $total = $data['total'];
            $pctI = $total > 0 ? ($data['I'] / $total) : 0;
            $pctII = $total > 0 ? ($data['II'] / $total) : 0;
            $pctIII = $total > 0 ? ($data['III'] / $total) : 0;
            $pct0 = $total > 0 ? ($data['0'] / $total) : 0;

            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValue('B' . $rowIdx, $monthName);
            $sheet->setCellValue('C' . $rowIdx, $data['I']);
            $sheet->setCellValue('D' . $rowIdx, $pctI);
            $sheet->setCellValue('E' . $rowIdx, $data['II']);
            $sheet->setCellValue('F' . $rowIdx, $pctII);
            $sheet->setCellValue('G' . $rowIdx, $data['III']);
            $sheet->setCellValue('H' . $rowIdx, $pctIII);
            $sheet->setCellValue('I' . $rowIdx, $data['0']);
            $sheet->setCellValue('J' . $rowIdx, $pct0);
            $sheet->setCellValue('K' . $rowIdx, $total);

            // Alignments
            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Format numbers
            $sheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('I' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

            $grandTotalI += $data['I'];
            $grandTotalII += $data['II'];
            $grandTotalIII += $data['III'];
            $grandTotal0 += $data['0'];
            $grandTotalAll += $total;

            $rowIdx++;
        }

        // Grand Total Row
        $sheet->setCellValue('A' . $rowIdx, '');
        $sheet->setCellValue('B' . $rowIdx, 'Grand Total');
        $sheet->setCellValue('C' . $rowIdx, $grandTotalI);
        $sheet->setCellValue('D' . $rowIdx, $grandTotalAll > 0 ? ($grandTotalI / $grandTotalAll) : 0);
        $sheet->setCellValue('E' . $rowIdx, $grandTotalII);
        $sheet->setCellValue('F' . $rowIdx, $grandTotalAll > 0 ? ($grandTotalII / $grandTotalAll) : 0);
        $sheet->setCellValue('G' . $rowIdx, $grandTotalIII);
        $sheet->setCellValue('H' . $rowIdx, $grandTotalAll > 0 ? ($grandTotalIII / $grandTotalAll) : 0);
        $sheet->setCellValue('I' . $rowIdx, $grandTotal0);
        $sheet->setCellValue('J' . $rowIdx, $grandTotalAll > 0 ? ($grandTotal0 / $grandTotalAll) : 0);
        $sheet->setCellValue('K' . $rowIdx, $grandTotalAll);

        // Format Grand Total row
        $sheet->getStyle('B' . $rowIdx . ':K' . $rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('I' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('K' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

        // Auto-width
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        
        $periodeStr = 'semua_periode';
        if ($startDate && $endDate) {
            $periodeStr = Carbon::parse($startDate)->format('Ymd') . '_sd_' . Carbon::parse($endDate)->format('Ymd');
        } elseif ($startDate) {
            $periodeStr = 'mulai_' . Carbon::parse($startDate)->format('Ymd');
        } elseif ($endDate) {
            $periodeStr = 'sampai_' . Carbon::parse($endDate)->format('Ymd');
        }
        $fileName = 'severity_export_' . $jenisRawat . '_' . $periodeStr . '.xlsx';

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ];

        if ($request->has('download_token')) {
            $headers['Set-Cookie'] = 'download_token_' . $request->query('download_token') . '=completed; Path=/; Max-Age=60';
        }

        return response()->stream(
            function() use ($writer) {
                $writer->save('php://output');
            },
            200,
            $headers
        );
    }
}

