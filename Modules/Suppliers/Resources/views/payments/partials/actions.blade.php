<div class="btn-group supplier-payment-actions" data-payment-id="{{ $paymentId }}">
    <button type="button" class="btn btn-xs btn-info dropdown-toggle supplier-payment-action-toggle" aria-haspopup="true" aria-expanded="false" data-supplier-payment-menu-toggle="1">
        @lang('suppliers::lang.actions') <span class="caret"></span>
    </button>
    {{-- IS1987: supplier-payment-action-menu is the hook the JS uses to lift this
         menu out of the horizontally scrollable table shell before showing it. --}}
    <ul class="dropdown-menu dropdown-menu-right supplier-payment-action-menu" role="menu">
        <li>
            <a href="#"
               class="supplier-payment-edit"
               data-url="{{ $editUrl }}"
               data-paid-on="{{ $editPaidOn ?? '' }}"
               data-location-id="{{ $editLocationId ?? 0 }}"
               data-method="{{ $editMethod ?? '' }}"
               data-account-id="{{ $editAccountId ?? 0 }}">
                <i class="fa fa-edit"></i> @lang('messages.edit')
            </a>
        </li>
        <li>
            <a href="#"
               class="supplier-payment-delete text-red"
               data-url="{{ $deleteUrl }}"
               data-payment-id="{{ $paymentId }}">
                <i class="fa fa-trash"></i> @lang('messages.delete')
            </a>
        </li>
        <li>
            <a href="#"
               class="supplier-payment-view"
               data-url="{{ $viewUrl }}">
                <i class="fa fa-eye"></i> @lang('messages.view')
            </a>
        </li>
    </ul>
</div>
