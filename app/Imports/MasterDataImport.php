<?php

namespace App\Imports;

use App\Models\MasterData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MasterDataImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $type = $row['tipe_data'] ?? null;
        $name = $row['nama'] ?? null;
        $code = $row['kode'] ?? null;

        if (!$type || !$name) {
            return null;
        }

        $validTypes = ['ruangan', 'dokter', 'rawat_inap', 'kelengkapan_dokter', 'formulir_lain'];
        $type = strtolower(trim($type));
        $name = trim($name);

        if (!in_array($type, $validTypes)) {
            return null;
        }

        // Filter per nama sesuai permintaan user (jangan double berdasarkan tipe dan nama)
        $exists = MasterData::where('type', $type)->where('name', $name)->exists();
        if ($exists) {
            return null;
        }

        return new MasterData([
            'type' => $type,
            'name' => $name,
            'code' => $code,
        ]);
    }
}
