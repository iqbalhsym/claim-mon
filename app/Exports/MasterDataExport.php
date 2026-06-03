<?php

namespace App\Exports;

use App\Models\MasterData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MasterDataExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return MasterData::select('type', 'name', 'code')->get();
    }

    public function headings(): array
    {
        return [
            'Tipe Data',
            'Nama',
            'Kode',
        ];
    }
}
