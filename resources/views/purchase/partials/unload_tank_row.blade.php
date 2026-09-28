<div class="col-md-12 check_tank_row purchase-unload-row"
    id="tank_row{{ $row_count }}"
    data-row_id="{{ $row_count }}"
    data-product_id="{{ $product->id }}">

    <div class="purchase-unload-summary bg-success">
        <div class="purchase-unload-product-name">
            {{ $product->name }}
        </div>

        <div class="purchase-unload-received-qty">
            <label for="receive_qty{{ $product->id }}">@lang('purchase.quantity')</label>
            <input type="text"
                id="receive_qty{{ $product->id }}"
                class="form-control receive_qty"
                readonly
                value="1">
        </div>

        <input type="hidden"
            id="tank_row_conut{{ $row_count }}"
            name="tank_row_conut{{ $row_count }}"
            value="{{ $row_count }}">
        <input type="hidden"
            id="stock_match{{ $row_count }}"
            name="stock_match{{ $row_count }}"
            value="0">
    </div>

    <div class="purchase-unload-table-wrap table-responsive">
        <table class="table table-bordered purchase-unload-table table{{ $product->id }}">
            <thead>
                <tr>
                    <th>@lang('purchase.warehouse')</th>
                    <th>@lang('purchase.quantity')</th>
                    <th>@lang('purchase.instock_qty')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fuel_tanks as $tank)
                    @php
                        $balance = number_format((float) ($current_balance[$tank->id] ?? 0), 2, '.', '');
                    @endphp
                    <tr>
                        <td>{{ $tank->fuel_tank_number }}</td>
                        <td>
                            {!! Form::number('tanks[' . $tank->id . '][qty]', null, [
                                'class' => 'form-control tank_qty tank_qty' . $product->id,
                                'data-id' => $product->id,
                                'step' => 'any',
                                'min' => '0',
                                'autocomplete' => 'off',
                            ]) !!}
                        </td>
                        <td>
                            {!! Form::number('tanks[' . $tank->id . '][instock_qty]', $balance, [
                                'class' => 'form-control onetankvalue',
                                'readonly',
                                'step' => 'any',
                            ]) !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-danger">
                            No unload tanks are configured for this product and business location.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
