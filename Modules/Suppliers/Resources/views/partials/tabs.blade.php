@php
    $active = $active ?? 'profile';
    $supplierCandidate = $supplierId ?? ($supplier ?? null);
    $resolvedSupplierId = \Modules\Suppliers\Utils\SupplierViewRuntimeUtil::supplierId($supplierCandidate);

    $supplierIndexUrl = route('suppliers.records.index');
    $profileUrl = $resolvedSupplierId ? route('suppliers.profile.index', $resolvedSupplierId) : $supplierIndexUrl;
    $contactsUrl = $resolvedSupplierId ? route('suppliers.contacts.index', $resolvedSupplierId) : $supplierIndexUrl;
    $documentsUrl = $resolvedSupplierId ? route('suppliers.documents.index', $resolvedSupplierId) : $supplierIndexUrl;
    $accountsUrl = $resolvedSupplierId ? route('suppliers.accounts.index', $resolvedSupplierId) : $supplierIndexUrl;
    $ledgerUrl = $resolvedSupplierId ? route('suppliers.ledger.index', $resolvedSupplierId) : $supplierIndexUrl;
    $purchaseHistoryUrl = $resolvedSupplierId ? route('suppliers.purchase_history.index', $resolvedSupplierId) : $supplierIndexUrl;
    $paymentsUrl = route('suppliers.payments.index', array_filter(['supplier_id' => $resolvedSupplierId]));
    $mappingUrl = route('suppliers.mappings.index', array_filter(['supplier_id' => $resolvedSupplierId]));
    $stockReportUrl = $resolvedSupplierId
        ? route('suppliers.stock_report.index', ['supplier' => $resolvedSupplierId])
        : route('suppliers.stock_report.index');
@endphp
<ul class="nav nav-tabs supplier-module-tabs" style="margin-bottom: 15px;" data-supplier-id="{{ $resolvedSupplierId }}">
    <li class="{{ $active == 'profile' ? 'active' : '' }}"><a href="{{ $profileUrl }}">@lang('suppliers::lang.profile')</a></li>
    <li class="{{ $active == 'contacts' ? 'active' : '' }}"><a href="{{ $contactsUrl }}">@lang('suppliers::lang.contacts')</a></li>
    <li class="{{ $active == 'documents' ? 'active' : '' }}"><a href="{{ $documentsUrl }}">@lang('suppliers::lang.documents')</a></li>
    <li class="{{ $active == 'accounts' ? 'active' : '' }}"><a href="{{ $accountsUrl }}">@lang('suppliers::lang.accounts')</a></li>
    <li class="{{ $active == 'ledger' ? 'active' : '' }}"><a href="{{ $ledgerUrl }}">@lang('suppliers::lang.ledger')</a></li>
    <li class="{{ $active == 'purchase_history' ? 'active' : '' }}"><a href="{{ $purchaseHistoryUrl }}">@lang('suppliers::lang.purchase_history')</a></li>
    <li class="{{ $active == 'payments' ? 'active' : '' }}"><a href="{{ $paymentsUrl }}">@lang('suppliers::lang.payments')</a></li>
    <li class="{{ $active == 'mappings' ? 'active' : '' }}"><a href="{{ $mappingUrl }}">@lang('suppliers::lang.product_mapping')</a></li>
    <li class="{{ $active == 'stock_report' ? 'active' : '' }}"><a href="{{ $stockReportUrl }}">@lang('suppliers::lang.stock_report')</a></li>
    <li class="{{ $active == 'imports' ? 'active' : '' }}"><a href="{{ route('suppliers.imports.index') }}">@lang('suppliers::lang.imports')</a></li>
    <li class="{{ $active == 'reports' ? 'active' : '' }}"><a href="{{ route('suppliers.reports.index') }}">@lang('suppliers::lang.reports')</a></li>
</ul>


@once
<script type="speculationrules">
{
    "prerender": [{
        "source": "document",
        "where": {"selector_matches": ".supplier-module-tabs a[href]"},
        "eagerness": "moderate"
    }]
}
</script>
@endonce
