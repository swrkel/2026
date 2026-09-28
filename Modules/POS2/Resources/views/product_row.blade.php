@php
    $product_name = $product->product_name . '<br />' . $product->sub_sku;
    if (!empty($product->brand)) {
        $product_name .= ' ' . $product->brand;
    }

    $hide_tax = 'hide';
    if (session()->get('business.enable_inline_tax') == 1) {
        $hide_tax = '';
    }

    $tax_id = $product->tax_id;
    $item_tax = !empty($product->item_tax) ? $product->item_tax : 0;

    $unit_price_inc_tax = $product->sell_price_inc_tax;

    if ($hide_tax == 'hide') {
        $tax_id = null;
    }

    $unit_price_ex_tax = $product->default_sell_price;
    $included_sales_tax = max($unit_price_inc_tax - $unit_price_ex_tax, 0);
    $unit_price_is_tax_inclusive = session()->get('business.sell_price_tax') === 'includes' ? 1 : 0;

    if (empty($product->quantity_ordered)) {
        $product->quantity_ordered = 1;
    }

    if (empty($product->current_stock)) {
        $product->current_stock = $product->qty_available;
    }

    $max_qty_rule = $product->current_stock;
    $max_qty_msg = __('validation.custom-messages.quantity_not_available', [
        'qty' => $product->formatted_qty_available,
        'unit' => $product->unit,
    ]);

    // Define multiplier for unit calculations
    $multiplier = 1;
    $allow_decimal = true;
    if ($product->unit_allow_decimal != 1) {
        $allow_decimal = false;
    }

    // Handle sub units
    foreach ($sub_units as $key => $value) {
        if (!empty($product->sub_unit_id) && $product->sub_unit_id == $key) {
            $multiplier = $value['multiplier'];
            $max_qty_rule = $max_qty_rule / $multiplier;
            $unit_name = $value['name'];
            $max_qty_msg = __('validation.custom-messages.quantity_not_available', [
                'qty' => $max_qty_rule,
                'unit' => $unit_name,
            ]);

            if (!empty($product->lot_no_line_id)) {
                $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', [
                    'qty' => $max_qty_rule,
                    'unit' => $unit_name,
                ]);
            }

            if ($value['allow_decimal']) {
                $allow_decimal = true;
            }
        }
    }
@endphp

<tr class="product_row" data-row_index="{{ $row_count }}">
    {{-- Product Column --}}
    <td class="text-center">{{ $row_count + 1 }}</td>
    <td class="text-center">{{ $product->sub_sku }}</td>
    <td>
        @if ($edit_price || $edit_discount)
            <div data-toggle="tooltip" data-placement="bottom" title="@lang('lang_v1.pos_edit_product_price_help')">
                @if (isset($price_later) && $price_later == 1)
                    <span class="text-info cursor-pointer">
                        {!! $product_name !!}
                        &nbsp;<i class="fa fa-info-circle"></i>
                    </span>
                @else
                    <span class="text-link text-info cursor-pointer row_edit_product_price_btn">
                        {!! $product_name !!}
                        &nbsp;<i class="fa fa-info-circle"></i>
                    </span>
                @endif
            </div>
        @else
            {!! $product_name !!}
        @endif

        <input type="hidden" class="enable_sr_no" value="{{ $product->enable_sr_no }}">
        <input type="hidden" class="product_type" name="products[{{ $row_count }}][product_type]"
            value="{{ $product->product_type }}">

        <div data-toggle="tooltip" data-placement="bottom" title="@lang('lang_v1.add_description')">
            <i class="fa fa-commenting cursor-pointer text-primary add-pos-row-description" data-toggle="modal"
                data-target="#row_description_modal_{{ $row_count }}"></i>
        </div>


        <!-- Description modal start -->
        @if ($toggle_popup == 0)
            <div class="modal fade row_description_modal in" id="row_description_modal_{{ $row_count }}"
                tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel">{{ $product->product_name }} -
                                {{ $product->sub_sku }}
                            </h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>@lang('lang_v1.description')</label>
                                @php
                                    $sell_line_note = '';
                                    if (!empty($product->sell_line_note)) {
                                        $sell_line_note = $product->sell_line_note;
                                    }
                                @endphp
                                <textarea class="form-control" name="products[{{ $row_count }}][sell_line_note]" rows="3">{{ $sell_line_note }}</textarea>
                                <p class="help-block">@lang('lang_v1.sell_line_description_help')</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default"
                                data-dismiss="modal">@lang('messages.close')</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- Description modal end -->

        @if ($edit_price || $edit_discount)
            <div class="modal fade row_edit_product_price_model" id="row_edit_product_price_modal_{{ $row_count }}"
                tabindex="-1" role="dialog">
                @include('pos2::partials.row_edit_product_price_modal')
            </div>
        @endif

        @if (in_array('modifiers', $enabled_modules))
            <div class="modifiers_html">
                @if (!empty($product->product_ms))
                    @include('restaurant.product_modifier_set.modifier_for_product', [
                        'edit_modifiers' => true,
                        'row_count' => $loop->index,
                        'product_ms' => $product->product_ms,
                    ])
                @endif
            </div>
        @endif

        @if (session()->get('business.enable_lot_number') == 1 || session()->get('business.enable_product_expiry') == 1)
            @php
                $lot_enabled = session()->get('business.enable_lot_number');
                $exp_enabled = session()->get('business.enable_product_expiry');
                $lot_no_line_id = '';
                if (!empty($product->lot_no_line_id)) {
                    $lot_no_line_id = $product->lot_no_line_id;
                }
            @endphp

            @if (!empty($product->lot_numbers))
                <select class="form-control lot_number input-sm" name="products[{{ $row_count }}][lot_no_line_id]"
                    @if (!empty($product->transaction_sell_lines_id)) disabled @endif>
                    <option value="">@lang('lang_v1.lot_n_expiry')</option>
                    @foreach ($product->lot_numbers as $lot_number)
                        @php
                            $selected = '';
                            if ($lot_number->purchase_line_id == $lot_no_line_id) {
                                $selected = 'selected';
                                $max_qty_rule = $lot_number->qty_available;
                                $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', [
                                    'qty' => $lot_number->qty_formated,
                                    'unit' => $product->unit,
                                ]);
                            }

                            $expiry_text = '';
                            if ($exp_enabled == 1 && !empty($lot_number->exp_date)) {
                                if (\Carbon::now()->gt(\Carbon::createFromFormat('Y-m-d', $lot_number->exp_date))) {
                                    $expiry_text = '(' . __('report.expired') . ')';
                                }
                            }

                            //preselected lot number if product searched by lot number
                            if (!empty($purchase_line_id) && $purchase_line_id == $lot_number->purchase_line_id) {
                                $selected = 'selected';
                                $max_qty_rule = $lot_number->qty_available;
                                $max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', [
                                    'qty' => $lot_number->qty_formated,
                                    'unit' => $product->unit,
                                ]);
                            }
                        @endphp
                        <option value="{{ $lot_number->purchase_line_id }}"
                            data-qty_available="{{ $lot_number->qty_available }}" data-msg-max="@lang('lang_v1.quantity_error_msg_in_lot', ['qty' => $lot_number->qty_formated, 'unit' => $product->unit])"
                            {{ $selected }}>
                            @if (!empty($lot_number->lot_number) && $lot_enabled == 1)
                                {{ $lot_number->lot_number }}
                                @endif @if ($lot_enabled == 1 && $exp_enabled == 1)
                                    -
                                    @endif @if ($exp_enabled == 1 && !empty($lot_number->exp_date))
                                        @lang('product.exp_date'): {{ @format_date($lot_number->exp_date) }}
                                    @endif
                                    {{ $expiry_text }}
                        </option>
                    @endforeach
                </select>
            @endif
        @endif
    </td>

    {{-- Unit column --}}
    <td class="text-center">
        {{ $product->unit }}
    </td>

    {{-- Unit Price Column --}}
    <td class="text-center">
        <input type="text" class="form-control pos_unit_price input_number mousetrap" readonly
            name="products[{{ $row_count }}][unit_price]" value="{{ @num_format($unit_price_ex_tax) }}"
            data-selling-price="{{ @num_format($unit_price_inc_tax) }}" data-rule-required="true"
            data-msg-required="@lang('validation.custom-messages.this_field_is_required')">
    </td>

    {{-- Quantity Column --}}
    <td class="text-center">
        {{-- Hidden fields --}}
        @if (!empty($product->transaction_sell_lines_id))
            <input type="hidden" name="products[{{ $row_count }}][transaction_sell_lines_id]" class="form-control"
                value="{{ $product->transaction_sell_lines_id }}">
        @endif
        <input type="hidden" name="products[{{ $row_count }}][product_id]" class="form-control product_id"
            value="{{ $product->product_id }}">
        <input type="hidden" value="{{ $product->variation_id }}" name="products[{{ $row_count }}][variation_id]"
            class="row_variation_id">
        <input type="hidden" value="{{ $product->enable_stock }}" name="products[{{ $row_count }}][enable_stock]">
        <input type="hidden" name="products[{{ $row_count }}][product_unit_id]" value="{{ $product->unit_id }}">
        <input type="hidden" class="base_unit_multiplier" name="products[{{ $row_count }}][base_unit_multiplier]"
            value="{{ $multiplier }}">
        <input type="hidden" class="hidden_base_unit_sell_price" value="{{ $unit_price_ex_tax / $multiplier }}">
        <input type="hidden" class="unit_price_is_tax_inclusive" value="{{ $unit_price_is_tax_inclusive }}">

        {{-- Quantity input --}}
        <input type="text" class="form-control pos_quantity input_number mousetrap input_quantity"
            name="products[{{ $row_count }}][quantity]" value="{{ @format_quantity($product->quantity_ordered) }}"
            data-min="0.01" step="0.01" inputmode="decimal" pattern="^\d*\.?\d*$" data-decimal="1"
            data-allow-overselling="@if (empty($pos_settings['allow_overselling'])) false@else true @endif" data-rule-required="true"
            data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
            @if ($product->enable_stock && empty($pos_settings['allow_overselling']) && empty($is_sales_order)) data-rule-max-value="{{ $max_qty_rule }}" data-qty_available="{{ $product->current_stock }}"
                data-msg-max-value="{{ $max_qty_msg }}"
                data-msg_max_default="@lang('validation.custom-messages.quantity_not_available', ['qty' => $product->formatted_qty_available, 'unit' => $product->unit])" @endif>
                
        {{-- Hidden fields for combo products --}}
        @if ($product->product_type == 'combo')
            @foreach ($product->combo_products as $k => $combo_product)
                @if (isset($action) && $action == 'edit')
                    @php
                        $combo_product['qty_required'] = $combo_product['quantity'] / $product->quantity_ordered;
                        $qty_total = $combo_product['quantity'];
                    @endphp
                @else
                    @php
                        $qty_total = $combo_product['qty_required'];
                    @endphp
                @endif

                <input type="hidden" name="products[{{ $row_count }}][combo][{{ $k }}][product_id]"
                    value="{{ $combo_product['product_id'] }}">
                <input type="hidden" name="products[{{ $row_count }}][combo][{{ $k }}][variation_id]"
                    value="{{ $combo_product['variation_id'] }}">
                <input type="hidden" class="combo_product_qty"
                    name="products[{{ $row_count }}][combo][{{ $k }}][quantity]"
                    data-unit_quantity="{{ $combo_product['qty_required'] }}" value="{{ $qty_total }}">
                @if (isset($action) && $action == 'edit')
                    <input type="hidden"
                        name="products[{{ $row_count }}][combo][{{ $k }}][transaction_sell_lines_id]"
                        value="{{ $combo_product['id'] }}">
                @endif
            @endforeach
        @endif
    </td>

    {{-- Discount Type Column --}}
@if(!empty($pos_settings['show_discount_type']))
    <td>
        @php
            $discount_type = !empty($product->line_discount_type)
                ? $product->line_discount_type
                : 'fixed';
        @endphp

        {!! Form::select(
            "products[$row_count][line_discount_type]",
            [
                'fixed' => __('lang_v1.fixed'),
                'percentage' => __('lang_v1.percentage')
            ],
            old("products.$row_count.line_discount_type", $discount_type),
            [
                'class' => 'form-control row_discount_type_table',
                'data-row' => $row_count,
                'disabled' => isset($price_later) && $price_later == 1,
            ]
        ) !!}
    </td>
@else
    {{-- Force fixed discount when hidden --}}
    <input type="hidden"
        name="products[{{ $row_count }}][line_discount_type]"
        value="fixed">
@endif


    {{-- Discount Amount Column --}}
    <td>
        {!! Form::text(
            "products[$row_count][line_discount_amount]",
            old("products.$row_count.line_discount_amount", number_format($product->line_discount_amount, 2, '.', '')),
            [
                'class' => 'form-control input_number row_discount_amount row_discount_amount_table',
                'data-row' => $row_count,
                'oninput' => '',
                'disabled' => isset($price_later) && $price_later == 1,
            ],
        ) !!}
    </td>

    @php
        $enable_line_discount = !empty($pos_settings['enable_line_discount']) ? 1 : 0;
    @endphp

    {{-- Price Inc Tax Column --}}
    <td class="text-center price_inc_tax_column v-center">
        <span class="display_currency price_inc_tax_display" data-currency_symbol="true">
            {{ @num_format($unit_price_inc_tax) }}
        </span>
    </td>

    {{-- Subtotal Column --}}
    <td class="text-center v-center">
        @php
            $subtotal_type = !empty($pos_settings['is_pos_subtotal_editable']) ? 'text' : 'hidden';
            $__initial_qty = $product->quantity_ordered ?: 1;
            $__initial_unit_inc = $unit_price_inc_tax ?: 0;
            $__initial_subtotal = $__initial_qty * $__initial_unit_inc;
        @endphp
        <input type="{{ $subtotal_type }}" class="form-control pos_line_total input_number"
            id="subtotal_{{ $row_count }}" name="products[{{ $row_count }}][subtotal]"
            value="{{ @num_format($__initial_subtotal) }}" readonly>
        <span class="display_currency pos_line_total_text" id="subtotal_text_{{ $row_count }}"
            data-currency_symbol="true">
            {{ @num_format($__initial_subtotal) }}
        </span>
    </td>

    {{-- Remove Column --}}
    <td class="text-center">
        <i class="fa fa-close text-danger pos_remove_row cursor-pointer" aria-hidden="true"></i>
        {{-- Hidden fields for tax calculation - MUST be inside the <tr> for JavaScript to find them --}}
        @php
            // Use the original product tax_id (not the potentially nullified $tax_id)
            // so that the tax rate is always available for JS recalculation
            $original_tax_id = $product->tax_id;
            $tax_rate = !empty($tax_dropdown['attributes'][$original_tax_id]['data-rate'])
                ? $tax_dropdown['attributes'][$original_tax_id]['data-rate']
                : 0;
        @endphp
        <input type="hidden" name="products[{{ $row_count }}][tax_id]" value="{{ $tax_id }}"
            class="tax_id" data-rate="{{ $tax_rate }}">
        <input type="hidden" name="products[{{ $row_count }}][item_tax]" value="{{ @num_format($item_tax) }}"
            class="item_tax">
        <input type="hidden" class="pos_line_total_discount" value="0">
        <input type="hidden" class="pos_line_discount" name="products[{{ $row_count }}][line_discount]"
            value="0">
        <input type="hidden" class="pos_unit_price_inc_tax" value="{{ @num_format($unit_price_inc_tax) }}">
        <input type="hidden" name="products[{{ $row_count }}][unit_price_inc_tax]" value="{{ @num_format($unit_price_inc_tax) }}">
        <input type="hidden" class="original_pos_unit_price_inc_tax" value="{{ @num_format($unit_price_inc_tax) }}">
    </td>
</tr>
