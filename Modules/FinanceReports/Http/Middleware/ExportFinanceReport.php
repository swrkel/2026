<?php

namespace Modules\FinanceReports\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns any Finance Report into a CSV, Excel or PDF download.
 *
 * WHY MIDDLEWARE RATHER THAN CODE IN EACH ACTION
 *
 *   There are twenty report actions on FinanceReportsController and they differ
 *   only in their filters - each builds its rows and hands them to a view. Adding
 *   export handling to every one would be twenty copies of the same four lines,
 *   twenty chances to write it slightly differently, and a twenty-first report
 *   later that quietly has no export.
 *
 *   Here it is written once. The response is intercepted after the action has
 *   done its work, so the export sees exactly the rows the page would have shown,
 *   under exactly the filters that produced them. That is also why the export
 *   cannot drift from the report: it IS the report.
 *
 * WHY THE ROWS ARE COMPLETE
 *
 *   These reports render server-side in full - there is no pagination - so the
 *   view data is the whole result set. An export taken from it is therefore
 *   complete, not the visible page. That matters most for the trial balance,
 *   which is meaningless if partial.
 */
class ExportFinanceReport
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $format = (string) $request->input('export', '');

        if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            return $response;
        }

        // Only a rendered view carries data to export.
        if (! method_exists($response, 'getOriginalContent')) {
            return $response;
        }

        $view = $response->getOriginalContent();

        if (! $view instanceof \Illuminate\Contracts\View\View) {
            return $response;
        }

        $data = $view->getData();
        $isProfitBreakdown = isset($data['profit_rows']);

        /*
         * IS2220: Profit & Loss PDF must contain the complete P&L report, not
         * only the active Profit Breakdown table. The generic export template
         * is intentionally row-oriented and therefore cannot represent the
         * Financial Performance Summary + Income + Expenses + Profit Breakdown
         * sections together. Use the dedicated print/PDF layout for this one
         * report while CSV/Excel continue to export the active breakdown table.
         */
        if ($format === 'pdf' && $isProfitBreakdown && isset($data['report'])) {
            return response()->view('financereports::layouts.profit_loss_pdf', [
                'report' => $data['report'],
                'profit_rows' => collect($data['profit_rows']),
                'profit_totals' => $data['profit_totals'] ?? [],
                'profit_tabs' => $data['profit_tabs'] ?? [],
                'profit_tab' => $data['profit_tab'] ?? 'products',
                'show_location_column' => ! empty($data['show_location_column']),
                'start' => $data['start'] ?? $request->input('start_date'),
                'end' => $data['end'] ?? $request->input('end_date'),
                'location_id' => $data['location_id'] ?? null,
                'locations' => $data['locations'] ?? collect(),
                'generated_at' => now(),
            ]);
        }

        if ($isProfitBreakdown) {
            $rows = collect($data['profit_rows']);

            if ($rows->isNotEmpty() && ! empty($data['profit_totals'])) {
                $rows = $rows->values()->push((object) [
                    'label' => 'Total',
                    'location_label' => null,
                    'quantity' => (float) ($data['profit_totals']['quantity'] ?? 0),
                    'revenue' => (float) ($data['profit_totals']['revenue'] ?? 0),
                    'cost' => (float) ($data['profit_totals']['cost'] ?? 0),
                    'discount' => (float) ($data['profit_totals']['discount'] ?? 0),
                    'profit' => (float) ($data['profit_totals']['profit'] ?? 0),
                    'margin' => (float) ($data['profit_totals']['margin'] ?? 0),
                ]);
            }
        } else {
            $rows = $this->rowsFrom($data);
        }

        if ($rows->isEmpty()) {
            /*
             * Nothing to export. The page is returned instead of an empty file,
             * so the operator sees the report say "no records" rather than
             * downloading a file with only headings and wondering why.
             */
            return $response;
        }

        $columns = $isProfitBreakdown
            ? $this->profitBreakdownColumns($data, $rows)
            : $this->columnsFrom($rows->first());

        if (empty($columns)) {
            return $response;
        }

        return $this->download($request, $format, $rows, $columns);
    }

    /**
     * The report rows, wherever this particular report keeps them.
     *
     * The actions are consistent about building a `report`, but not about its
     * shape: some return ['rows' => ..., 'totals' => ...], some a bare
     * collection, some a plain array. All three are handled rather than assuming
     * one and silently failing on the others.
     */
    protected function rowsFrom(array $data): Collection
    {
        $report = $data['report'] ?? null;

        if (is_array($report) && isset($report['rows'])) {
            return collect($report['rows']);
        }

        if ($report instanceof Collection) {
            return $report;
        }

        if (is_array($report) && $this->looksLikeRows($report)) {
            return collect($report);
        }

        // Some reports pass their rows under their own name.
        foreach (['rows', 'transactions', 'entries', 'ledger', 'accounts'] as $key) {
            if (isset($data[$key]) && ($data[$key] instanceof Collection || is_array($data[$key]))) {
                $candidate = collect($data[$key]);

                if ($candidate->isNotEmpty()) {
                    return $candidate;
                }
            }
        }

        return collect();
    }

    /**
     * Is this array a list of rows, or a keyed structure of totals?
     */
    protected function looksLikeRows(array $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $first = reset($value);

        return is_array($first) || is_object($first);
    }

    /**
     * Column headings taken from the first row.
     *
     * Derived rather than declared per report, so a report gains a column and its
     * export gains it too. Machine keys become readable headings.
     *
     * @return array<string, callable>
     */
    protected function columnsFrom($row): array
    {
        $keys = array_keys((array) $row);
        $columns = [];

        foreach ($keys as $key) {
            // Internal identifiers are noise in an export.
            if (in_array($key, ['id', 'account_id', 'business_id', 'deleted_at'], true)) {
                continue;
            }

            $columns[ucwords(str_replace('_', ' ', (string) $key))] =
                fn ($current) => data_get($current, $key);
        }

        return $columns;
    }

    /**
     * IS2216: Profit & Loss exports must export the active Profit Breakdown
     * table, not the Financial Performance Summary's keyed report array.
     */
    protected function profitBreakdownColumns(array $data, Collection $rows): array
    {
        $firstColumn = data_get(
            $data,
            'profit_tabs.' . ($data['profit_tab'] ?? 'products') . '.column',
            'Description'
        );

        $showLocation = ! empty($data['show_location_column'])
            || $rows->contains(function ($row) {
                return ! empty(data_get($row, 'location_label'));
            });

        $columns = [
            $firstColumn => fn ($row) => data_get($row, 'label'),
        ];

        if ($showLocation) {
            $columns['Location'] = fn ($row) => data_get($row, 'location_label');
        }

        $columns['Quantity'] = fn ($row) => data_get($row, 'quantity');
        $columns['Revenue'] = fn ($row) => data_get($row, 'revenue');
        $columns['Cost'] = fn ($row) => data_get($row, 'cost');
        $columns['Discount'] = fn ($row) => data_get($row, 'discount');
        $columns['Gross Profit'] = fn ($row) => data_get($row, 'profit');
        $columns['Margin %'] = fn ($row) => data_get($row, 'margin');

        return $columns;
    }

    /**
     * CSV and Excel stream; PDF renders as printable HTML.
     *
     * Excel is served as CSV with an .xls extension - it opens directly and needs
     * no spreadsheet library. PDF goes through the browser's print-to-PDF, which
     * avoids a PDF dependency for a plain table. All three render from the same
     * rows and the same columns.
     */
    protected function download(Request $request, string $format, Collection $rows, array $columns)
    {
        $title = Str::title(str_replace(['-', '_'], ' ', (string) ($request->segment(2) ?: 'report')));
        $filename = Str::slug($title) . '-' . date('Y-m-d-His');

        if ($format === 'pdf') {
            $period = trim(($request->input('start_date') ?: '') . ' to ' . ($request->input('end_date') ?: ''));

            return response()->view('financereports::layouts.export_pdf', [
                'title' => $title,
                'rows' => $rows,
                'columns' => $columns,
                'generated_at' => now(),
                'period' => $period,
            ]);
        }

        $extension = $format === 'xlsx' ? 'xls' : 'csv';

        $callback = function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            // Excel needs the BOM to read UTF-8 names correctly.
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, array_keys($columns));

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($resolve) => $resolve($row), $columns));
            }

            fclose($handle);
        };

        $contentType = $format === 'xlsx'
            ? 'application/vnd.ms-excel; charset=UTF-8'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload($callback, $filename . '.' . $extension, [
            'Content-Type' => $contentType,
        ]);
    }
}
