<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private int $startRow = 0;
    private int $endRow = 0;

    /**
     * Set the rows that we want to read
     */
    public function __construct(int $startRow, int $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    /**
     * Interface method to determine if a cell should be read
     */
    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        // Always read the header row (row 1) and any row within the target range
        if ($row == 1 || ($row >= $this->startRow && $row <= $this->endRow)) {
            return true;
        }
        return false;
    }
}
