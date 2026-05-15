<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientGeography extends Model
{
    protected $fillable = [
        'nama_pasien',
        'no_rm',
        'alamat',
        'provinsi',
        'kabupaten_kota',
        'guarantor',
        'tanggal_kunjungan',
        'kat_guarantor',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
    ];
}
