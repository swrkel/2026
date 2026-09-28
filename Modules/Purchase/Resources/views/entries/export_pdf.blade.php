{{--
    IS2047: the PDF export.

    Rendered as printable HTML and sent to the browser's own print-to-PDF, rather
    than through a PDF library. The list has ten plain text columns and no
    layout requirement beyond fitting the page, so a dependency would buy
    nothing and would be one more thing to keep working.

    It renders from the same row set and the same column list the CSV and Excel
    exports use, passed in from the controller, so the three cannot disagree.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Entries</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 18px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; }
        /* The three money columns are the last three. */
        td:nth-last-child(-n+3), th:nth-last-child(-n+3) { text-align: right; }
        tr:nth-child(even) td { background: #fafcff; }
        .notice { margin-top: 10px; padding: 6px 8px; border: 1px solid #fca5a5; background: #fef2f2; color: #991b1b; }

        @media print {
            /* Repeat the header on every page of a long list. */
            thead { display: table-header-group; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <h1>Purchase Entries</h1>
    <div class="meta">
        {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('entry', $rows->count()) }}
        &middot; generated {{ $generated_at->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach(array_keys($columns) as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $resolve)
                        <td>{{ $resolve($row) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No purchase entries matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($truncated)
        {{-- Said plainly rather than quietly cutting the list off. --}}
        <div class="notice">
            This export reached the maximum of {{ number_format(\Modules\Purchase\Services\Entry\PurchaseEntryListService::EXPORT_LIMIT) }}
            rows and may be incomplete. Narrow the date range or choose a supplier, then export again.
        </div>
    @endif

</body>
</html>
