<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctors';

    protected $fillable = [
        'nama',
        'nama_gelar',
        'ksm',
        'spesialis',
        'status',
    ];

    /**
     * Normalisasi nama dokter untuk mempermudah pencarian/pencocokan
     */
    public static function normalizeName($name)
    {
        if (empty($name)) {
            return '';
        }
        
        $name = strtolower($name);
        
        // Menghapus gelar akademik, spesialis, dan gelar profesi
        $pattern = '/\b(dr|drg|prof|sp\.[a-z\(\)-]+|subsp\.[a-z\(\)\.-]+|k\-[a-z]+|k|ph\.d|mars|m\.m|m\.sc|mres|bmedsc\(hons\)|finasim|fiha|fics|s\.ked)\b/i';
        $name = preg_replace($pattern, '', $name);
        
        // Hapus karakter non-alphabet
        $name = preg_replace('/[^a-z]/i', '', $name);
        
        return $name;
    }

    /**
     * Menentukan KSM (Spesialisasi) untuk nama DPJP tertentu
     */
    public static function resolveKsm(?string $dpjp)
    {
        $dpjp = trim($dpjp ?? '');
        if (empty($dpjp) || strcasecmp($dpjp, 'Tanpa Nama Dokter') === 0) {
            return 'Lain-lain';
        }

        // Cache data dokter dan exact mapping untuk pencocokan cepat
        static $exactMap = null;
        static $normMap = null;
        static $allDoctorsWithNormalized = null;

        if ($exactMap === null) {
            $exactMap = [];
            $normMap = [];
            $allDoctorsWithNormalized = [];
            try {
                $allDoctors = self::all();
                foreach ($allDoctors as $doc) {
                    $normNama = self::normalizeName($doc->nama);
                    $normGelar = self::normalizeName($doc->nama_gelar);

                    if (!empty($doc->nama)) {
                        $exactMap[strtolower($doc->nama)] = $doc->ksm;
                        if ($normNama !== '') {
                            $normMap[$normNama] = $doc->ksm;
                        }
                    }
                    if (!empty($doc->nama_gelar)) {
                        $exactMap[strtolower($doc->nama_gelar)] = $doc->ksm;
                        if ($normGelar !== '') {
                            $normMap[$normGelar] = $doc->ksm;
                        }
                    }

                    $allDoctorsWithNormalized[] = [
                        'ksm' => $doc->ksm,
                        'norm_nama' => $normNama,
                        'norm_gelar' => $normGelar,
                    ];
                }
            } catch (\Exception $e) {
                $allDoctorsWithNormalized = [];
            }
        }

        // 1. Pencocokan langsung (exact case-insensitive) lewat cache map
        $dpjpLower = strtolower($dpjp);
        if (isset($exactMap[$dpjpLower])) {
            return $exactMap[$dpjpLower];
        }

        // 2. Pencocokan nama yang dinormalisasi (menghapus gelar/spasi) lewat cache map
        $normDb = self::normalizeName($dpjp);
        if (!empty($normDb)) {
            if (isset($normMap[$normDb])) {
                return $normMap[$normDb];
            }

            // 3. Pencocokan loose substring
            foreach ($allDoctorsWithNormalized as $item) {
                $normExcelNama = $item['norm_nama'];
                if ($normExcelNama !== '' && (strpos($normDb, $normExcelNama) !== false || strpos($normExcelNama, $normDb) !== false)) {
                    return $item['ksm'];
                }
            }
        }

        // 4. Fallback: Deteksi KSM berdasarkan pola teks gelar spesialis di nama DPJP
        $normalizedTitle = str_replace([' ', '.'], '', strtoupper($dpjp));
        if (strpos($normalizedTitle, 'SPOG') !== false) {
            return 'KSM Obstetri dan Ginekologi';
        }
        if (strpos($normalizedTitle, 'SPPD') !== false) {
            return 'KSM Ilmu Penyakit Dalam';
        }
        if (strpos($normalizedTitle, 'SPA') !== false) {
            return 'KSM Ilmu Kesehatan Anak';
        }
        if (strpos($normalizedTitle, 'SPB') !== false) {
            if (strpos($normalizedTitle, 'DRG') !== false) {
                return 'KSM Gigi dan Mulut';
            }
            return 'KSM Ilmu Bedah';
        }
        if (strpos($normalizedTitle, 'SPU') !== false) {
            return 'KSM Urologi';
        }
        if (strpos($normalizedTitle, 'SPJP') !== false) {
            return 'KSM Kardiologi dan Kedokteran Vaskular';
        }
        if (strpos($normalizedTitle, 'SPBS') !== false) {
            return 'KSM Ilmu Bedah';
        }
        if (strpos($normalizedTitle, 'SPOT') !== false) {
            return 'KSM Orthopaedi dan Traumatologi';
        }
        if (strpos($normalizedTitle, 'SPN') !== false || strpos($normalizedTitle, 'SPS') !== false) {
            return 'KSM Neurologi';
        }
        if (strpos($normalizedTitle, 'SPM') !== false) {
            return 'KSM Ilmu Kesehatan Mata';
        }
        if (strpos($normalizedTitle, 'SPTHT') !== false) {
            return 'KSM THT-KL';
        }
        if (strpos($normalizedTitle, 'SPAN') !== false) {
            return 'KSM Anestesiologi dan Terapi Intensif';
        }
        if (strpos($normalizedTitle, 'SPDVE') !== false || strpos($normalizedTitle, 'SPKK') !== false) {
            return 'KSM Dermatologi dan Venereologi';
        }
        if (strpos($normalizedTitle, 'SPKJ') !== false) {
            return 'KSM Ilmu Kesehatan Jiwa';
        }
        if (strpos($normalizedTitle, 'SPRAD') !== false) {
            return 'KSM Radiologi';
        }
        if (strpos($normalizedTitle, 'SPPK') !== false) {
            return 'KSM Patologi Klinik';
        }
        if (strpos($normalizedTitle, 'SPPA') !== false) {
            return 'KSM Patologi Anatomik';
        }
        if (strpos($normalizedTitle, 'SPKGA') !== false) {
            return 'KSM Gigi dan Mulut';
        }
        if (strpos($normalizedTitle, 'SPKG') !== false) {
            return 'KSM Gigi dan Mulut';
        }
        if (strpos($normalizedTitle, 'SPORT') !== false) {
            return 'KSM Gigi dan Mulut';
        }
        if (strpos($normalizedTitle, 'SPPM') !== false) {
            return 'KSM Gigi dan Mulut';
        }

        return 'Lain-lain';
    }
}
