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

        // 1. Pencocokan langsung (exact case-insensitive)
        $doctor = self::where('nama', $dpjp)
            ->orWhere('nama_gelar', $dpjp)
            ->first();
            
        if ($doctor) {
            return $doctor->ksm;
        }

        // Cache data dokter untuk meminimalkan beban query database berulang
        static $allDoctors = null;
        if ($allDoctors === null) {
            try {
                $allDoctors = self::all();
            } catch (\Exception $e) {
                $allDoctors = collect();
            }
        }

        // 2. Pencocokan nama yang dinormalisasi (menghapus gelar/spasi)
        $normDb = self::normalizeName($dpjp);
        if (!empty($normDb)) {
            foreach ($allDoctors as $doc) {
                if (self::normalizeName($doc->nama) === $normDb || self::normalizeName($doc->nama_gelar) === $normDb) {
                    return $doc->ksm;
                }
            }

            // 3. Pencocokan loose substring
            foreach ($allDoctors as $doc) {
                $normExcelNama = self::normalizeName($doc->nama);
                if ($normExcelNama !== '' && (strpos($normDb, $normExcelNama) !== false || strpos($normExcelNama, $normDb) !== false)) {
                    return $doc->ksm;
                }
            }
        }

        // 4. Fallback: Deteksi KSM berdasarkan pola teks gelar spesialis di nama DPJP
        $normalizedTitle = str_replace([' ', '.'], '', strtoupper($dpjp));
        if (strpos($normalizedTitle, 'SPOG') !== false) {
            return 'Dokter Spesialis Kebidanan dan Kandungan';
        }
        if (strpos($normalizedTitle, 'SPPD') !== false) {
            return 'Dokter Spesialis Penyakit Dalam';
        }
        if (strpos($normalizedTitle, 'SPA') !== false) {
            return 'Dokter Spesialis Anak';
        }
        if (strpos($normalizedTitle, 'SPB') !== false) {
            if (strpos($normalizedTitle, 'DRG') !== false) {
                return 'Dokter Gigi Spesialis Bedah Mulut';
            }
            return 'Dokter Spesialis Bedah';
        }
        if (strpos($normalizedTitle, 'SPU') !== false) {
            return 'Dokter Spesialis Urologi';
        }
        if (strpos($normalizedTitle, 'SPJP') !== false) {
            return 'Dokter Spesialis Jantung dan Pembuluh Darah';
        }
        if (strpos($normalizedTitle, 'SPBS') !== false) {
            return 'Dokter Spesialis Bedah Saraf';
        }
        if (strpos($normalizedTitle, 'SPOT') !== false) {
            return 'Dokter Spesialis Orthopedi dan Traumatologi';
        }
        if (strpos($normalizedTitle, 'SPN') !== false || strpos($normalizedTitle, 'SPS') !== false) {
            return 'Dokter Spesialis Saraf';
        }
        if (strpos($normalizedTitle, 'SPM') !== false) {
            return 'Dokter Spesialis Mata';
        }
        if (strpos($normalizedTitle, 'SPTHT') !== false) {
            return 'Dokter Spesialis THT-KL';
        }
        if (strpos($normalizedTitle, 'SPAN') !== false) {
            return 'Dokter Spesialis Anestesi';
        }
        if (strpos($normalizedTitle, 'SPDVE') !== false || strpos($normalizedTitle, 'SPKK') !== false) {
            return 'Dokter Spesialis Kulit dan Kelamin';
        }
        if (strpos($normalizedTitle, 'SPKJ') !== false) {
            return 'Dokter Spesialis Kedokteran Jiwa';
        }
        if (strpos($normalizedTitle, 'SPRAD') !== false) {
            return 'Dokter Spesialis Radiologi';
        }
        if (strpos($normalizedTitle, 'SPPK') !== false) {
            return 'Dokter Spesialis Patologi Klinik';
        }
        if (strpos($normalizedTitle, 'SPPA') !== false) {
            return 'Dokter Spesialis Patologi Anatomi';
        }
        if (strpos($normalizedTitle, 'SPKGA') !== false) {
            return 'Dokter Gigi Spesialis Kedokteran Gigi Anak';
        }
        if (strpos($normalizedTitle, 'SPKG') !== false) {
            return 'Dokter Gigi Spesialis Konservasi Gigi';
        }
        if (strpos($normalizedTitle, 'SPORT') !== false) {
            return 'Dokter Gigi Spesialis Ortodonsia';
        }
        if (strpos($normalizedTitle, 'SPPM') !== false) {
            return 'Dokter Gigi Spesialis Penyakit Mulut';
        }

        return 'Lain-lain';
    }
}
