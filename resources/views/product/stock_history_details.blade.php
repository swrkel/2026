@php
    $common_settings = session()->get('business.common_settings');
@endphp
<div class="row">
    <div class="col-md-12">
        <h4>{{ $stock_details['variation'] }}</h4>
    </div>
    <div class="col-md-4 col-xs-4">
        <strong>@lang('lang_v1.quantities_in')</strong>
        <table class="table table-condensed">
            <tr>
                <th>@lang('report.total_purchase')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['total_purchase'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.added_purchase')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['total_purchase'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.opening_stock')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_opening_stock'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.total_sell_return')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_sell_return'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.stock_transfers') (@lang('lang_v1.in'))</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_purchase_transfer'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('manufacturing::lang.manufactured') </th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['manufactured'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-4 col-xs-4">
        <strong>@lang('lang_v1.quantities_out')</strong>
        <table class="table table-condensed">
            <tr>
                <th>@lang('lang_v1.total_sold')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['total_sold'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('report.total_stock_adjustment')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_adjusted'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            <tr>
                <th>@lang('lang_v1.total_purchase_return')</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_purchase_return'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>

            <tr>
                <th>@lang('lang_v1.stock_transfers') (@lang('lang_v1.out'))</th>
                <td>
                    <span class="display_currency"
                        data-is_quantity="true">{{ $stock_details['total_sell_transfer'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>

            <tr>
                <th>@lang('manufacturing::lang.ingredient')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['input'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>

        </table>
    </div>

    <div class="col-md-4 col-xs-4">
        <strong>@lang('lang_v1.totals')</strong>
        <table class="table table-condensed">
            <tr>
                <th>@lang('report.current_stock')</th>
                <td>
                    <span class="display_currency" data-is_quantity="true">{{ $stock_details['current_stock'] }}</span>
                    {{ $stock_details['unit'] }}
                </td>
            </tr>
            @foreach ($storedetails as $store)
                <tr>
                    <th>{{ $store->name }}</th>
                    <td>
                        <span class="display_currency"
                            data-is_quantity="true">{{ @num_format($store->qty_available) }}</span>
                        {{ $stock_details['unit'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <hr>
        <style>
            #stock_history_table td {
                vertical-align: middle;
                padding: 8px !important;
            }
            #stock_history_table .display_currency {
                white-space: nowrap;
            }
            .nowrap {
                white-space: nowrap;
            }
        </style>
        <table class="table table-bordered table-striped" id="stock_history_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('lang_v1.type')</th>
                    <th>@lang('stock_adjustment.adjustment_type')</th>
                    <th>@lang('lang_v1.quantity_change')</th>
                    @if (!empty($common_settings['enable_secondary_unit']))
                        <th>@lang('lang_v1.quantity_change') (@lang('lang_v1.secondary_unit'))</th>
                    @endif
                    <th>@lang('lang_v1.new_quantity')</th>
                    @if (!empty($common_settings['enable_secondary_unit']))
                        <th>@lang('lang_v1.new_quantity') (@lang('lang_v1.secondary_unit'))</th>
                    @endif
                    <th>@lang('lang_v1.date')</th>
                    <th>@lang('purchase.ref_no')</th>
                    <th>@lang('lang_v1.description')</th> {{-- NEW --}}
                    <th>@lang('lang_v1.customer_pump_operator_supplier_info')</th>
                    <th>@lang('lang_v1.cheque_number')</th> {{-- NEW --}}
                    <th>@lang('account.debit')</th> {{-- NEW --}}
                    <th>@lang('lang_v1.credit')</th> {{-- NEW --}}
                </tr>
            </thead>

            <tbody>
                @forelse($stock_history as $history)
                    <tr>
                        <td>
                            @if (($history['type'] ?? '') === 'purchase_return')
                                {{ $history['type_label'] }} - {{ $history['ref_no'] }}
                            @else
                                {{ $history['type_label'] }}
                            @endif
                        </td>
                        <td>
                            @if (($history['type'] ?? '') === 'stock_adjustment')
                                {{ ucfirst($history['adjustment_type'] ?? '') }}
                            @else
                                --
                            @endif
                        </td>

                        {{-- Quantity Change --}}
                        @if ($history['quantity_change'] > 0)
                            <td class="text-success">+<span class="display_currency"
                                    data-is_quantity="true">{{ $history['quantity_change'] }}</span></td>
                        @else
                            <td class="text-danger"><span class="display_currency text-danger"
                                    data-is_quantity="true">{{ $history['quantity_change'] }}</span></td>
                        @endif

                        {{-- Secondary Unit --}}
                        @if (!empty($common_settings['enable_secondary_unit']))
                            <td>
                                @if ($history['quantity_change'] > 0 && !empty($history['purchase_secondary_unit_quantity']))
                                    +<span class="display_currency"
                                        data-is_quantity="true">{{ $history['purchase_secondary_unit_quantity'] }}</span>
                                    {{ $stock_details['second_unit'] }}
                                @elseif(!empty($history['sell_secondary_unit_quantity']))
                                    -<span class="display_currency"
                                        data-is_quantity="true">{{ $history['sell_secondary_unit_quantity'] }}</span>
                                    {{ $stock_details['second_unit'] }}
                                @endif
                            </td>
                        @endif

                        {{-- New Quantity --}}
                        <td class="nowrap"><span class="display_currency" data-is_quantity="true">{{ $history['stock'] }}</span></td>

                        @if (!empty($common_settings['enable_secondary_unit']))
                            <td>
                                @if (!empty($stock_details['second_unit']))
                                    <span class="display_currency"
                                        data-is_quantity="true">{{ $history['stock_in_second_unit'] }}</span>
                                    {{ $stock_details['second_unit'] }}
                                @endif
                            </td>
                        @endif

                        <td class="nowrap" data-order="{{ strtotime($history['date']) }}">{{ @format_datetime($history['date']) }}</td>
                        <td>{{ $history['ref_no'] }}</td>

                        {{-- Description --}}
                        <td>{{ $history['description'] ?? '--' }}</td>

                        {{-- Supplier / Customer --}}
                        <td>
                            {{ $history['contact_name'] ?? '--' }}
                            @if (!empty($history['supplier_business_name']))
                                - {{ $history['supplier_business_name'] }}
                            @endif
                        </td>

                        {{-- Cheque --}}
                        <td>{{ $history['cheque_number'] ?? '--' }}</td>

                        {{-- Debit --}}
                        <td>
                            @if (!empty($history['payment_amount_debit']))
                                <span class="display_currency">{{ $history['payment_amount_debit'] }}</span>
                                ({{ $history['account_name'] ?? '' }})
                            @endif
                        </td>

                        {{-- Credit --}}
                        <td>
                            @if (!empty($history['stock_credit_amount']))
                                <span class="display_currency">{{ $history['stock_credit_amount'] }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center">@lang('lang_v1.no_stock_history_found')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
