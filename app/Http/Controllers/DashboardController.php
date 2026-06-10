<?php

namespace App\Http\Controllers;

use App\Models\ClaimRecord;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRecord = ClaimRecord::count();
        $totalTotalTarif = ClaimRecord::sum('total_tarif');
        $totalTarifRs = ClaimRecord::sum('tarif_rs');
        $totalSelisih = ClaimRecord::sum('selisih');

        // Top 5 Doctors by patient count
        $topDoctors = ClaimRecord::selectRaw('
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

        $rawSeverity = ClaimRecord::selectRaw("
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
        $recentRecords = ClaimRecord::latest()->limit(5)->get();

        return view('dashboard', compact(
            'totalRecord',
            'totalTotalTarif',
            'totalTarifRs',
            'totalSelisih',
            'topDoctors',
            'months',
            'severityCounts',
            'severityPercentages',
            'recentRecords'
        ));
    }
}
