<!-- Main content -->
<section class="content">

    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
                {!! Form::label('customer_payment_simple_payment_ref_no', __( 'lang_v1.ref_no' ) ) !!}
                <div class="input-group">
                    <div class="input-group-addon">
                        <i class="fa fa-link"></i>
                    </div>
                    {!! Form::text('customer_payment_simple_payment_ref_no', $latest_ref_number_CPS, ['class' => 'form-control
                    customer_payment_simple_fields',
                    'placeholder' => __(
                    'lang_v1.ref_no' ),'readonly'=>true ]); !!}
                </div>
                <small class="text-muted">Configured Customer Settings reference preview; final reference is assigned on Save.</small>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_payment_simple_customer_id', __('petro::lang.customer').':') !!}
                    {!! Form::select('customer_payment_simple_customer_id', $customers, request()->customer_id ?? null, ['class' => 'form-control
                    select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all')]); !!}
                    <p class="help-block"><strong>@lang('lang_v1.balance'):</strong> <span id="customer_simple_balance">0.00</span></p>
                </div>
            </div>
            <div class="payment_data_row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_payment_simple_payment_method', __('petro::lang.payment_method').':')
                        !!}
                        {!! Form::select('customer_payment_simple_payment_method',['cash' => 'Cash', 'card' => 'Card',
                        'cheque' =>
                        'Cheque'], null, ['class' => 'form-control
                        select2', 'style' => 'width: 100%;', 'placeholder' => __(
                        'petro::lang.please_select' ) ]); !!}
                    </div>
                </div>
                <div class="hide cheque_divs">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('customer_payment_simple_bank_name', __( 'petro::lang.bank_name' ) ) !!}
                            {!! Form::text('customer_payment_simple_bank_name', null, ['class' => 'form-control
                            customer_payment_simple_fields
                            bank_name',
                            'placeholder' => __(
                            'petro::lang.bank_name' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('customer_payment_simple_cheque_date', __( 'petro::lang.cheque_date' ) ) !!}
                            {!! Form::text('customer_payment_simple_cheque_date', null, ['class' => 'form-control
                            cheque_date',
                            'placeholder' => __(
                            'petro::lang.cheque_date' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('customer_payment_simple_cheque_number', __( 'petro::lang.cheque_number' ) )
                            !!}
                            {!! Form::text('customer_payment_simple_cheque_number', null, ['class' => 'form-control
                            customer_payment_simple_fields
                            cheque_number',
                            'placeholder' => __(
                            'petro::lang.cheque_number' ) ]); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('amount', __( 'petro::lang.amount' ) ) !!}
                        {!! Form::text('amount', request()->amount ?? null, ['class' => 'form-control customer_payment_simple_fields
                        customer_payment', 'required', 'id' => 'amount',
                        'placeholder' => __(
                        'petro::lang.amount' ) ]); !!}
                    </div>
                </div>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-primary btn_customer_payment"
                        style="margin-top: 23px;">@lang('messages.add')</button>
            </div>
        </div>
    </div>
    <br>
    <br>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-striped" id="customer_payment_simple_table">
                <thead>
                <tr>
                    <th>@lang('petro::lang.customer' )</th>
                    <th>@lang('lang_v1.ref_no' )</th>
                    <th>@lang('petro::lang.payment_method' )</th>
                    <th>@lang('petro::lang.bank_name' )</th>
                    <th>@lang('petro::lang.cheque_date' )</th>
                    <th>@lang('petro::lang.cheque_number' )</th>
                    <th>@lang('petro::lang.amount' )</th>
                    <th>@lang('petro::lang.action' )</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $customer_payment_simple_total = 0.00;
                @endphp
                @if (!empty($active_settlement))
                    @foreach ($active_settlement->customer_payments as $item)
                        @php
                            $customer_name = App\Contact::where('id', $item->customer_id)->select('name')->first()->name;
                            $customer_payment_simple_total += $item->amount;
                        @endphp
                        <tr>
                            <td>{{ $customer_name}}</td>
                            <td>{{ $item->payment_ref_no }}</td>
                            <td>{{ $item->payment_method }}</td>
                            <td>{{ $item->bank_name }}</td>
                            <td>{{ @format_date($item->cheque_date) }}</td>
                            <td>{{ $item->cheque_number }}</td>
                            <td>{{ @num_format($item->amount)}}</td>
                            <td>
                                <button class="btn btn-xs btn-danger delete_customer_payment_simple"
                                        data-href="/customers/customer-payment-simple/{{$item->id}}"><i
                                            class="fa fa-times"></i>
                            </td>
                        </tr>
                    @endforeach
                @endif

                </tbody>

                <tfoot>
                {{-- <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">@lang('petro::lang.total')
                        :</td>
                    <td style="text-align: left; font-weight: bold;" class="customer_payment_simple_total">
                        {{ @num_format( $customer_payment_simple_total)}}</td>
                </tr> --}}
                <input type="hidden" value="{{$customer_payment_simple_total}}" name="customer_payment_simple_total"
                       id="customer_payment_simple_total">
                </tfoot>
            </table>
        </div>
    </div>
    <input type="hidden" name="row_index" id="row_index" value="0">
    {!! Form::open(['url' => url('/customers/customer-payment-simple'), 'method' => 'post']) !!}
    <div class="row_data"></div>
    <div class="col-md-12">
        <button type="submit" class="btn btn-primary pull-right">@lang('lang_v1.save')</button>
    </div>
    <div class="clearfix"></div>
    {!! Form::close() !!}


</section>
<!-- /.content -->
