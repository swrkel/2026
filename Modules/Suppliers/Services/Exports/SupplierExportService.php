<?php

namespace Modules\Suppliers\Services\Exports;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Modules\Suppliers\Services\SupplierBalanceService;
use Modules\Suppliers\Services\SupplierQueryService;

class SupplierExportService
{
    public function __construct(
        private SupplierQueryService $supplierQueryService,
        private SupplierBalanceService $supplierBalanceService
    ) {
    }

    public function export(string $format, array $filters)
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 404);

        $suppliers = $this->supplierQueryService
            ->listQuery($filters)
            ->orderBy('contacts.id', 'desc')
            ->get();

        $this->attachBalances($suppliers);

        if ($format === 'csv') {
            return $this->csv($suppliers);
        }

        if ($format === 'excel') {
            return $this->excel($suppliers);
        }

        return $this->pdf($suppliers, $filters);
    }


    private function attachBalances(Collection $suppliers): void
    {
        foreach ($suppliers->chunk(500) as $chunk) {
            $ids = $chunk->pluck('id')->map(static fn ($id) => (int) $id)->all();
            $balances = $this->supplierBalanceService->balances($ids);

            foreach ($chunk as $supplier) {
                $supplier->setAttribute(
                    'total_due',
                    (float) data_get($balances, ((int) $supplier->id) . '.total_due', 0)
                );
            }
        }
    }

    private function rows(Collection $suppliers): array
    {
        $rows = [];

        foreach ($suppliers as $supplier) {
            $rows[] = [
                'supplier_no' => (string) ($supplier->contact_id ?? ''),
                'name' => (string) ($supplier->name ?? ''),
                'mobile' => (string) ($supplier->mobile ?? ''),
                'email' => (string) ($supplier->email ?? ''),
                'total_due' => number_format(
                    (float) ($supplier->total_due ?? 0),
                    (int) session('business.currency_precision', 2),
                    session('currency.decimal_separator', '.'),
                    session('currency.thousand_separator', ',')
                ),
                'created_at' => optional($supplier->created_at)->format('Y-m-d H:i'),
            ];
        }

        return $rows;
    }

    private function headings(): array
    {
        return [
            __('suppliers::lang.supplier_no'),
            __('suppliers::lang.name'),
            __('suppliers::lang.mobile'),
            __('suppliers::lang.email'),
            __('contact.total_due'),
            __('suppliers::lang.created_at'),
        ];
    }

    private function csv(Collection $suppliers): Response
    {
        $filename = 'supplier_records_' . now()->format('Ymd_His') . '.csv';
        $rows = $this->rows($suppliers);
        $headings = $this->headings();

        $callback = function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function excel(Collection $suppliers): Response
    {
        $filename = 'supplier_records_' . now()->format('Ymd_His') . '.xls';
        $rows = $this->rows($suppliers);
        $headings = $this->headings();

        $html = view('suppliers::suppliers.exports.excel', compact('headings', 'rows'))->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function pdf(Collection $suppliers, array $filters): Response
    {
        $filename = 'supplier_records_' . now()->format('Ymd_His') . '.html';
        $rows = $this->rows($suppliers);
        $headings = $this->headings();

        $html = view('suppliers::suppliers.exports.pdf', compact('headings', 'rows', 'filters'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
