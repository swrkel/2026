{{--
    The PDF export for any Finance Report.

    Rendered as printable HTML and handed to the browser's own print-to-PDF
    rather than going through a PDF library. These reports are plain tables of
    text and figures with no layout requirement beyond fitting the page, so a
    library would add a dependency and a failure mode for no gain.

    It renders from the same rows and the same column list the CSV and Excel
    exports use, passed in by the controller, so the three cannot disagree.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 18px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #64748b; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; }
        tr:nth-child(even) td { background: #fafcff; }

        /* Figures read as columns; the last two are the money columns on every
           report here. */
        td:nth-last-child(-n+2), th:nth-last-child(-n+2) { text-align: right; }

        @media print {
            /* Repeat the header on each page of a long report. */
            thead { display: table-header-group; }
        }
    </style>
</head>
<body onload="window.print()">

    <h1>{{ $title }}</h1>
    <div class="meta">
        @if(!empty($period) && trim($period) !== 'to')
            Period: {{ $period }} &middot;
        @endif
        {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('row', $rows->count()) }}
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
                    <td colspan="{{ count($columns) }}">No records matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
