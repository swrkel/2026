<?php

namespace App\Exports;

// Modified by Engr. Alex -- task 7889
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomerListExport implements FromCollection, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Customers';
    }
}
