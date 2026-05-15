<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterData extends Model
{
    protected $table = 'master_data';

    protected $fillable = ['type', 'name', 'code'];

    // Constants untuk tipe
    const TYPE_RUANGAN          = 'ruangan';
    const TYPE_DOKTER           = 'dokter';
    const TYPE_RAWAT_INAP       = 'rawat_inap';
    const TYPE_KELENGKAPAN_DOKTER = 'kelengkapan_dokter';
    const TYPE_FORMULIR_LAIN    = 'formulir_lain';

    public static $types = [
        self::TYPE_RUANGAN            => 'Ruangan',
        self::TYPE_DOKTER             => 'Dokter / KSM',
        self::TYPE_RAWAT_INAP         => 'Rawat Inap',
        self::TYPE_KELENGKAPAN_DOKTER => 'Kelengkapan Dokter',
        self::TYPE_FORMULIR_LAIN      => 'Formulir Lain-lain',
    ];

    public static function getByType(string $type)
    {
        return self::where('type', $type)->orderBy('name')->pluck('name');
    }

    public static function search(string $type, string $q)
    {
        return self::where('type', $type)
            ->where('name', 'ilike', '%' . $q . '%')
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');
    }
}
