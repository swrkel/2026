<!-- Main content -->
<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                @if (empty($only_pumper))
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('payment_summary_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('payment_summary_location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('petro::lang.all'),
                                'id' => 'payment_summary_location_id',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('payment_summary_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('payment_summary_date_range', @format_date('today') . ' ~ ' . @format_date('today'), [
                                'class' => 'form-control',
                                'id' => 'payment_summary_date_range',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                @endif

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_pump_operators', __('petro::lang.pump_operator') . ':') !!}
                        {!! Form::select('payment_summary_pump_operators', $pump_operators, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petro::lang.all'),
                            'id' => 'payment_summary_pump_operators',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_shift_id', __('petro::lang.shift_number') . ':') !!}
                        <select class="form-control select2" style='width:100%' id="payment_summary_shift_id">
                            @if (empty($only_pumper))
                                <option value="">@lang('lang_v1.all')</option>
                            @endif

                            @php
                                $latest_shift_id = optional($shifts->sortByDesc('id')->first())->id;
                            @endphp

                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ $shift->id == $latest_shift_id ? 'selected' : '' }}>
                                    {{ $shift->id }} -
                                    ({{ @format_date($shift->shift_date) }} to
                                    {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>






                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_payment_method', __('petro::lang.payment_method') . ':') !!}
                        {!! Form::select('payment_summary_payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petro::lang.all'),
                            'id' => 'payment_summary_payment_method',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_customer', __('petro::lang.customer') . ':') !!}
                        {!! Form::select('payment_summary_customer', $customers, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petro::lang.all'),
                            'id' => 'payment_summary_customer',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_slip_no', __('petro::lang.slip_no') . ':') !!}
                        {!! Form::text('payment_summary_slip_no', null, [
                            'class' => 'form-control',
                            'placeholder' => 'Enter Slip No',
                            'id' => 'payment_summary_slip_no',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_order_no', __('petro::lang.order_no') . ':') !!}
                        {!! Form::text('payment_summary_order_no', null, [
                            'class' => 'form-control',
                            'placeholder' => 'Enter order no',
                            'id' => 'payment_summary_order_no',
                        ]) !!}
                    </div>
                </div>
            @endcomponent

        </div>
    </div>


    @component('components.widget', ['class' => 'box-primary', 'title' => __('petro::lang.all_your_payments')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="pump_operators_payment_summary_table"
                style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('petro::lang.action')</th>
                        <th>@lang('petro::lang.date')</th>
                        <th>@lang('petro::lang.location')</th>
                        <th>@lang('petro::lang.time')</th>
                        <th>@lang('petro::lang.pump_operator')</th>
                        <th>@lang('petro::lang.shift_number')</th>
                        <th>@lang('petro::lang.collection_form_no')</th>
                        <th>@lang('petro::lang.payment_type')</th>
                        <th>Customer</th>
                        <th>@lang('petro::lang.slip_no')</th>
                        <th>@lang('petro::lang.order_no')</th>
                        <th>@lang('petro::lang.amount')</th>
                        @if (empty($only_pumper))
                            <th>@lang('petro::lang.note')</th>
                            <th>@lang('petro::lang.edited_by')</th>
                        @endif
                    </tr>
                </thead>

                <tfoot>
                    <tr class="bg-gray font-17 footer-total">
                        <td colspan="11" class="text-right" style="color:brown">
                            <strong>@lang('sale.total'):</strong>
                        </td>
                        <td style="color:brown"><span class="display_currency" id="footer_payment_summary_amount"
                                data-currency_symbol="true"></span>
                            @if (empty($only_pumper))
                        <td></td>
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->
