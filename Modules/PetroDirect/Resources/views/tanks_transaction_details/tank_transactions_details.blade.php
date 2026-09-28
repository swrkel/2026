<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('transaction_details_date_range', null, ['placeholder' =>
                        __('lang_v1.select_a_date_range'), 'class' =>
                        'form-control', 'id' => 'transaction_details_date_range', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_location_id', __('petrodirect::lang.business_location') . ':') !!}
                        {!! Form::select('transaction_details_location_id', $business_locations, null, ['class' =>
                        'form-control select2 daily_report_change',
                        'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_details_location_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_tank_number', __('petrodirect::lang.fuel_tank_number') . ':') !!}
                        {!! Form::select('transaction_details_tank_number', $tank_numbers, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_details_tank_number', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_product_id', __('petrodirect::lang.products') . ':') !!}
                        {!! Form::select('transaction_details_product_id', $products, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_details_product_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_settlement_id', __('petrodirect::lang.settlment_nos') . ':') !!}
                        {!! Form::select('transaction_details_settlement_id', $settlements, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_details_settlement_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('transaction_details_purhcase_no', __('petrodirect::lang.purhcase_no') . ':') !!}
                        {!! Form::select('transaction_details_purhcase_no', $purhcase_nos, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrodirect::lang.all'), 'id' => 'transaction_details_purhcase_no', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __(
    'petrodirect::lang.all_your_tank_transaction_details')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="tank_transaction_details_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.date_and_time')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.transaction_date')</th>
                    <th>@lang('petrodirect::lang.fuel_tank_number')</th>
                    <th>@lang('petrodirect::lang.product')</th>
                    <th>@lang('petrodirect::lang.type')</th>
                    <th>@lang('petrodirect::lang.settlement_purchase_invoice_no') / @lang('petrodirect::lang.transfer_no')</th>
                    <th>@lang('petrodirect::lang.starting_qty')</th>
                    <th>@lang('petrodirect::lang.purchase_qty') / @lang('petrodirect::lang.transferred_in')</th>
                    <th>@lang('petrodirect::lang.testing_qty')</th> <!-- new column -->
                    <th>@lang('petrodirect::lang.sold_qty') / @lang('petrodirect::lang.transferred_out')</th>
                    <th>@lang('petrodirect::lang.balance_qty')</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                    <td colspan="8"><strong>@lang('petrodirect::lang.total'):</strong></td>
                    <td><span class="display_currency" id="footer_transaction_total_purchase_qty"></span></td>
                    <td><span class="display_currency" id="footer_transaction_total_testing_qty"></span></td> <!-- new footer -->
                    <td><span class="display_currency" id="footer_transaction_sold_qty"></span></td>
                </tr>
            </tfoot>
        </table>

    </div>
    @endcomponent

</section>
<!-- /.content -->