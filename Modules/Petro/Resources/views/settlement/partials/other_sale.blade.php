@php
    use App\Business;

    $business_id = request()->session()->get('user.business_id');
    $business_record = Business::find($business_id);
    $businessCurrencyPrecise = $business_record->currency_precision ?? 2;

    // IS1762: the Store field must always open with a real default.
    // Prefer the session/business configured default and safely fall back to
    // the first store available to this business.
    $available_store_ids = collect($stores ?? [])->keys()->map(function ($id) {
        return (string) $id;
    })->values();

    $default_store = $default_store
        ?? request()->session()->get('business.default_store')
        ?? ($business_record->default_store ?? null);

    if (empty($default_store) || !$available_store_ids->contains((string) $default_store)) {
        $default_store = $available_store_ids->first();
    }
@endphp
<br>
<div class="col-md-12">
    <div class="row">
        <div class="col-md-3 pull-right">
            <div class="checkbox pull-right">
                <label>
                    {!! Form::checkbox('show_bulk_tank', 1, false, ['class' => 'input-icheck', 'id' => 'show_bulk_tank']);
                    !!}
                    @lang('petro::lang.bulk_tank')
                </label>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-2 store_field">
            <div class="form-group">
                {!! Form::label('store', __('petro::lang.store').':') !!}
                {!! Form::select('store_id', $stores, $default_store, ['class' => 'form-control check_pumper
                select2', 'style' => 'width: 100%;', 'id' => 'store_id',
                'data-default-store-id' => (string) ($default_store ?? ''),
                'placeholder' => __('petro::lang.please_select')]); !!}
            </div>
        </div>
        <div class="col-md-2 bulk_tank_field hide">
            <div class="form-group">
                {!! Form::label('bulk_tank', __('petro::lang.bulk_tank').':') !!}
                {!! Form::select('bulk_tank', $bulk_tanks, null, ['class' => 'form-control check_pumper
                select2', 'style' => 'width: 100%;', 'id' => 'bulk_tank',
                'placeholder' => __('petro::lang.please_select')]); !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('item', __('petro::lang.select_item').':') !!}
                {!! Form::select('item', $items, null, ['class' => 'form-control other_sale_fields check_pumper
                select2', 'style' => 'width: 100%;','placeholder' => __('petro::lang.please_select')]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('balance_stock', __( 'petro::lang.balance_stock' ) ) !!}
                {!! Form::text('balance_stock', null, ['class' => 'form-control other_sale_fields check_pumper input_number
                balance_stock', 'required', 'readonly',
                'placeholder' => __(
                'petro::lang.balance_stock' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_sale_price', __( 'petro::lang.price' ) ) !!}
                {!! Form::text('other_sale_price', null, ['class' => 'form-control other_sale_fields check_pumper input_number
                other_sale_price', 'required', 'readonly',
                'placeholder' => __(
                'petro::lang.price' ) ]); !!}
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-2 col-md-offset-3">
            <div class="form-group">
                {!! Form::label('other_sale_qty', __( 'petro::lang.qty' ) ) !!}
                {!! Form::text('other_sale_qty', null, ['class' => 'form-control other_sale_fields check_pumper qty input_number',
                'required',
                'placeholder' => __(
                'petro::lang.qty' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_sale_discount_type', __( 'petro::lang.discount_type' ) ) !!}
                {!! Form::select('other_sale_discount_type', ['fixed' => 'Fixed', 'percentage' => 'Percentage'], null, ['class' => 'form-control other_sale_fields check_pumper
                input_number
                other_sale_discount_type', 'required',
                'placeholder' => __(
                'petro::lang.please_select' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_sale_discount', __( 'petro::lang.discount' ) ) !!}
                {!! Form::text('other_sale_discount', null, ['class' => 'form-control other_sale_fields check_pumper input_number
                other_sale_discount', 'required',
                'placeholder' => __(
                'petro::lang.discount' ) ]); !!}
            </div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-primary btn_other_sale"
                    style="margin-top: 23px;">@lang('messages.add')</button>
        </div>
    </div>
</div>
<br>
<br>
<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="other_sale_table">
            <thead>
            <tr>
                <th>@lang('petro::lang.code' )</th>
                <th>@lang('petro::lang.products' )</th>
                <th>@lang('petro::lang.balance_stock' )</th>
                <th>@lang('petro::lang.price')</th>
                <th>@lang('petro::lang.qty' )</th>
                <th>@lang('petro::lang.discount_type' )</th>
                <th>@lang('petro::lang.discount_value' )</th>
                <th>@lang('petro::lang.before_discount' )</th>
                <th>@lang('petro::lang.after_discount' )</th>
                <th>@lang('petro::lang.action' )</th>
            </tr>
            </thead>

            <tbody>
            <!-- @php
                $other_sale_final_total = 0.00;
            @endphp
            @if (!empty($active_settlement))
                @foreach ($active_settlement->other_sales as $ot_item)
                    @php
                        $product = App\Product::where('id', $ot_item->product_id)->first();
                        $discount_amount = $ot_item->discount_amount;
                        $withDiscount = $ot_item->sub_total - $discount_amount;
                        $other_sale_final_total += $withDiscount;
                    @endphp

                            <tr>
                                <td>{{!empty($product) ? $product->sku : ''}}</td>
                            <td>{{!empty($product) ? $product->name : ''}}</td>
                            <td>{{number_format($ot_item->balance_stock,$businessCurrencyPrecise,'.',',')}}</td>
                            <td>{{number_format($ot_item->price, $currency_precision)}}</td>
                            <td>{{number_format($ot_item->qty,$businessCurrencyPrecise,'.',',')}}</td>
                            <td>{{$ot_item->discount_type}}</td>
                            <td>{{number_format($ot_item->discount, $currency_precision)}}</td>
                            <td>{{number_format($ot_item->sub_total, $currency_precision)}}</td>
                            <td>{{number_format($withDiscount, $currency_precision)}}</td>
                            <td><button class="btn btn-xs btn-danger delete_other_sale"
                                    data-href="/petro/settlement/delete-other-sale/{{$ot_item->id}}"><i class="fa fa-times"></i>
                            </td>
                        </tr>

                @endforeach
            @endif -->

            @php
                $other_sale_final_total = 0.00;
                $manual_other_sale_final_total = 0.00;
            @endphp
            @foreach ($combinedOtherSales as $item)
                @php
                    $line_total = (float) str_replace(',', '', (string) ($item['with_discount'] ?? 0));
                    $other_sale_final_total += $line_total;
                    if (($item['user_check'] ?? 0) == 1) {
                        $manual_other_sale_final_total += $line_total;
                    }
                @endphp
                <tr data-source="{{ ($item['user_check'] ?? 0) == 1 ? 'manual' : 'pumper' }}">
                    <td>{{ $item['sku'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="">
                        {{ number_format((float) ($item['balance_stock'] ?? 0), 4, '.', ',') }}
                    </td>
                    <td class="">{{ $item['price'] }}</td>
                    <td class="">{{ $item['qty'] }}</td>
                    <td>{{ $item['discount_type'] }}</td>
                    <td class="">{{ $item['discount'] }}</td>
                    <td class="">{{ $item['sub_total'] }}</td>
                    <td class="">{{ $item['with_discount'] }}</td>
                    <td>
                        @if ($item['user_check'] == 1)
                            <button class="btn btn-xs btn-danger delete_other_sale"
                                    data-href="/petro/settlement/delete-other-sale/{{ $item['id'] ?? '' }}">
                                <i class="fa fa-times"></i>
                            </button>
                        @else
                        @endif
                    </td>
                </tr>
            @endforeach


            </tbody>

            <tfoot>
            <tr>
                <td colspan="7" style="text-align: right; font-weight: bold;">@lang('petro::lang.other_sale_total')
                    :
                </td>
                <td style="text-align: left; font-weight: bold;" class="other_sale_total">
                    {{number_format( $other_sale_final_total, $currency_precision)}}</td>

            </tr>
            <input type="hidden" value="{{$manual_other_sale_final_total}}" name="other_sale_total" id="other_sale_total">
            </tfoot>
        </table>
    </div>
</div>

<input type="hidden" value="0" id="shift_operator_other_sale_total">

<div class="table-responsive 1234" id="outside_other_sale_table" style="display: none;">
    <table class="table table-bordered table-striped" id="pump_operator_other_sale_table"
           style="width: 100%;">
        <thead>
        <tr>
            <th>@lang('petro::lang.code' )</th>
            <th>@lang('petro::lang.products' )</th>
            <th>@lang('petro::lang.balance_stock' )</th>
            <th>@lang('petro::lang.price')</th>
            <th>@lang('petro::lang.qty' )</th>
            <th>@lang('petro::lang.discount_type' )</th>
            <th>@lang('petro::lang.discount_value' )</th>
            <th>@lang('petro::lang.before_discount' )</th>
            <th>@lang('petro::lang.after_discount' )</th>
        </tr>
        </thead>

        <tfoot>
        <tr class="bg-gray font-17 footer-total">
            <td colspan="8" class="" style="color:brown">
                <strong>@lang('sale.total'):</strong></td>
            <td style="color:brown"><span class="display_currency" id="footer_list_other_sales_amount"
                                          data-currency_symbol="true"></span>
        </tr>
        </tfoot>
    </table>
</div>

<script id="is1762-petro-other-sale-default-store">
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    function selectDefaultStore(forceProductReload) {
        var $store = $('#store_id');
        if (!$store.length) {
            return;
        }

        var preferred = String($store.attr('data-default-store-id') || '');
        var target = '';

        if (preferred && $store.find('option[value="' + preferred.replace(/"/g, '\\"') + '"]').length) {
            target = preferred;
        } else {
            var $firstRealOption = $store.find('option').filter(function () {
                return String(this.value || '') !== '';
            }).first();
            target = $firstRealOption.length ? String($firstRealOption.val()) : '';
        }

        if (!target) {
            return;
        }

        var changed = String($store.val() || '') !== target;
        if (changed) {
            $store.val(target);
        }

        if ($store.data('select2')) {
            $store.trigger('change.select2');
        }

        if (changed || forceProductReload === true) {
            $store.trigger('change');
        }
    }

    $(function () {
        setTimeout(function () {
            selectDefaultStore(true);
        }, 0);
    });

    $(document)
        .off('shown.bs.tab.is1762OtherSaleStore')
        .on('shown.bs.tab.is1762OtherSaleStore', 'a[data-petro-main-tab="#other_sale_tab"]', function () {
            selectDefaultStore(false);
        })
        .off('ajaxComplete.is1762OtherSaleStore')
        .on('ajaxComplete.is1762OtherSaleStore', function (event, xhr, settings) {
            var url = settings && settings.url ? String(settings.url) : '';
            if (url.indexOf('/petro/get-stores-by-id') === -1) {
                return;
            }

            setTimeout(function () {
                selectDefaultStore(true);
            }, 0);
        });
})(window, document, window.jQuery);
</script>
