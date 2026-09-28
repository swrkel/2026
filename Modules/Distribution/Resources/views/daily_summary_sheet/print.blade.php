<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Summary Sheet - {{ $sheet->sheet_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .sheet {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 5mm;
            box-sizing: border-box;
            background: #fff;
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }

        .header {
            text-align: center;
            margin-bottom: 6px;
        }

        .sheet-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 0;
        }

        .business-name {
            font-size: 12px;
            font-weight: normal;
            margin-bottom: 4px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
            flex-wrap: nowrap;
        }

        .header-field {
            flex: 1;
            text-align: left;
            min-width: 0;
            margin-right: 8px;
        }

        .header-field:last-child {
            margin-right: 0;
        }

        .header-field label {
            font-weight: bold;
            margin-right: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        #sheet_table {
            table-layout: fixed;
            width: 100%;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        table thead th {
            border-top: 2px solid #000;
            font-weight: bold;
            background: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-flex {
            display: flex;
            margin-top: 4px;
            align-items: flex-start;
            gap: 4px;
        }

        .stock-left {
            flex: 3;
        }

        .right-stack {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .stock-status-card,
        .cash-card,
        .calls-card {
            border: 1px solid #000;
            padding: 0;
            background: #fff;
            margin-bottom: 4px;
        }

        .stock-status-card h5,
        .cash-card h5,
        .calls-card h5 {
            font-weight: 700;
            margin: 0;
            padding: 2px 4px;
            background: #f0f0f0;
            font-size: 11px;
        }

        .stock-status-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            border-top: 1px solid #000;
        }

        .stock-status-table th,
        .stock-status-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
            white-space: nowrap;
        }

        .stock-status-table th:first-child,
        .stock-status-table td:first-child {
            text-align: left;
            font-weight: bold;
            white-space: normal;
            word-wrap: break-word;
        }

        #products_summary_table {
            table-layout: fixed;
            width: 100%;
        }

        #products_summary_table th:first-child,
        #products_summary_table td:first-child {
            width: 180px;
            min-width: 180px;
        }

        #products_summary_table th:not(:first-child),
        #products_summary_table td:not(:first-child) {
            width: 70px;
            min-width: 70px;
        }

        .product-header-summary {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
            white-space: nowrap;
            color: #0066cc;
            font-weight: bold;
            height: auto;
            min-height: 60px;
            text-align: center;
            vertical-align: middle;
            padding: 4px !important;
        }

        .free-issue-header {
            background-color: #f0fff0 !important;
            color: #006600 !important;
            border-left: 2px solid #4CAF50 !important;
        }

        .free-issue-cell {
            background-color: #f0fff0 !important;
            color: #006600 !important;
            font-weight: bold;
        }

        .cash-card table,
        .calls-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .cash-card td,
        .cash-card th,
        .calls-card td,
        .calls-card th {
            border: 1px solid #000;
            padding: 2px 4px;
        }

        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 10px;
            width: 100%;
            gap: 10px;
        }

        .signature-section>div {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .signature-line {
            border-top: 1px dotted #333;
            width: 100%;
            text-align: center;
            padding-top: 4px;
            font-weight: bold;
            font-size: 11px;
        }

        .page-number {
            text-align: right;
            margin-top: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .total-row {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .sheet {
                page-break-after: always;
            }

            .sheet:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>
    @php
        // ── Helper: normalise a free-issue key so null unit_id and 0 unit_id produce the same key ──
        // e.g. "123_" -> "123_0",  "123_null" -> "123_0",  "123_0" -> "123_0"
        function normFreeKey(string $key): string
        {
            $parts = explode('_', $key, 2);
            $pid = $parts[0] ?? '';
            $uid = $parts[1] ?? '';
            if ($uid === '' || $uid === 'null' || $uid === null) {
                $uid = '0';
            }
            return $pid . '_' . $uid;
        }

        $business = \Modules\Distribution\Entities\Core\Business::find($sheet->business_id);
        $productList = $sheet->product_list ?? [];
        $lines = $sheet->lines ?? collect();
        $stockStatus = $sheet->stock_status ?? [];
        $loadingSheets = $sheet->loading_sheets ?? [];

        // Group lines by page
        $linesByPage = $lines->groupBy('page_no');
        $totalPages = max($sheet->total_pages ?? 1, $linesByPage->count());

        // Stock status keyed by product_id
        $stockByProduct = [];
        if (is_array($stockStatus)) {
            foreach ($stockStatus as $stock) {
                if (isset($stock['product_id'])) {
                    $stockByProduct[$stock['product_id']] = $stock;
                }
            }
        }

        // ── Build column lists ─────────────────────────────────────────────────────
        // $regularProductList  – normal product columns
        // $freeIssueColMap     – normalised_key => ['label' => string]
        $regularProductList = [];
        $freeIssueColMap = []; // keyed by normFreeKey

        if (is_array($productList) || is_object($productList)) {
            foreach ($productList as $p) {
                $p = (array) $p;
                if (empty($p['is_free'])) {
                    $regularProductList[] = $p;
                } else {
                    // id is stored as "free_pid_uid"
                    $rawKey = preg_replace('/^free_/', '', $p['id'] ?? '');
                    if ($rawKey !== '') {
                        $nk = normFreeKey($rawKey);
                        if (!isset($freeIssueColMap[$nk])) {
                            $freeIssueColMap[$nk] = ['label' => $p['name'] ?? $rawKey];
                        }
                    }
                }
            }
        }

        // Also scan every line's free_issues_json so we never miss a column
// that wasn't captured in product_list (e.g. if sheet was saved before
        // the free-issue column was registered on the JS side).
        foreach ($lines as $line) {
            $raw = $line->free_issues_json ?? [];
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }
            if (!is_array($raw)) {
                continue;
            }

            foreach ($raw as $fi) {
                if (!is_array($fi)) {
                    continue;
                }

                // Derive the raw key the same way the JS does:
                // key field takes priority, otherwise product_id + '_' + (unit_id ?? 0)
                $rawKey = $fi['key'] ?? $fi['product_id'] . '_' . ($fi['unit_id'] ?? 0);
                $nk = normFreeKey((string) $rawKey);

                if (!isset($freeIssueColMap[$nk])) {
                    $pName = $fi['product_name'] ?? 'Product';
                    $uName = $fi['unit_name'] ?? '';
                    $freeIssueColMap[$nk] = [
                        'label' => 'Free' . ($uName ? " ($uName)" : '') . ': ' . $pName,
                    ];
                }
            }
        }

        $hasFreeIssues = !empty($freeIssueColMap);
        $totalColumns = 6 + count($regularProductList) + ($hasFreeIssues ? count($freeIssueColMap) : 0);
    @endphp

    @for ($pageNo = 1; $pageNo <= $totalPages; $pageNo++)
        @php
            $pageLines = $linesByPage->get($pageNo, collect());
            $isLastPage = $pageNo == $totalPages;
        @endphp

        <div class="sheet">
            <!-- Header -->
            <div class="header">
                <div class="sheet-title">Daily Summary Sheet</div>
                <div class="business-name">{{ $business->name ?? 'Business Name' }}</div>
            </div>

            <!-- Header Fields -->
            <div class="header-row">
                <div class="header-field">
                    <label>Sales Rep:</label>
                    {{ $sheet->salesRep->name ?? ($sheet->agent_name ?? 'N/A') }}
                </div>
                <div class="header-field">
                    <label>Agent:</label>
                    {{ $sheet->agent_name ?? 'N/A' }}
                </div>
                <div class="header-field">
                    <label>Date:</label>
                    {{ $sheet->date ? \Carbon\Carbon::parse($sheet->date)->format('d/m/Y') : 'N/A' }}
                </div>
                <div class="header-field">
                    <label>Route:</label>
                    {{ $sheet->route->name ?? 'N/A' }}
                </div>
                <div class="header-field">
                    <label>Vehicle No:</label>
                    {{ $sheet->vehicle->vehicle_no ?? 'N/A' }}
                </div>
                <div class="header-field">
                    <label>Product Category:</label>
                    {{ $sheet->productCategory->name ?? 'N/A' }}
                </div>
                <div class="header-field">
                    <label>Distance (km):</label>
                    {{ number_format($sheet->distance_km ?? 0, 2) }}
                </div>
            </div>

            <!-- Sheet Number and Loading Sheet -->
            <div class="header-row" style="justify-content: space-between; margin-top: 4px;">
                <div class="header-field">
                    <label>Sheet No:</label>
                    <strong>{{ $sheet->sheet_number ?? 'N/A' }}</strong>
                </div>
                <div class="header-field">
                    <label>Loading Sheet:</label>
                    <strong>{{ $sheet->loading_sheet_no ?? 'N/A' }}</strong>
                </div>
            </div>

            <!-- Main Table -->
            <table id="sheet_table">

                <thead>
                    <tr>
                        <th style="width:30px;">#</th>
                        <th style="width:70px;">Bill No</th>
                        <th style="width:120px;">Customer</th>

                        @foreach ($regularProductList as $product)
                            <th class="product-header-summary" style="width:70px;">
                                {{ $product['name'] ?? 'Product' }}
                            </th>
                        @endforeach

                        @if ($hasFreeIssues)
                            @foreach ($freeIssueColMap as $fiKey => $fiData)
                                <th class="product-header-summary free-issue-header"
                                    style="width:70px; background-color:#f0fff0; color:#006600;">
                                    {{ $fiData['label'] }}
                                </th>
                            @endforeach
                        @endif

                        <th style="width:80px;" class="text-right">Gross Sale</th>
                        <th style="width:70px;" class="text-right">Discount</th>
                        <th style="width:80px;" class="text-right">Net Sale</th>
                    </tr>
                </thead>

                <tbody>
                    @php $rowIndex = 1; @endphp

                    @forelse ($pageLines as $line)
                        @php
                            // ── Parse products_json for this line ──────────────────
                            $productsJson = $line->products_json ?? [];
                            if (is_string($productsJson)) {
                                $productsJson = json_decode($productsJson, true) ?: [];
                            }
                            if (!is_array($productsJson)) {
                                $productsJson = [];
                            }

                            // ── Parse free_issues_json and build a lookup by normalised key ──
                            // The JS stores items with shape:
                            //   { key, qty, product_id, unit_id, product_name, unit_name }
                            // The DB query (getInvoiceFreeIssues) returns total_qty.
                            // We handle both field names here.
                            $freeIssuesRaw = $line->free_issues_json ?? [];
                            if (is_string($freeIssuesRaw)) {
                                $freeIssuesRaw = json_decode($freeIssuesRaw, true) ?: [];
                            }
                            if (!is_array($freeIssuesRaw)) {
                                $freeIssuesRaw = [];
                            }

                            // normalised_key => float qty
                            $freeQtyByKey = [];
                            foreach ($freeIssuesRaw as $fi) {
                                if (!is_array($fi)) {
                                    continue;
                                }

                                // Accept both stored-key and derived key
                                $rawKey = $fi['key'] ?? $fi['product_id'] . '_' . ($fi['unit_id'] ?? 0);
                                $nk = normFreeKey((string) $rawKey);

                                // Accept both 'qty' (JS dataset) and 'total_qty' (DB query)
                                $qty = floatval($fi['qty'] ?? ($fi['total_qty'] ?? 0));

                                // Sum in case the same key appears more than once
                                $freeQtyByKey[$nk] = ($freeQtyByKey[$nk] ?? 0) + $qty;
                            }
                        @endphp

                        <tr>
                            <td class="text-center">{{ $rowIndex++ }}</td>
                            <td>{{ $line->invoice_no ?? ($line->bill_id ?? '') }}</td>
                            <td>{{ $line->customer_name ?? '' }}</td>

                            {{-- Regular product quantity columns --}}
                            @foreach ($regularProductList as $product)
                                @php
                                    $pid = $product['id'] ?? null;
                                    $qty = isset($productsJson[$pid]) ? floatval($productsJson[$pid]) : 0;
                                @endphp
                                <td class="text-center">
                                    {{ $qty > 0 ? number_format($qty, 2) : '' }}
                                </td>
                            @endforeach

                            {{-- Free issue quantity columns --}}
                            @if ($hasFreeIssues)
                                @foreach ($freeIssueColMap as $fiKey => $fiData)
                                    @php
                                        $fiQty = $freeQtyByKey[$fiKey] ?? 0;
                                    @endphp
                                    <td class="text-center free-issue-cell">
                                        {{ $fiQty > 0 ? number_format($fiQty, 2) : '' }}
                                    </td>
                                @endforeach
                            @endif

                            <td class="text-right">{{ number_format($line->gross_sale ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($line->discount ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($line->net_sale ?? 0, 2) }}</td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="{{ $totalColumns }}" class="text-center">
                                No data for this page
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if ($isLastPage)
                    <tfoot>
                        {{-- Total This Page --}}
                        <tr class="total-row">
                            <td colspan="3" class="text-right">Total This Page:</td>
                            @for ($i = 0; $i < count($regularProductList); $i++)
                                <td></td>
                            @endfor
                            @if ($hasFreeIssues)
                                @for ($i = 0; $i < count($freeIssueColMap); $i++)
                                    <td></td>
                                @endfor
                            @endif
                            <td class="text-right">{{ number_format($sheet->total_this_page ?? 0, 2) }}</td>
                            <td></td>
                            <td class="text-right">{{ number_format($sheet->total_this_page ?? 0, 2) }}</td>
                        </tr>

                        {{-- Previous Page GT --}}
                        <tr class="total-row">
                            <td colspan="3" class="text-right">Previous Page GT:</td>
                            @for ($i = 0; $i < count($regularProductList); $i++)
                                <td></td>
                            @endfor
                            @if ($hasFreeIssues)
                                @for ($i = 0; $i < count($freeIssueColMap); $i++)
                                    <td></td>
                                @endfor
                            @endif
                            <td class="text-right">{{ number_format($sheet->previous_page_gt ?? 0, 2) }}</td>
                            <td></td>
                            <td class="text-right">{{ number_format($sheet->previous_page_gt ?? 0, 2) }}</td>
                        </tr>

                        {{-- Grand Total --}}
                        <tr class="total-row" style="border-top:2px solid #000;">
                            <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                            @for ($i = 0; $i < count($regularProductList); $i++)
                                <td></td>
                            @endfor
                            @if ($hasFreeIssues)
                                @for ($i = 0; $i < count($freeIssueColMap); $i++)
                                    <td></td>
                                @endfor
                            @endif
                            <td class="text-right">
                                <strong>{{ number_format($sheet->grand_total ?? 0, 2) }}</strong>
                            </td>
                            <td></td>
                            <td class="text-right">
                                <strong>{{ number_format($sheet->grand_total ?? 0, 2) }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <!-- Summary Sections -->
            <div class="summary-flex">

                <!-- Left Column: Cash + Stock Status -->
                <div class="stock-left">

                    <!-- Cash Summary -->
                    <div class="cash-card">
                        <h5>Cash Summary</h5>
                        <table>
                            <tr>
                                <td>Cash Deposited</td>
                                <td class="text-right">{{ number_format($sheet->cash_deposited ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Cheque Deposited</td>
                                <td class="text-right">{{ number_format($sheet->cheque_deposited ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Credit Bills B/F</td>
                                <td class="text-right">{{ number_format($sheet->credit_bills_bf ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Cheques in Hand</td>
                                <td class="text-right">{{ number_format($sheet->cheques_in_hand ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Credit Bills in Hand</td>
                                <td class="text-right">{{ number_format($sheet->credit_bills_in_hand ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Cash in Hand</td>
                                <td class="text-right">{{ number_format($sheet->cash_in_hand ?? 0, 2) }}</td>
                            </tr>
                            <tr style="background:#f5f5f5; font-weight:bold;">
                                <td>Total Cash</td>
                                <td class="text-right">
                                    @php
                                        $cashTotal =
                                            ($sheet->cash_deposited ?? 0) +
                                            ($sheet->cheque_deposited ?? 0) +
                                            ($sheet->credit_bills_bf ?? 0) +
                                            ($sheet->cheques_in_hand ?? 0) +
                                            ($sheet->credit_bills_in_hand ?? 0) +
                                            ($sheet->cash_in_hand ?? 0);
                                    @endphp
                                    {{ number_format($cashTotal, 2) }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Stock Status Summary -->
                    @if (!empty($regularProductList))
                        <div class="stock-status-card products-summary" style="margin-top:4px;">
                            <h5>Stock Status Summary</h5>
                            <table class="stock-status-table" id="products_summary_table">

                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        @foreach ($regularProductList as $product)
                                            <th class="product-header-summary">
                                                {{ $product['name'] ?? 'Product' }}
                                            </th>
                                        @endforeach
                                        @if ($hasFreeIssues)
                                            @foreach ($freeIssueColMap as $fiKey => $fiData)
                                                <th class="product-header-summary free-issue-header">
                                                    {{ $fiData['label'] }}
                                                </th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        $stockRows = [
                                            'store_opening_qty' => 'Store - Day Opening Qty',
                                            'vehicle_opening_qty' => 'Vehicle Qty before Loading',
                                            'loaded_qty' => 'Loaded Qty',
                                            'sold_qty' => 'Sold Qty',
                                            'balance_qty' => 'Balance Qty - Vehicle',
                                        ];

                                        // Add Free Issues Given row if there are free issues
                                        $hasFreeIssuesSummary = !empty($freeIssueColMap);
                                    @endphp
                                    @foreach ($stockRows as $field => $label)
                                        <tr
                                            @if ($loop->last) style="background:#f5f5f5;font-weight:bold;" @endif>
                                            <td>{{ $label }}</td>
                                            @foreach ($regularProductList as $product)
                                                @php
                                                    $pid = $product['id'] ?? null;
                                                    $stock = $stockByProduct[$pid] ?? [];
                                                    $val = floatval($stock[$field] ?? 0);
                                                @endphp
                                                <td class="text-center">
                                                    {{ $val != 0 ? number_format($val, 2) : '' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach

                                    {{-- ADD FREE ISSUES GIVEN ROW HERE --}}
                                    @if ($hasFreeIssuesSummary)
                                        <tr style="background-color: #f0fff0; font-weight: bold;">
                                            <td style="color: #006600;">Free Issues Given</td>

                                            {{-- Regular products columns --}}
                                            @foreach ($regularProductList as $product)
                                                @php
                                                    $pid = $product['id'] ?? null;
                                                    $totalFreeQty = 0;
                                                    foreach ($pageLines as $line) {
                                                        $freeIssuesRaw = $line->free_issues_json ?? [];
                                                        if (is_string($freeIssuesRaw)) {
                                                            $freeIssuesRaw = json_decode($freeIssuesRaw, true) ?: [];
                                                        }
                                                        if (!is_array($freeIssuesRaw)) {
                                                            continue;
                                                        }

                                                        foreach ($freeIssuesRaw as $fi) {
                                                            if (!is_array($fi)) {
                                                                continue;
                                                            }
                                                            $freeProductId = $fi['product_id'] ?? null;
                                                            if ($freeProductId == $pid) {
                                                                $qty = floatval($fi['qty'] ?? ($fi['total_qty'] ?? 0));
                                                                $totalFreeQty += $qty;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <td class="text-center"
                                                    style="color: #006600; background-color: #f0fff0;">
                                                    {{ $totalFreeQty > 0 ? number_format($totalFreeQty, 2) : '' }}
                                                </td>
                                            @endforeach

                                            {{-- Free products columns (this is what's missing) --}}
                                            @if ($hasFreeIssues)
                                                @foreach ($freeIssueColMap as $fiKey => $fiData)
                                                    @php
                                                        $totalFreeQty = 0;
                                                        foreach ($pageLines as $line) {
                                                            $freeIssuesRaw = $line->free_issues_json ?? [];
                                                            if (is_string($freeIssuesRaw)) {
                                                                $freeIssuesRaw =
                                                                    json_decode($freeIssuesRaw, true) ?: [];
                                                            }
                                                            if (!is_array($freeIssuesRaw)) {
                                                                continue;
                                                            }

                                                            foreach ($freeIssuesRaw as $fi) {
                                                                if (!is_array($fi)) {
                                                                    continue;
                                                                }
                                                                $rawKey =
                                                                    $fi['key'] ??
                                                                    $fi['product_id'] . '_' . ($fi['unit_id'] ?? 0);
                                                                $nk = normFreeKey((string) $rawKey);
                                                                if ($nk === $fiKey) {
                                                                    $qty = floatval(
                                                                        $fi['qty'] ?? ($fi['total_qty'] ?? 0),
                                                                    );
                                                                    $totalFreeQty += $qty;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    <td class="text-center"
                                                        style="color: #006600; background-color: #f0fff0;">
                                                        {{ $totalFreeQty > 0 ? number_format($totalFreeQty, 2) : '' }}
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div><!-- end .stock-left -->

                <!-- Right Column: Calls + Loading Sheets -->
                <div class="right-stack">

                    <div class="calls-card">
                        <h5>Calls Summary</h5>
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-center">Calls Visited</th>
                                    <th class="text-center">Productive Calls</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>B / F</strong></td>
                                    <td class="text-center">{{ $sheet->calls_visited_bf ?? 0 }}</td>
                                    <td class="text-center">{{ $sheet->productive_calls_bf ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Day Calls</strong></td>
                                    <td class="text-center">{{ $sheet->calls_visited ?? 0 }}</td>
                                    <td class="text-center">{{ $sheet->productive_calls ?? 0 }}</td>
                                </tr>
                                <tr style="background:#f5f5f5;font-weight:bold;">
                                    <td><strong>Total</strong></td>
                                    <td class="text-center">
                                        {{ ($sheet->calls_visited ?? 0) + ($sheet->calls_visited_bf ?? 0) }}
                                    </td>
                                    <td class="text-center">
                                        {{ ($sheet->productive_calls ?? 0) + ($sheet->productive_calls_bf ?? 0) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if (!empty($loadingSheets))
                        <div class="cash-card" style="margin-top:4px;">
                            <h5>Loading Sheets</h5>
                            <div style="padding:2px 4px;">
                                @foreach ($loadingSheets as $ls)
                                    <div>{{ $ls['loading_no'] ?? '' }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div><!-- end .right-stack -->

            </div><!-- end .summary-flex -->

            @if ($isLastPage)
                <div class="signature-section">
                    <div>
                        <div class="signature-line">Sales Rep Signature</div>
                    </div>
                    <div>
                        <div class="signature-line">Agent Signature</div>
                    </div>
                    <div>
                        <div class="signature-line">Manager Signature</div>
                    </div>
                </div>
            @endif

            <div class="page-number">
                Page {{ $pageNo }} of {{ $totalPages }}
            </div>

        </div><!-- end .sheet -->
    @endfor

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>
