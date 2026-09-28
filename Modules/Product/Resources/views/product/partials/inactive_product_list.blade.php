@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp
<table class="table table-bordered table-striped ajax_view" id="inactive_product_table" style="width: 100%">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th class="plp-col-product">@lang('sale.product')</th>
            <th>@lang('purchase.business_location') @show_tooltip(__('lang_v1.product_business_location_tooltip'))</th>
            @can('view_purchase_price')
                <th class="plp-col-purchase-price">@lang('lang_v1.unit_perchase_price')</th>
            @endcan
            @can('access_default_selling_price')
                <th class="plp-col-selling-price">@lang('lang_v1.selling_price')</th>
            @endcan
            <th class="plp-col-current-stock">@lang('report.current_stock')</th>
            <th class="plp-col-product-type">@lang('product.product_type')</th>
            <th class="plp-col-category">@lang('product.category')</th>
            <th>@lang('product.brand')</th>
            <th>@lang('product.tax')</th>
            <th>@lang('product.sku')</th>
            <th>@lang('unit.semi_finished')</th>
            <th>{{ $custom_labels['product']['custom_field_1'] ?? '' }}</th>
            <th>{{ $custom_labels['product']['custom_field_2'] ?? '' }}</th>
            <th>{{ $custom_labels['product']['custom_field_3'] ?? '' }}</th>
            <th>{{ $custom_labels['product']['custom_field_4'] ?? '' }}</th>
        </tr>
    </thead>
</table>
