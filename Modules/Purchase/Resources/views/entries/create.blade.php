@extends('layouts.app')
@section('title', !empty($is_edit) ? 'Edit Purchase Entry' : __('purchase::lang.add_purchase'))

@section('content')
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-entry.css')) !!}</style>
<style>{!! file_get_contents(module_path('Purchase', 'Resources/assets/css/purchase-workspace.css')) !!}</style>

@php
    $isEdit = (bool) ($is_edit ?? false);
    $entry = $purchase ?? [];
    $field = fn (string $name, mixed $default = null) => old($name, data_get($entry, $name, $default));
    $selectedSupplier = $supplier ?? null;
    $selectedSupplierName = trim((string) data_get($selectedSupplier, 'name', ''));
    $selectedSupplierDetails = array_values(array_filter([
        $selectedSupplierName,
        data_get($selectedSupplier, 'mobile') ? 'Mobile: ' . data_get($selectedSupplier, 'mobile') : null,
    ]));

    // Keep the product grid compact while reserving enough room for every enabled optional column.
    // IS8040: SKU now sits below Product / Variation and the Tax column is 40% narrower.
    $purchaseTableMinWidth = 1250
        + ($enable_free_qty ? 60 : 0)
        + ($enable_editing_product_from_purchase ? 210 : 0)
        + ($enable_lot_number ? 110 : 0)
        + ($enable_product_expiry ? 250 : 0);

    $purchaseEntryConfig = [
        'routes' => $routes,
        'taxes' => $taxes,
        'accounts' => $accounts,
        'units' => $units,
        'paymentMethods' => $payment_methods,
        'paymentMethodsByLocation' => $payment_methods_by_location ?? [],
        'paymentReferencePreview' => (string) ($payment_reference_preview ?? ''),
        'paymentMethodAccounts' => $payment_method_accounts ?? [],
        'initialSuppliers' => $initial_suppliers ?? [],
        'initialSuppliersMore' => (bool) ($initial_suppliers_more ?? false),
        'supplierPageSize' => (int) ($supplier_page_size ?? 50),
        'currencyPrecision' => $currency_precision,
        // ProductsNew List Product uses four decimals for purchase/selling price display.
        'productPricePrecision' => 4,
        // CH1 IS2115: display product prices at 4 decimals, but keep the full
        // calculation precision in hidden line values so the saved transaction
        // total is identical to the total calculated on Add/Edit Purchase.
        'preserveCalculationPrecision' => true,
        'quantityPrecision' => $quantity_precision,
        'currencySymbol' => $currency_symbol,
        'enableLotNumber' => $enable_lot_number,
        'enableProductExpiry' => $enable_product_expiry,
        'enableFreeQty' => $enable_free_qty,
        'enableSellingPrice' => $enable_editing_product_from_purchase,
        'initialLines' => $initial_lines ?? [],
        'initialPayments' => $initial_payments ?? [],
        'initialTanks' => $initial_tanks ?? [],
        'referenceExcludeId' => $reference_exclude_id ?? null,
        'initialPurchaseOrderId' => (int) old('linked_purchase_order_id', 0),
        'initialPurchaseOrderNo' => (string) $field('order_no', ''),
        'isEdit' => $isEdit,
        'csrf' => csrf_token(),
    ];
@endphp

<section class="content purchase-workspace purchase-entry-page">
    <div class="purchase-workspace-header">
        <div>
            <h1><i class="fa fa-shopping-cart"></i> {{ $isEdit ? 'Edit Purchase Entry' : 'Add Purchase Entry' }}</h1>
            <div class="purchase-breadcrumb">Purchase (New) / {{ $isEdit ? 'Edit Purchase Entry' : 'Add Purchase Entry' }}</div>
        </div>
        <div class="purchase-workspace-actions">
            <a href="{{ route('purchase.entries.index') }}" class="btn btn-default">
                <i class="fa fa-list"></i> List Purchase Entries
            </a>
        </div>
    </div>
    <div id="purchase_entry_alert" class="purchase-entry-alert" hidden></div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Unable to save the purchase.</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $routes['store'] }}" method="post" enctype="multipart/form-data" id="purchase_entry_form" novalidate>
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="purchase-card">
            <div class="purchase-card-title">
                <span><i class="fa fa-file-text-o"></i> Purchase Details</span>
                <span class="purchase-required-note">* Required fields</span>
            </div>
            <div class="purchase-card-body purchase-grid purchase-grid-4">
                <div class="purchase-field">
                    <label for="invoice_no">Purchase No. *</label>
                    <input type="text" class="form-control" name="invoice_no" id="invoice_no" value="{{ $field('invoice_no', $purchase_no) }}" readonly required>
                </div>

                <div class="purchase-field">
                    <label for="location_id">Business Location *</label>
                    <select class="form-control" name="location_id" id="location_id" required>
                        <option value="">Please select</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) $field('location_id', $locations->first()->id ?? '') === (string) $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="purchase-field">
                    <label for="store_id">Store *</label>
                    <select class="form-control" name="store_id" id="store_id" required>
                        <option value="">Please select</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) $field('store_id', $stores->first()->id ?? '') === (string) $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- SW Shift No. A cash purchase takes money out of the
                     till, so it reduces Balance In Hand. Bound to the location
                     field above rather than offering a second one. --}}
                <div class="purchase-field">
                    @includeIf('sw::partials.shift_field', ['bindLocation' => '#location_id'])
                </div>

                <div class="purchase-field">
                    <label for="status">Purchase Status *</label>
                    <select class="form-control" name="status" id="status" required>
                        @foreach ($order_statuses as $value => $label)
                            <option value="{{ $value }}" @selected((string) $field('status', 'received') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="purchase-field purchase-supplier-field">
                    <label for="supplier_search">Supplier *</label>
                    <div class="purchase-input-action">
                        <div class="purchase-autocomplete-wrap">
                            <input type="text" class="form-control" id="supplier_search" value="{{ $selectedSupplierName }}" autocomplete="off" placeholder="Type supplier name, code or mobile" required>
                            <input type="hidden" name="contact_id" id="contact_id" value="{{ $field('contact_id') }}">
                            <div id="supplier_results" class="purchase-search-results" hidden></div>
                        </div>
                        <button type="button" class="btn btn-primary" id="open_quick_supplier" title="Add supplier"><i class="fa fa-plus"></i></button>
                    </div>
                    <small id="selected_supplier_text" class="purchase-help">{{ $selectedSupplierDetails ? implode(' · ', $selectedSupplierDetails) : 'Select a supplier from the filtered results.' }}</small>
                </div>

                <div class="purchase-field">
                    <label for="ref_no">Supplier Invoice / Reference No.{{ !$isEdit ? ' *' : '' }}</label>
                    <input type="text" class="form-control" name="ref_no" id="ref_no" value="{{ $field('ref_no') }}" maxlength="255" @if(!$isEdit) required @endif>
                    <small id="reference_status" class="purchase-help"></small>
                </div>

                <div class="purchase-field">
                    <label for="transaction_date">Received Date & Time *</label>
                    <input type="datetime-local" class="form-control" name="transaction_date" id="transaction_date" value="{{ $field('transaction_date', $transaction_date) }}" required>
                </div>

                <div class="purchase-field">
                    <label for="invoice_date">Invoice Date *</label>
                    <input type="date" class="form-control" name="invoice_date" id="invoice_date" value="{{ $field('invoice_date', $invoice_date) }}" required>
                </div>

                <div class="purchase-field">
                    @if($isEdit)
                        <label for="order_no">Purchase Order No.</label>
                        <input type="text" class="form-control" name="order_no" id="order_no" value="{{ $field('order_no') }}" maxlength="100">
                    @else
                        <label for="linked_purchase_order_id">Purchase Order No.</label>
                        <select class="form-control" name="linked_purchase_order_id" id="linked_purchase_order_id" disabled>
                            <option value="">Select a supplier first</option>
                        </select>
                        <input type="hidden" name="order_no" id="order_no" value="{{ $field('order_no') }}">
                        <small id="purchase_order_status" class="purchase-help">Pending purchase orders will appear after selecting the supplier.</small>
                    @endif
                </div>

                <div class="purchase-field purchase-pay-term">
                    <label>Pay Term</label>
                    <div class="purchase-inline-fields">
                        <input type="number" class="form-control" name="pay_term_number" id="pay_term_number" value="{{ $field('pay_term_number') }}" min="0" placeholder="0">
                        <select class="form-control" name="pay_term_type" id="pay_term_type">
                            <option value="days" @selected($field('pay_term_type', 'days') === 'days')>Days</option>
                            <option value="months" @selected($field('pay_term_type', 'days') === 'months')>Months</option>
                        </select>
                    </div>
                </div>

                <div class="purchase-field">
                    <label for="exchange_rate">Exchange Rate</label>
                    <input type="number" step="0.000001" min="0.000001" class="form-control purchase-number" name="exchange_rate" id="exchange_rate" value="{{ $field('exchange_rate', 1) }}">
                </div>

                <div class="purchase-field">
                    <label for="document">Purchase Document</label>
                    <input type="file" class="form-control" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    @if($isEdit && $field('document'))
                        <small class="purchase-help">Current file: {{ basename((string) $field('document')) }}. Select a new file only to replace it.</small>
                    @endif
                </div>

                <div class="purchase-field purchase-check-field">
                    <label>Tax / Free Product Options</label>
                    <label class="purchase-check"><input type="checkbox" name="is_vat" value="1" @checked((bool) $field('is_vat', false))> VAT purchase</label>
                    <label class="purchase-check"><input type="checkbox" name="apply_free_product_total" id="apply_free_product_total" value="1" @checked((bool) $field('apply_free_product_total', false))> Include free-product value in total</label>
                </div>
            </div>
        </div>

        <div class="purchase-card">
            <div class="purchase-card-title purchase-product-title">
                <span><i class="fa fa-cubes"></i> Products</span>
                <span id="purchase_line_count">0 product rows</span>
            </div>
            <div class="purchase-card-body">
                <div class="purchase-product-search-row">
                    <div class="purchase-product-search-wrap">
                        <i class="fa fa-search"></i>
                        <input type="text" id="purchase_product_search" class="form-control" autocomplete="off" placeholder="Type product name, barcode, SKU or variation and select from the results">
                        <div id="product_results" class="purchase-search-results purchase-product-results" hidden></div>
                    </div>
                    <button type="button" class="btn btn-primary" id="open_quick_product"><i class="fa fa-plus"></i> Add Product</button>
                </div>

                <div class="purchase-table-scroll">
                    <table class="table table-bordered purchase-lines-table" id="purchase_entry_lines_table" style="--purchase-lines-min-width: {{ $purchaseTableMinWidth }}px;">
                        <colgroup>
                            <col class="purchase-col-no">
                            <col class="purchase-col-product">
                            <col class="purchase-col-stock">
                            <col class="purchase-col-qty">
                            <col class="purchase-col-unit">
                            @if ($enable_free_qty)<col class="purchase-col-free-qty">@endif
                            <col class="purchase-col-unit-cost">
                            <col class="purchase-col-discount-type">
                            <col class="purchase-col-discount">
                            <col class="purchase-col-tax">
                            <col class="purchase-col-cost-inc-tax">
                            <col class="purchase-col-line-total">
                            @if ($enable_editing_product_from_purchase)
                                <col class="purchase-col-profit">
                                <col class="purchase-col-selling-price">
                            @endif
                            @if ($enable_lot_number)<col class="purchase-col-lot">@endif
                            @if ($enable_product_expiry)
                                <col class="purchase-col-mfg-date">
                                <col class="purchase-col-expiry-date">
                            @endif
                            <col class="purchase-col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="purchase-col-no"><span class="purchase-th-label">#</span></th>
                                <th class="purchase-col-product"><span class="purchase-th-label">Product /<br>Variation</span></th>
                                <th class="purchase-col-stock"><span class="purchase-th-label">Current<br>Stock</span></th>
                                <th class="purchase-col-qty"><span class="purchase-th-label">Qty <span class="purchase-required-mark">*</span></span></th>
                                <th class="purchase-col-unit"><span class="purchase-th-label">Unit</span></th>
                                @if ($enable_free_qty)<th class="purchase-col-free-qty"><span class="purchase-th-label">Free<br>Qty</span></th>@endif
                                <th class="purchase-col-unit-cost"><span class="purchase-th-label">Unit<br>Cost</span></th>
                                <th class="purchase-col-discount-type"><span class="purchase-th-label">Discount<br>Type</span></th>
                                <th class="purchase-col-discount"><span class="purchase-th-label">Discount</span></th>
                                <th class="purchase-col-tax"><span class="purchase-th-label">Tax</span></th>
                                <th class="purchase-col-cost-inc-tax"><span class="purchase-th-label">Cost Inc.<br>Tax</span></th>
                                <th class="purchase-col-line-total"><span class="purchase-th-label">Line<br>Total</span></th>
                                @if ($enable_editing_product_from_purchase)
                                    <th class="purchase-col-profit"><span class="purchase-th-label">Profit<br>%</span></th>
                                    <th class="purchase-col-selling-price"><span class="purchase-th-label">Selling<br>Price</span></th>
                                @endif
                                @if ($enable_lot_number)<th class="purchase-col-lot"><span class="purchase-th-label">Lot No.</span></th>@endif
                                @if ($enable_product_expiry)
                                    <th class="purchase-col-mfg-date"><span class="purchase-th-label">Mfg<br>Date</span></th>
                                    <th class="purchase-col-expiry-date"><span class="purchase-th-label">Expiry<br>Date</span></th>
                                @endif
                                <th class="purchase-col-action"><span class="purchase-th-label">Action</span></th>
                            </tr>
                        </thead>
                        <tbody id="purchase_lines_body">
                            <tr class="purchase-empty-row">
                                <td colspan="22">Search and select a product to add it to this purchase.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="purchase-card purchase-unload-card" id="purchase_unload_card" hidden aria-hidden="true">
            <div class="purchase-card-title purchase-unload-title">
                <span><i class="fa fa-truck"></i> Unload Tanks</span>
                <span id="purchase_unload_count">0 fuel products</span>
            </div>
            <div class="purchase-card-body" id="purchase_unload_rows"></div>
        </div>

        <div class="purchase-grid purchase-grid-2 purchase-bottom-grid">
            <div class="purchase-card">
                <div class="purchase-card-title"><span><i class="fa fa-calculator"></i> Totals & Notes</span></div>
                <div class="purchase-card-body">
                    <div class="purchase-total-form-grid">
                        <div class="purchase-field">
                            <label for="discount_type">Purchase Discount</label>
                            <div class="purchase-inline-fields">
                                <select name="discount_type" id="discount_type" class="form-control">
                                    <option value="fixed" @selected($field('discount_type', 'fixed') === 'fixed')>Fixed Amount</option>
                                    <option value="percentage" @selected($field('discount_type', 'fixed') === 'percentage')>Percentage</option>
                                </select>
                                <input type="number" step="0.000001" min="0" name="discount_amount" id="discount_amount" value="{{ $field('discount_amount', 0) }}" class="form-control purchase-number">
                            </div>
                        </div>

                        <div class="purchase-field">
                            <label for="tax_id">Additional Purchase Tax</label>
                            <select name="tax_id" id="tax_id" class="form-control">
                                <option value="">None</option>
                                @foreach ($taxes as $tax)
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->amount }}" @selected((string) $field('tax_id') === (string) $tax->id)>{{ $tax->name }} ({{ $tax->amount }}%)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="purchase-field">
                            <label for="shipping_details">Shipping Details</label>
                            <input type="text" class="form-control" name="shipping_details" id="shipping_details" value="{{ $field('shipping_details') }}" maxlength="1000">
                        </div>

                        <div class="purchase-field">
                            <label for="shipping_charges">Additional Shipping Charges</label>
                            <input type="number" step="0.000001" min="0" class="form-control purchase-number" name="shipping_charges" id="shipping_charges" value="{{ $field('shipping_charges', 0) }}">
                        </div>

                        <div class="purchase-field">
                            <label for="price_adjustment">Price Adjustment (+ / -)</label>
                            <input type="number" step="0.000001" class="form-control purchase-number" name="price_adjustment" id="price_adjustment" value="{{ $field('price_adjustment', 0) }}">
                        </div>

                        <div class="purchase-field purchase-field-full">
                            <label for="additional_notes">Additional Notes</label>
                            <textarea class="form-control" name="additional_notes" id="additional_notes" rows="3">{{ $field('additional_notes') }}</textarea>
                        </div>
                    </div>

                    <input type="hidden" name="total_before_tax" id="total_before_tax" value="0">
                    <input type="hidden" name="tax_amount" id="tax_amount" value="0">
                    <input type="hidden" name="final_total" id="final_total" value="0">
                </div>
            </div>

            <div class="purchase-card purchase-summary-card">
                <div class="purchase-card-title"><span><i class="fa fa-money"></i> Purchase Summary</span></div>
                <div class="purchase-card-body">
                    <div class="purchase-summary-row"><span>Subtotal before tax</span><strong id="summary_subtotal">0.00</strong></div>
                    <div class="purchase-summary-row"><span>Product tax</span><strong id="summary_line_tax">0.00</strong></div>
                    <div class="purchase-summary-row"><span>Purchase discount</span><strong id="summary_discount">0.00</strong></div>
                    <div class="purchase-summary-row"><span>Additional tax</span><strong id="summary_order_tax">0.00</strong></div>
                    <div class="purchase-summary-row"><span>Shipping</span><strong id="summary_shipping">0.00</strong></div>
                    <div class="purchase-summary-row"><span>Price adjustment</span><strong id="summary_adjustment">0.00</strong></div>
                    <div class="purchase-summary-row"><span>Free-product value</span><strong id="summary_free_total">0.00</strong></div>
                    <div class="purchase-summary-row purchase-grand-total"><span>Purchase Total</span><strong id="summary_final_total">0.00</strong></div>
                </div>
            </div>
        </div>

        <div class="purchase-card">
            <div class="purchase-card-title purchase-payment-title">
                <span><i class="fa fa-credit-card"></i> Payments</span>
                <button type="button" class="btn btn-primary btn-sm" id="add_payment_row"><i class="fa fa-plus"></i> Add Payment Row</button>
            </div>
            <div class="purchase-card-body">
                <div id="payment_status_note" class="purchase-help purchase-payment-note">Actual payments are enabled for Received purchases. System payment references are generated automatically using the active prefix and starting number in Supplier Settings. Pending/Ordered purchases remain fully due.</div>
                <div class="purchase-table-scroll">
                    <table class="table table-bordered purchase-payment-table">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th>System Reference</th>
                                <th>Amount</th>
                                <th>Payment Account</th>
                                <th>Paid On</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="purchase_payment_rows"></tbody>
                    </table>
                </div>
                <div class="purchase-payment-summary">
                    <span class="purchase-payment-summary-item purchase-payment-entered">Amount Entered: <strong id="payment_entered_total">0.00</strong></span>
                    <span class="purchase-payment-summary-item purchase-payment-actual">Actual Paid: <strong id="payment_paid_total">0.00</strong></span>
                    <span class="purchase-payment-summary-item purchase-payment-due">Payment Due: <strong id="payment_due">0.00</strong></span>
                </div>
            </div>
        </div>

        <input type="hidden" name="save_action" id="save_action" value="list">
        <div class="purchase-save-bar" id="purchase_save_bar" @if(!$isEdit) hidden aria-hidden="true" @else aria-hidden="false" @endif>
            <a href="{{ route('purchase.entries.index') }}" class="btn btn-default">Cancel</a>
            <div class="purchase-save-actions" id="purchase_save_actions" aria-hidden="false">
                @if(!$isEdit)
                    <button type="submit" class="btn btn-info purchase-save-button" data-save-action="new"><i class="fa fa-plus-circle"></i> Save & Add Another</button>
                    <button type="submit" class="btn btn-success purchase-save-button" data-save-action="view"><i class="fa fa-eye"></i> Save & View</button>
                    <button type="submit" class="btn btn-primary purchase-save-button" data-save-action="list" id="save_purchase_entry"><i class="fa fa-save"></i> Save Purchase Entry</button>
                @else
                    <button type="submit" class="btn btn-success purchase-save-button" data-save-action="view"><i class="fa fa-eye"></i> Update & View</button>
                    <button type="submit" class="btn btn-primary purchase-save-button" data-save-action="list" id="save_purchase_entry"><i class="fa fa-save"></i> Update Purchase Entry</button>
                @endif
            </div>
        </div>
    </form>
</section>

<div class="purchase-modal" id="quick_supplier_modal" hidden>
    <div class="purchase-modal-backdrop" data-close-supplier-modal></div>
    <div class="purchase-modal-dialog">
        <div class="purchase-modal-header">
            <h4>Add Supplier</h4>
            <button type="button" class="purchase-modal-close" data-close-supplier-modal>&times;</button>
        </div>
        <form id="quick_supplier_form">
            <div class="purchase-modal-body purchase-grid purchase-grid-2">
                <div class="purchase-field">
                    <label>Contact Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="purchase-field">
                    <label>Supplier Business Name</label>
                    <input type="text" name="supplier_business_name" class="form-control">
                </div>
                <div class="purchase-field">
                    <label>Mobile</label>
                    <input type="text" name="mobile" class="form-control">
                </div>
                <div class="purchase-field">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="purchase-field">
                    <label>Tax Number</label>
                    <input type="text" name="tax_number" class="form-control">
                </div>
                <div class="purchase-field">
                    <label>Pay Term</label>
                    <div class="purchase-inline-fields">
                        <input type="number" name="pay_term_number" class="form-control" min="0">
                        <select name="pay_term_type" class="form-control">
                            <option value="days">Days</option>
                            <option value="months">Months</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="purchase-modal-footer">
                <button type="button" class="btn btn-default" data-close-supplier-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<div class="purchase-modal" id="quick_product_modal" hidden>
    <div class="purchase-modal-backdrop" data-close-product-modal></div>
    <div class="purchase-modal-dialog purchase-modal-dialog-wide">
        <div class="purchase-modal-header">
            <h4>Add Product</h4>
            <button type="button" class="purchase-modal-close" data-close-product-modal>&times;</button>
        </div>
        <form id="quick_product_form">
            <div class="purchase-modal-body purchase-grid purchase-grid-3">
                <input type="hidden" name="location_id" id="quick_product_location_id">
                <input type="hidden" name="store_id" id="quick_product_store_id">
                <div class="purchase-field">
                    <label>Product Name *</label>
                    <input type="text" name="name" class="form-control" required maxlength="191">
                </div>
                <div class="purchase-field">
                    <label>SKU / Barcode</label>
                    <input type="text" name="sku" class="form-control" maxlength="191">
                </div>
                <div class="purchase-field">
                    <label>Unit *</label>
                    <select name="unit_id" class="form-control" required>
                        <option value="">Please select</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->actual_name }} ({{ $unit->short_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="purchase-field">
                    <label>Purchase Tax</label>
                    <select name="tax_id" class="form-control">
                        <option value="">None</option>
                        @foreach ($taxes as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->amount }}%)</option>
                        @endforeach
                    </select>
                </div>
                <div class="purchase-field">
                    <label>Tax Type</label>
                    <select name="tax_type" class="form-control">
                        <option value="exclusive">Exclusive</option>
                        <option value="inclusive">Inclusive</option>
                    </select>
                </div>
                <div class="purchase-field purchase-check-field">
                    <label>Stock</label>
                    <label class="purchase-check"><input type="checkbox" name="enable_stock" value="1" checked> Enable stock tracking</label>
                </div>
                <div class="purchase-field">
                    <label>Purchase Price *</label>
                    <input type="number" step="0.000001" min="0" name="purchase_price" class="form-control" value="0" required>
                </div>
                <div class="purchase-field">
                    <label>Selling Price (Incl. Tax)</label>
                    <input type="number" step="0.000001" min="0" name="selling_price" class="form-control" value="0">
                </div>
            </div>
            <div class="purchase-modal-footer">
                <button type="button" class="btn btn-default" data-close-product-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Product</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
window.PurchaseEntryConfig = @json($purchaseEntryConfig);
</script>
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-entry-create.js')) !!}</script>
@endsection
