<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BillingRecord;
use Illuminate\Support\Facades\DB;
use App\Jobs\ImportBillingRecordsJob;

class BillingRecordController extends Controller
{
    public function guarantorReportRanap(Request $request)
    {
        return $this->guarantorReport($request, 'ranap');
    }

    public function guarantorReportRajal(Request $request)
    {
        return $this->guarantorReport($request, 'rajal');
    }

    /**
     * Tampilkan data laporan rekapan perpenjamin (guarantor)
     */
    public function guarantorReport(Request $request, $jenisRawat)
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $monthExpr = "to_char(discharge_date, 'YYYY-MM')";
        } elseif ($driver === 'sqlite') {
            $monthExpr = "strftime('%Y-%m', discharge_date)";
        } else {
            $monthExpr = "DATE_FORMAT(discharge_date, '%Y-%m')";
        }

        // Get all unique year-month of discharge date
        $availableMonths = BillingRecord::where('jenis_rawat', $jenisRawat)
            ->whereNotNull('discharge_date')
            ->selectRaw("$monthExpr as month_key")
            ->groupBy('month_key')
            ->orderBy('month_key', 'desc')
            ->pluck('month_key');

        $selectedMonth = $request->query('month');

        // Query stats per guarantor
        $query = BillingRecord::where('jenis_rawat', $jenisRawat);

        if (!empty($selectedMonth)) {
            $query->whereRaw("$monthExpr = ?", [$selectedMonth]);
        }

        $stats = $query->select(
                'guarantor',
                DB::raw('COUNT(no_rm) as kunjungan'),
                DB::raw('SUM(total_invoice_before_discount) as rill_billing'),
                DB::raw('SUM(total_guarantee) as ajuan_klaim'),
                DB::raw('SUM(total_payment) as dibayar_pasien'),
                DB::raw('SUM(total_discount_per_item) as discount_rs'),
                DB::raw('SUM(total_invoice_before_discount - total_discount_per_item) as net_billing'),
                DB::raw('SUM(hospital_guarantee + total_must_be_paid - hospital_fee) as jasa_rs'),
                DB::raw('SUM(doctor_guarantee + total_must_be_paid - doctor_fee) as jasa_pelayanan')
            )
            ->groupBy('guarantor')
            ->orderBy('guarantor', 'asc')
            ->get();

        return view('claim_records.guarantor', compact('jenisRawat', 'stats', 'availableMonths', 'selectedMonth'));
    }

    /**
     * Import Billing Records from Excel (Billing Verification)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:20480', // max 20MB
            'jenis_rawat' => 'required|in:ranap,rajal',
        ]);

        $jenisRawatSource = $request->input('jenis_rawat');
        $file = $request->file('file_excel');
        $originalName = $file->getClientOriginalName();
        $storedPath = $file->store('imports', 'local');
        $absolutePath = \Illuminate\Support\Facades\Storage::disk('local')->path($storedPath);

        // Dispatch synchronously for immediate feedback, similar to Claim Records
        ImportBillingRecordsJob::dispatchSync($absolutePath, $jenisRawatSource, $originalName);

        return redirect()->back()->with('success', "File <strong>{$originalName}</strong> berhasil diimpor.");
    }

    /**
     * Truncate/Hapus data Billing Records
     */
    public function truncate(Request $request)
    {
        $jenisRawat = $request->query('jenis_rawat');
        $deleteMonth = $request->input('delete_month');
        
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $monthExpr = "to_char(discharge_date, 'YYYY-MM')";
        } elseif ($driver === 'sqlite') {
            $monthExpr = "strftime('%Y-%m', discharge_date)";
        } else {
            $monthExpr = "DATE_FORMAT(discharge_date, '%Y-%m')";
        }

        if (!in_array($jenisRawat, ['ranap', 'rajal'])) {
            return redirect()->back()->with('error', 'Jenis rawat tidak valid.');
        }

        if ($deleteMonth === 'all') {
            BillingRecord::where('jenis_rawat', $jenisRawat)->delete();
            $msg = 'Semua data Billing Verification (' . strtoupper($jenisRawat) . ') berhasil dihapus.';
        } else {
            // Delete specific month
            BillingRecord::where('jenis_rawat', $jenisRawat)
                ->whereRaw("$monthExpr = ?", [$deleteMonth])
                ->delete();
            $msg = 'Data Billing Verification untuk bulan ' . $deleteMonth . ' berhasil dihapus.';
        }

        return redirect()->back()->with('success', $msg);
    }
}
