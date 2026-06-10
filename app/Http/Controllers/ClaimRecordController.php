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

        $query = ClaimRecord::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_pasien', 'ilike', "%{$search}%")
                  ->orWhere('no_rm', 'ilike', "%{$search}%")
                  ->orWhere('inacbg', 'ilike', "%{$search}%")
                  ->orWhere('dpjp', 'ilike', "%{$search}%");
            });
        }

        $records = $query->orderBy('discharge_date', 'desc')->paginate(50);
        $totalRecords = ClaimRecord::count();

        return view('claim_records.index', compact('records', 'totalRecords', 'search'));
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

                $totalTarif = (float)($sheet->getCell('AM' . $row)->getValue() ?? 0);
                $tarifRs = (float)($sheet->getCell('AN' . $row)->getValue() ?? 0);
                $selisih = $totalTarif - $tarifRs;

                $batch[] = [
                    'no_rm' => $noRm,
                    'nama_pasien' => $namaPasien,
                    'admission_date' => $admissionDate?->toDateString(),
                    'discharge_date' => $dischargeDate?->toDateString(),
                    'inacbg' => $inacbg,
                    'severity' => $severity,
                    'dpjp' => $dpjp,
                    'total_tarif' => $totalTarif,
                    'tarif_rs' => $tarifRs,
                    'selisih' => $selisih,
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

    public function truncate()
    {
        ClaimRecord::truncate();
        return redirect()->route('claim-records.index')->with('success', 'Semua data klaim berhasil dihapus.');
    }

    public function dpjpReport(Request $request)
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $stats = ClaimRecord::selectRaw("
                to_char(discharge_date, 'YYYY-MM') as month_key,
                dpjp,
                count(*) as patient_count,
                sum(total_tarif) as total_total_tarif,
                sum(tarif_rs) as total_tarif_rs,
                sum(total_tarif - tarif_rs) as total_selisih
            ")
            ->groupBy('month_key', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('dpjp', 'asc')
            ->get();
        } else {
            $stats = ClaimRecord::selectRaw("
                strftime('%Y-%m', discharge_date) as month_key,
                dpjp,
                count(*) as patient_count,
                sum(total_tarif) as total_total_tarif,
                sum(tarif_rs) as total_tarif_rs,
                sum(total_tarif - tarif_rs) as total_selisih
            ")
            ->groupBy('month_key', 'dpjp')
            ->orderBy('month_key', 'desc')
            ->orderBy('dpjp', 'asc')
            ->get();
        }

        // Calculate grand totals
        $grandTotalPatients = $stats->sum('patient_count');
        $grandTotalTarif = $stats->sum('total_total_tarif');
        $grandTotalRs = $stats->sum('total_tarif_rs');
        $grandTotalSelisih = $stats->sum('total_selisih');

        return view('claim_records.dpjp', compact(
            'stats',
            'grandTotalPatients',
            'grandTotalTarif',
            'grandTotalRs',
            'grandTotalSelisih'
        ));
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
