@php
    $links = [
        ['route' => 'suppliers.communication.contacts.index', 'label' => __('suppliers::lang.contact_persons')],
        ['route' => 'suppliers.communication.documents.index', 'label' => __('suppliers::lang.documents')],
        ['route' => 'suppliers.communication.notes.index', 'label' => __('suppliers::lang.notes')],
        ['route' => 'suppliers.communication.logs.index', 'label' => __('suppliers::lang.communication_log')],
        ['route' => 'suppliers.communication.emails.index', 'label' => __('suppliers::lang.email_history')],
        ['route' => 'suppliers.communication.audit.index', 'label' => __('suppliers::lang.audit_trail')],
    ];
@endphp

<div class="supplier-communication-nav mb-3">
    <div class="btn-group flex-wrap" role="group" aria-label="Supplier communication tabs">
        @foreach($links as $link)
            <a href="{{ route($link['route'], $supplier) }}"
               class="btn btn-sm {{ \Modules\Suppliers\Utils\SupplierViewRuntimeUtil::routeIs($link['route']) ? 'btn-primary' : 'btn-default' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</div>
