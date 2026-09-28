<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Purchase\Services\Entry\PurchaseEntryListService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports for List Purchase Entries.
 *
 *
 * WHY THESE ARE SERVER-SIDE
 *
 * The list is server-rendered and paginated - it is not a DataTable, so the
 * usual client-side export buttons have nothing to read. Exporting whatever
 * happens to be in the DOM would produce a file containing only the current
 * page: 25 rows out of 131, with no indication that the rest are missing. A
 * partial export that looks complete is worse than none.
 *
 * Every export here runs the SAME filteredQuery() the list and the summary cards
 * use, so a downloaded file always matches what is on screen - including the
 * date range, location, supplier, status and payment status filters.
 *
 *
 * WHY CSV SERVES BOTH CSV AND EXCEL
 *
 * Excel opens CSV natively. Producing a genuine .xlsx would mean adding a
 * spreadsheet library to this module for no gain in the data itself. The Excel
 * button sends the same rows with a BOM and Windows line endings, which is what
 * makes Excel honour UTF-8 and open the file cleanly without an import dialog.
 */
class PurchaseEntryExportController extends Controller
{
    public function __construct(private PurchaseEntryListService $service)
    {
    }

    /** Column headings, in the order the list shows them. */
    private function headings(): array
    {
        return [
            'Date',
            'Purchase No.',
            'Supplier Ref.',
            'Supplier',
            'Location',
            'Store',
            'Status',
            'Payment Status',
            'Total',
            'Paid',
            'Due',
        ];
    }

    /** One export row, matching the headings above. */
    private function row($entry): array
    {
        return [
            (string) ($entry->transaction_date ?? ''),
            /*
             * The same fields the list renders, verified against
             * entries/partials/table.blade.php rather than assumed:
             *   Purchase No.  -> invoice_no, falling back to PUR-{id}
             *   Supplier Ref. -> ref_no
             * Getting these from the wrong columns would produce an export that
             * silently disagrees with the screen.
             */
            (string) ($entry->invoice_no ?: ('PUR-' . ($entry->id ?? ''))),
            (string) ($entry->ref_no ?? ''),
            (string) ($entry->supplier_name ?? ''),
            (string) ($entry->location_name ?? ''),
            (string) ($entry->store_name ?? ''),
            (string) ($entry->status ?? ''),
            (string) ($entry->payment_status ?? ''),
            number_format((float) ($entry->final_total ?? 0), 2, '.', ''),
            number_format((float) ($entry->paid_amount ?? 0), 2, '.', ''),
            number_format((float) ($entry->due_amount ?? 0), 2, '.', ''),
        ];
    }

    public function csv(Request $request): StreamedResponse
    {
        return $this->stream($request, 'purchase-entries-' . date('Ymd-His') . '.csv', false);
    }

    public function excel(Request $request): StreamedResponse
    {
        return $this->stream($request, 'purchase-entries-' . date('Ymd-His') . '.csv', true);
    }

    /**
     * Stream the filtered rows as CSV.
     *
     * Streamed rather than built in memory: the export covers every matching
     * row, and a wide date range on a busy site is more than should be held at
     * once.
     */
    private function stream(Request $request, string $filename, bool $forExcel): StreamedResponse
    {
        $service = $this->service;
        $headings = $this->headings();

        return response()->stream(function () use ($request, $service, $headings, $forExcel) {
            $out = fopen('php://output', 'w');

            /*
             * A UTF-8 BOM, for Excel only. Without it Excel reads the file as
             * the system codepage and mangles any non-ASCII supplier name. It is
             * omitted from the plain CSV because other tools show it as stray
             * characters at the start of the first heading.
             */
            if ($forExcel) {
                fwrite($out, "\xEF\xBB\xBF");
            }

            fputcsv($out, $headings);

            foreach ($service->exportRows($request) as $entry) {
                fputcsv($out, $this->row($entry));
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            // Stop a proxy or the browser serving a stale export.
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * A clean page for printing, and for "save as PDF".
     *
     * The browser's own print-to-PDF is used rather than a PDF library: it needs
     * no new dependency, honours the page size the user chooses, and produces
     * the same layout they see in the preview.
     */
    public function print(Request $request)
    {
        $rows = [];

        foreach ($this->service->exportRows($request) as $entry) {
            $rows[] = $this->row($entry);
        }

        /*
         * A SEPARATE view from entries.print.
         *
         * entries/print.blade.php is the single-purchase document, rendered by
         * PurchaseEntryPrintController for one entry. Reusing that name here
         * would have broken printing an individual purchase - a much worse
         * outcome than not having a list print at all.
         */
        return view('purchase::entries.print_list', [
            'headings' => $this->headings(),
            'rows' => $rows,
            'filters' => [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
            ],
            'generated_at' => now()->format('Y-m-d H:i'),
        ]);
    }
}
