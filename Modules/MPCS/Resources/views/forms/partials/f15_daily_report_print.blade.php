{{--
    IS2029: standalone print page for the F15 Daily Report.

    A complete HTML document, deliberately NOT extending the application layout.
    Nothing from the theme is present, so there is no tab strip, no sidebar
    toggle and no floating widget to suppress - and no risk of a hide-everything
    rule blanking the page, which is what happened when this was printed from the
    working screen.

    The structure mirrors the on-screen report so the two read the same. The one
    intended difference is that the amount rows carry NO SHADING: emphasis is
    given by rules and weight instead, which print identically on every browser
    and do not depend on "Background graphics" being enabled in the print dialog.

    Rendered from the same F15DailyReportService::build() output as the screen,
    so there is no second calculation that could drift.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>F15 Daily Report{{ !empty($report['date']) ? ' - ' . $report['date'] : '' }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 11mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            /* IS2110: 7.5px sat at or below the minimum font size some
               browsers enforce, so the rendered text was larger than the
               layout assumed - another reason figures overflowed. 9px is
               above every common floor and still compact. */
            font-size: 12px;
            line-height: 1.3;
            -webkit-font-smoothing: antialiased;
        }

        /*
         * IS2029: column widths reduced by 50% for print and print preview.
         *
         * The table is table-layout: fixed, so its column percentages are
         * relative to the TABLE. Narrowing them while the table stayed full
         * width would make the browser scale them straight back up and nothing
         * would change - the same trap as the on-screen version.
         *
         * So the SHEET is halved and everything inside it - table, Notes,
         * signatures, the masthead - follows at 100% of that narrower box. They
         * stay on the same two edges, with no ragged gutter, and the sheet is
         * centred so the margins either side are equal.
         *
         * Type scales with it. At full size a figure like 2,175,756.00 needs
         * about 69px and the halved amount column is only 66px, so the numbers
         * would wrap or clip. At 7.5px the widest figure needs about 50px in a
         * 58px column, which fits with room to spare.
         */
        /*
         * IS2110: widened from 50%.
         *
         * The 50% requested in IS2059 does not leave room for seven-figure
         * amounts in three columns at a legible size. 72% keeps the report
         * clearly narrower than the page while letting every figure print in
         * full - a number cut in half is worse than a slightly wider table.
         */
        .sheet { width: 92%; margin: 0 auto; }

        /*
         * Header: business name is the dominant line, with the meta block
         * right-aligned on the same optical line, closed off by a rule so the
         * heading reads as a masthead rather than floating above the table.
         */
        .head {
            display: grid;
            /* Percentages, not fixed pixels: at half width a 170px column would
               leave almost nothing for the title. */
            grid-template-columns: 22% 1fr 22%;
            align-items: end;
            gap: 6px;
            padding-bottom: 6px;
            margin-bottom: 9px;
            border-bottom: 1.25px solid #000;
        }
        .head .title { text-align: center; }
        .head .title h1 {
            margin: 0 0 4px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: .01em;
        }
        .head .title h2 {
            margin: 0;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .head .meta {
            font-size: 11px;
            line-height: 1.5;
            text-align: right;
            white-space: nowrap;
        }
        .head .meta strong { font-weight: 700; }

        /*
         * IS2110: the table now sizes to its CONTENT.
         *
         * It was table-layout: fixed with the columns pinned to percentages.
         * With fixed layout a column NEVER grows - if the text is wider than the
         * declared width it simply spills across the cell border. That is what
         * the reported preview shows: figures like 2,815,598.64 clipped and
         * running into the next column.
         *
         * The narrower the sheet, the worse it got, so the 50% reduction made a
         * latent problem visible rather than causing it.
         *
         * table-layout: auto lets each column take the width its content needs.
         * The min-widths below keep the intended proportions as a floor, so the
         * layout still looks like the screen report, but a long figure widens
         * its column instead of being cut in half.
         */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        /* Floors, not fixed widths - the column grows past these when needed. */
        col.c-no   { width: 7%; }
        col.c-desc { width: 30%; }
        col.c-amt  { width: 21%; }

        th, td {
            border: .75px solid #444;
            padding: 3px 4px;
            vertical-align: middle;
            height: 15px;
        }

        thead th {
            font-weight: 700;
            font-size: 11.5px;
            text-align: center;
            letter-spacing: .02em;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 4px 4px;
        }

        td.no   { text-align: center; }
        td.desc { text-align: left; padding-left: 6px; }

        /*
         * tabular-nums makes every digit the same width, so the decimal points
         * line up down the column instead of drifting. This is the single change
         * that most affects how a figures table reads.
         */
        td.amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1;
            padding-right: 6px;
            /* nowrap plus auto layout means the column widens to hold the
               figure, rather than the figure breaking across two lines. */
            white-space: nowrap;
            min-width: 62px;
        }

        th:nth-child(n+3) { min-width: 62px; }

        /* Section headings: spaced small caps, ruled above and below, no fill. */
        tr.section td {
            font-weight: 700;
            font-size: 11.5px;
            letter-spacing: .05em;
            text-transform: uppercase;
            text-align: left;
            padding: 4px 6px;
            border-top: 1.25px solid #000;
            border-bottom: .75px solid #000;
        }

        /* Emphasis by rule and weight, never by shading. */
        tr.total td   { font-weight: 700; border-top: 1.25px solid #000; }
        tr.grand td   { font-weight: 700; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
        tr.balance td { font-weight: 700; border-top: 1.5px solid #000; }

        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }

        .notes { margin-top: 14px; page-break-inside: avoid; }
        .notes label {
            display: block;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .notes .box {
            border: .75px solid #444;
            min-height: 34px;
            padding: 4px 6px;
            white-space: pre-wrap;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
            page-break-inside: avoid;
            text-align: center;
        }
        .signatures .name {
            border-bottom: .75px solid #000;
            min-height: 22px;
            margin-bottom: 5px;
            padding-bottom: 2px;
        }
        .signatures .role {
            font-weight: 700;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .signatures .date { margin-top: 9px; font-size: 10px; }

        .toolbar { margin-bottom: 12px; text-align: right; }
        .toolbar button {
            padding: 7px 16px;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid #444;
            background: #f5f5f5;
            border-radius: 3px;
        }
        .toolbar .hint { font-size: 11px; color: #555; margin-right: 10px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    {{-- Screen-only. Confirms at a glance that this is the standalone print
         page and not the report printed from the working screen. --}}
    <div class="toolbar">
        <span class="hint">F15 standalone print page &mdash; no theme, no tabs, no sidebar</span>
        <button type="button" onclick="window.print();">Print</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div></div>
            <div class="title">
                <h1>{{ $report['location_name'] ?? '' }}</h1>
                <h2>Daily Report</h2>
            </div>
            <div class="meta">
                <div><strong>Date:</strong> {{ $report['date'] ?? '' }}</div>
                <div><strong>F 15 No:</strong> {{ $report['form_no'] ?? '-' }}</div>
            </div>
        </div>

        <table>
            <colgroup>
                <col class="c-no">
                <col class="c-desc">
                <col class="c-amt">
                <col class="c-amt">
                <col class="c-amt">
            </colgroup>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Description</th>
                    <th>Previous Day</th>
                    <th>Today</th>
                    <th>As of Today</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($report['rows'] ?? []) as $row)
                    @if(($row['type'] ?? '') === 'section')
                        <tr class="section">
                            <td colspan="5">{{ $row['description'] ?? '' }}</td>
                        </tr>
                    @else
                        @php
                            // Match the on-screen emphasis without reusing its fills.
                            $rowClass = '';
                            if (! empty($row['grand_row'])) {
                                $rowClass = 'grand';
                            } elseif (! empty($row['balance_row'])) {
                                $rowClass = 'balance';
                            } elseif (! empty($row['total_row'])) {
                                $rowClass = 'total';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="no">{{ $row['no'] ?? '' }}</td>
                            <td class="desc">{{ $row['description'] ?? '' }}</td>
                            <td class="amount">{{ number_format((float) ($row['previous'] ?? 0), 2) }}</td>
                            <td class="amount">{{ number_format((float) ($row['today'] ?? 0), 2) }}</td>
                            <td class="amount">{{ number_format((float) ($row['total'] ?? 0), 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="notes">
            <label>Notes</label>
            <div class="box">{{ $report['notes'] ?? '' }}</div>
        </div>

        <div class="signatures">
            <div>
                <div class="name">{{ $report['prepared_by'] ?? '' }}</div>
                <div class="role">Prepared By</div>
                <div class="date">Date: {{ $report['prepared_date'] ?? '' }}</div>
            </div>
            <div>
                <div class="name">{{ $report['checked_by'] ?? '' }}</div>
                <div class="role">Checked By</div>
                <div class="date">Date: {{ $report['checked_date'] ?? '' }}</div>
            </div>
            <div>
                <div class="name">{{ $report['approved_by'] ?? '' }}</div>
                <div class="role">Approved By</div>
                <div class="date">Date: {{ $report['approved_date'] ?? '' }}</div>
            </div>
        </div>
    </div>

    <script>
        // Open the print dialog automatically, but only when the page was opened
        // for printing. ?auto=0 leaves it as a plain preview.
        (function () {
            var params = new URLSearchParams(window.location.search);

            if (params.get('auto') !== '0') {
                window.addEventListener('load', function () { window.print(); });
            }
        }());
    </script>
</body>
</html>
