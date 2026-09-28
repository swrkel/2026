@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.supplier_ledger'))
@section('suppliers_content')
<section class="content-header supplier-ledger-heading">
    <div class="supplier-ledger-heading-row">
        <h1>
            <span>@lang('suppliers::lang.supplier_ledger')</span>
            @if(!empty($supplier))
                <span class="supplier-ledger-supplier-name">
                    {{ $supplier->name }} @if(!empty($supplier->contact_id)) ({{ $supplier->contact_id }}) @endif
                </span>
            @endif
        </h1>
        <a href="{{ route('suppliers.records.index') }}" class="btn btn-primary btn-sm supplier-ledger-list-btn">
            <i class="fa fa-list"></i> List Suppliers Page
        </a>
    </div>
</section>
<section class="content main-content-inner">
    <div class="box box-primary">
        <div class="box-body">
            @include('suppliers::partials.tabs', ['active' => 'ledger'])

            <div class="supplier-ledger-summary" style="margin-bottom:15px;">
                <div class="supplier-ledger-summary-item">
                    <strong>Opening Balance:</strong>
                    <span id="supplier_ledger_opening_balance" class="display_currency" data-currency_symbol="true">0</span>
                </div>
                <div class="supplier-ledger-summary-item">
                    <strong>Total Debit:</strong>
                    <span id="supplier_ledger_total_debit" class="display_currency" data-currency_symbol="true">0</span>
                </div>
                <div class="supplier-ledger-summary-item">
                    <strong>Total Credit:</strong>
                    <span id="supplier_ledger_total_credit" class="display_currency" data-currency_symbol="true">0</span>
                </div>
                <div class="supplier-ledger-summary-item">
                    <strong>Balance:</strong>
                    <span id="supplier_ledger_balance" class="display_currency" data-currency_symbol="true">0</span>
                </div>
                <div class="supplier-ledger-summary-item"><strong>Records:</strong> <span id="supplier_ledger_record_count">0</span></div>
            </div>

            @if($supplier && $ledgerDataUrl)
                <div id="supplier_ledger_table_toolbar" class="supplier-dt-toolbar-host" aria-label="Supplier ledger table controls"></div>
                <div class="table-responsive supplier-full-table-shell supplier-ledger-table-shell">
                    <table
                        class="table table-bordered table-striped supplier-full-width-table"
                        id="supplier_ledger_table"
                        style="width:100%"
                        data-source-url="{{ $ledgerDataUrl }}"
                        data-currency-precision="{{ config('suppliers.currency_precision', 2) }}"
                    >
                        <thead>
                            <tr>
                                <th>System Entered Date</th>
                                <th>Transaction Date</th>
                                <th>@lang('suppliers::lang.description')</th>
                                <th>Type</th>
                                <th>Payment Status</th>
                                <th>Reference</th>
                                <th>Location</th>
                                <th class="text-right">@lang('suppliers::lang.debit')</th>
                                <th class="text-right">@lang('suppliers::lang.credit')</th>
                                <th class="text-right">@lang('suppliers::lang.balance')</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-right">Total</th>
                                <th id="supplier_ledger_footer_debit" class="text-right">0.00</th>
                                <th id="supplier_ledger_footer_credit" class="text-right">0.00</th>
                                <th id="supplier_ledger_footer_balance" class="text-right">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-info">Please select a supplier from the Supplier List to view the ledger.</div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('suppliers_styles')
<style>

    .supplier-ledger-heading-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .supplier-ledger-list-btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .supplier-ledger-heading h1,
    .supplier-stock-report-heading h1 {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        column-gap: 18px;
        row-gap: 4px;
        margin-bottom: 0;
    }

    .supplier-ledger-supplier-name,
    .supplier-tab-supplier-name {
        color: inherit;
        font-family: inherit;
        font-size: inherit;
        font-weight: inherit;
        line-height: inherit;
    }

    /*
     * IS2087: keep the complete Supplier Ledger visible inside the page.
     * Global table rules use nowrap/min-content sizing, which forces this
     * 11-column ledger wider than the content area.  The ledger already has
     * explicit percentage column widths, so allow its headings/text to wrap
     * and let those percentages control the desktop layout.
     */
    .supplier-ledger-table-shell {
        max-width: 100%;
        overflow-x: hidden;
        width: 100%;
    }

    #supplier_ledger_table {
        min-width: 0 !important;
        table-layout: fixed !important;
        width: 100% !important;
    }

    #supplier_ledger_table th,
    #supplier_ledger_table td {
        box-sizing: border-box;
        overflow-wrap: anywhere;
        white-space: normal !important;
        word-break: normal;
    }

    #supplier_ledger_table th {
        line-height: 1.2;
        vertical-align: middle;
    }

    #supplier_ledger_table td:nth-child(8),
    #supplier_ledger_table td:nth-child(9),
    #supplier_ledger_table td:nth-child(10) {
        overflow-wrap: normal;
        white-space: nowrap !important;
    }

    @media (max-width: 767px) {
        .supplier-ledger-heading-row {
            align-items: flex-start;
            flex-direction: column;
        }

        .supplier-ledger-heading h1,
        .supplier-stock-report-heading h1 {
            column-gap: 10px;
        }

        .supplier-ledger-table-shell {
            overflow-x: auto;
        }

        #supplier_ledger_table {
            min-width: 900px !important;
            table-layout: auto !important;
        }
    }
</style>
@endpush
