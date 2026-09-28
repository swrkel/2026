{{--
    Print / Save-as-PDF view for LIST Purchase Entries.

    Deliberately a separate file from entries/print.blade.php, which is the
    single-purchase document rendered by PurchaseEntryPrintController. Reusing
    that name would have broken printing an individual purchase.

    A standalone page with no theme, on purpose. Printing the list screen itself
    would carry the sidebar, filter panel and toolbar onto the paper, and
    suppressing those with @media print rules has repeatedly proved fragile on
    this system - the MPCS tab strip and link URLs kept reappearing because a
    theme rule outweighed the print rule. Nothing here depends on the
    application's stylesheets, so nothing can override it.

    "Export to PDF" uses this same page: the browser's own print-to-PDF needs no
    extra library and produces exactly the layout shown in the preview.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="erp-skip-global-document-chrome" content="1">
    <title>Purchase Entries</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }

        html, body {
            margin: 0; padding: 0; color: #000; background: #fff;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10px; line-height: 1.35;
        }

        .masthead { border-bottom: 1.25px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
        .masthead h1 { margin: 0; font-size: 15px; font-weight: 700; }
        .masthead .meta { margin-top: 3px; font-size: 9px; color: #333; }

        /* auto, not fixed: a fixed column never grows, so a long figure spills
           across the cell border instead of widening it. */
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        th, td { border: .75px solid #444; padding: 4px 6px; vertical-align: middle; }
        th {
            font-size: 9px; font-weight: 700; text-align: left;
            text-transform: uppercase; letter-spacing: .02em;
            border-top: 1.25px solid #000; border-bottom: 1.25px solid #000;
        }

        /* Money columns: equal-width digits so decimal points line up. */
        td.amount, th.amount {
            text-align: right; white-space: nowrap;
            font-variant-numeric: tabular-nums; font-feature-settings: "tnum" 1;
        }

        tfoot td { font-weight: 700; border-top: 1.25px solid #000; }
        .empty { padding: 24px; text-align: center; color: #555; }

        @media print { .no-print { display: none !important; } }
        .no-print { margin-bottom: 10px; }
        .no-print button {
            font: inherit; padding: 6px 14px; border: 1px solid #888;
            border-radius: 4px; background: #f4f4f4; cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="masthead">
        <h1>Purchase Entries</h1>
        <div class="meta">
            @if (!empty($filters['start_date']) || !empty($filters['end_date']))
                Period: {{ $filters['start_date'] ?: '-' }} to {{ $filters['end_date'] ?: '-' }} &nbsp;|&nbsp;
            @endif
            {{ count($rows) }} record(s) &nbsp;|&nbsp; Generated {{ $generated_at }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    {{-- The last three columns are money. --}}
                    <th class="{{ $loop->index >= count($headings) - 3 ? 'amount' : '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $index => $cell)
                        <td class="{{ $index >= count($row) - 3 ? 'amount' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headings) }}">No purchase entries match the selected filters.</td></tr>
            @endforelse
        </tbody>
        @if (count($rows) > 0)
            @php
                // Totalled from the exported rows, so the footer always agrees
                // with what is printed above it.
                $totalAmount = 0.0; $totalPaid = 0.0; $totalDue = 0.0;
                foreach ($rows as $r) {
                    $n = count($r);
                    $totalAmount += (float) ($r[$n - 3] ?? 0);
                    $totalPaid   += (float) ($r[$n - 2] ?? 0);
                    $totalDue    += (float) ($r[$n - 1] ?? 0);
                }
            @endphp
            <tfoot>
                <tr>
                    <td colspan="{{ count($headings) - 3 }}" style="text-align:right;">Total</td>
                    <td class="amount">{{ number_format($totalAmount, 2) }}</td>
                    <td class="amount">{{ number_format($totalPaid, 2) }}</td>
                    <td class="amount">{{ number_format($totalDue, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

</body>
</html>
