@php
    $precision = max(0, min(6, (int) (session('business.currency_precision') ?? 2)));
    $method = trim((string) data_get($payment, 'method', ''));
    $methodLabel = $method !== '' ? ucwords(str_replace('_', ' ', $method)) : '-';
    $reference = trim((string) data_get($payment, 'payment_ref_no', ''));
    $paidOnDate = isset($paidOnDate) ? (string) $paidOnDate : '';
    $locationId = isset($locationId) ? (int) $locationId : 0;
    $locationName = isset($locationName) ? trim((string) $locationName) : '';
    $paymentMethods = isset($paymentMethods) && is_array($paymentMethods) ? $paymentMethods : [];
@endphp

<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form method="post"
              action="{{ $updateUrl }}"
              class="supplier-payment-root-edit-form"
              data-update-url="{{ $updateUrl }}"
              data-location-id="{{ $locationId }}"
              data-account-endpoint="{{ url('/finance/get-account-group-name-dp') }}"
              data-account-endpoint-legacy="{{ url('/accounting-module/get-account-group-name-dp') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="location_id" value="{{ $locationId }}">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    Edit Supplier Payment
                    @if($reference !== '')
                        <small>(Reference No: {{ $reference }})</small>
                    @endif
                </h4>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger supplier-payment-edit-error" style="display:none"></div>

                <div class="row">
                    <div class="col-sm-4 form-group">
                        <label>Supplier</label>
                        <input type="text" class="form-control" value="{{ data_get($supplier, 'supplier_business_name') ?: data_get($supplier, 'name', '-') }}" readonly>
                    </div>
                    <div class="col-sm-4 form-group">
                        <label>Business Location</label>
                        <input type="text" class="form-control" value="{{ $locationName !== '' ? $locationName : '-' }}" readonly>
                    </div>
                    <div class="col-sm-4 form-group">
                        <label>Amount</label>
                        <input type="text" class="form-control" value="{{ number_format((float) data_get($payment, 'amount', 0), $precision, '.', '') }}" readonly>
                        <small class="help-block">The allocated supplier payment amount is kept unchanged here.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 form-group">
                        <label for="supplier_payment_edit_paid_on">Paid on</label>
                        <input type="date"
                               id="supplier_payment_edit_paid_on"
                               name="paid_on"
                               class="form-control"
                               value="{{ $paidOnDate }}"
                               required>
                    </div>
                    <div class="col-sm-6 form-group">
                        <label for="supplier_payment_edit_method">Payment Method</label>
                        <select id="supplier_payment_edit_method"
                                name="method"
                                class="form-control select2 supplier-payment-edit-method"
                                required
                                style="width:100%">
                            @if(empty($paymentMethods))
                                <option value="{{ $method }}" selected>{{ $methodLabel }}</option>
                            @else
                                @foreach($paymentMethods as $methodValue => $methodText)
                                    <option value="{{ $methodValue }}" {{ (string) $methodValue === $method ? 'selected' : '' }}>{{ $methodText }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="supplier_payment_edit_account">Payment Account</label>
                    <select id="supplier_payment_edit_account"
                            name="account_id"
                            class="form-control select2 supplier-payment-edit-account"
                            data-current-account="{{ (int) data_get($payment, 'account_id', 0) }}"
                            required
                            style="width:100%">
                        <option value="">Please select</option>
                        @foreach($accounts as $accountId => $accountName)
                            <option value="{{ $accountId }}" {{ (int) data_get($payment, 'account_id', 0) === (int) $accountId ? 'selected' : '' }}>{{ $accountName }}</option>
                        @endforeach
                    </select>
                    <small class="help-block supplier-payment-edit-account-help"></small>
                </div>

                <div class="form-group">
                    <label for="supplier_payment_edit_note">Payment Note</label>
                    <textarea id="supplier_payment_edit_note" name="note" class="form-control" rows="3">{{ data_get($payment, 'note', '') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary supplier-payment-edit-save">
                    <i class="fa fa-save"></i> Update
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>
