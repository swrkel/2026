@php
    $mode = $mode ?? 'create';
@endphp

<div class="box box-primary">
    <div class="box-body">
        <ul class="nav nav-tabs product-form-tabs" role="tablist">
            <li class="active"><a href="#product_basic_tab" data-toggle="tab">@lang('product::product.basic_information')</a></li>
            <li><a href="#product_pricing_tab" data-toggle="tab">@lang('product::pricing.pricing')</a></li>
            <li><a href="#product_stock_tab" data-toggle="tab">@lang('product::stock.stock_settings')</a></li>
            <li><a href="#product_tax_tab" data-toggle="tab">@lang('product::tax.tax_settings')</a></li>
            <li><a href="#product_units_tab" data-toggle="tab">@lang('product::units.units')</a></li>
            <li><a href="#product_variations_tab" data-toggle="tab">@lang('product::variations.variations')</a></li>
            <li><a href="#product_barcode_tab" data-toggle="tab">@lang('product::barcode.barcode')</a></li>
            <li><a href="#product_images_tab" data-toggle="tab">@lang('product::product.images')</a></li>
            <li><a href="#product_custom_fields_tab" data-toggle="tab">@lang('product::product.custom_fields')</a></li>
        </ul>

        <div class="tab-content" style="padding-top:20px;">
            @include('product::products.tabs.basic', ['mode' => $mode])
            @include('product::products.tabs.pricing', ['mode' => $mode])
            @include('product::products.tabs.stock', ['mode' => $mode])
            @include('product::products.tabs.tax', ['mode' => $mode])
            @include('product::products.tabs.units', ['mode' => $mode])
            @include('product::products.tabs.variations', ['mode' => $mode])
            @include('product::products.tabs.barcode', ['mode' => $mode])
            @include('product::products.tabs.images', ['mode' => $mode])
            @include('product::products.tabs.custom_fields', ['mode' => $mode])
        </div>
    </div>

    <div class="box-footer text-right">
        <a href="{{ route('product.products.index') }}" class="btn btn-default">@lang('product::common.cancel')</a>
        <button type="submit" class="btn btn-primary">@lang('product::common.save')</button>
    </div>
</div>
