@php
    $ledgerEmail = trim((string)($customer->email ?? ''));
    $ledgerWhatsapp = trim((string)($customer->whatsapp_number ?? $customer->whatsapp_no ?? $customer->mobile ?? ''));
    $ledgerPeriodLabel = \Carbon\Carbon::parse($dateFilter['start_date'])->format('d M Y') . ' - ' . \Carbon\Carbon::parse($dateFilter['end_date'])->format('d M Y');
@endphp
<div class="customer-ledger-toolbar"
     id="customer_ledger_toolbar"
     data-customer-name="{{ $customer->name }}"
     data-customer-code="{{ $customer->contact_id }}"
     data-customer-email="{{ $ledgerEmail }}"
     data-customer-whatsapp="{{ $ledgerWhatsapp }}"
     data-period="{{ $ledgerPeriodLabel }}"
     data-balance="{{ number_format((float)($summary['balance'] ?? 0), 2, '.', '') }}">
    <div class="customer-ledger-toolbar-left">
        <label class="customer-ledger-toolbar-field customer-ledger-page-length">
            <span>Show</span>
            <select class="form-control input-sm" id="customer_ledger_page_length">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
                <option value="-1">All</option>
            </select>
            <span>transactions</span>
        </label>

        <input type="text"
               class="form-control input-sm customer-ledger-search"
               id="customer_ledger_universal_search"
               placeholder="Universal Search"
               autocomplete="off"
               aria-label="Universal Search">

        <label class="customer-ledger-toolbar-field customer-ledger-entry-filter">
            <span>Entry</span>
            <select class="form-control input-sm" id="customer_ledger_side_filter">
                <option value="all">All</option>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
            </select>
        </label>
    </div>

    <div class="customer-ledger-toolbar-right">
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-csv" data-ledger-export="csv"><i class="fa fa-file-text-o"></i> CSV</button>
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-excel" data-ledger-export="excel"><i class="fa fa-file-excel-o"></i> Excel</button>
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-pdf" data-ledger-export="pdf"><i class="fa fa-file-pdf-o"></i> PDF</button>
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-print" data-ledger-export="print"><i class="fa fa-print"></i> Print</button>
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-colvis" data-ledger-colvis><i class="fa fa-columns"></i> Column Visibility</button>
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-email" data-ledger-share="email"><i class="fa fa-envelope"></i> Email</button>
        <button type="button" class="btn btn-sm customer-ledger-tool-btn tool-whatsapp" data-ledger-share="whatsapp"><i class="fa fa-whatsapp"></i> WhatsApp</button>
    </div>
</div>
