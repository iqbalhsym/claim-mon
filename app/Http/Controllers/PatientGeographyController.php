<?php

namespace App\Http\Controllers;

use App\Models\PatientGeography;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PatientGeographyExport;
use App\Imports\PatientGeographyImport;

class PatientGeographyController extends Controller
{
    /**
     * Koordinat pusat setiap provinsi untuk peta (lat, lng, zoom-level)
     */
    public static array $provinsiCoords = [
        'Aceh'                   => [-4.695135, 96.749397, 8],
        'Sumatera Utara'         => [2.115202, 99.030380, 8],
        'Sumatera Barat'         => [-0.739649, 100.800002, 8],
        'Riau'                   => [0.293462, 101.706383, 8],
        'Jambi'                  => [-1.610028, 103.611938, 8],
        'Sumatera Selatan'       => [-3.319490, 103.914399, 8],
        'Bengkulu'               => [-3.793883, 102.264824, 8],
        'Lampung'                => [-4.557573, 105.405060, 8],
        'Bangka Belitung'        => [-2.741051, 106.440895, 8],
        'Kepulauan Riau'         => [3.942468, 108.142857, 7],
        'DKI Jakarta'            => [-6.200000, 106.816666, 11],
        'Jawa Barat'             => [-7.090911, 107.668888, 9],
        'Jawa Tengah'            => [-7.150975, 110.140259, 9],
        'DI Yogyakarta'          => [-7.879306, 110.376482, 11],
        'Jawa Timur'             => [-7.536064, 112.238402, 9],
        'Banten'                 => [-6.405919, 106.064445, 9],
        'Bali'                   => [-8.409518, 115.188919, 10],
        'Nusa Tenggara Barat'    => [-8.652406, 117.361648, 9],
        'Nusa Tenggara Timur'    => [-8.657382, 121.079113, 8],
        'Kalimantan Barat'       => [0.013533, 109.341240, 7],
        'Kalimantan Tengah'      => [-1.681488, 113.382355, 7],
        'Kalimantan Selatan'     => [-3.092600, 115.281582, 8],
        'Kalimantan Timur'       => [1.681488, 116.419389, 7],
        'Kalimantan Utara'       => [3.073523, 116.041290, 7],
        'Sulawesi Utara'         => [1.269543, 124.845772, 9],
        'Sulawesi Tengah'        => [-1.430196, 121.445702, 8],
        'Sulawesi Selatan'       => [-3.659326, 119.973083, 8],
        'Sulawesi Tenggara'      => [-4.144856, 122.174011, 8],
        'Gorontalo'              => [0.533872, 123.058968, 9],
        'Sulawesi Barat'         => [-2.840286, 119.232552, 9],
        'Maluku'                 => [-3.237586, 130.145270, 7],
        'Maluku Utara'           => [1.570854, 127.808742, 7],
        'Papua Barat'            => [-1.336240, 133.174659, 7],
        'Papua'                  => [-4.269928, 138.080353, 6],
        'Luar DKI'               => [-2.5, 117.5, 5],
        'Lainnya'                => [-2.5, 117.5, 5],
    ];

    /**
     * Koordinat pusat setiap kota/kab untuk flyTo (lat, lng, zoom)
     */
    public static array $kotaCoords = [
        // DKI Jakarta
        'Jakarta Selatan'    => [-6.261493, 106.810600, 13],
        'Jakarta Pusat'      => [-6.186486, 106.834091, 13],
        'Jakarta Barat'      => [-6.168455, 106.763755, 13],
        'Jakarta Utara'      => [-6.121435, 106.774124, 13],
        'Jakarta Timur'      => [-6.225144, 106.900447, 13],
        'Kepulauan Seribu'   => [-5.614070, 106.570290, 11],
        // Jawa Barat
        'Kota Bandung'       => [-6.914744, 107.609810, 12],
        'Kota Bogor'         => [-6.597147, 106.806038, 12],
        'Kota Bekasi'        => [-6.238270, 106.975573, 12],
        'Kota Depok'         => [-6.400971, 106.819450, 12],
        'Kabupaten Bogor'    => [-6.595038, 106.816635, 11],
        'Kabupaten Bekasi'   => [-6.272729, 107.142632, 11],
        'Kota Cimahi'        => [-6.872025, 107.542732, 13],
        'Kota Sukabumi'      => [-6.921183, 106.927849, 13],
        'Kabupaten Karawang' => [-6.302694, 107.300079, 11],
        'Kabupaten Bandung'  => [-7.050880, 107.560600, 11],
        // Banten
        'Kota Tangerang'          => [-6.178306, 106.631126, 12],
        'Kota Tangerang Selatan'  => [-6.288453, 106.721167, 12],
        'Kabupaten Tangerang'     => [-6.155900, 106.638100, 11],
        'Kota Serang'             => [-6.119969, 106.150528, 13],
        'Kota Cilegon'            => [-6.003460, 106.025500, 13],
        // Jawa Tengah
        'Kota Semarang'   => [-6.966667, 110.416664, 12],
        'Kota Solo'       => [-7.571030, 110.827806, 13],
        'Kota Magelang'   => [-7.470254, 110.217633, 13],
        'Kabupaten Klaten'=> [-7.706885, 110.593718, 12],
        'Kabupaten Sleman'=> [-7.718064, 110.358105, 12],
        // DI Yogyakarta
        'Kota Yogyakarta'     => [-7.797068, 110.370529, 13],
        'Kabupaten Bantul'    => [-7.888730, 110.328125, 12],
        'Kabupaten Gunungkidul' => [-7.989770, 110.589622, 11],
        // Jawa Timur
        'Kota Surabaya'   => [-7.250445, 112.768845, 12],
        'Kota Malang'     => [-7.983908, 112.621391, 13],
        'Kota Kediri'     => [-7.818300, 112.014540, 13],
        'Kabupaten Sidoarjo' => [-7.446085, 112.718078, 11],
        'Kota Mojokerto'  => [-7.470196, 112.434120, 14],
        // Sumatera Utara
        'Kota Medan'           => [3.591420, 98.675499, 12],
        'Kota Pematangsiantar' => [2.959460, 99.056070, 13],
        'Kabupaten Deli Serdang' => [3.607855, 98.864937, 11],
        // Sumatera Selatan
        'Kota Palembang'    => [-2.976074, 104.775429, 12],
        'Kota Lubuklinggau' => [-3.299450, 102.861778, 13],
        // Riau
        'Kota Pekanbaru' => [0.507068, 101.447777, 12],
        'Kota Dumai'     => [1.675540, 101.451385, 12],
        'Kabupaten Kampar' => [0.295180, 101.151810, 11],
        // Lampung
        'Kota Bandar Lampung'   => [-5.454040, 105.261467, 12],
        'Kota Metro'            => [-5.113220, 105.306473, 13],
        'Kabupaten Lampung Selatan' => [-5.594260, 105.627200, 11],
        // Kalimantan Timur
        'Kota Samarinda'  => [-0.502065, 117.153610, 12],
        'Kota Balikpapan' => [-1.267950, 116.831902, 12],
        'Kota Bontang'    => [0.133477, 117.498100, 13],
        // Sulawesi Selatan
        'Kota Makassar' => [-5.135399, 119.412296, 12],
        'Kota Parepare'  => [-4.014600, 119.623260, 13],
        // Bali
        'Kota Denpasar'   => [-8.650000, 115.216667, 12],
        'Kabupaten Badung'=> [-8.610040, 115.135850, 11],
        'Kabupaten Gianyar'=> [-8.536480, 115.323390, 12],
        // NTB
        'Kota Mataram'       => [-8.586310, 116.101200, 13],
        'Kabupaten Lombok Barat' => [-8.652406, 116.036140, 11],
        // Kalimantan Barat
        'Kota Pontianak'  => [-0.022736, 109.341240, 12],
        'Kota Singkawang' => [0.907660, 108.985430, 13],
        // Aceh
        'Kota Banda Aceh' => [5.548290, 95.323529, 13],
        'Kota Langsa'     => [4.469869, 97.969338, 13],
        // Papua
        'Kota Jayapura'   => [-2.533333, 140.716667, 12],
        'Kabupaten Mimika'=> [-4.584610, 136.404150, 11],
        // Sulawesi Utara
        'Kota Manado' => [1.474770, 124.842072, 12],
        'Kota Bitung' => [1.442430, 125.192790, 13],
        // Kalimantan Selatan
        'Kota Banjarmasin' => [-3.316667, 114.583611, 12],
        'Kota Banjarbaru'  => [-3.441680, 114.831670, 13],
        // Sumatera Barat
        'Kota Padang'      => [-0.949000, 100.354000, 12],
        'Kota Bukittinggi' => [-0.308190, 100.368599, 13],
        'Kota Solok'       => [-0.794870, 100.659309, 13],
        // Maluku
        'Kota Ambon'   => [-3.695400, 128.182800, 12],
        // Kepulauan Riau
        'Kota Batam'         => [1.045620, 104.030704, 12],
        'Kota Tanjungpinang' => [0.918630, 104.455710, 13],
        // Jambi
        'Kota Jambi' => [-1.610028, 103.611938, 12],
        // Bengkulu
        'Kota Bengkulu' => [-3.800441, 102.265434, 12],
        // Sulawesi Tengah
        'Kota Palu' => [-0.900000, 119.866667, 12],
        // Sulawesi Tenggara
        'Kota Kendari' => [-3.970910, 122.515289, 12],
        // Gorontalo
        'Kota Gorontalo' => [0.540600, 123.060600, 12],
        // Kalimantan Tengah
        'Kota Palangka Raya' => [-2.213893, 113.917953, 12],
        // NTT
        'Kota Kupang' => [-10.170000, 123.607056, 12],
        // Maluku Utara
        'Kota Ternate' => [0.787700, 127.376800, 12],
        // Papua Barat
        'Kota Sorong' => [-0.876490, 131.250260, 12],
        // Sulawesi Barat
        'Kabupaten Majene' => [-3.540780, 118.964860, 12],
        // Bangka Belitung
        'Kota Pangkalpinang' => [-2.109270, 106.117149, 13],
        // Generic / Fallbacks
        'DKI Jakarta'     => [-6.2088, 106.8456, 11],
        'Jawa Barat'      => [-6.9147, 107.6098, 9],
        'Banten'          => [-6.4058, 106.0640, 9],
        'Non DKI Jakarta' => [-2.5, 117.5, 5],
        'Lainnya'         => [-2.5, 117.5, 5],
        'Tidak Diketahui' => [-2.5, 117.5, 5],
    ];

    public function index()
    {
        $byProvinsi = PatientGeography::selectRaw("
                provinsi,
                count(*) as total,
                SUM(CASE WHEN kat_guarantor = 'JKN' THEN 1 ELSE 0 END) as bpjs,
                SUM(CASE WHEN kat_guarantor != 'JKN' OR kat_guarantor IS NULL THEN 1 ELSE 0 END) as non_bpjs
            ")
            ->groupBy('provinsi')
            ->orderByDesc('total')
            ->get();

        $totalPasien  = PatientGeography::count();
        $totalBpjs    = PatientGeography::where('kat_guarantor', 'JKN')->count();
        $totalNonBpjs = $totalPasien - $totalBpjs;
        $totalProvinsi = $byProvinsi->count();

        $provinsiList = array_keys(self::$provinsiCoords);
        sort($provinsiList);

        // --- Data per bulan (Jan 2025 - Mar 2026) dari tanggal_kunjungan ---
        $perBulan = [];
        $startDate = \Carbon\Carbon::create(2025, 1, 1);
        $endDate   = \Carbon\Carbon::create(2026, 3, 1);
        $current   = $startDate->copy();

        while ($current->lte($endDate)) {
            $jkn    = PatientGeography::where('kat_guarantor', 'JKN')
                        ->whereMonth('tanggal_kunjungan', $current->month)
                        ->whereYear('tanggal_kunjungan', $current->year)->count();
            $nonJkn = PatientGeography::where('kat_guarantor', '!=', 'JKN')
                        ->whereMonth('tanggal_kunjungan', $current->month)
                        ->whereYear('tanggal_kunjungan', $current->year)->count();
            $perBulan[] = [
                'label'    => $current->translatedFormat('M Y'),
                'bpjs'     => $jkn,
                'non_bpjs' => $nonJkn,
                'total'    => $jkn + $nonJkn,
            ];
            $current->addMonth();
        }

        return view('patient_geography.index', compact(
            'byProvinsi', 'totalPasien', 'totalBpjs', 'totalNonBpjs', 'totalProvinsi', 'provinsiList',
            'perBulan'
        ));
    }

    /** AJAX: data untuk peta (semua provinsi atau filter) */
    public function apiData(Request $request)
    {
        $provinsi = $request->query('provinsi');
        $kota     = $request->query('kota');

        $query = PatientGeography::query();

        if ($provinsi) {
            $query->where('provinsi', $provinsi);
        }
        if ($kota) {
            $query->where('kabupaten_kota', $kota);
        }

        if ($kota) {
            // Return per kota
            $data = $query->selectRaw("
                    kabupaten_kota,
                    count(*) as total,
                    SUM(CASE WHEN kat_guarantor = 'JKN' THEN 1 ELSE 0 END) as bpjs,
                    SUM(CASE WHEN kat_guarantor != 'JKN' OR kat_guarantor IS NULL THEN 1 ELSE 0 END) as non_bpjs
                ")
                ->groupBy('kabupaten_kota')
                ->get();
        } elseif ($provinsi) {
            // Return per kota dalam provinsi + koordinat
            $data = $query->selectRaw("
                    kabupaten_kota,
                    count(*) as total,
                    SUM(CASE WHEN kat_guarantor = 'JKN' THEN 1 ELSE 0 END) as bpjs,
                    SUM(CASE WHEN kat_guarantor != 'JKN' OR kat_guarantor IS NULL THEN 1 ELSE 0 END) as non_bpjs
                ")
                ->groupBy('kabupaten_kota')
                ->get()
                ->map(function ($item) {
                    $coords = self::$kotaCoords[$item->kabupaten_kota] ?? null;
                    return [
                        'kabupaten_kota' => $item->kabupaten_kota,
                        'total'          => $item->total,
                        'bpjs'           => (int) $item->bpjs,
                        'non_bpjs'       => (int) $item->non_bpjs,
                        'lat'            => $coords[0] ?? null,
                        'lng'            => $coords[1] ?? null,
                    ];
                });
        } else {
            // Return per provinsi
            $data = PatientGeography::selectRaw("
                    provinsi,
                    count(*) as total,
                    SUM(CASE WHEN kat_guarantor = 'JKN' THEN 1 ELSE 0 END) as bpjs,
                    SUM(CASE WHEN kat_guarantor != 'JKN' OR kat_guarantor IS NULL THEN 1 ELSE 0 END) as non_bpjs
                ")
                ->groupBy('provinsi')
                ->get()
                ->map(function ($item) {
                    $coords = self::$provinsiCoords[$item->provinsi] ?? null;
                    return [
                        'provinsi' => $item->provinsi,
                        'total'    => $item->total,
                        'bpjs'     => (int) $item->bpjs,
                        'non_bpjs' => (int) $item->non_bpjs,
                        'lat'      => $coords[0] ?? null,
                        'lng'      => $coords[1] ?? null,
                        'zoom'     => $coords[2] ?? 8,
                    ];
                });
        }

        return response()->json($data);
    }

    /** AJAX: daftar kota berdasarkan provinsi yang dipilih */
    public function filterKota(Request $request)
    {
        $provinsi = $request->query('provinsi', '');
        if (!$provinsi) {
            return response()->json([]);
        }

        $kotaList = PatientGeography::where('provinsi', $provinsi)
            ->distinct()
            ->orderBy('kabupaten_kota')
            ->pluck('kabupaten_kota');

        // Sertakan koordinat untuk flyTo
        $result = $kotaList->map(function ($kota) {
            $coords = self::$kotaCoords[$kota] ?? null;
            return [
                'name' => $kota,
                'lat'  => $coords[0] ?? null,
                'lng'  => $coords[1] ?? null,
                'zoom' => $coords[2] ?? 12,
            ];
        });

        return response()->json($result);
    }

    /** Koordinat provinsi (untuk JS) */
    public function provinsiCoords()
    {
        return response()->json(self::$provinsiCoords);
    }

    public function export()
    {
        return Excel::download(new PatientGeographyExport, 'geografi_pasien.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,csv,xls'
        ]);

        Excel::import(new PatientGeographyImport, $request->file('file_excel'));

        return back()->with('success', 'Data Geografi Pasien berhasil diimpor.');
    }

    /**
     * Import dari master_pasien.xlsx (data mentah dari Afya)
     * Kolom: KAT Guarantor (JKN/Non JKN), KAT EDITED (wilayah), Created Date (tanggal)
     */
    public function importMaster(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls'
        ]);

        // Hapus data lama sebelum import
        \App\Models\PatientGeography::truncate();

        $import = new \App\Imports\MasterPasienImport();
        Excel::import($import, $request->file('file_excel'));

        return back()->with('success',
            "Import master pasien selesai. Berhasil: {$import->inserted} data, Dilewati: {$import->skipped} data."
        );
    }

    /**
     * Hapus semua data geografi pasien
     */
    public function truncate()
    {
        \App\Models\PatientGeography::truncate();
        return back()->with('success', 'Semua data geografi pasien berhasil dihapus.');
    }
}
