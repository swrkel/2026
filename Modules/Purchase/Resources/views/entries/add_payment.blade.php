@extends('layouts.app')
@section('title', 'Add Purchase Payment')

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>

<section class="content purchase-workspace purchase-entry-payment-page">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-money"></i> Add Payments</h1>
            <div class="purchase-breadcrumb">Purchase (New) / List Purchase Entries / Add Payments</div>
        </div>
        <div class="purchase-workspace-actions">
            <a href="{{ route('purchase.entries.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Purchases</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="purchase-summary-strip">
        <div class="purchase-summary-tile"><span>Purchase No.</span><strong>{{ $purchase_no }}</strong></div>
        <div class="purchase-summary-tile"><span>Purchase Total</span><strong>{{ number_format((float)$purchase->final_total, 2) }}</strong></div>
        <div class="purchase-summary-tile"><span>Already Paid</span><strong>{{ number_format((float)$paid_total, 2) }}</strong></div>
        <div class="purchase-summary-tile"><span>Due</span><strong>{{ number_format((float)$due_total, 2) }}</strong></div>
    </div>

    <div class="purchase-panel">
        <div class="purchase-panel-heading"><span><i class="fa fa-credit-card"></i> Payment Details</span></div>
        <div class="purchase-panel-body">
            <div class="row" style="margin-bottom:15px">
                <div class="col-md-4"><strong>Supplier:</strong> {{ $supplier_name ?: '-' }}</div>
                <div class="col-md-4"><strong>Location:</strong> {{ $location_name ?: '-' }}</div>
                <div class="col-md-4"><strong>Payment Status:</strong> {{ ucfirst((string)($purchase->payment_status ?: 'due')) }}</div>
            </div>

            <form method="post" action="{{ route('purchase.entries.payments.store', $purchase->id) }}" id="purchase_entry_add_payment_form">
                @csrf
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="payment_method">Payment Method *</label>
                        @php($defaultPaymentMethod = array_key_first($payment_methods))
                        <select class="form-control" id="payment_method" name="method" required>
                            @foreach($payment_methods as $methodKey => $methodLabel)
                                <option value="{{ $methodKey }}" @selected(old('method', $defaultPaymentMethod) === $methodKey)>{{ $methodLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="payment_amount">Amount *</label>
                        <input type="number" step="0.000001" min="0.000001" max="{{ $due_total }}" class="form-control" id="payment_amount" name="amount" value="{{ old('amount', number_format((float)$due_total, 2, '.', '')) }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="payment_account_id">Payment Account *</label>
                        <select class="form-control" id="payment_account_id" name="account_id" required>
                            <option value="">Please Select</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}"
                                    data-group-id="{{ $account->group_id ?? '' }}"
                                    data-location-id="{{ $account->location_id ?? 'all' }}"
                                    @selected((string)old('account_id') === (string)$account->id)>
                                    {{ $account->name }}{{ !empty($account->group_name) ? ' — '.$account->group_name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="payment_account_help"></small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="paid_on">Paid On *</label>
                        <input type="datetime-local" class="form-control" id="paid_on" name="paid_on" value="{{ old('paid_on', $paid_on) }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>System Payment Reference</label>
                        <input type="text" class="form-control" value="{{ $payment_ref_preview ?? '' }}" readonly>
                        <small class="text-muted">Preview from Supplier Settings. The final reference is assigned when the payment is saved.</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="reference_no">External Reference (Optional)</label>
                        <input type="text" class="form-control" id="reference_no" name="reference_no" value="{{ old('reference_no') }}" placeholder="Bank / slip / external reference">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="payment_note">Note</label>
                        <input type="text" class="form-control" id="payment_note" name="note" value="{{ old('note') }}">
                    </div>
                </div>

                <div class="row purchase-payment-extra" data-payment-extra="cheque" style="display:none">
                    <div class="col-md-4 form-group"><label>Cheque No.</label><input type="text" class="form-control" name="cheque_number" value="{{ old('cheque_number') }}"></div>
                    <div class="col-md-4 form-group"><label>Cheque Date</label><input type="date" class="form-control" name="cheque_date" value="{{ old('cheque_date') }}"></div>
                    <div class="col-md-4 form-group"><label>Bank Name</label><input type="text" class="form-control" name="bank_name" value="{{ old('bank_name') }}"></div>
                </div>
                <div class="row purchase-payment-extra" data-payment-extra="bank_transfer" style="display:none">
                    <div class="col-md-4 form-group"><label>Bank Name</label><input type="text" class="form-control" name="bank_name" value="{{ old('bank_name') }}"></div>
                    <div class="col-md-4 form-group"><label>Bank Account Number</label><input type="text" class="form-control" name="bank_account_number" value="{{ old('bank_account_number') }}"></div>
                    <div class="col-md-4 form-group"><label>Transfer Date</label><input type="date" class="form-control" name="transfer_date" value="{{ old('transfer_date') }}"></div>
                </div>
                <div class="row purchase-payment-extra" data-payment-extra="card" style="display:none">
                    <div class="col-md-3 form-group"><label>Card Transaction No.</label><input type="text" class="form-control" name="card_transaction_number" value="{{ old('card_transaction_number') }}"></div>
                    <div class="col-md-3 form-group"><label>Card Number</label><input type="text" class="form-control" name="card_number" value="{{ old('card_number') }}"></div>
                    <div class="col-md-3 form-group"><label>Card Type</label><input type="text" class="form-control" name="card_type" value="{{ old('card_type') }}"></div>
                    <div class="col-md-3 form-group"><label>Card Holder Name</label><input type="text" class="form-control" name="card_holder_name" value="{{ old('card_holder_name') }}"></div>
                </div>

                <div class="text-right">
                    <a href="{{ route('purchase.entries.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@section('javascript')
@php
    /*
     * Keep the config construction outside Blade's @json directive.
     * Older Blade directive parsers can mis-parse nested PHP expressions in
     * @json([...]) and generate invalid cached PHP for this page.
     */
    $purchaseEntryAddPaymentConfig = [
        'locationId' => (string) ($purchase->location_id ?? ''),
        'methodAccounts' => $payment_method_accounts,
        'oldAccountId' => (string) old('account_id', ''),
    ];
@endphp
<script>
window.purchaseEntryAddPaymentConfig = {!! json_encode(
    $purchaseEntryAddPaymentConfig,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
) !!};
</script>
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-entry-add-payment.js')) !!}</script>
@endsection
