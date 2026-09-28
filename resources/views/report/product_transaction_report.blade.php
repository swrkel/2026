<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.product_transaction_report') }}</h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open([
                    'url' => action('ReportController@getProductTransactionReport'),
                    'method' => 'get',
                    'id' => 'product_transaction_report_filter_form',
                ]) !!}

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_location_id',
                        ]) !!}
                    </div>
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_store_id', __('lang_v1.store_id') . ':') !!}
                        {!! Form::select('store_id', $stores->pluck('name','id'), null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_store_id',
                            'placeholder' => __('lang_v1.all')
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_category_id', __('category.category') . ':') !!}
                        {!! Form::select('category_id', $categories, null, [
                            'placeholder' => __('messages.all'),
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_category_id',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_sub_category_id', __('product.sub_category') . ':') !!}
                        {!! Form::select('sub_category_id', [], null, [
                            'placeholder' => __('messages.all'),
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_sub_category_id',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_product', __('report.product') . ':') !!}
                        {!! Form::select('product_id', $products, null, [
                            'placeholder' => __('messages.all'),
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_product',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_brand', __('product.brand') . ':') !!}
                        {!! Form::select('brand_id', $brands, null, [
                            'placeholder' => __('messages.all'),
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_brand',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_unit', __('product.unit') . ':') !!}
                        {!! Form::select('unit_id', $units, null, [
                            'placeholder' => __('messages.all'),
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'id' => 'product_transaction_unit',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_transaction_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', @format_date('today') . ' ~ ' . @format_date('today'), [
                            'placeholder' => __('lang_v1.select_a_date_range'),
                            'class' => 'form-control',
                            'id' => 'product_transaction_date_range',
                            'readonly',
                        ]) !!}
                    </div>
                </div>

                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    {{-- Shared summary section (used by both tabs) --}}
    @include('report.partials.report_summary_section')

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div id="table_div">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="product_transaction_report_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="notexport">@lang('messages.action')</th>
                                    <th>@lang('report.date_and_time')</th>
                                    <th>@lang('report.sku')</th>
                                    <th>@lang('report.product')</th>
                                    <th>@lang('report.description')</th>
                                    <th>@lang('report.store_name')</th>
                                    <th class="text-right">@lang('report.starting_qty')</th>
                                    <th class="text-right">@lang('report.purchase_qty')</th>
                                    <th class="text-right">@lang('report.bonus_qty')</th>
                                    <th class="text-right">@lang('report.sold_qty')</th>
                                    <th class="text-right">@lang('report.balance_qty')</th>
                                    <th class="text-right">@lang('report.balance_qty_value')</th>
                                    <th class="text-right">@lang('report.stock_value_date_wise')</th>
                                    <th>@lang('lang_v1.product_added_date')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <input type="hidden" id="product_transaction_report_ajax_url"
       value="{{ action('ReportController@getProductTransactionReport') }}">

</section>
<!-- /.content -->
