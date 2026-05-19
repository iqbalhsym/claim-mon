<?php

namespace App\Http\Controllers;

use App\Models\MasterData;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterDataExport;
use App\Imports\MasterDataImport;

class MasterDataController extends Controller
{
    public function index()
    {
        $grouped = [];
        foreach (MasterData::$types as $type => $label) {
            $grouped[$type] = [
                'label' => $label,
                'items' => MasterData::where('type', $type)->orderBy('name')->get(),
            ];
        }
        return view('master_data.index', compact('grouped'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:ruangan,dokter,rawat_inap,kelengkapan_dokter,formulir_lain',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        // Cek duplikat dalam type yang sama
        $exists = MasterData::where('type', $request->type)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Data "' . $request->name . '" sudah ada di kategori ini.');
        }

        MasterData::create($request->only('type', 'name', 'code'));
        return back()->with('success', 'Data master berhasil ditambahkan.');
    }

    public function update(Request $request, MasterData $masterDatum)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        $masterDatum->update($request->only('name', 'code'));
        return back()->with('success', 'Data master berhasil diperbarui.');
    }

    public function destroy(MasterData $masterDatum)
    {
        $masterDatum->delete();
        return back()->with('success', 'Data master berhasil dihapus.');
    }

    public function export()
    {
        return Excel::download(new MasterDataExport, 'master_data.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,csv,xls'
        ]);

        Excel::import(new MasterDataImport, $request->file('file_excel'));

        return back()->with('success', 'Data Master berhasil diimpor.');
    }

    /**
     * AJAX: Cari data master untuk autocomplete
     * GET /master-data/search?type=ruangan&q=rumput
     */
    public function search(Request $request)
    {
        $type = $request->query('type', '');
        $q    = $request->query('q', '');

        if (!array_key_exists($type, MasterData::$types)) {
            return response()->json([]);
        }

        $results = MasterData::where('type', $type)
            ->where('name', 'ilike', '%' . $q . '%')
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');

        return response()->json($results);
    }
}
