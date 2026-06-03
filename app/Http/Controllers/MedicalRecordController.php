<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MedicalRecordsExport;
use App\Imports\MedicalRecordsImport;
use App\Services\AfyaService;

class MedicalRecordController extends Controller
{
    public function index()
    {
        $records = MedicalRecord::latest()->get();
        return view('medical_records.index', compact('records'));
    }

    public function create()
    {
        return view('medical_records.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $data['formulir_rawat_inap'] = $this->buildGroupedFormulirArray($request->input('ri_groups', []));
        $data['kelengkapan_dokter'] = $this->buildGroupedFormulirArray($request->input('kd_groups', []));

        $data['formulir_lain'] = $this->buildFormulirArray(
            $request->input('fl_nama', []),
            $request->input('fl_status', [])
        );

        MedicalRecord::create($data);
        return redirect()->route('medical-records.index')->with('success', 'Data Rekam Medis berhasil disimpan.');
    }

    public function show(MedicalRecord $medicalRecord)
    {
        return view('medical_records.show', compact('medicalRecord'));
    }

    public function edit(MedicalRecord $medicalRecord)
    {
        return view('medical_records.edit', compact('medicalRecord'));
    }

    public function update(Request $request, MedicalRecord $medicalRecord)
    {
        $data = $request->all();

        $data['formulir_rawat_inap'] = $this->buildGroupedFormulirArray($request->input('ri_groups', []));
        $data['kelengkapan_dokter'] = $this->buildGroupedFormulirArray($request->input('kd_groups', []));

        $data['formulir_lain'] = $this->buildFormulirArray(
            $request->input('fl_nama', []),
            $request->input('fl_status', [])
        );

        $medicalRecord->update($data);
        return redirect()->route('medical-records.index')->with('success', 'Data Rekam Medis berhasil diupdate.');
    }

    /**
     * Bangun array [{group_name, items: [{nama, is_kembali, tanggal_kembali}]}]
     */
    private function buildGroupedFormulirArray(array $groups): array
    {
        $result = [];
        foreach ($groups as $group) {
            $groupName = trim($group['group_name'] ?? '');
            if ($groupName === '' && empty($group['items'])) continue;

            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                $itemName = trim($item['nama'] ?? '');
                if ($itemName !== '') {
                    $items[] = [
                        'nama' => $itemName,
                        'is_kembali' => isset($item['is_kembali']) && $item['is_kembali'] == '1',
                        'tanggal_kembali' => $item['tanggal_kembali'] ?? null,
                    ];
                }
            }

            if ($groupName !== '' || !empty($items)) {
                $result[] = [
                    'group_name' => $groupName,
                    'items' => $items
                ];
            }
        }
        return $result;
    }

    /**
     * Bangun array [{nama, status}] dari dua array input terpisah (untuk formulir_lain).
     */
    private function buildFormulirArray(array $namas, array $statuses): array
    {
        $result = [];
        foreach ($namas as $idx => $nama) {
            $nama = trim($nama);
            if ($nama !== '') {
                $result[] = [
                    'nama'   => $nama,
                    'status' => $statuses[$idx] ?? 'belum_selesai',
                ];
            }
        }
        return $result;
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        $medicalRecord->delete();
        return redirect()->route('medical-records.index')->with('success', 'Data Rekam Medis berhasil dihapus.');
    }

    /**
     * Hapus semua data rekam medis.
     */
    public function truncate()
    {
        MedicalRecord::truncate();
        return redirect()->route('medical-records.index')->with('success', 'Semua data rekam medis berhasil dihapus.');
    }

    public function export()
    {
        return Excel::download(new MedicalRecordsExport, 'data_rekam_medis.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,csv,xls'
        ]);

        Excel::import(new MedicalRecordsImport, $request->file('file_excel'));

        return redirect()->route('medical-records.index')->with('success', 'Data Rekam Medis berhasil diimpor.');
    }

    /**
     * AJAX: Cari data pasien AKTIF di Afya berdasarkan No RM.
     *
     * Keterbatasan API Afya:
     * - Hanya dapat menemukan pasien yang SEDANG AKTIF dirawat (ada di tempat tidur saat ini).
     * - Pasien yang sudah pulang (discharge/historis) TIDAK dapat ditemukan.
     * - Jika tidak ditemukan, petugas mengisi data secara manual.
     */
    public function afyaLookup(Request $request)
    {
        $noRm = trim($request->query('no_rm', ''));

        if (!$noRm) {
            return response()->json(['success' => false, 'message' => 'No RM diperlukan.']);
        }

        // Validasi format: No RM RSUI adalah 6-8 digit angka
        if (!preg_match('/^\d{6,8}$/', $noRm)) {
            return response()->json([
                'success' => false,
                'message' => 'Format No RM tidak valid. Masukkan 6–8 digit angka (contoh: 00361881).',
            ]);
        }

        try {
            $afya    = new AfyaService();
            $patient = $afya->getPatientByNoRM($noRm);

            if ($patient) {
                // Exact match check: bandingkan MRN dari Afya (tanpa leading zero) dengan input
                $afyaMrn  = ltrim($patient['MRN'] ?? $patient['mrn'] ?? '', '0');
                $inputMrn = ltrim($noRm, '0');

                if ($afyaMrn !== $inputMrn) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No RM tidak cocok dengan data Afya. Silakan isi data secara manual.',
                        'info'    => 'mismatch',
                    ]);
                }

                return response()->json(['success' => true, 'data' => $patient]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke sistem Afya. Silakan isi data secara manual.',
                'info'    => 'connection_error',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Pasien tidak sedang aktif dirawat di RSUI saat ini. Untuk pasien yang sudah pulang, silakan isi data secara manual.',
            'info'    => 'not_active',
        ]);
    }
}
