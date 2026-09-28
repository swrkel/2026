<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('transaction_summary_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'transaction_summary_date_range', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_location_id', __('petrodirect::lang.business_location') . ':') !!}
                    {!! Form::select('transaction_summary_location_id', $business_locations, null, ['class' =>
                    'form-control select2 daily_report_change',
                    'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_summary_location_id', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_product_id', __('petrodirect::lang.products') . ':') !!}
                    {!! Form::select('transaction_summary_product_id', $products, null, ['class' => 'form-control
                    select2 daily_report_change',
                    'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_summary_product_id', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transaction_summary_tank_number', __('petrodirect::lang.fuel_tank_number') . ':') !!}
                    {!! Form::select('transaction_summary_tank_number', $tank_numbers, null, ['class' => 'form-control
                    select2 daily_report_change',
                    'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_summary_tank_number', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __(
    'petrodirect::lang.all_your_tank_transaction_summary')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tank_transaction_summary_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.transaction_date')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.fuel_tank_number')</th>
                    <th>@lang('petrodirect::lang.product')</th>
                    <th>@lang('petrodirect::lang.tank_starting_stock')</th>
                    <th>@lang('petrodirect::lang.total_purchase') / @lang('petrodirect::lang.transferred_in') / @lang('petrodirect::lang.testing_in')</th>
                    <th>@lang('petrodirect::lang.total_sold_qty') / @lang('petrodirect::lang.transferred_out')</th>
                    <th>@lang('petrodirect::lang.balance_qty')</th>

                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                    <td colspan="5"><strong>@lang('petrodirect::lang.total'):</strong></td>
                    <td><span class="display_currency" id="footer_total_purchase_qty"></span></td>
                    <td><span class="display_currency" id="footer_sold_qty"></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent
</section>
<!-- /.content -->

