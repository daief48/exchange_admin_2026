<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Generates the downloadable .xlsx bulk-upload template for a master entity,
 * with a header row plus one sample row demonstrating the expected format.
 */
class MasterTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected array $templateHeadings,
        protected array $templateRows,
    ) {
    }

    public function array(): array
    {
        return $this->templateRows;
    }

    public function headings(): array
    {
        return $this->templateHeadings;
    }
}
