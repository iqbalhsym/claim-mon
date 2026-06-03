<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalRecord;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // --- Statistik Utama ---
        $totalRecord   = MedicalRecord::count();
        $lengkap       = MedicalRecord::where('is_rm_lengkap', true)->count();
        $tidakLengkap  = MedicalRecord::where('is_rm_lengkap', false)->count();
        $persenLengkap = $totalRecord > 0 ? round(($lengkap / $totalRecord) * 100) : 0;

        // --- Monitoring RM ---
        $sudahKembali  = MedicalRecord::where('status_kembali_rm', true)->count();
        $belumKembali  = MedicalRecord::where('status_kembali_rm', false)->count();
        $sudahAnalisa  = MedicalRecord::where('status_analisa', true)->count();
        $belumAnalisa  = MedicalRecord::where('status_analisa', false)->count();

        // --- Breakdown Guarantor (top 5) ---
        $byGuarantor = MedicalRecord::selectRaw('guarantor, count(*) as total')
            ->whereNotNull('guarantor')
            ->groupBy('guarantor')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // --- Data Masuk Bulan Ini vs Bulan Lalu ---
        $bulanIni   = MedicalRecord::whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year)->count();
        $bulanLalu  = MedicalRecord::whereMonth('created_at', Carbon::now()->subMonth()->month)
                        ->whereYear('created_at', Carbon::now()->subMonth()->year)->count();

        // --- 5 Rekam Medis Terbaru ---
        $recentRecords = MedicalRecord::latest()->limit(5)->get();

        // --- Breakdown Laporan Pembedahan ---
        $pembedahanLengkap     = MedicalRecord::where('laporan_pembedahan', 'LENGKAP')->count();
        $pembedahanTidakLengkap= MedicalRecord::where('laporan_pembedahan', 'TIDAK LENGKAP')->count();
        $pembedahanKosong      = MedicalRecord::where('laporan_pembedahan', 'KOSONG')
                                    ->orWhereNull('laporan_pembedahan')->count();

        return view('dashboard', compact(
            'totalRecord', 'lengkap', 'tidakLengkap', 'persenLengkap',
            'sudahKembali', 'belumKembali', 'sudahAnalisa', 'belumAnalisa',
            'byGuarantor', 'bulanIni', 'bulanLalu',
            'recentRecords',
            'pembedahanLengkap', 'pembedahanTidakLengkap', 'pembedahanKosong'
        ));
    }
}
