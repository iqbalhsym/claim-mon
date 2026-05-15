<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasterPasienSeeder extends Seeder
{
    // Kolom yang dibutuhkan (0-indexed, A=0)
    // A=0  : Medical Record No. (no_rm)
    // B=1  : Patient Name (nama_pasien)
    // X=23 : KAT Guarantor (kat_guarantor)
    // Y=24 : Guarantor (guarantor)
    // AJ=35: Provinces (provinsi)
    // AL=37: KAT EDITED (kabupaten_kota)
    // BD=55: Created Date (tanggal_kunjungan)
    private array $neededColIdx = [0, 1, 23, 24, 35, 37, 55];

    public function run(): void
    {
        ini_set('memory_limit', '1G');

        $filePath = database_path('seeders/master_pasien.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan: $filePath");
            return;
        }

        $this->command->info('Menghapus data geografi pasien lama...');
        DB::table('patient_geographies')->truncate();

        $this->command->info('Membaca file xlsx (native ZIP+XML parser)...');

        // Buka xlsx sebagai ZIP
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            $this->command->error('Gagal membuka file xlsx!');
            return;
        }

        // 1. Baca shared strings (lookup tabel string)
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $this->command->info('Memuat shared strings...');
            $xml = simplexml_load_string($ssXml);
            foreach ($xml->si as $si) {
                // Gabungkan semua <t> dalam satu <si>
                $val = '';
                foreach ($si->r as $r) {
                    $val .= (string) $r->t;
                }
                if (empty($val)) {
                    $val = (string) $si->t;
                }
                $sharedStrings[] = $val;
            }
            $this->command->info('  Shared strings: ' . count($sharedStrings));
        }

        // 2. Temukan sheet "2025 - 03,2026"
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $sheetFile   = null;
        if ($workbookXml) {
            $wb = simplexml_load_string($workbookXml);
            $wb->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

            // Baca relasi sheet
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            $rels     = [];
            if ($relsXml) {
                $relsDoc = simplexml_load_string($relsXml);
                foreach ($relsDoc->Relationship as $rel) {
                    $rels[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }

            foreach ($wb->sheets->sheet as $sheet) {
                $name = (string) $sheet['name'];
                if ($name === '2025 - 03,2026') {
                    $rId = (string) $sheet->attributes('r', true)['id'];
                    if (isset($rels[$rId])) {
                        $target = $rels[$rId];
                        $sheetFile = 'xl/' . ltrim($target, '/');
                    }
                    break;
                }
            }
        }

        if (!$sheetFile) {
            $this->command->error('Sheet "2025 - 03,2026" tidak ditemukan!');
            $zip->close();
            return;
        }

        $this->command->info("Sheet ditemukan: {$sheetFile}");

        // 3. Parse sheet XML dengan XMLReader (streaming, hemat memory)
        $sheetContent = $zip->getFromName($sheetFile);
        $zip->close();

        if (!$sheetContent) {
            $this->command->error('Gagal membaca konten sheet!');
            return;
        }

        $this->command->info('Memproses baris data...');

        $start = Carbon::create(2025, 1, 1);
        $end   = Carbon::create(2026, 3, 31);
        $now   = now()->toDateTimeString();

        $inserted    = 0;
        $skipped     = 0;
        $rowNum      = 0;
        $insertBatch = [];
        $batchSize   = 1000;

        // Parse dengan XMLReader untuk streaming
        $reader = new \XMLReader();
        $reader->XML($sheetContent);

        $inRow      = false;
        $inCell     = false;
        $inValue    = false;
        $cellType   = '';
        $cellCol    = 0;
        $currentRow = [];
        $currentVal = '';

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT) {
                if ($reader->localName === 'row') {
                    $inRow      = true;
                    $currentRow = [];
                    $rowNum++;
                } elseif ($reader->localName === 'c' && $inRow) {
                    $inCell   = true;
                    $cellType = $reader->getAttribute('t') ?? '';
                    $ref      = $reader->getAttribute('r') ?? '';
                    // Ambil kolom dari referensi (misal "A1" → 0, "B1" → 1, "BD1" → 55)
                    preg_match('/^([A-Z]+)/', $ref, $m);
                    $cellCol = $m ? $this->colLetterToIndex($m[1]) : 0;
                    $currentVal = '';
                } elseif ($reader->localName === 'v' && $inCell) {
                    $inValue = true;
                    $currentVal = '';
                }
            } elseif ($reader->nodeType === \XMLReader::TEXT && $inValue) {
                $currentVal .= $reader->value;
            } elseif ($reader->nodeType === \XMLReader::END_ELEMENT) {
                if ($reader->localName === 'v') {
                    $inValue = false;
                    // Resolve nilai
                    if ($cellType === 's') {
                        // Shared string
                        $val = $sharedStrings[(int) $currentVal] ?? '';
                    } else {
                        $val = $currentVal;
                    }
                    $currentRow[$cellCol] = $val;
                } elseif ($reader->localName === 'c') {
                    $inCell = false;
                } elseif ($reader->localName === 'row') {
                    $inRow = false;

                    // Skip header
                    if ($rowNum === 1) continue;

                    $noRm = trim((string) ($currentRow[0] ?? ''));
                    if (empty($noRm)) { $skipped++; continue; }

                    $katEdited = trim((string) ($currentRow[37] ?? ''));
                    if (empty($katEdited)) { $skipped++; continue; }

                    $provinsi = trim((string) ($currentRow[35] ?? ''));
                    if (empty($provinsi)) $provinsi = $this->inferProvinsi($katEdited);

                    $katGuarantor = strtoupper(trim((string) ($currentRow[23] ?? '')));
                    $katGuarantor = ($katGuarantor === 'JKN') ? 'JKN' : 'Non JKN';

                    $guarantor = trim((string) ($currentRow[24] ?? ''));

                    $tanggal = $this->parseExcelDate($currentRow[55] ?? null);

                    // Filter periode
                    if ($tanggal && ($tanggal->lt($start) || $tanggal->gt($end))) {
                        $skipped++;
                        continue;
                    }

                    $insertBatch[] = [
                        'no_rm'             => $noRm,
                        'nama_pasien'       => trim((string) ($currentRow[1] ?? '')),
                        'alamat'            => '-',
                        'provinsi'          => $provinsi,
                        'kabupaten_kota'    => $katEdited,
                        'guarantor'         => $guarantor,
                        'kat_guarantor'     => $katGuarantor,
                        'tanggal_kunjungan' => $tanggal?->toDateString(),
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    if (count($insertBatch) >= $batchSize) {
                        DB::table('patient_geographies')->insertOrIgnore($insertBatch);
                        $inserted += count($insertBatch);
                        $insertBatch = [];
                        $this->command->info("  Baris {$rowNum} | Inserted: {$inserted} | Skipped: {$skipped}");
                    }
                }
            }
        }

        $reader->close();

        // Sisa batch
        if (!empty($insertBatch)) {
            DB::table('patient_geographies')->insertOrIgnore($insertBatch);
            $inserted += count($insertBatch);
        }

        $this->command->newLine();
        $this->command->info('✓ Selesai!');
        $this->command->info("  Berhasil diimport : {$inserted} data");
        $this->command->info("  Dilewati           : {$skipped} data");
        $this->command->info('  Total di database  : ' . DB::table('patient_geographies')->count() . ' data');
    }

    /**
     * Konversi huruf kolom Excel ke index 0-based
     * A=0, B=1, ..., Z=25, AA=26, ..., BD=55
     */
    private function colLetterToIndex(string $col): int
    {
        $col   = strtoupper($col);
        $index = 0;
        $len   = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1; // 0-based
    }

    private function parseExcelDate($value): ?Carbon
    {
        if (empty($value) && $value !== '0') return null;
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

    private function inferProvinsi(string $katEdited): string
    {
        $kat = strtolower($katEdited);
        if (str_contains($kat, 'jakarta') || str_contains($kat, 'dki'))                                                                       return 'DKI Jakarta';
        if (str_contains($kat, 'bekasi') || str_contains($kat, 'bogor') || str_contains($kat, 'depok') || str_contains($kat, 'bandung'))      return 'Jawa Barat';
        if (str_contains($kat, 'tangerang') || str_contains($kat, 'serang') || str_contains($kat, 'cilegon') || str_contains($kat, 'banten')) return 'Banten';
        if (str_contains($kat, 'jawa barat'))  return 'Jawa Barat';
        if (str_contains($kat, 'jawa tengah')) return 'Jawa Tengah';
        if (str_contains($kat, 'jawa timur'))  return 'Jawa Timur';
        if (str_contains($kat, 'yogya'))       return 'DI Yogyakarta';
        return 'Lainnya';
    }
}
