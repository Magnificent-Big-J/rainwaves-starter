<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * One generic export used by every admin table export — pass it the rows, the
 * column headings, and a row-mapping closure, rather than writing a dedicated
 * Export class per resource.
 */
class CollectionExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $headings,
        private readonly \Closure $mapRow,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return ($this->mapRow)($row);
    }
}
