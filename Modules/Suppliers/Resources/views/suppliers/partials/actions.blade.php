@php
    $supplierId = (int) $supplier->id;
    $totalDue = (float) ($supplier->total_due ?? 0);
    $totalPurchaseReturn = (float) ($supplier->total_purchase_return ?? 0);
    $purchaseReturnPaid = (float) ($supplier->purchase_return_paid ?? 0);
    $purchaseReturnDue = $totalPurchaseReturn - $purchaseReturnPaid;
    $supplierType = $supplier->type ?? 'supplier';
    $isActive = (bool) ($supplier->active ?? true);
@endphp

<div class="btn-group supplier-row-actions">
    <button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        @lang('suppliers::lang.actions') <span class="caret"></span>
    </button>

    <ul class="dropdown-menu dropdown-menu-left supplier-action-menu" role="menu">
        @if($totalDue > 0.00001)
            <li>
                <a href="{{ route('suppliers.records.prepare_pay_due', ['supplier' => $supplierId]) }}" class="pay_purchase_due">
                    <i class="fa fa-credit-card text-info"></i> @lang('contact.pay_due_amount')
                </a>
            </li>
        @endif

        @if($purchaseReturnDue > 0.00001 && $totalDue < 0)
            <li>
                <a href="{{ url('/payments/pay-contact-due/' . $supplierId) }}?type=purchase_return" class="pay_purchase_due">
                    <i class="fa fa-credit-card text-info"></i> @lang('lang_v1.receive_purchase_return_due')
                </a>
            </li>
        @endif

        <li>
            <a href="{{ url('/payments/advance-payment/' . $supplierId) }}?type=advance_payment" class="pay_purchase_due">
                <i class="fa fa-money text-success"></i> @lang('lang_v1.advance_payment')
            </a>
        </li>
        <li>
            <a href="{{ url('/payments/refund_deposit/' . $supplierId) }}" class="pay_purchase_due">
                <i class="fa fa-money"></i> @lang('contact.refund_deposit')
            </a>
        </li>
        <li>
            <a href="{{ url('/payments/security-deposit/' . $supplierId) }}?type=security_deposit" class="pay_purchase_due">
                <i class="fa fa-shield"></i> @lang('lang_v1.security_deposit')
            </a>
        </li>
        @can('supplier.view')
            <li>
                <a href="{{ route('suppliers.records.show', $supplierId) }}">
                    <i class="fa fa-eye"></i> @lang('messages.view')
                </a>
            </li>
        @endcan

        @can('supplier.update')
            <li>
                <a href="{{ route('suppliers.records.edit', $supplierId) }}">
                    <i class="fa fa-edit"></i> @lang('messages.edit')
                </a>
            </li>
        @endcan

        @can('supplier.delete')
            <li>
                <form method="POST" action="{{ route('suppliers.records.destroy', $supplierId) }}" class="supplier-dropdown-form" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="supplier-dropdown-action supplier-delete-action">
                        <i class="fa fa-trash"></i> @lang('messages.delete')
                    </button>
                </form>
            </li>
        @endcan

        @can('supplier.view')
            <li class="divider"></li>

            @if(\Illuminate\Support\Facades\Route::has('airline.create_linked_supplier_account'))
                <li>
                    <a href="#"
                       class="btn-modal"
                       data-href="{{ route('airline.create_linked_supplier_account', ['supplier_id' => $supplierId]) }}"
                       data-container=".linked_account_modal">
                        <i class="fa fa-plus"></i> @lang('lang_v1.linked_supplier_account')
                    </a>
                </li>
            @endif

            <li>
                <a href="{{ route('suppliers.balance_summary.index', $supplierId) }}">
                    <i class="fa fa-eye"></i> @lang('contact.balance_details')
                </a>
            </li>
            <li>
                <a href="{{ route('suppliers.contacts.index', $supplierId) }}">
                    <i class="fa fa-user"></i> @lang('suppliers::lang.contact_info')
                </a>
            </li>
            <li>
                <a href="{{ route('suppliers.ledger.index', $supplierId) }}">
                    <i class="fa fa-anchor"></i> @lang('lang_v1.ledger')
                </a>
            </li>

            @if(in_array($supplierType, ['both', 'supplier'], true))
                <li>
                    <a href="{{ route('suppliers.purchase_history.index', $supplierId) }}">
                        <i class="fa fa-arrow-circle-down"></i> @lang('purchase.purchases')
                    </a>
                </li>
            @endif

            <li>
                <a href="{{ route('suppliers.profile.index', $supplierId) }}#tab-documents">
                    <i class="fa fa-paperclip"></i> @lang('lang_v1.documents_and_notes')
                </a>
            </li>
        @endcan

        @can('supplier.update')
            <li>
                <form method="POST" action="{{ route('suppliers.records.toggle_active', $supplierId) }}" class="supplier-dropdown-form">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="supplier-dropdown-action">
                        @if($isActive)
                            <i class="fa fa-times"></i> @lang('lang_v1.deactivate')
                        @else
                            <i class="fa fa-check"></i> @lang('lang_v1.activate')
                        @endif
                    </button>
                </form>
            </li>
        @endcan
    </ul>
</div>
