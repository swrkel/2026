@extends('customers::layouts.action', ['title' => 'Customer Ledger'])

@section('customer_action_body')
@php
    $summary = $summary ?? [
        'opening_balance' => 0,
        'debit' => 0,
        'credit' => 0,
        'balance' => 0,
    ];
    $dateFilter = $dateFilter ?? [
        'preset' => 'current_month',
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ];
    $rows = collect($rows ?? []);
    $runningBalance = 0;
@endphp

<style>
    .customer-action-ledger {
        width:100%; max-width:100%; min-width:0; overflow:hidden;
        --ledger-blue: #2563eb;
        --ledger-green: #16a34a;
        --ledger-orange: #f59e0b;
        --ledger-purple: #7c3aed;
        --ledger-ink: #0f172a;
        --ledger-muted: #64748b;
    }
    .customer-action-ledger .customer-ledger-filter-panel {
        margin-bottom: 16px; padding: 16px 18px; border: 1px solid #dce6f2;
        border-radius: 14px; background: #f8fbff;
    }
    .customer-action-ledger .customer-ledger-filter-grid {
        display: grid; grid-template-columns: minmax(190px,1.25fr) minmax(150px,1fr) minmax(150px,1fr) auto;
        gap: 12px; align-items: end;
    }
    .customer-action-ledger .customer-ledger-filter-panel label {
        display:block; margin-bottom:6px; color:#334155; font-size:12px; font-weight:800;
    }
    .customer-action-ledger .customer-ledger-filter-panel .form-control {
        min-height:40px; border-radius:9px; border-color:#d6e0ed;
    }
    .customer-action-ledger .customer-ledger-filter-panel .btn {
        min-height:40px; border-radius:9px; font-weight:800;
    }
    .customer-action-ledger .customer-ledger-period-label {
        display:inline-block; margin-top:8px; padding:5px 10px; border-radius:999px;
        background:#eef4ff; color:#2456bd; font-size:12px; font-weight:700;
    }
    .customer-action-ledger .customer-ledger-summary-grid {
        display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:16px;
    }
    .customer-action-ledger .customer-ledger-summary-card {
        position:relative; display:flex; align-items:center; gap:12px; min-height:88px; padding:15px 16px;
        border:1px solid #e2eaf4; border-radius:15px; background:#fff;
        box-shadow:0 7px 18px rgba(15,23,42,.055); overflow:hidden;
    }
    .customer-action-ledger .customer-ledger-summary-card::before {
        content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--card-color);
    }
    .customer-action-ledger .customer-ledger-summary-card.opening { --card-color:var(--ledger-blue); --card-soft:#eef4ff; }
    .customer-action-ledger .customer-ledger-summary-card.debit { --card-color:var(--ledger-purple); --card-soft:#f4efff; }
    .customer-action-ledger .customer-ledger-summary-card.credit { --card-color:var(--ledger-green); --card-soft:#eefbf2; }
    .customer-action-ledger .customer-ledger-summary-card.balance { --card-color:var(--ledger-orange); --card-soft:#fff8e8; }
    .customer-action-ledger .customer-ledger-summary-icon {
        width:42px; height:42px; flex:0 0 42px; display:inline-flex; align-items:center; justify-content:center;
        border-radius:13px; color:var(--card-color); background:var(--card-soft); font-size:17px;
    }
    .customer-action-ledger .customer-ledger-summary-label {
        display:block; margin-bottom:5px; color:var(--ledger-muted); font-size:10px; font-weight:800;
        letter-spacing:.04em; text-transform:uppercase;
    }
    .customer-action-ledger .customer-ledger-summary-value {
        display:block; color:var(--ledger-ink); font-size:clamp(16px,1.4vw,22px); line-height:1.05;
        font-weight:850; white-space:nowrap;
    }

    .customer-action-ledger .customer-ledger-toolbar {
        display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;
        margin:0 0 14px; padding:12px 14px; background:#fff; border:1px solid #e2eaf4;
        border-radius:14px; box-shadow:0 8px 22px rgba(15,76,129,.07);
    }
    .customer-action-ledger .customer-ledger-toolbar-left,
    .customer-action-ledger .customer-ledger-toolbar-right {
        display:flex; flex-wrap:wrap; gap:8px; align-items:center;
    }
    .customer-action-ledger .customer-ledger-toolbar-left { flex:1 1 430px; }
    .customer-action-ledger .customer-ledger-toolbar-right { justify-content:flex-end; margin-left:auto; }
    .customer-action-ledger .customer-ledger-toolbar-field {
        display:inline-flex; gap:6px; align-items:center; margin:0; color:#475569; font-size:12px; font-weight:700;
        white-space:nowrap;
    }
    .customer-action-ledger .customer-ledger-toolbar .form-control {
        height:38px; border-radius:10px; border-color:#d8e2ee;
    }
    .customer-action-ledger .customer-ledger-page-length select { width:82px; }
    .customer-action-ledger .customer-ledger-entry-filter select { width:100px; }
    .customer-action-ledger .customer-ledger-search { min-width:240px; max-width:360px; }
    .customer-action-ledger .customer-ledger-tool-btn {
        border:0 !important; border-radius:9px !important; color:#fff !important; padding:8px 11px !important;
        font-size:11px !important; font-weight:800 !important; box-shadow:0 5px 12px rgba(15,23,42,.12) !important;
    }
    .customer-action-ledger .tool-csv { background:#0f766e !important; }
    .customer-action-ledger .tool-excel { background:#15803d !important; }
    .customer-action-ledger .tool-pdf { background:#dc2626 !important; }
    .customer-action-ledger .tool-print { background:#334155 !important; }
    .customer-action-ledger .tool-colvis { background:#7c3aed !important; }
    .customer-action-ledger .tool-email { background:#2563eb !important; }
    .customer-action-ledger .tool-whatsapp { background:#16a34a !important; }

    #customers_ledger_table_wrapper > .dt-buttons { display:none !important; }
    .customer-ledger-manual-colvis {
        position:absolute; z-index:999999; min-width:220px; max-height:350px; overflow:auto;
        background:#fff; border:1px solid #d7e2ef; border-radius:10px; padding:10px;
        box-shadow:0 12px 30px rgba(0,0,0,.18);
    }
    .customer-ledger-manual-colvis label { display:block; margin:5px 0; font-weight:600; }

    .customer-action-ledger .customer-ledger-header-row {
        display:flex; justify-content:space-between; gap:14px; align-items:flex-start; margin-bottom:10px;
    }
    .customer-action-ledger .customer-ledger-table-wrap {
        width:100%; max-width:100%; min-width:0; overflow-x:hidden;
        border:1px solid #e2e8f0; border-radius:11px;
    }
    .customer-action-ledger #customers_ledger_table {
        width:100% !important; max-width:100% !important; min-width:0 !important; margin:0; table-layout:fixed; border-collapse:collapse;
    }
    .customer-action-ledger #customers_ledger_table th,
    .customer-action-ledger #customers_ledger_table td {
        padding:9px 7px; vertical-align:top; white-space:normal; word-break:break-word; font-size:11px;
    }
    .customer-action-ledger #customers_ledger_table th {
        background:#357ca5; color:#fff; font-size:10px; font-weight:800; line-height:1.2; text-transform:uppercase;
        cursor:pointer;
    }
    .customer-action-ledger #customers_ledger_table th:nth-child(1),
    .customer-action-ledger #customers_ledger_table td:nth-child(1) { width:10%; }
    .customer-action-ledger #customers_ledger_table th:nth-child(2),
    .customer-action-ledger #customers_ledger_table td:nth-child(2) { width:9%; }
    .customer-action-ledger #customers_ledger_table th:nth-child(3),
    .customer-action-ledger #customers_ledger_table td:nth-child(3) { width:19.2%; } /* Description: reduced by 20% */
    .customer-action-ledger #customers_ledger_table th:nth-child(4),
    .customer-action-ledger #customers_ledger_table td:nth-child(4) { width:8%; }
    .customer-action-ledger #customers_ledger_table th:nth-child(5),
    .customer-action-ledger #customers_ledger_table td:nth-child(5) { width:8%; } /* Payment Status: reduced by 20% */
    .customer-action-ledger #customers_ledger_table th:nth-child(6),
    .customer-action-ledger #customers_ledger_table td:nth-child(6) { width:8%; }
    .customer-action-ledger #customers_ledger_table th:nth-child(7),
    .customer-action-ledger #customers_ledger_table td:nth-child(7) { width:8%; }
    .customer-action-ledger #customers_ledger_table th:nth-child(8),
    .customer-action-ledger #customers_ledger_table td:nth-child(8) { width:9%; }
    .customer-action-ledger #customers_ledger_table th:nth-child(9),
    .customer-action-ledger #customers_ledger_table td:nth-child(9) { width:20.8%; }
    .customer-action-ledger #customers_ledger_table .customer-opening-balance-row td {
        background:#fff8e1 !important; font-weight:700;
    }
    .customer-action-ledger #customers_ledger_table .ledger-description { white-space:normal; line-height:1.4; }
    .customer-action-ledger .ledger-description-detail,
    .customer-action-ledger .ledger-payment-detail {
        display:block; margin-top:3px; color:#475569; font-size:10px; line-height:1.35;
    }
    .customer-action-ledger .ledger-payment-method-name {
        display:block; color:#0f172a; font-weight:800; margin-bottom:3px;
    }
    .customer-action-ledger .ledger-status-wrap { display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
    .customer-action-ledger .ledger-payment-status {
        display:inline-block; padding:3px 7px; border-radius:999px; color:#2456bd; background:#eef4ff;
        font-size:10px; font-weight:800;
    }
    .customer-action-ledger .customer-ledger-bills-btn {
        border:0; border-radius:6px; padding:4px 8px; color:#fff !important; background:#f59e0b;
        font-size:10px; font-weight:800; text-decoration:none !important;
    }
    .customer-action-ledger .ledger-bill-details-panel {
        display:none; margin-top:7px; padding:8px; border:1px solid #f8d78e; border-radius:8px; background:#fffaf0;
    }
    .customer-action-ledger .ledger-bill-reference {
        display:inline-block; margin-bottom:6px; padding:3px 7px; border-radius:999px;
        color:#2456bd; background:#eef4ff; font-size:10px; font-weight:800;
    }
    .customer-action-ledger .ledger-bill-table { width:100%; margin:0; background:#fff; }
    .customer-action-ledger .ledger-bill-table th,
    .customer-action-ledger .ledger-bill-table td { padding:5px 6px !important; font-size:10px !important; }

    @media (max-width:1450px) {
        .customer-action-ledger #customers_ledger_table th,
        .customer-action-ledger #customers_ledger_table td { padding:7px 5px; font-size:10px; }
        .customer-action-ledger #customers_ledger_table th { font-size:9px; }
        .customer-action-ledger .ledger-description-detail,
        .customer-action-ledger .ledger-payment-detail { font-size:9px; }
    }
    @media (max-width:1199px) {
        .customer-action-ledger .customer-ledger-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .customer-action-ledger .customer-ledger-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .customer-action-ledger .customer-ledger-toolbar-left,
        .customer-action-ledger .customer-ledger-toolbar-right { flex:1 1 100%; justify-content:flex-start; margin-left:0; }
    }
    @media (max-width:900px) {
        .customer-action-ledger #customers_ledger_table th,
        .customer-action-ledger #customers_ledger_table td { padding:6px 4px; font-size:9px; }
        .customer-action-ledger #customers_ledger_table th { font-size:8px; }
        .customer-action-ledger .customer-ledger-tool-btn { padding:7px 8px !important; font-size:10px !important; }
        .customer-action-ledger .customer-ledger-search { min-width:180px; max-width:100%; }
    }
    @media (max-width:600px) {
        .customer-action-ledger .customer-ledger-filter-grid,
        .customer-action-ledger .customer-ledger-summary-grid { grid-template-columns:1fr; }
        .customer-action-ledger .customer-ledger-filter-grid .btn,
        .customer-action-ledger .customer-ledger-search { width:100%; max-width:none; }
        .customer-action-ledger .customer-ledger-header-row { flex-direction:column; }
        .customer-action-ledger .customer-ledger-summary-value { white-space:normal; }
    }
    @media print {
        .customer-action-ledger .customer-ledger-filter-panel,
        .customer-action-ledger .customer-ledger-toolbar,
        .customer-action-ledger .customer-ledger-bills-btn,
        .customer-action-ledger .ledger-bill-details-panel { display:none !important; }
        .customer-action-ledger .customer-ledger-table-wrap { overflow:visible; border:0; }
        .customer-action-ledger #customers_ledger_table { min-width:0; }
    }
</style>

<div class="customer-action-ledger">
    <form method="GET"
          action="{{ route('customers.ledger', ['id' => $customer->id]) }}"
          class="customer-ledger-filter-panel"
          id="customer_ledger_filter_form"
          onsubmit="event.preventDefault(); event.stopPropagation(); var f=this; var q=new URLSearchParams(new FormData(f)).toString(); var u=f.action+(f.action.indexOf('?')===-1?'?':'&')+q; var m=f.closest('.customer_modal'); var b=f.querySelector('button[type=submit]'); if(b){b.disabled=true;} fetch(u,{method:'GET',credentials:'same-origin',cache:'no-store',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){if(!r.ok){throw new Error('HTTP '+r.status);} return r.text();}).then(function(h){if(m){m.innerHTML=h;if(window.CustomersLedgerTable){window.CustomersLedgerTable.init(m);}}else{window.location.href=u;}}).catch(function(){alert('Unable to refresh customer ledger.');}).finally(function(){if(b){b.disabled=false;}}); return false;">
        <div class="customer-ledger-filter-grid">
            <div>
                <label for="customer_ledger_date_preset">Date Range</label>
                <select name="date_preset" id="customer_ledger_date_preset" class="form-control"
                        onchange="(function(select){var custom=select.value==='custom';var fields=select.form.querySelectorAll('.customer-ledger-custom-date');for(var i=0;i<fields.length;i++){fields[i].style.display=custom?'':'none';}if(!custom){if(select.form.requestSubmit){select.form.requestSubmit();}else{var ev=document.createEvent('Event');ev.initEvent('submit',true,true);select.form.dispatchEvent(ev);}}})(this)">
                    <option value="current_month" {{ $dateFilter['preset'] === 'current_month' ? 'selected' : '' }}>Current Month</option>
                    <option value="today" {{ $dateFilter['preset'] === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $dateFilter['preset'] === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last_7_days" {{ $dateFilter['preset'] === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="last_30_days" {{ $dateFilter['preset'] === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="last_month" {{ $dateFilter['preset'] === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ $dateFilter['preset'] === 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="last_year" {{ $dateFilter['preset'] === 'last_year' ? 'selected' : '' }}>Last Year</option>
                    <option value="this_fy" {{ $dateFilter['preset'] === 'this_fy' ? 'selected' : '' }}>This FY</option>
                    <option value="last_fy" {{ $dateFilter['preset'] === 'last_fy' ? 'selected' : '' }}>Last FY</option>
                    <option value="custom" {{ $dateFilter['preset'] === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>
            <div class="customer-ledger-custom-date" style="{{ $dateFilter['preset'] === 'custom' ? '' : 'display:none;' }}">
                <label for="customer_ledger_start_date">Start Date</label>
                <input type="date" name="ledger_start_date" id="customer_ledger_start_date" class="form-control" value="{{ $dateFilter['start_date'] }}">
            </div>
            <div class="customer-ledger-custom-date" style="{{ $dateFilter['preset'] === 'custom' ? '' : 'display:none;' }}">
                <label for="customer_ledger_end_date">End Date</label>
                <input type="date" name="ledger_end_date" id="customer_ledger_end_date" class="form-control" value="{{ $dateFilter['end_date'] }}">
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
            </div>
        </div>
    </form>

    <div class="customer-ledger-summary-grid">
        <div class="customer-ledger-summary-card opening"><span class="customer-ledger-summary-icon"><i class="fa fa-history"></i></span><div><span class="customer-ledger-summary-label">Opening Balance</span><strong class="customer-ledger-summary-value">{{ number_format((float)($summary['opening_balance'] ?? 0), 2) }}</strong></div></div>
        <div class="customer-ledger-summary-card debit"><span class="customer-ledger-summary-icon"><i class="fa fa-arrow-down"></i></span><div><span class="customer-ledger-summary-label">Total Debit</span><strong class="customer-ledger-summary-value">{{ number_format((float)($summary['debit'] ?? 0), 2) }}</strong></div></div>
        <div class="customer-ledger-summary-card credit"><span class="customer-ledger-summary-icon"><i class="fa fa-arrow-up"></i></span><div><span class="customer-ledger-summary-label">Total Credit</span><strong class="customer-ledger-summary-value">{{ number_format((float)($summary['credit'] ?? 0), 2) }}</strong></div></div>
        <div class="customer-ledger-summary-card balance"><span class="customer-ledger-summary-icon"><i class="fa fa-balance-scale"></i></span><div><span class="customer-ledger-summary-label">Balance Due</span><strong class="customer-ledger-summary-value">{{ number_format((float)($summary['balance'] ?? 0), 2) }}</strong></div></div>
    </div>

    @include('customers::ledger.partials.toolbar')

    <div class="customer-ledger-header-row">
        <p>
            <strong>{{ $customer->name }}</strong><br>
            {{ $customer->contact_id }}<br>
            {{ $customer->mobile }}<br>
            <span class="customer-ledger-period-label">{{ \Carbon\Carbon::parse($dateFilter['start_date'])->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateFilter['end_date'])->format('d M Y') }}</span>
        </p>
    </div>

    <div class="customer-ledger-table-wrap">
        <table class="table table-bordered table-striped" id="customers_ledger_table">
            <thead>
            <tr>
                <th>System Created<br>Date</th>
                <th>Transaction<br>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th>Payment<br>Status</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
                <th>Payment Method<br>& Details</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $amount = abs((float)($row->amount ?? $row->final_total ?? 0));
                    $rowType = strtolower((string)($row->acc_transaction_type ?? $row->type ?? 'debit'));
                    $isCredit = $rowType === 'credit';
                    $debit = $isCredit ? 0 : $amount;
                    $credit = $isCredit ? $amount : 0;
                    $runningBalance += ($debit - $credit);
                    $transactionType = strtolower((string)($row->transaction_type ?? $row->type ?? ''));
                    $description = trim((string)($row->description ?? $row->invoice_no ?? $row->ref_no ?? '-'));
                    $isOpeningBalance = !empty($row->is_opening_balance)
                        || in_array($transactionType, ['opening_balance', 'fleet_opening_balance'], true)
                        || stripos($description, 'opening balance') !== false;
                    $isBroughtForward = !empty($row->is_brought_forward) || $transactionType === 'bf_balance';
                    $bulkBills = is_array($row->bulk_payment_bills ?? null) ? $row->bulk_payment_bills : [];
                    $hasBulkBills = !empty($bulkBills);
                    $isBulkPaymentAllocation = !empty($row->is_bulk_payment_allocation);
                    $isBulkPaymentUnallocated = !empty($row->is_bulk_payment_unallocated);
                    $bulkLumpSumAmount = (float)($row->bulk_lump_sum_amount ?? 0);
                    $billDetailId = 'customer-ledger-bills-' . md5((string)($row->id ?? '') . '-' . $loop->index);
                    $paymentReference = trim((string)($row->payment_ref_no ?? ''));
                    if ($paymentReference === '' && preg_match('/bulk\s+payment\s+([a-z0-9_-]+)/i', $description, $referenceMatch)) {
                        $paymentReference = (string)($referenceMatch[1] ?? '');
                    }
                    $paymentStatus = trim((string)($row->payment_status ?? ''));
                    $paymentMethod = strtolower(trim((string)($row->payment_method ?? '')));
                    $voucherNo = trim((string)($row->voucher_no ?? ''));
                    $vehicleReference = trim((string)($row->customer_ref ?? ''));
                    if ($vehicleReference === '') { $vehicleReference = trim((string)($row->ref_no ?? '')); }
                    if ($vehicleReference === '') { $vehicleReference = trim((string)($row->sale_ref ?? '')); }

                    $descriptionDetails = [];
                    if ($vehicleReference !== '' && stripos($description, $vehicleReference) === false) {
                        $descriptionDetails[] = ['label' => 'Vehicle / Ref No', 'value' => $vehicleReference];
                    }
                    if ($voucherNo !== '' && stripos($description, $voucherNo) === false) {
                        $descriptionDetails[] = ['label' => 'Voucher No', 'value' => $voucherNo];
                    }

                    $paymentDetails = [];
                    if ($paymentReference !== '') { $paymentDetails[] = ['label' => 'Payment Ref', 'value' => $paymentReference]; }
                    $paymentReferenceNo = trim((string)($row->payment_reference_no ?? ''));
                    if ($paymentReferenceNo !== '' && $paymentReferenceNo !== $paymentReference) { $paymentDetails[] = ['label' => 'Reference', 'value' => $paymentReferenceNo]; }
                    if ($voucherNo !== '' && $voucherNo !== $paymentReference && $voucherNo !== $paymentReferenceNo) { $paymentDetails[] = ['label' => 'Voucher', 'value' => $voucherNo]; }

                    $chequeNumber = trim((string)($row->cheque_number ?? ''));
                    $chequeDate = trim((string)($row->cheque_date ?? ''));
                    $bankName = trim((string)($row->bank_name ?? ''));
                    if ($paymentMethod === 'cheque' || $chequeNumber !== '') {
                        if ($chequeNumber !== '') { $paymentDetails[] = ['label' => 'Cheque No', 'value' => $chequeNumber]; }
                        if ($chequeDate !== '' && strtotime($chequeDate)) { $paymentDetails[] = ['label' => 'Cheque Date', 'value' => date('Y-m-d', strtotime($chequeDate))]; }
                        if ($bankName !== '') { $paymentDetails[] = ['label' => 'Bank', 'value' => $bankName]; }
                    } else {
                        if ($bankName !== '') { $paymentDetails[] = ['label' => 'Bank', 'value' => $bankName]; }
                        $transferDate = trim((string)($row->transfer_date ?? ''));
                        if ($transferDate !== '' && strtotime($transferDate)) { $paymentDetails[] = ['label' => 'Transfer Date', 'value' => date('Y-m-d', strtotime($transferDate))]; }
                    }

                    $cardType = trim((string)($row->card_type ?? ''));
                    $cardNumber = preg_replace('/\s+/', '', trim((string)($row->card_number ?? '')));
                    $cardHolder = trim((string)($row->card_holder_name ?? ''));
                    $cardTransaction = trim((string)($row->card_transaction_number ?? ''));
                    if ($cardType !== '') { $paymentDetails[] = ['label' => 'Card Type', 'value' => $cardType]; }
                    if ($cardNumber !== '') { $paymentDetails[] = ['label' => 'Card', 'value' => '**** ' . substr($cardNumber, -4)]; }
                    if ($cardHolder !== '') { $paymentDetails[] = ['label' => 'Card Holder', 'value' => $cardHolder]; }
                    if ($cardTransaction !== '') { $paymentDetails[] = ['label' => 'Card Txn', 'value' => $cardTransaction]; }
                    $paymentNote = trim((string)($row->payment_note ?? ''));
                    if ($paymentNote !== '') { $paymentDetails[] = ['label' => 'Note', 'value' => $paymentNote]; }

                    $createdOrder = !empty($row->created_at) && strtotime($row->created_at) ? date('Y-m-d H:i:s', strtotime($row->created_at)) : '';
                    $transactionOrder = !empty($row->transaction_date) && strtotime($row->transaction_date) ? date('Y-m-d H:i:s', strtotime($row->transaction_date)) : '';
                @endphp
                <tr class="{{ ($isOpeningBalance || $isBroughtForward) ? 'customer-opening-balance-row' : '' }}" data-ledger-side="{{ $isCredit ? 'credit' : 'debit' }}">
                    <td data-order="{{ $createdOrder }}">{{ $createdOrder !== '' ? date('Y-m-d H:i', strtotime($createdOrder)) : '' }}</td>
                    <td data-order="{{ $transactionOrder }}">{{ $transactionOrder !== '' ? date('Y-m-d', strtotime($transactionOrder)) : '' }}</td>
                    <td class="ledger-description">
                        <span>{{ $description !== '' ? $description : '-' }}</span>
                        @foreach($descriptionDetails as $detail)
                            <span class="ledger-description-detail"><strong>{{ $detail['label'] }}:</strong> {{ $detail['value'] }}</span>
                        @endforeach
                    </td>
                    <td>{{ $isBroughtForward ? 'Brought Forward' : ucwords(str_replace('_', ' ', $row->transaction_type ?? $row->type ?? '-')) }}</td>
                    <td>
                        <div class="ledger-status-wrap">
                            <span class="ledger-payment-status">{{ $paymentStatus !== '' ? ucwords(str_replace('_', ' ', $paymentStatus)) : '-' }}</span>
                            @if($hasBulkBills)
                                <button type="button" class="customer-ledger-bills-btn" data-target="#{{ $billDetailId }}">Bills</button>
                            @endif
                        </div>
                        @if($hasBulkBills)
                            <div id="{{ $billDetailId }}" class="ledger-bill-details-panel">
                                <span class="ledger-bill-reference">{{ $paymentReference !== '' ? $paymentReference : 'Bulk Payment' }}</span>
                                <table class="table table-bordered table-condensed ledger-bill-table">
                                    <thead><tr><th>Bill No</th><th class="text-right">Allocated Amount</th></tr></thead>
                                    <tbody>
                                        @php $billAllocatedTotal = 0; @endphp
                                        @foreach($bulkBills as $bill)
                                            @php $billAmount = (float)($bill['amount'] ?? 0); $billAllocatedTotal += $billAmount; @endphp
                                            <tr><td>{{ $bill['bill_no'] ?? '-' }}</td><td class="text-right">{{ number_format($billAmount, 2) }}</td></tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot><tr><th class="text-right">Total</th><th class="text-right">{{ number_format($billAllocatedTotal, 2) }}</th></tr></tfoot>
                                </table>
                            </div>
                        @endif
                    </td>
                    <td class="text-right" data-order="{{ $debit }}">{{ $debit ? number_format($debit, 2) : '-' }}</td>
                    <td class="text-right" data-order="{{ $credit }}">{{ $credit ? number_format($credit, 2) : '-' }}</td>
                    <td class="text-right" data-order="{{ $runningBalance }}">{{ number_format($runningBalance, 2) }}</td>
                    <td>
                        @if($isBulkPaymentAllocation || $isBulkPaymentUnallocated)
                            <span class="ledger-payment-method-name"><strong>Bulk Payment</strong></span>
                            <span class="ledger-payment-detail"><strong>Lump Sum Amount:</strong> {{ number_format($bulkLumpSumAmount, 2) }}</span>
                            <span class="ledger-payment-detail"><strong>Payment Method:</strong> {{ $paymentMethod !== '' ? ucwords(str_replace('_', ' ', $paymentMethod)) : '-' }}</span>
                            @if($paymentMethod === 'cheque' || $chequeNumber !== '')
                                @if($chequeNumber !== '')
                                    <span class="ledger-payment-detail"><strong>Cheque No:</strong> {{ $chequeNumber }}</span>
                                @endif
                                @if($chequeDate !== '' && strtotime($chequeDate))
                                    <span class="ledger-payment-detail"><strong>Cheque Date:</strong> {{ date('Y-m-d', strtotime($chequeDate)) }}</span>
                                @endif
                                @if($bankName !== '')
                                    <span class="ledger-payment-detail"><strong>Bank:</strong> {{ $bankName }}</span>
                                @endif
                            @endif
                        @else
                            <span class="ledger-payment-method-name">{{ $paymentMethod !== '' ? ucwords(str_replace('_', ' ', $paymentMethod)) : '-' }}</span>
                            @foreach($paymentDetails as $detail)
                                <span class="ledger-payment-detail"><strong>{{ $detail['label'] }}:</strong> {{ $detail['value'] }}</span>
                            @endforeach
                        @endif
                    </td>
                </tr>
            @empty
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
