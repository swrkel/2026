<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\DayEndSettlementController@store'), 'method' =>
        'post',
        'id' =>
        'add_day_end' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.day_end_settlement' )</h4>
        </div>

        <div class="modal-body">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#pump_details" data-toggle="tab" aria-expanded="true">@lang('petrogeneral::lang.pump_details')</a>
                    </li>
                    <li>
                        <a href="#pos_details" data-toggle="tab" aria-expanded="false">@lang('petrogeneral::lang.pos_details')</a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Pump Details Tab -->
                    <div class="tab-pane active" id="pump_details">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('date_and_time', __( 'petrogeneral::lang.date_and_time' ) . ':*') !!}
                                        {!! Form::text('date_and_time', @format_datetime(date('Y-m-d H:i')), ['class' => 'form-control', 'required', 'readonly',
                                        'placeholder' => __(
                                        'petrogeneral::lang.date_and_time' ) ]); !!}
                                    </div>
                                </div>
                                
                                
                                 <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('day_end_date', __( 'petrogeneral::lang.day_end_date' ) . ':*') !!}
                                        {!! Form::text('day_end_date', null, ['class' => 'form-control date_and_time', 'required', 'readonly',
                                        'placeholder' => __(
                                        'petrogeneral::lang.day_end_date' ) ]); !!}
                                        <input type="hidden" name="day_end_date_iso" id="day_end_date_iso" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                
                                <div class="clearfix"></div>
                                
                                <hr>
                                <div class="clearfix"></div>
                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <h5 class="text-center"><b>@lang('petrogeneral::lang.pending_pumps')</b></h5><hr><br>
                                        <div class="col-md-6"><b>@lang('petrogeneral::lang.pump')</b></div>
                                        <div class="col-md-6"><b>@lang('petrogeneral::lang.no_operation')</b></div>
                                        <div class="clearfix"></div>
                                        <div class="pending_pumps">
                                            @foreach($pumps as $key => $pump)
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h5><b>{{$pump}}</b></h5>
                                                    </div>
                                                    <div class="col-md-6">
                                                        {!! Form::checkbox('pumps[]', $key, null , []); !!}
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-6">
                                        <h5 class="text-center"><b>@lang('petrogeneral::lang.pumps_in_settlement')</b></h5><hr>
                                        <div class="col-md-6"><b>@lang('petrogeneral::lang.pump')</b></div>
                                        <div class="col-md-6"><b>@lang('petrogeneral::lang.settlement_no')</b></div>
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
                                        <b>@lang('petrogeneral::lang.total_pos_amount_today')</b>
                                    </h4>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        {!! Form::label('pos_transaction_ids', __( 'petrogeneral::lang.pos_details' ) . ':') !!}
                                        <select name="pos_transaction_ids[]" class="form-control select2" id="pos_transaction_ids" multiple style="width: 100%;">
                                            @include('petrogeneral::petro_settings.partials.pos_transactions_options', ['pos_transactions' => $pos_transactions ?? [], 'selected_pos_transaction_ids' => $selected_pos_transaction_ids ?? []])
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('total_pos_cash_amount', __( 'petrogeneral::lang.total_pos_cash_amount' ) . ':') !!}
                                        {!! Form::text('total_pos_cash_amount', number_format($total_pos_payments, 2), ['class' => 'form-control input_number', 'readonly', 'placeholder' => __( 'petrogeneral::lang.total_pos_cash_amount' ) ]); !!}
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('total_pos_sales', __( 'petrogeneral::lang.total_pos_sales' ) . ':') !!}
                                        {!! Form::text('total_pos_sales', number_format($total_pos_sales, 2), ['class' => 'form-control input_number', 'readonly', 'placeholder' => __( 'petrogeneral::lang.total_pos_sales' ) ]); !!}
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
      
        function petroGeneralDayEndIsoDate() {
            var $dateInput = $('#add_day_end .date_and_time');
            var selectedDate = null;

            try {
                selectedDate = $dateInput.datepicker('getDate');
            } catch (e) {
                selectedDate = null;
            }

            if (!(selectedDate instanceof Date) || isNaN(selectedDate.getTime())) {
                return $('#day_end_date_iso').val() || '';
            }

            var year = selectedDate.getFullYear();
            var month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            var day = String(selectedDate.getDate()).padStart(2, '0');
            var iso = year + '-' + month + '-' + day;
            $('#day_end_date_iso').val(iso);
            return iso;
        }

        $('#add_day_end .date_and_time').datepicker("setDate", new Date());
        petroGeneralDayEndIsoDate();

        // Always submit the same unambiguous ISO date used by the AJAX loaders.
        $(document).off('submit.dayEndIso', '#add_day_end').on('submit.dayEndIso', '#add_day_end', function(){
            petroGeneralDayEndIsoDate();
        });
        
        // Namespaced + off() so re-opening the modal does not stack duplicate handlers.
        $(document).off('change.dayEndPumps changeDate.dayEndPumps').on('change.dayEndPumps changeDate.dayEndPumps','#add_day_end .date_and_time',function(){
            var isoDate = petroGeneralDayEndIsoDate();
            $.ajax({
                url: "{{ route('petrogeneral.day_end_settlement.pumps') }}",
                type: 'GET',
                data: {
                    date: isoDate
                },
                success: function(response) {
                    // Append the response to the pending_pumps div
                    $('.pending_pumps').html(response.pending);
                    $('.sold_pumps').html(response.sold);

                    if(response.pos_transactions_options !== undefined) {
                        $('#pos_transaction_ids').html(response.pos_transactions_options);
                        if(response.selected_pos_transaction_ids !== undefined) {
                            $('#pos_transaction_ids').val(response.selected_pos_transaction_ids);
                        }
                        $('#pos_transaction_ids').trigger('change');
                    }

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

        $(document).off('change.dayEndPos').on('change.dayEndPos', '#add_day_end #pos_transaction_ids', function(){
            $.ajax({
                url: "{{ route('petrogeneral.day_end_settlement.pos_totals') }}",
                type: 'GET',
                data: {
                    date: petroGeneralDayEndIsoDate(),
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