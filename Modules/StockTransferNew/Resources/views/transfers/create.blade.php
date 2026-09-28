@extends('stocktransfernew::layouts.app')

@section('stocktransfernew_content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-create.css') }}?v=20260712-4">

@include('stocktransfernew::partials.header', [
    'title' => 'Create Stock Transfer',
    'subtitle' => 'Create a controlled transfer between locations and stores'
])

<div class="stn-create-shell">
    <form method="post"
          action="{{ route('stock-transfer-new.transfers.store') }}"
          id="stn-transfer-form">
        @csrf

        <section class="stn-form-card">
            <div class="stn-form-card-heading">
                <div class="stn-form-heading-icon">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div>
                    <h2>Transfer Details</h2>
                    <p>The page opens immediately; lookups load in the background.</p>
                </div>
            </div>

            <div class="stn-form-card-body">
                <div class="stn-form-grid stn-form-grid-six">
                    <div class="stn-field">
                        <label for="stn-transfer-no">Transfer No</label>
                        <div class="stn-input-icon">
                            <i class="fa fa-hashtag"></i>
                            <input id="stn-transfer-no-display"
                                   value="Auto-generated on save"
                                   class="form-control"
                                   readonly>
                            <input type="hidden"
                                   id="stn-transfer-no"
                                   name="transfer_no"
                                   value="">
                        </div>
                    </div>

                    <div class="stn-field">
                        <label for="stn-transfer-date">Date</label>
                        <div class="stn-input-icon">
                            <i class="fa fa-calendar"></i>
                            <input id="stn-transfer-date"
                                   type="date"
                                   name="transfer_date"
                                   value="{{ old('transfer_date', date('Y-m-d')) }}"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                    <div class="stn-field">
                        <label for="stn-from-location">From Location</label>
                        <select name="from_location_id"
                                id="stn-from-location"
                                class="form-control"
                                required>
                            <option value="">Loading locations...</option>
                        </select>
                    </div>

                    <div class="stn-field">
                        <label for="stn-to-location">To Location</label>
                        <select name="to_location_id"
                                id="stn-to-location"
                                class="form-control"
                                required>
                            <option value="">Loading locations...</option>
                        </select>
                    </div>

                    <div class="stn-field">
                        <label for="stn-from-store">From Store</label>
                        <select name="from_store_id"
                                id="stn-from-store"
                                class="form-control"
                                required
                                disabled>
                            <option value="">Select location first</option>
                        </select>
                    </div>

                    <div class="stn-field">
                        <label for="stn-to-store">To Store</label>
                        <select name="to_store_id"
                                id="stn-to-store"
                                class="form-control"
                                required
                                disabled>
                            <option value="">Select location first</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="stn-form-card stn-items-card">
            <div class="stn-form-card-heading stn-items-heading">
                <div class="stn-heading-copy">
                    <div class="stn-form-heading-icon stn-form-heading-icon-green">
                        <i class="fa fa-cubes"></i>
                    </div>
                    <div>
                        <h2>Transfer Items</h2>
                        <p>Products are fetched only after typing at least two characters.</p>
                    </div>
                </div>

                <button type="button"
                        class="stn-btn stn-btn-secondary"
                        id="stn-add-line">
                    <i class="fa fa-plus"></i>
                    Add Row
                </button>
            </div>

            <div class="stn-table-wrap">
                <table class="stn-table stn-entry-table" id="stn-lines">
                    <thead>
                        <tr>
                            <th class="stn-col-no">#</th>
                            <th>Product</th>
                            <th class="stn-col-qty">Quantity</th>
                            <th class="stn-col-cost">Unit Cost</th>
                            <th>Remarks</th>
                            <th class="stn-col-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="stn-line-row">
                            <td class="stn-row-number">1</td>
                            <td>
                                <select name="lines[0][product_id]"
                                        class="form-control stn-product-select"
                                        data-placeholder="Type product name or SKU">
                                    <option value=""></option>
                                </select>
                            </td>
                            <td>
                                <input name="lines[0][qty_requested]"
                                       class="form-control stn-number-input"
                                       type="number"
                                       min="0"
                                       step="0.0001"
                                       placeholder="0.0000">
                            </td>
                            <td>
                                <input name="lines[0][unit_cost]"
                                       class="form-control stn-number-input"
                                       type="number"
                                       min="0"
                                       step="0.0001"
                                       placeholder="0.0000">
                            </td>
                            <td>
                                <input name="lines[0][remarks]"
                                       class="form-control"
                                       placeholder="Optional remarks">
                            </td>
                            <td class="stn-action-cell">
                                <button type="button"
                                        class="stn-remove-line"
                                        title="Remove row">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="stn-form-card stn-notes-card">
            <div class="stn-form-card-body">
                <div class="stn-field stn-field-full">
                    <label for="stn-reason">Reason / Internal Note</label>
                    <textarea id="stn-reason"
                              name="reason"
                              class="form-control"
                              rows="3"
                              placeholder="Explain the purpose of this transfer">{{ old('reason') }}</textarea>
                </div>
            </div>
        </section>

        <div class="stn-sticky-actions">
            <a href="{{ route('stock-transfer-new.transfers.index') }}"
               class="stn-btn stn-btn-light">
                <i class="fa fa-times"></i>
                Cancel
            </a>

            <button type="submit"
                    class="stn-btn stn-btn-primary">
                <i class="fa fa-save"></i>
                Save Draft
            </button>
        </div>
    </form>
</div>

<script>
window.StockTransferNewCreate = {
    locationsUrl: @json(route('stock-transfer-new.lookups.locations')),
    storesUrl: @json(route('stock-transfer-new.lookups.stores')),
    productLookupUrl: @json(route('stock-transfer-new.lookups.products')),
    oldFromLocation: @json(old('from_location_id')),
    oldToLocation: @json(old('to_location_id')),
    oldFromStore: @json(old('from_store_id')),
    oldToStore: @json(old('to_store_id'))
};
</script>
<script defer src="{{ asset('modules/stocktransfernew/js/stocktransfernew-create.js') }}?v=20260906-is2196-r2"></script>
<script>
/* IS2196 deployment-safe fallback for Location -> Store. */
(function (window, document) {
    'use strict';

    var cfg = window.StockTransferNewCreate || {};
    var selectors = {
        'stn-from-location': 'stn-from-store',
        'stn-to-location': 'stn-to-store'
    };

    function refresh(select) {
        if (window.jQuery) {
            var $select = window.jQuery(select);
            $select.prop('disabled', !!select.disabled);
            if ($select.data('select2')) {
                $select.trigger('change.select2');
            }
        }
    }

    function setMessage(select, message, disabled) {
        select.innerHTML = '<option value="">' + message + '</option>';
        select.disabled = !!disabled;
        refresh(select);
    }

    function load(locationSelect) {
        var storeId = selectors[locationSelect.id];
        var storeSelect = storeId ? document.getElementById(storeId) : null;
        var locationId = String(locationSelect.value || '').trim();

        if (!storeSelect || !cfg.storesUrl) {
            return;
        }

        if (!locationId) {
            storeSelect.dataset.is2196Location = '';
            setMessage(storeSelect, 'Select location first', true);
            return;
        }

        if (storeSelect.dataset.is2196Location === locationId &&
            !storeSelect.disabled &&
            storeSelect.options.length > 1) {
            return;
        }

        storeSelect.dataset.is2196Location = locationId;
        var token = String(Date.now()) + ':' + Math.random();
        storeSelect.dataset.is2196Token = token;
        setMessage(storeSelect, 'Loading stores...', true);

        fetch(cfg.storesUrl + '?location_id=' + encodeURIComponent(locationId), {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('store lookup failed');
                }
                return response.json();
            })
            .then(function (payload) {
                if (storeSelect.dataset.is2196Token !== token ||
                    storeSelect.dataset.is2196Location !== locationId) {
                    return;
                }

                var items = payload && Array.isArray(payload.results)
                    ? payload.results
                    : [];
                var html = '<option value="">' +
                    (items.length ? 'Please select' : 'No stores for selected location') +
                    '</option>';

                items.forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = String(item.id);
                    option.textContent = item.text == null ? '' : String(item.text);
                    html += option.outerHTML;
                });

                storeSelect.innerHTML = html;
                storeSelect.disabled = false;
                refresh(storeSelect);
            })
            .catch(function () {
                if (storeSelect.dataset.is2196Token === token) {
                    setMessage(storeSelect, 'Unable to load stores', true);
                }
            });
    }

    function handle(target) {
        if (target && selectors[target.id]) {
            load(target);
        }
    }

    document.addEventListener('change', function (event) {
        handle(event.target);
    }, true);

    if (window.jQuery) {
        window.jQuery(document)
            .off('.is2196StoreFallback', '#stn-from-location, #stn-to-location')
            .on(
                'change.is2196StoreFallback select2:select.is2196StoreFallback select2:clear.is2196StoreFallback',
                '#stn-from-location, #stn-to-location',
                function () { handle(this); }
            );
    }

    function sync() {
        ['stn-from-location', 'stn-to-location'].forEach(function (id) {
            var select = document.getElementById(id);
            if (select && select.value) {
                load(select);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.setTimeout(sync, 0);
            window.setTimeout(sync, 400);
            window.setTimeout(sync, 1400);
        });
    } else {
        window.setTimeout(sync, 0);
        window.setTimeout(sync, 400);
        window.setTimeout(sync, 1400);
    }
})(window, document);
</script>
@endsection
