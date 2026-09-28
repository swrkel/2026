<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => route('petropd.day-end-settlements.update', $day_end->id), 'method' =>
        'put',
        'id' =>
        'edit_day_end' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petropd::lang.day_end_settlement' )</h4>
        </div>

        <div class="modal-body">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#pump_details" data-toggle="tab" aria-expanded="true">@lang('petropd::lang.pump_details')</a>
                    </li>
                    <li>
                        <a href="#pos_details" data-toggle="tab" aria-expanded="false">@lang('petropd::lang.pos_details')</a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Pump Details Tab -->
                    <div class="tab-pane active" id="pump_details">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('date_and_time', __( 'petropd::lang.date_and_time' ) . ':*') !!}
                                        {!! Form::text('date_and_time', @format_datetime($day_end->created_at), ['class' => 'form-control', 'required', 'readonly',
                                        'placeholder' => __(
                                        'petropd::lang.date_and_time' ) ]); !!}
                                    </div>
                                </div>
                                
                                
                                 <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('day_end_date', __( 'petropd::lang.day_end_date' ) . ':*') !!}
                                        {!! Form::text('day_end_date', null, ['class' => 'form-control date_and_time', 'required', 'readonly',
                                        'placeholder' => __(
                                        'petropd::lang.day_end_date' ) ]); !!}
                                    </div>
                                </div>
                                
                                <div class="clearfix"></div>
                               <div class="row">
                                    <div class="col-sm-6">
                                        <h5 class="text-center"><b>@lang('petropd::lang.pending_pumps')</b></h5><hr><br>
                                        <div class="col-md-6"><b>@lang('petropd::lang.pump')</b></div>
                                        <div class="col-md-6"><b>@lang('petropd::lang.no_operation')</b></div>
                                        <div class="clearfix"></div>
                                        @foreach($pumps_ as $key => $pump)
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5><b>{{$pump}}</b></h5>
                                                </div>
                                                <div class="col-md-6">
                                                    {!! Form::checkbox('pumps[]', $key, $key , ['required']); !!}
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    <div class="col-sm-6">
                                        <h5 class="text-center"><b>@lang('petropd::lang.pumps_in_settlement')</b></h5><hr>
                                        <div class="col-md-6"><b>@lang('petropd::lang.pump')</b></div>
                                        <div class="col-md-6"><b>@lang('petropd::lang.settlement_no')</b></div>
                                        <div class="clearfix"></div>
                                        <div class="sold_pumps">{!! $html_sold !!}</div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                    <!-- POS Details Tab -->
                    <div class="tab-pane" id="pos_details">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4 class="text-center" style="color: #007bff; margin-bottom: 20px;">
                                        <b>@lang('petropd::lang.total_pos_amount_today')</b>
                                    </h4>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        {!! Form::label('pos_transaction_ids', __( 'petropd::lang.pos_details' ) . ':') !!}
                                        <select name="pos_transaction_ids[]" class="form-control select2" id="pos_transaction_ids" multiple style="width: 100%;">
                                            @include('petropd::day_end_settlement.partials.pos_transactions_options', ['pos_transactions' => $pos_transactions ?? [], 'selected_pos_transaction_ids' => $selected_pos_transaction_ids ?? []])
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('total_pos_cash_amount', __( 'petropd::lang.total_pos_cash_amount' ) . ':') !!}
                                        {!! Form::text('total_pos_cash_amount', number_format($total_pos_payments, 2), ['class' => 'form-control input_number', 'readonly', 'placeholder' => __( 'petropd::lang.total_pos_cash_amount' ) ]); !!}
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('total_pos_sales', __( 'petropd::lang.total_pos_sales' ) . ':') !!}
                                        {!! Form::text('total_pos_sales', number_format($total_pos_sales, 2), ['class' => 'form-control input_number', 'readonly', 'placeholder' => __( 'petropd::lang.total_pos_sales' ) ]); !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
      
        $('.date_and_time').datepicker("setDate", new Date('{{@format_date($day_end->day_end_date)}}'));

        $(document).on('change', '#pos_transaction_ids', function(){
            $.ajax({
                url: '/petropd/day-end-settlement-pos-totals',
                type: 'GET',
                data: {
                    date: $('.date_and_time').val(),
                    pos_transaction_ids: $(this).val()
                },
                success: function(response) {
                    if(response.total_pos_payments !== undefined) {
                        $('input[name="total_pos_cash_amount"]').val(response.total_pos_payments);
                    }
                    if(response.total_pos_sales !== undefined) {
                        $('input[name="total_pos_sales"]').val(response.total_pos_sales);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error: ', error);
                }
            });
        });
        $('.select2').select2();
    </script>