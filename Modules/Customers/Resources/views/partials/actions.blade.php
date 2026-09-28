@php
    $id = (int) $row->id;
    $balanceDeferred = isset($balanceDeferred) ? (bool) $balanceDeferred : false;
    $customerHasOutstandingDue = !$balanceDeferred && (float) ($row->total_due ?? 0) > 0.00001;
    $base = '/customers/' . $id;
    $isActiveCustomer = !isset($row->active) || (int) $row->active === 1;
@endphp
<div class="btn-group customer-actions-dropdown">
    <button type="button" class="btn btn-sm btn-primary customers-action-toggle" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-cogs"></i> Actions <span class="caret"></span>
    </button>
    <ul class="dropdown-menu customer-actions-menu">
        @if($balanceDeferred)
            <li class="customer-payment-action-slot" data-customer-id="{{ $id }}">
                <a href="#" onclick="return false;"><i class="fa fa-spinner fa-spin"></i> Loading Balance...</a>
            </li>
        @elseif($customerHasOutstandingDue)
            <li class="customer-payment-action-slot" data-customer-id="{{ $id }}"><a href="{{ $base }}/pay-due" class="customers-action-popup"><i class="fa fa-credit-card text-info"></i> Pay Due Amount</a></li>
        @else
            <li class="customer-payment-action-slot" data-customer-id="{{ $id }}"><a href="{{ $base }}/advance-payment" class="customers-action-popup"><i class="fa fa-money text-success"></i> Advance Payment</a></li>
        @endif
        <li><a href="{{ $base }}/loan" class="customers-action-popup"><i class="fa fa-money text-warning"></i> Loan to Customer</a></li>
        <li class="divider"></li>
        <li class="dropdown-header">Refund Actions</li>
        <li><a href="{{ $base }}/refund-deposit" class="customers-action-popup"><i class="fa fa-money"></i> Refund Deposit</a></li>
        <li><a href="{{ $base }}/security-deposit" class="customers-action-popup"><i class="fa fa-shield"></i> Security Deposit</a></li>
        <li><a href="{{ $base }}/refund-payment" class="customers-action-popup"><i class="fa fa-undo"></i> Refund Payment</a></li>
        <li><a href="{{ $base }}/cheque-return" class="customers-action-popup"><i class="fa fa-exchange"></i> Cheque Return</a></li>
        <li class="divider"></li>
        <li><a href="{{ $base }}" class="customers-action-popup"><i class="fa fa-eye text-info"></i> View Customer</a></li>
        <li><a href="{{ $base }}/edit" class="customers-action-popup"><i class="fa fa-pencil text-warning"></i> Edit Customer</a></li>
        <li><a href="{{ $base }}/balance-details" class="customers-action-popup"><i class="fa fa-eye"></i> Balance Details</a></li>
        <li><a href="{{ $base }}/contact-info" class="customers-action-popup"><i class="fa fa-user"></i> Contact Info</a></li>
        <li><a href="{{ $base }}/ledger" class="customers-action-popup"><i class="fa fa-anchor"></i> Ledger</a></li>
        <li><a href="{{ $base }}/statement" class="customers-action-popup"><i class="fa fa-file-text-o"></i> Statement</a></li>
        <li><a href="{{ $base }}/notes" class="customers-action-popup"><i class="fa fa-sticky-note"></i> Notes</a></li>
        <li><a href="{{ $base }}/documents" class="customers-action-popup"><i class="fa fa-paperclip"></i> Attachments</a></li>
        <li><a href="{{ $base }}/audit" class="customers-action-popup"><i class="fa fa-history"></i> Audit Trail</a></li>
        <li class="divider"></li>
        @if($isActiveCustomer)
            <li>
                <a href="#" class="text-warning" onclick="event.preventDefault(); if (window.confirm('Deactivate this customer? The customer will be removed from active customer dropdowns.')) { document.getElementById('customer-deactivate-{{ $id }}').submit(); }">
                    <i class="fa fa-ban"></i> Deactivate
                </a>
                <form id="customer-deactivate-{{ $id }}" action="{{ route('customers.deactivate', ['customer' => $id]) }}" method="POST" style="display:none;">
                    {{ csrf_field() }}
                </form>
            </li>
        @endif
        <li>
            <a href="{{ $base }}" class="text-danger customers-delete-action" data-form-id="customer-delete-{{ $id }}"><i class="fa fa-trash"></i> Delete</a>
            <form id="customer-delete-{{ $id }}" action="{{ $base }}" method="POST" style="display:none;">
                {{ csrf_field() }}{{ method_field('DELETE') }}
            </form>
        </li>
    </ul>
</div>
