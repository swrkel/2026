@extends('layouts.app')
@section('title', __('purchase::lang.add_purchase_return'))

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>

@php
    $purchaseReturnConfig = [
        'routes' => $routes,
        'currencyPrecision' => $currency_precision,
        'quantityPrecision' => $quantity_precision,
        'currencySymbol' => $currency_symbol,
        'csrf' => csrf_token(),
        'oldPurchaseId' => (int) old('purchase_id', 0),
    ];
@endphp

<section class="content purchase-workspace">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-undo"></i> Add Purchase Return Entry</h1>
            <div class="purchase-breadcrumb">Purchase (New) / Add Purchase Return Entry</div>
        </div>
        <div class="purchase-workspace-actions">
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-default"><i class="fa fa-list"></i> List Purchase Returns</a>
        </div>
    </div>

    <div id="purchase_return_alert" class="alert" hidden></div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Unable to save the purchase return.</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('purchase.returns.store') }}" id="purchase_return_form" novalidate>
        @csrf
        <input type="hidden" name="purchase_id" id="return_purchase_id" value="{{ old('purchase_id') }}">
        <input type="hidden" name="contact_id" id="return_contact_id" value="{{ old('contact_id') }}">
        <input type="hidden" name="location_id" id="return_location_id" value="{{ old('location_id') }}">
        <input type="hidden" name="store_id" id="return_store_id" value="{{ old('store_id') }}">

        <div class="purchase-panel">
            <div class="purchase-panel-heading">
                <span><i class="fa fa-file-text-o"></i> Purchase Return Details</span>
                <span class="text-red">* Required fields</span>
            </div>
            <div class="purchase-panel-body">
                <div class="purchase-form-grid">
                    <div class="form-group">
                        <label>Purchase Return No. *</label>
                        <input type="text" name="ref_no" id="return_ref_no" class="form-control" value="{{ old('ref_no', $return_no) }}" readonly required>
                    </div>
                    <div class="form-group">
                        <label>Return Date & Time *</label>
                        <input type="datetime-local" name="transaction_date" id="return_transaction_date" class="form-control" value="{{ old('transaction_date', $transaction_date) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Supplier Filter</label>
                        <select id="return_supplier_filter" class="form-control select2" style="width:100%">
                            <option value="">All suppliers</option>
                            @foreach($suppliers as $supplier)
                                @php($supplierName = trim((string)($supplier->supplier_business_name ?: $supplier->name)))
                                <option value="{{ $supplier->id }}">{{ $supplierName }}{{ $supplier->contact_id ? ' - '.$supplier->contact_id : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Business Location Filter</label>
                        <select id="return_location_filter" class="form-control select2" style="width:100%">
                            <option value="">All locations</option>
                            @foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group span-2">
                        <label>Original Purchase *</label>
                        <div class="purchase-search-shell">
                            <div class="purchase-inline-control">
                                <input type="text" id="return_purchase_search" class="form-control" autocomplete="off" placeholder="Type purchase number, supplier reference or supplier name">
                                <button type="button" class="btn btn-primary" id="return_purchase_search_button"><i class="fa fa-search"></i> Search</button>
                            </div>
                            <div id="return_purchase_results" class="purchase-search-dropdown" hidden></div>
                        </div>
                        <small class="text-muted">Select the original received purchase. Only quantities still available for return will be shown.</small>
                    </div>
                    <div class="form-group">
                        <label>Purchase Return Account</label>
                        <select name="return_account_id" id="return_account_id" class="form-control select2" style="width:100%">
                            <option value="">Auto select Accounts Payable / Purchase Return account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}{{ !empty($account->group_name) ? ' - '.$account->group_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Additional Tax</label>
                        <input type="number" name="tax_amount" id="return_tax_amount" class="form-control text-right" min="0" step="0.000001" value="{{ old('tax_amount', 0) }}">
                    </div>
                    <div class="form-group full-width">
                        <div id="selected_purchase_note" class="purchase-info-note">No original purchase has been selected.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="purchase-panel">
            <div class="purchase-panel-heading">
                <span><i class="fa fa-cubes"></i> Return Products</span>
                <span id="return_line_counter">0 product lines</span>
            </div>
            <div class="purchase-panel-body">
                <div class="purchase-table-wrap">
                    <table class="table table-bordered purchase-data-table purchase-return-lines" id="purchase_return_lines_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Unit</th>
                                <th>Purchased Qty</th>
                                <th>Already Returned</th>
                                <th>Available to Return</th>
                                <th>Return Qty *</th>
                                <th>Unit Cost</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody id="purchase_return_lines_body">
                            <tr><td colspan="10" class="purchase-empty-state">Select an original purchase to load the returnable products.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="purchase-return-total-box">
                    <span>Purchase Return Total</span>
                    <strong id="purchase_return_total">0.00</strong>
                </div>
            </div>
        </div>

        <div class="purchase-panel">
            <div class="purchase-panel-heading"><span><i class="fa fa-sticky-note-o"></i> Notes</span></div>
            <div class="purchase-panel-body">
                <textarea name="additional_notes" id="return_additional_notes" class="form-control" rows="3" placeholder="Reason for return, supplier instructions or other notes">{{ old('additional_notes') }}</textarea>
                <p class="purchase-info-note" style="margin-top:12px;">Saving the return decreases location/store stock, updates returned quantities against the original purchase, creates the supplier return transaction, and posts the module-owned accounting entries.</p>
            </div>
        </div>

        <div class="purchase-form-footer">
            <a href="{{ route('purchase.returns.index') }}" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-primary" id="save_purchase_return"><i class="fa fa-save"></i> Save Purchase Return</button>
        </div>
    </form>
</section>
@endsection

@section('javascript')
<script>window.PurchaseReturnConfig = @json($purchaseReturnConfig);</script>
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-return-create.js')) !!}</script>
@endsection
