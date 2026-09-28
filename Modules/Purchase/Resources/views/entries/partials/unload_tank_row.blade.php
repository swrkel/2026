@php
    // Received quantity keeps the existing business precision. Tank stock follows
    // Petro General -> Tank Transaction Details, which is a fuel quantity at 3 decimals.
    $displayPrecision = max(0, min(6, (int) (session('business.currency_precision') ?? 2)));
    $tankQtyPrecision = 3;
@endphp
<div class="purchase-unload-row" data-product-id="{{ $product_id }}" data-has-tanks="{{ $tanks->isNotEmpty() ? '1' : '0' }}">
    <div class="purchase-unload-summary">
        <div>
            <strong>{{ $product_name ?: 'Fuel product' }}</strong>
            <small>Allocate the complete received quantity among the tanks below.</small>
        </div>
        <div class="purchase-unload-received">
            <label>Received Qty</label>
            <input type="text" class="form-control unload-received-qty" value="0" readonly>
        </div>
        <div class="purchase-unload-match is-pending">Waiting for allocation</div>
    </div>

    <div class="purchase-table-scroll">
        <table class="table table-bordered purchase-unload-table">
            <thead>
                <tr>
                    <th>Unload Tank</th>
                    <th>Unload Quantity</th>
                    <th>Current Tank Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tanks as $tank)
                    <tr>
                        <td>
                            <strong>{{ $tank->fuel_tank_number }}</strong>
                            @if(!empty($tank->tank_capacity))
                                <small>Capacity: {{ number_format((float) $tank->tank_capacity, 3) }} {{ $tank->unit_name ?? '' }}</small>
                            @endif
                        </td>
                        <td>
                            <input type="number"
                                class="form-control tank-qty purchase-number"
                                name="tanks[{{ $product_id }}][{{ $tank->id }}][qty]"
                                value=""
                                min="0"
                                step="0.000001"
                                autocomplete="off">
                        </td>
                        <td>
                            <input type="text"
                                class="form-control"
                                name="tanks[{{ $product_id }}][{{ $tank->id }}][instock_qty]"
                                value="{{ number_format((float) ($tank->current_balance ?? 0), $tankQtyPrecision, '.', '') }}"
                                readonly>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="purchase-unload-empty">
                            No unload tank is configured for this fuel product at the selected business location.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
