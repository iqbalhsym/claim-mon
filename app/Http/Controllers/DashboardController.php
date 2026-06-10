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
    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $baseQuery = ClaimRecord::query();

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
        ];
        $severityPercentages = [
            'I' => [],
            'II' => [],
            'III' => [],
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

            if ($total > 0) {
                $severityPercentages['I'][] = round(($data['I'] / $total) * 100, 1);
                $severityPercentages['II'][] = round(($data['II'] / $total) * 100, 1);
                $severityPercentages['III'][] = round(($data['III'] / $total) * 100, 1);
            } else {
                $severityPercentages['I'][] = 0;
                $severityPercentages['II'][] = 0;
                $severityPercentages['III'][] = 0;
            }
        }

        // Recent 5 claims
        $recentRecords = (clone $baseQuery)->latest('discharge_date')->limit(5)->get();

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
            'endDate'
        ));
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $baseQuery = ClaimRecord::query();

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
        $sheet->setTitle('Severity bulanan');

        // Title Block
        $sheet->setCellValue('A1', 'LAPORAN KASUS SEVERITY PER BULAN');
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
            'I4' => 'Total Pasien',
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

            $sheet->setCellValue('A' . $rowIdx, $no++);
            $sheet->setCellValue('B' . $rowIdx, $monthName);
            $sheet->setCellValue('C' . $rowIdx, $data['I']);
            $sheet->setCellValue('D' . $rowIdx, $pctI);
            $sheet->setCellValue('E' . $rowIdx, $data['II']);
            $sheet->setCellValue('F' . $rowIdx, $pctII);
            $sheet->setCellValue('G' . $rowIdx, $data['III']);
            $sheet->setCellValue('H' . $rowIdx, $pctIII);
            $sheet->setCellValue('I' . $rowIdx, $total);

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

            $grandTotalI += $data['I'];
            $grandTotalII += $data['II'];
            $grandTotalIII += $data['III'];
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
        $sheet->setCellValue('I' . $rowIdx, $grandTotalAll);

        // Format Grand Total row
        $sheet->getStyle('B' . $rowIdx . ':I' . $rowIdx)->getFont()->setBold(true);
        $sheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . $rowIdx)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('I' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');

        // Auto-width
        foreach (range('A', 'I') as $col) {
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
        $fileName = 'severity_export_' . $periodeStr . '.xlsx';

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
}
