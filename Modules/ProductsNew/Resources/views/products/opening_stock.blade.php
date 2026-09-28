@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Add / Edit Opening Stock')
@section('productsnew_page_subtitle', 'Set the opening quantity for ' . $product->name . ' by variation, location and store.')
@section('productsnew_flash_mode', 'popup')

@section('productsnew_content')
<div class="pn-product-opening-toolbar no-print">
    <div class="pn-product-opening-identity">
        <span class="pn-product-opening-icon"><i class="fa fa-cubes" aria-hidden="true"></i></span>
        <div>
            <h3>{{ $product->name }}</h3>
            <p>SKU: {{ $product->sku ?: '—' }}</p>
        </div>
        <span class="pn-badge {{ $isInactive ? 'pn-badge-muted' : 'pn-badge-success' }}">
            {{ $isInactive ? 'Inactive' : 'Active' }}
        </span>
    </div>
    <div class="pn-product-opening-links">
        <a class="pn-btn pn-btn-light" href="{{ route('products-new.products.index') }}">
            <i class="fa fa-arrow-left" aria-hidden="true"></i> List Products
        </a>
        <a class="pn-btn pn-btn-primary" href="{{ route('products-new.stock-history.index', ['product_id' => $product->id]) }}">
            <i class="fa fa-history" aria-hidden="true"></i> Product History
        </a>
    </div>
</div>

@if($isInactive)
    <div class="alert alert-warning productsnew-alert no-print">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        <span>This product is inactive. Existing opening stock can be reviewed, but editing is locked until the product is activated.</span>
    </div>
@endif

<div class="pn-grid-2 pn-product-opening-grid">
    <section class="pn-card pn-product-opening-form-card" id="pn-product-opening-form-card">
        <div class="pn-card-header">
            <div>
                <strong><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Opening Stock Entry</strong>
                <span class="pn-muted">Enter the final required opening quantity. The system posts only the difference.</span>
            </div>
        </div>
        <div class="pn-card-body">
            <form method="post" action="{{ route('products-new.products.opening-stock.update', $product->id) }}" id="pn-product-opening-form">
                @csrf
                <fieldset class="pn-product-opening-fieldset" @if($isInactive) disabled @endif>
                <div class="pn-form-grid pn-product-opening-form-grid">
                    <div class="pn-col-span">
                        <label for="pn_opening_variation">Variation <span class="text-danger">*</span></label>
                        <select class="form-control" id="pn_opening_variation" name="variation_id" required>
                            <option value="">Select Variation</option>
                            @foreach($variations as $variation)
                                @php
                                    $variationLabel = trim((string) ($variation->name ?? '')) ?: 'Default';
                                    $variationSku = trim((string) ($variation->sub_sku ?? ''));
                                @endphp
                                <option value="{{ $variation->id }}" @selected((string) old('variation_id') === (string) $variation->id)>
                                    {{ $variationLabel }}{{ $variationSku !== '' ? ' — ' . $variationSku : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="pn_opening_location">Location <span class="text-danger">*</span></label>
                        <select class="form-control" id="pn_opening_location" name="location_id" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="pn_opening_store">Store</label>
                        <select class="form-control" id="pn_opening_store" name="store_id">
                            <option value="">Location Total / No Store</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}"
                                        data-location-id="{{ $store->location_id }}"
                                        @selected((string) old('store_id') === (string) $store->id)>
                                    {{ $store->name ?? ('Store #' . $store->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="pn_opening_quantity">Opening Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control text-right" id="pn_opening_quantity" name="quantity"
                               value="{{ old('quantity') }}" min="0" step="0.001" required placeholder="0.000">
                    </div>

                    <div>
                        <label for="pn_opening_unit_cost">Unit Cost</label>
                        <input type="number" class="form-control text-right" id="pn_opening_unit_cost" name="unit_cost"
                               value="{{ old('unit_cost') }}" min="0" step="0.0001" placeholder="0.0000">
                    </div>

                    <div>
                        <label for="pn_opening_date">Opening Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="pn_opening_date" name="opening_date"
                               value="{{ old('opening_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="pn-col-span">
                        <label for="pn_opening_notes">Notes</label>
                        <textarea class="form-control" id="pn_opening_notes" name="notes" rows="3"
                                  placeholder="Reason or reference for this opening stock entry">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="pn-product-opening-form-actions">
                    <button type="button" class="pn-btn pn-btn-light" id="pn-opening-reset">
                        <i class="fa fa-refresh" aria-hidden="true"></i> Clear
                    </button>
                    <button type="submit" class="pn-btn pn-btn-success" id="pn-opening-save">
                        <i class="fa fa-check" aria-hidden="true"></i> Save Opening Stock
                    </button>
                </div>
                </fieldset>
            </form>
        </div>
    </section>

    <aside class="pn-card pn-product-opening-help-card">
        <div class="pn-card-header">
            <div>
                <strong><i class="fa fa-shield" aria-hidden="true"></i> Safe Editing Rule</strong>
                <span class="pn-muted">Existing sales and purchases are never overwritten.</span>
            </div>
        </div>
        <div class="pn-card-body">
            <div class="pn-opening-rule-list">
                <div><span>1</span><p>Select the variation and location.</p></div>
                <div><span>2</span><p>Enter the final opening quantity required.</p></div>
                <div><span>3</span><p>The system calculates and posts only the difference.</p></div>
                <div><span>4</span><p>A reduction is blocked if it would create negative current stock.</p></div>
            </div>
        </div>
    </aside>
</div>

<section class="pn-card pn-mt pn-product-opening-table-card">
    <div class="pn-card-header">
        <div>
            <strong><i class="fa fa-list-alt" aria-hidden="true"></i> Existing Opening Stock</strong>
            <span class="pn-muted">Use Edit to load a row into the form above.</span>
        </div>
        <span class="pn-badge">{{ number_format($rows->count()) }} row(s)</span>
    </div>
    <div class="pn-card-body pn-table-wrap">
        <table class="table pn-table pn-product-opening-table">
            <thead>
                <tr>
                    <th>Variation</th>
                    <th>Location</th>
                    <th>Store</th>
                    <th class="text-right">Opening Qty</th>
                    <th class="text-right">Current Stock</th>
                    <th class="text-right">Unit Cost</th>
                    <th>Date</th>
                    <th>Source</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row->variation_name ?: 'Default' }}</strong>
                        @if($row->variation_sku)<small>{{ $row->variation_sku }}</small>@endif
                    </td>
                    <td>{{ $row->location_name ?: 'Unassigned' }}</td>
                    <td>{{ $row->store_name ?: '—' }}</td>
                    <td class="text-right"><strong>{{ number_format((float) $row->opening_qty, 3) }}</strong></td>
                    <td class="text-right">{{ number_format((float) $row->current_stock, 3) }}</td>
                    <td class="text-right">{{ number_format((float) $row->unit_cost, 4) }}</td>
                    <td>{{ $row->opening_date ? date('d M Y', strtotime($row->opening_date)) : '—' }}</td>
                    <td><span class="pn-source-badge">{{ $row->sources }}</span></td>
                    <td class="text-center">
                        <button type="button" class="pn-btn pn-btn-primary pn-btn-sm pn-opening-edit-row"
                                data-variation-id="{{ $row->variation_id }}"
                                data-location-id="{{ $row->location_id }}"
                                data-store-id="{{ $row->store_id }}"
                                data-quantity="{{ number_format((float) $row->opening_qty, 3, '.', '') }}"
                                data-unit-cost="{{ number_format((float) $row->unit_cost, 4, '.', '') }}"
                                data-opening-date="{{ $row->opening_date ? date('Y-m-d', strtotime($row->opening_date)) : date('Y-m-d') }}">
                            <i class="fa fa-pencil" aria-hidden="true"></i> Edit
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center pn-empty-state">
                        No opening stock has been recorded for this product. Use the form above to add it.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('javascript')
<script>
(function ($) {
    'use strict';

    function filterStores() {
        var locationId = String($('#pn_opening_location').val() || '');
        var $store = $('#pn_opening_store');
        var selected = String($store.val() || '');

        $store.find('option').each(function () {
            var $option = $(this);
            var optionLocation = String($option.data('location-id') || '');
            var visible = $option.val() === '' || locationId === '' || optionLocation === locationId;
            $option.prop('disabled', !visible).toggle(visible);
        });

        if (selected && $store.find('option[value="' + selected + '"]:enabled').length === 0) {
            $store.val('');
        }
        $store.trigger('change.select2');
    }

    $(function () {
        $('#pn_opening_location').on('change', filterStores);
        filterStores();

        $('.pn-opening-edit-row').on('click', function () {
            var $button = $(this);
            $('#pn_opening_variation').val($button.data('variation-id')).trigger('change');
            $('#pn_opening_location').val($button.data('location-id')).trigger('change');
            filterStores();
            $('#pn_opening_store').val(String($button.data('store-id') || '')).trigger('change');
            $('#pn_opening_quantity').val($button.data('quantity'));
            $('#pn_opening_unit_cost').val($button.data('unit-cost'));
            $('#pn_opening_date').val($button.data('opening-date'));
            $('html, body').animate({ scrollTop: $('#pn-product-opening-form-card').offset().top - 90 }, 250);
            $('#pn_opening_quantity').trigger('focus').select();
        });

        $('#pn-opening-reset').on('click', function () {
            document.getElementById('pn-product-opening-form').reset();
            filterStores();
        });

        function popupMessage(type, message) {
            if (!message) return;
            if (window.toastr && typeof window.toastr[type] === 'function') { window.toastr[type](message); return; }
            if (window.Swal && typeof window.Swal.fire === 'function') { window.Swal.fire({icon:type === 'success' ? 'success':'error',title:type === 'success' ? 'Success':'Please check',text:message,confirmButtonText:'OK'}); return; }
            if (typeof window.swal === 'function') { window.swal(type === 'success' ? 'Success':'Please check',message,type === 'success' ? 'success':'error'); return; }
            window.alert(message);
        }
        function popupConfirm(message, finalStep) {
            return new Promise(function (resolve) {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({icon:'warning',title:finalStep ? 'Final Confirmation':'Confirm Opening Stock',text:message,showCancelButton:true,confirmButtonText:finalStep ? 'Yes, Save':'Continue',cancelButtonText:'Cancel',reverseButtons:true}).then(function(result){resolve(!!(result && result.isConfirmed));}); return;
                }
                if (window.bootbox && typeof window.bootbox.confirm === 'function') { window.bootbox.confirm({title:finalStep ? 'Final Confirmation':'Confirm Opening Stock',message:message,buttons:{confirm:{label:finalStep ? 'Yes, Save':'Continue',className:'btn-primary'},cancel:{label:'Cancel',className:'btn-default'}},callback:function(confirmed){resolve(!!confirmed);}}); return; }
                resolve(window.confirm(message));
            });
        }
        var serverStatus = @json(session('status'));
        var serverErrors = @json($errors->all());
        if (serverStatus) popupMessage('success',serverStatus);
        if (serverErrors && serverErrors.length) popupMessage('error',serverErrors.join('\n'));
        $('#pn-product-opening-form').on('submit', function (event) {
            var form=this; if(form.dataset.pnOpeningSubmitting === '1') return;
            event.preventDefault(); var productName=@json($product->name); var qty=$('#pn_opening_quantity').val();
            popupConfirm('Confirm the opening stock for "'+productName+'" as '+qty+'?',false).then(function(ok){if(!ok)return false;return popupConfirm('Save this opening stock change and update the stock balance?',true);}).then(function(ok){if(!ok)return;form.dataset.pnOpeningSubmitting='1';HTMLFormElement.prototype.submit.call(form);});
        });
    });
})(jQuery);
</script>
@endpush
