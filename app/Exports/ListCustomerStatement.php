<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class ListCustomerStatement implements FromView, WithTitle, WithEvents
{
    protected $logo;
    protected $contact;
    protected $business_details;
    protected $for_pdf;
    protected $location_details;
    protected $reprint_no;
    protected $statement;
    protected $visible_cols = []; // ← أسماء الأعمدة الظاهرة
    protected $bill_lines;        // ← بيانات الصفوف

    public function __construct(
        $logo,
        $contact,
        $business_details,
        $for_pdf,
        $location_details,
        $reprint_no,
        $row,
        array $visibleCols,   // ← الآن أسماء (date, invoice_no, ...)
        $billLines            // ← Collection|array
    ){
        $this->logo             = $logo;
        $this->contact          = $contact;
        $this->business_details = $business_details;
        $this->for_pdf          = $for_pdf;
        $this->location_details = $location_details;
        $this->reprint_no       = $reprint_no;
        $this->statement        = $row;
        $this->visible_cols     = $visibleCols;
        $this->bill_lines       = $billLines;
    }

    public function view(): View
    {
        return view('customer_statement.list_customer_statement_export', [
            'logo'             => $this->logo,
            'contact'          => $this->contact,
            'business_details' => $this->business_details,
            'for_pdf'          => $this->for_pdf,
            'location_details' => $this->location_details,
            'statement'        => $this->statement,
            'reprint_no'       => $this->reprint_no,
            'visible_cols'     => $this->visible_cols, // ← أسماء
            'bill_lines'       => $this->bill_lines,
        ]);
    }

    public function title(): string
    {
        return 'customer-statement';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // أي تهيئة إضافية للورقة (اختياري)
            },
        ];
    }
}
