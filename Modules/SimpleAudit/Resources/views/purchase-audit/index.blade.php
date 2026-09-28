@extends(config('simpleaudit.host_layout', 'simpleaudit::layouts.standalone'))

@section('title', __('simpleaudit::simpleaudit.purchase_audit'))

@section('content')
@php
    $sauAssetVersion = '1.0.21-' . ((string) (@filemtime(base_path('Modules/SimpleAudit/Resources/assets/js/simple-audit.js')) ?: 0));
@endphp
<link rel="stylesheet" href="{{ route('simpleaudit.asset', ['type'=>'css','file'=>'simple-audit.css']) }}?v={{ $sauAssetVersion }}">

<div id="sauApp" class="sau-page" data-build="1.0.21"
     data-tenants-url="{{ route('simpleaudit.context.tenants') }}"
     data-businesses-url="{{ route('simpleaudit.context.businesses') }}"
     data-locations-url="{{ route('simpleaudit.context.locations') }}"
     data-stores-url="{{ route('simpleaudit.context.stores') }}"
     data-data-url="{{ route('simpleaudit.purchase-audit.data') }}"
     data-details-url="{{ route('simpleaudit.purchase-audit.details') }}"
     data-export-url="{{ url(config('simpleaudit.route_prefix','simple-audit').'/purchase-audit/export') }}"
     data-print-url="{{ route('simpleaudit.purchase-audit.print') }}"
     data-share-url="{{ route('simpleaudit.purchase-audit.share') }}"
     data-email-url="{{ route('simpleaudit.purchase-audit.email') }}"
     data-page-length="{{ $defaultPageLength }}"
     data-show-store="{{ $showStoreFilter ? '1' : '0' }}">

    <div class="sau-page-header">
        <div>
            <div class="sau-eyebrow">{{ __('simpleaudit::simpleaudit.module_name') }}</div>
            <h1>{{ __('simpleaudit::simpleaudit.purchase_audit') }}</h1>
            <p id="sauSelectionSummary">{{ __('simpleaudit::simpleaudit.select_context_help') }}</p>
        </div>
        <div class="sau-status-pill" id="sauChangePill">0 {{ __('simpleaudit::simpleaudit.audit_changes') }}</div>
    </div>

    <section class="sau-card sau-filter-card">
        <div class="sau-filter-grid">
            <div class="sau-field" id="tenantField">
                <label>{{ __('simpleaudit::simpleaudit.tenant') }}</label>
                <div class="sau-combo" data-name="tenant_id">
                    <input type="text" class="sau-type-filter" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" placeholder="{{ __('simpleaudit::simpleaudit.select_tenant') }}">
                    <input type="hidden" name="tenant_id">
                    <div class="sau-combo-menu"></div>
                </div>
            </div>
            <div class="sau-field">
                <label>{{ __('simpleaudit::simpleaudit.business') }}</label>
                <div class="sau-combo" data-name="business_id">
                    <input type="text" class="sau-type-filter" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" placeholder="{{ __('simpleaudit::simpleaudit.select_business') }}">
                    <input type="hidden" name="business_id">
                    <div class="sau-combo-menu"></div>
                </div>
            </div>
            <div class="sau-field">
                <label>{{ __('simpleaudit::simpleaudit.location') }}</label>
                <div class="sau-combo" data-name="location_id">
                    <input type="text" class="sau-type-filter" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" placeholder="{{ __('simpleaudit::simpleaudit.select_location') }}">
                    <input type="hidden" name="location_id">
                    <div class="sau-combo-menu"></div>
                </div>
            </div>
            <div class="sau-field" id="storeField" style="{{ $showStoreFilter ? '' : 'display:none' }}">
                <label>{{ __('simpleaudit::simpleaudit.store') }}</label>
                <div class="sau-combo" data-name="store_id">
                    <input type="text" class="sau-type-filter" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" placeholder="{{ __('simpleaudit::simpleaudit.all_stores') }}">
                    <input type="hidden" name="store_id">
                    <div class="sau-combo-menu"></div>
                </div>
            </div>
            <div class="sau-field sau-date-field">
                <label>{{ __('simpleaudit::simpleaudit.date_period') }}</label>
                <div class="sau-date-range-wrapper">
                    <span class="sau-date-icon" aria-hidden="true"><i class="fa fa-calendar"></i></span>
                    <input id="dateRange"
                           class="sau-date-range-picker"
                           type="text"
                           autocomplete="off"
                           inputmode="text"
                           aria-label="{{ __('simpleaudit::simpleaudit.date_period') }}"
                           placeholder="{{ __('simpleaudit::simpleaudit.date_range_placeholder') }}">
                    <input id="dateFrom" type="hidden">
                    <input id="dateTo" type="hidden">
                </div>
                <small class="sau-filter-help">{{ __('simpleaudit::simpleaudit.date_range_help') }}</small>
            </div>
        </div>
    </section>

    <section class="sau-card sau-toolbar">
        <div class="sau-toolbar-left">
            <div class="sau-search-wrap">
                <span>⌕</span>
                <input id="universalSearch" type="search" placeholder="{{ __('simpleaudit::simpleaudit.universal_search') }}">
            </div>
            <label class="sau-inline-control">{{ __('simpleaudit::simpleaudit.transactions_per_page') }}
                <select id="pageLength">
                    <option>10</option><option selected>25</option><option>50</option><option>100</option><option value="999999">{{ __('simpleaudit::simpleaudit.all') }}</option>
                </select>
            </label>
        </div>
        <div class="sau-toolbar-actions">
            <button type="button" class="sau-btn" data-action="xls">{{ __('simpleaudit::simpleaudit.export_excel') }}</button>
            <button type="button" class="sau-btn" data-action="csv">{{ __('simpleaudit::simpleaudit.export_csv') }}</button>
            <button type="button" class="sau-btn" data-action="columns">{{ __('simpleaudit::simpleaudit.columns') }}</button>
            <button type="button" class="sau-btn" data-action="print">{{ __('simpleaudit::simpleaudit.print') }}</button>
            <button type="button" class="sau-btn" data-action="pdf">{{ __('simpleaudit::simpleaudit.pdf') }}</button>
            <button type="button" class="sau-btn" data-action="email">{{ __('simpleaudit::simpleaudit.email') }}</button>
            <button type="button" class="sau-btn sau-btn-accent" data-action="whatsapp">{{ __('simpleaudit::simpleaudit.whatsapp') }}</button>
        </div>
    </section>

    <div id="sauAlert" class="sau-alert" hidden></div>
    <div id="sauCoverageAlert" class="sau-alert sau-alert-info" hidden></div>

    <section class="sau-report-card" data-section="purchases">
        <div class="sau-section-head"><h2>{{ __('simpleaudit::simpleaudit.purchase') }}</h2><span class="sau-section-count"></span></div>
        <div class="sau-table-wrap">
            <table class="sau-table">
                <thead><tr>
                    <th data-col="product">{{ __('simpleaudit::simpleaudit.product') }}</th>
                    <th data-col="sku">{{ __('simpleaudit::simpleaudit.code') }}</th>
                    <th data-col="qty" class="num">{{ __('simpleaudit::simpleaudit.qty') }}</th>
                    <th data-col="unit_cost" class="num">{{ __('simpleaudit::simpleaudit.unit_cost') }}</th>
                    <th data-col="total" class="num">{{ __('simpleaudit::simpleaudit.total') }}</th>
                    <th data-col="discount" class="num">{{ __('simpleaudit::simpleaudit.discount') }}</th>
                    <th data-col="tax" class="num">{{ __('simpleaudit::simpleaudit.tax') }}</th>
                </tr></thead>
                <tbody></tbody><tfoot></tfoot>
            </table>
        </div>
        <div class="sau-pagination"></div>
    </section>

    <section class="sau-report-card" data-section="stock_movements">
        <div class="sau-section-head"><h2>{{ __('simpleaudit::simpleaudit.stock_movements') }}</h2><span class="sau-section-count"></span></div>
        <div class="sau-table-wrap">
            <table class="sau-table">
                <thead><tr>
                    <th data-col="product">{{ __('simpleaudit::simpleaudit.product') }}</th>
                    <th data-col="sku">{{ __('simpleaudit::simpleaudit.code') }}</th>
                    <th data-col="before" class="num">{{ __('simpleaudit::simpleaudit.before') }}</th>
                    <th data-col="purchases" class="num">{{ __('simpleaudit::simpleaudit.purchases') }}</th>
                    <th data-col="purchase_return" class="num">{{ __('simpleaudit::simpleaudit.purchase_return') }}</th>
                    <th data-col="stock_adjustment" class="num">{{ __('simpleaudit::simpleaudit.stock_adjustment') }}</th>
                    <th data-col="after" class="num">{{ __('simpleaudit::simpleaudit.after') }}</th>
                    <th data-col="difference" class="num">{{ __('simpleaudit::simpleaudit.difference') }}</th>
                </tr></thead>
                <tbody></tbody><tfoot></tfoot>
            </table>
        </div>
        <div class="sau-pagination"></div>
    </section>

    <div class="sau-two-col">
        <section class="sau-report-card" data-section="supplier_payments">
            <div class="sau-section-head"><h2>{{ __('simpleaudit::simpleaudit.supplier_payments') }}</h2><span class="sau-section-count"></span></div>
            <div class="sau-table-wrap"><table class="sau-table">
                <thead><tr><th data-col="supplier">{{ __('simpleaudit::simpleaudit.supplier') }}</th><th data-col="before" class="num">{{ __('simpleaudit::simpleaudit.before') }}</th><th data-col="after" class="num">{{ __('simpleaudit::simpleaudit.after') }}</th><th data-col="difference" class="num">{{ __('simpleaudit::simpleaudit.difference') }}</th></tr></thead>
                <tbody></tbody><tfoot></tfoot>
            </table></div><div class="sau-pagination"></div>
        </section>

        <section class="sau-report-card" data-section="accounts">
            <div class="sau-section-head"><h2>{{ __('simpleaudit::simpleaudit.accounts') }}</h2><span class="sau-section-count"></span></div>
            <div class="sau-table-wrap"><table class="sau-table">
                <thead><tr><th data-col="account">{{ __('simpleaudit::simpleaudit.account') }}</th><th data-col="account_number">{{ __('simpleaudit::simpleaudit.account_no') }}</th><th data-col="before" class="num">{{ __('simpleaudit::simpleaudit.before') }}</th><th data-col="after" class="num">{{ __('simpleaudit::simpleaudit.after') }}</th><th data-col="difference" class="num">{{ __('simpleaudit::simpleaudit.difference') }}</th></tr></thead>
                <tbody></tbody><tfoot></tfoot>
            </table></div><div class="sau-pagination"></div>
        </section>
    </div>

    <section class="sau-report-card" data-section="supplier_ledgers">
        <div class="sau-section-head"><h2>{{ __('simpleaudit::simpleaudit.supplier_ledgers') }}</h2><span class="sau-section-count"></span></div>
        <div class="sau-table-wrap"><table class="sau-table">
            <thead><tr><th data-col="supplier">{{ __('simpleaudit::simpleaudit.supplier') }}</th><th data-col="before" class="num">{{ __('simpleaudit::simpleaudit.before') }}</th><th data-col="after" class="num">{{ __('simpleaudit::simpleaudit.after') }}</th><th data-col="difference" class="num">{{ __('simpleaudit::simpleaudit.difference') }}</th></tr></thead>
            <tbody></tbody><tfoot></tfoot>
        </table></div><div class="sau-pagination"></div>
    </section>

    <div id="sauLoading" class="sau-loading" hidden><div class="sau-spinner"></div><strong>{{ __('simpleaudit::simpleaudit.loading_purchase_audit') }}</strong></div>

    <div id="detailsModal" class="sau-modal" hidden>
        <div class="sau-modal-backdrop" data-close-modal></div>
        <div class="sau-modal-dialog sau-modal-lg">
            <div class="sau-modal-head"><div><small>{{ __('simpleaudit::simpleaudit.module_name') }}</small><h3>{{ __('simpleaudit::simpleaudit.details') }}</h3></div><button type="button" data-close-modal aria-label="{{ __('simpleaudit::simpleaudit.close') }}">×</button></div>
            <div class="sau-modal-tabs"><button class="active" data-detail-tab="rows">{{ __('simpleaudit::simpleaudit.transactions') }}</button><button data-detail-tab="changes">{{ __('simpleaudit::simpleaudit.changes') }}</button></div>
            <div id="detailsRows" class="sau-modal-body"></div>
            <div id="detailsChanges" class="sau-modal-body" hidden></div>
        </div>
    </div>

    <div id="columnsModal" class="sau-modal" hidden>
        <div class="sau-modal-backdrop" data-close-modal></div>
        <div class="sau-modal-dialog">
            <div class="sau-modal-head"><div><small>{{ __('simpleaudit::simpleaudit.display') }}</small><h3>{{ __('simpleaudit::simpleaudit.column_visibility') }}</h3></div><button type="button" data-close-modal aria-label="{{ __('simpleaudit::simpleaudit.close') }}">×</button></div>
            <div id="columnOptions" class="sau-modal-body sau-check-list"></div>
        </div>
    </div>

    <div id="emailModal" class="sau-modal" hidden>
        <div class="sau-modal-backdrop" data-close-modal></div>
        <div class="sau-modal-dialog">
            <div class="sau-modal-head"><div><small>{{ __('simpleaudit::simpleaudit.share_report') }}</small><h3>{{ __('simpleaudit::simpleaudit.email_purchase_audit') }}</h3></div><button type="button" data-close-modal aria-label="{{ __('simpleaudit::simpleaudit.close') }}">×</button></div>
            <form id="emailForm" class="sau-modal-body">
                <label>{{ __('simpleaudit::simpleaudit.email_address') }}<input name="email" type="email" required placeholder="name@example.com"></label>
                <label>{{ __('simpleaudit::simpleaudit.note') }}<textarea name="note" rows="4" placeholder="{{ __('simpleaudit::simpleaudit.optional_note_recipient') }}"></textarea></label>
                <div class="sau-form-actions"><button type="button" class="sau-btn" data-close-modal>{{ __('simpleaudit::simpleaudit.cancel') }}</button><button type="submit" class="sau-btn sau-btn-primary">{{ __('simpleaudit::simpleaudit.send_email') }}</button></div>
            </form>
        </div>
    </div>

    <div id="whatsappModal" class="sau-modal" hidden>
        <div class="sau-modal-backdrop" data-close-modal></div>
        <div class="sau-modal-dialog">
            <div class="sau-modal-head"><div><small>{{ __('simpleaudit::simpleaudit.share_report') }}</small><h3>{{ __('simpleaudit::simpleaudit.whatsapp_purchase_audit') }}</h3></div><button type="button" data-close-modal aria-label="{{ __('simpleaudit::simpleaudit.close') }}">×</button></div>
            <form id="whatsappForm" class="sau-modal-body">
                <label>{{ __('simpleaudit::simpleaudit.whatsapp_number') }}<input name="phone" type="tel" placeholder="9477XXXXXXX" required></label>
                <label>{{ __('simpleaudit::simpleaudit.note') }}<textarea name="note" rows="4" placeholder="{{ __('simpleaudit::simpleaudit.optional_message') }}"></textarea></label>
                <p class="sau-help">{{ __('simpleaudit::simpleaudit.whatsapp_link_help') }}</p>
                <div class="sau-form-actions"><button type="button" class="sau-btn" data-close-modal>{{ __('simpleaudit::simpleaudit.cancel') }}</button><button type="submit" class="sau-btn sau-btn-accent">{{ __('simpleaudit::simpleaudit.open_whatsapp') }}</button></div>
            </form>
        </div>
    </div>
</div>
<script>
window.SAU_CSRF = @json(csrf_token());
window.SAU_I18N = @json(trans('simpleaudit::simpleaudit'));
</script>
<script src="{{ route('simpleaudit.asset', ['type'=>'js','file'=>'simple-audit.js']) }}?v={{ $sauAssetVersion }}"></script>
@endsection
