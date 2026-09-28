<?php

namespace Modules\PetroDirect\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

abstract class BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;
    protected array $headings;
    protected array $columns;

    public function __construct($query, array $headings, array $columns)
    {
        $this->query = $query;
        $this->headings = $headings;
        $this->columns = $columns;
    }

    public function collection(): Collection
    {
        if ($this->query instanceof Collection) {
            return $this->query;
        }

        return $this->query->get();
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return array_map(function ($column) use ($row) {
            return data_get($row, $column, '');
        }, $this->columns);
    }
}
