<?php

namespace App\Services;

use ZipArchive;
use XMLReader;

class FastXlsxReader
{
    /**
     * Stream an XLSX file row-by-row yielding an array of cell values indexed by 0-based column index.
     *
     * @param string $filePath
     * @param callable $rowCallback function(array $rowCells, int $rowNumber): void
     */
    public static function readRows(string $filePath, callable $rowCallback): void
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException("Tidak dapat membuka file Excel: $filePath");
        }

        // 1. Read shared strings
        $sharedStrings = [];
        if ($ssXml = $zip->getFromName('xl/sharedStrings.xml')) {
            $xml = simplexml_load_string($ssXml);
            foreach ($xml->si as $val) {
                if (isset($val->t)) {
                    $sharedStrings[] = (string) $val->t;
                } elseif (isset($val->r)) {
                    $text = '';
                    foreach ($val->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
            unset($xml, $ssXml);
        }

        // 2. Open sheet1.xml via XMLReader
        $xmlReader = new XMLReader();
        $realPath = realpath($filePath);
        if (!$realPath || !$xmlReader->open('zip://' . $realPath . '#xl/worksheets/sheet1.xml')) {
            $zip->close();
            throw new \RuntimeException("Tidak dapat membaca worksheet dari file Excel: $filePath");
        }

        $currentRow = 0;
        $rowCells = [];
        $cellType = null;
        $colIndex = 0;

        while ($xmlReader->read()) {
            $nodeType = $xmlReader->nodeType;
            $nodeName = $xmlReader->name;

            if ($nodeType === XMLReader::ELEMENT) {
                if ($nodeName === 'row') {
                    $currentRow = (int) $xmlReader->getAttribute('r');
                    $rowCells = [];
                } elseif ($nodeName === 'c') {
                    $cellRef = $xmlReader->getAttribute('r');
                    $cellType = $xmlReader->getAttribute('t');

                    $colLetter = '';
                    $cellRefLen = strlen($cellRef ?? '');
                    for ($i = 0; $i < $cellRefLen; $i++) {
                        $ch = $cellRef[$i];
                        if ($ch >= 'A' && $ch <= 'Z') {
                            $colLetter .= $ch;
                        } else {
                            break;
                        }
                    }
                    $colIndex = self::columnLetterToIndex($colLetter);
                } elseif ($nodeName === 'v') {
                    $v = $xmlReader->readString();
                    if ($cellType === 's') {
                        $val = $sharedStrings[(int) $v] ?? '';
                    } else {
                        $val = $v;
                    }
                    $rowCells[$colIndex] = $val;
                } elseif ($nodeName === 't' && $cellType === 'inlineStr') {
                    $val = $xmlReader->readString();
                    $rowCells[$colIndex] = $val;
                }
            } elseif ($nodeType === XMLReader::END_ELEMENT) {
                if ($nodeName === 'row') {
                    $rowCallback($rowCells, $currentRow);
                    $rowCells = [];
                }
            }
        }

        $xmlReader->close();
        $zip->close();
    }

    public static function columnLetterToIndex(string $col): int
    {
        static $cache = [];
        if (isset($cache[$col])) {
            return $cache[$col];
        }

        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }
        return $cache[$col] = ($index - 1);
    }
}
