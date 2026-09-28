<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print F 20 Form – CDS</title>
@php
    $f20_currency_precision = isset($currency_precision) ? (int) $currency_precision : 2;
    $f20_quantity_precision = isset($quantity_precision) ? (int) $quantity_precision : 3;

    $formatAmount = function ($value) use ($f20_currency_precision) {
        return number_format((float) ($value ?? 0), $f20_currency_precision, '.', ',');
    };
    $formatQty = function ($value) use ($f20_quantity_precision) {
        return number_format((float) ($value ?? 0), $f20_quantity_precision, '.', ',');
    };

    $rowFor = function ($section) use ($details) {
        if (!isset($details[$section])) {
            return null;
        }
        return collect($details[$section])->first();
    };

    $meterRows = [
        'last_meter' => 'Last Meter',
        'starting_meter' => 'Starting Meter',
        'total_sale' => 'Total Sale',
        'balance' => 'Balance',
        'pumps_checked' => 'Testing',
        'cash_sale' => 'Cash Sale',
    ];

    $tankRows = [
        'previous_balance' => 'Previous Balance Qty',
        'received_qty' => 'Received Qty',
        'total' => 'Total',
        'issued' => 'Issued',
        'balance_qty' => 'Balance Qty',
        'testing_qty' => 'Testing Qty',
        'days_balance_qty' => "Day's Balance Qty",
    ];

    $displayPumps = collect($pumps ?? []);
    if ($displayPumps->isEmpty()) {
        $displayPumps = collect(range(1, 9))->map(function ($id) {
            return (object) ['id' => $id, 'pump_name' => (string) $id];
        });
    }

    $displayTanks = collect($tanks ?? []);
    if ($displayTanks->isEmpty()) {
        $displayTanks = collect([1, 2])->map(function ($id) {
            return (object) ['id' => $id, 'tank_name' => 'Tank No xxxx'];
        });
    }

    $dailySalesRows = collect($detail_rows ?? [])->filter(function ($row) {
        return strpos((string) $row->section, 'daily_sales_') === 0;
    })->sortBy(function ($row) {
        return (int) preg_replace('/\D+/', '', (string) $row->section);
    })->values();

    $creditRows = collect($detail_rows ?? [])->filter(function ($row) {
        return strpos((string) $row->section, 'credit_customer_') === 0;
    })->sortBy(function ($row) {
        return (int) preg_replace('/\D+/', '', (string) $row->section);
    })->values();

    $dailyRowCount = max(10, $dailySalesRows->count());
    $creditRowCount = max(10, $creditRows->count());

    $printBusinessName = !empty($header->society_name)
        ? $header->society_name
        : (optional($business)->name ?: 'Business');
    $printLocationName = optional($location)->name ?: 'All Locations';
    $printManagerName = !empty($header->manager_name) ? $header->manager_name : '';
@endphp

<style>
    /*
     * IS2275 / IS2270 - F20 CDS Print Preview
     * Keep this report self-contained.  The old preview inherited the application
     * layout width and then used a 1180px minimum width/print zoom, which made the
     * report appear extremely small and stretched in the preview.
     */
    .f20-cds-print-page {
        background: #f7f8fa;
        padding-bottom: 24px;
    }

    .f20-cds-print-actions {
        max-width: 1180px;
        margin: 0 auto 10px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .f20-cds-paper-wrap {
        width: 100%;
        overflow-x: auto;
        background: transparent;
        border: 0;
        padding: 0 10px;
        box-shadow: none;
        box-sizing: border-box;
    }

    .f20-cds-paper {
        width: 100%;
        max-width: 1180px;
        min-width: 940px;
        margin: 0 auto;
        padding: 18px 20px 22px;
        box-sizing: border-box;
        background: #fff;
        color: #111;
        font-family: Arial, 'Noto Sans Sinhala', sans-serif;
        border: 1px solid #d7dce1;
        box-shadow: 0 1px 5px rgba(0,0,0,.08);
    }

    /* Requested reference layout: Daily Report is the only report heading. */
    .f20-cds-daily-report {
        text-align: center;
        font-size: 24px;
        line-height: 1.15;
        font-weight: 800;
        margin: 0 0 14px;
    }

    .f20-cds-print-masthead {
        display: grid;
        grid-template-columns: 27% 46% 27%;
        align-items: end;
        gap: 10px;
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #273849;
    }

    .f20-cds-print-business {
        text-align: center;
        font-size: 18px;
        line-height: 1.15;
        font-weight: 800;
        margin-bottom: 3px;
    }

    .f20-cds-print-masthead .f20-cds-daily-report {
        font-size: 21px;
        margin: 0;
    }

    .f20-cds-print-meta {
        font-size: 11px;
        line-height: 1.35;
    }

    .f20-cds-print-meta-right { text-align: right; }
    .f20-cds-print-meta strong { font-weight: 700; }

    .f20-section-title,
    .balance-stock-title {
        font-size: 13px;
        line-height: 1.2;
        font-weight: 700;
        margin: 0 0 6px;
    }

    .f20-grid-table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        table-layout: fixed;
        background: #fff;
    }

    .f20-grid-table th,
    .f20-grid-table td {
        border: 1px solid #aeb5bc;
        height: 29px;
        padding: 0;
        font-size: 11px;
        line-height: 1.15;
        vertical-align: middle;
        box-sizing: border-box;
    }

    .f20-grid-table th {
        background: #edf1f4;
        text-align: center;
        font-weight: 700;
        padding: 5px 4px;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .f20-grid-table .row-label {
        background: #edf1f4;
        font-weight: 700;
        text-align: left;
        padding: 5px 6px;
    }

    .f20-cell-value {
        width: 100%;
        min-height: 28px;
        padding: 5px 6px;
        display: flex;
        align-items: center;
        box-sizing: border-box;
        overflow: hidden;
    }

    .f20-num {
        text-align: right;
        justify-content: flex-end;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .f20-meter-table {
        margin-bottom: 16px;
    }

    .f20-cds-lower {
        display: grid;
        grid-template-columns: minmax(0, 1.12fr) minmax(0, .88fr);
        column-gap: 34px;
        align-items: start;
    }

    .daily-sales-wrap {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 8px;
    }

    .f20-total-row td {
        font-weight: 700;
        background: #fafafa;
    }

    @media (max-width: 1050px) {
        .f20-cds-paper-wrap { padding: 0 6px; }
        .f20-cds-paper { min-width: 900px; padding: 14px; }
    }

    body {
        margin: 0;
        padding: 14px;
        background: #f7f8fa;
        color: #111;
        font-family: Arial, 'Noto Sans Sinhala', sans-serif;
    }

    .content-header {
        max-width: 1180px;
        margin: 0 auto 10px;
    }

    .content-header h1 {
        font-size: 20px;
        margin: 0;
    }

    .btn {
        display: inline-block;
        padding: 6px 10px;
        border: 1px solid #b9c2cc;
        border-radius: 4px;
        background: #fff;
        color: #222;
        text-decoration: none;
        cursor: pointer;
        font-size: 12px;
    }

    .btn-primary {
        background: #337ab7;
        border-color: #2e6da4;
        color: #fff;
    }

    @media print {
        @page { size: A4 landscape; margin: 5mm; }

        html,
        body {
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
        }

        body a[href]:after,
        body abbr[title]:after,
        a[href]:after,
        abbr[title]:after {
            content: "" !important;
            display: none !important;
        }

        .no-print,
        .content-header,
        .header-area,
        .header-area *,
        .main-header,
        .main-header *,
        .main-sidebar,
        .main-sidebar *,
        .sidebar,
        .sidebar *,
        .main-footer,
        .breadcrumb,
        .page-title-area,
        .page-title-area *,
        .nav-btn,
        .nav-btn *,
        .sidebar-toggle {
            display: none !important;
        }

        .content-wrapper,
        .right-side,
        .main-content,
        .page-container,
        .f20-cds-print-page,
        section.content {
            width: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        .f20-cds-paper-wrap {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        /*
         * Fit directly to the printable A4-landscape width instead of the old
         * 185% width + zoom trick.  This keeps browser preview and paper output
         * at the same proportions and prevents the tiny stretched result.
         */
        .f20-cds-paper {
            width: 100% !important;
            min-width: 0 !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
            zoom: 1 !important;
            page-break-inside: avoid !important;
            break-inside: avoid-page !important;
        }

        .f20-cds-daily-report {
            font-size: 21px !important;
            margin: 0 !important;
        }

        .f20-section-title,
        .balance-stock-title {
            font-size: 12px !important;
            margin: 0 0 5px !important;
        }

        .f20-cds-print-masthead {
            margin: 0 0 8px !important;
            padding-bottom: 6px !important;
            gap: 8px !important;
        }

        .f20-cds-print-business { font-size: 16px !important; }
        .f20-cds-print-meta { font-size: 10.5px !important; }

        .f20-meter-table { margin-bottom: 10px !important; }
        .f20-cds-lower { column-gap: 18px !important; }
        .daily-sales-wrap { gap: 6px !important; }

        .f20-grid-table th,
        .f20-grid-table td {
            height: 24px !important;
            padding: 2px 4px !important;
            font-size: 10.5px !important;
            line-height: 1.15 !important;
        }

        .f20-grid-table th,
        .f20-grid-table .row-label {
            padding: 2px 4px !important;
        }

        .f20-cell-value {
            min-height: 22px !important;
            height: 22px !important;
            padding: 2px 4px !important;
            font-size: 10.5px !important;
            line-height: 1.15 !important;
        }
    }
</style>
</head>
<body>

<section class="content-header no-print">
    <h1>F 20 Form – CDS <small>Print Preview</small></h1>
</section>

<section class="content f20-cds-print-page">
    <div class="f20-cds-print-actions no-print">
        <a href="{{ action('\\Modules\\MPCS\\Http\\Controllers\\F20CDSFormController@list') }}" class="btn btn-default btn-sm">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
        <button type="button" onclick="window.print();" class="btn btn-primary btn-sm">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="f20-cds-paper-wrap">
        <div class="f20-cds-paper">
            <div class="f20-cds-print-masthead">
                <div class="f20-cds-print-meta">
                    <div><strong>Location:</strong> {{ $printLocationName }}</div>
                    @if($printManagerName !== '')
                        <div><strong>Manager:</strong> {{ $printManagerName }}</div>
                    @endif
                </div>
                <div>
                    <div class="f20-cds-print-business">{{ $printBusinessName }}</div>
                    <div class="f20-cds-daily-report">Daily Report</div>
                </div>
                <div class="f20-cds-print-meta f20-cds-print-meta-right">
                    <div><strong>Form No:</strong> {{ $header->form_no }}</div>
                    <div><strong>Date:</strong> {{ !empty($header->form_date) ? \Carbon\Carbon::parse($header->form_date)->format('Y-m-d') : '' }}</div>
                    <div><strong>Form:</strong> F 20 Form – CDS</div>
                </div>
            </div>

            <div class="f20-section-title">Meter Reading Section</div>
            <table class="f20-grid-table f20-meter-table">
                <colgroup>
                    <col style="width:17%;">
                    @foreach($displayPumps as $pump)
                        <col style="width:{{ round(83 / max(1, $displayPumps->count()), 2) }}%;">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        <th>Pump No.</th>
                        @foreach($displayPumps as $pump)
                            <th>{{ $pump->pump_name ?: ('Pump ' . $pump->id) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($meterRows as $rowKey => $rowLabel)
                        <tr>
                            <td class="row-label">{{ $rowLabel }}</td>
                            @foreach($displayPumps as $pump)
                                @php $meterRow = $rowFor('meter_'.$rowKey.'_'.$pump->id); @endphp
                                <td><div class="f20-cell-value f20-num">{{ $meterRow ? $meterRow->description : '' }}</div></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="f20-cds-lower">
                <div>
                    <div class="f20-section-title">Daily Sales Status</div>
                    <div class="daily-sales-wrap">
                        <table class="f20-grid-table">
                            <thead>
                                <tr><th>Details</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < $dailyRowCount; $i++)
                                    @php $row = $dailySalesRows->get($i); @endphp
                                    <tr>
                                        <td><div class="f20-cell-value">{{ $row ? $row->description : '' }}</div></td>
                                        <td><div class="f20-cell-value f20-num">{{ $row ? $formatAmount($row->amount) : '' }}</div></td>
                                    </tr>
                                @endfor
                                <tr class="f20-total-row">
                                    <td><div class="f20-cell-value">Total</div></td>
                                    <td><div class="f20-cell-value f20-num">{{ $formatAmount($header->sales_total) }}</div></td>
                                </tr>
                            </tbody>
                        </table>

                        <table class="f20-grid-table">
                            <thead>
                                <tr><th>Details</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < $creditRowCount; $i++)
                                    @php $row = $creditRows->get($i); @endphp
                                    <tr>
                                        <td><div class="f20-cell-value">{{ $row ? $row->description : '' }}</div></td>
                                        <td><div class="f20-cell-value f20-num">{{ $row ? $formatAmount($row->amount) : '' }}</div></td>
                                    </tr>
                                @endfor
                                <tr class="f20-total-row">
                                    <td><div class="f20-cell-value">Total</div></td>
                                    <td><div class="f20-cell-value f20-num">{{ $formatAmount($header->expenses_total) }}</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="balance-stock-title">Balance Stock</div>
                    <table class="f20-grid-table f20-tank-table">
                        <colgroup>
                            <col style="width:33%;">
                            @foreach($displayTanks as $tank)
                                <col style="width:{{ round(67 / max(1, $displayTanks->count()), 2) }}%;">
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Tank Nos</th>
                                @foreach($displayTanks as $tank)
                                    <th>{{ $tank->tank_name ?: ('Tank ' . $tank->id) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tankRows as $stockKey => $stockLabel)
                                <tr class="{{ $stockKey === 'days_balance_qty' ? 'f20-total-row' : '' }}">
                                    <td class="row-label">{{ $stockLabel }}</td>
                                    @foreach($displayTanks as $tank)
                                        @php $stockRow = $rowFor('stock_'.$stockKey.'_'.$tank->id); @endphp
                                        <td><div class="f20-cell-value f20-num">{{ $stockRow ? $formatQty($stockRow->amount) : '' }}</div></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
