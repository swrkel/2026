<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\PumperDayEntryController@update',
        $day_entry->id), 'method' => 'put', 'id' =>
        'edit_pumper_day_entry' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.edit_pumper_day_entry' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('date', __( 'petrogeneral::lang.date' ) . ':*') !!}
                    {!! Form::text('date', null, ['class' => 'form-control date', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.date' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('pump_operator_id', __( 'petrogeneral::lang.pump_operator' ) . ':*') !!}
                    {!! Form::select('pump_operator_id', $pump_operators, $day_entry->pump_operator_id , ['class' =>
                    'form-control select2
                    pump_operator_id', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('pump_id', __( 'petrogeneral::lang.pump' ) . ':*') !!}
                    {!! Form::select('pump_id', $pumps, $day_entry->pump_id , ['class' => 'form-control select2
                    pump_id', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('starting_meter', __( 'petrogeneral::lang.starting_meter' ) . ':*') !!}
                    {!! Form::text('starting_meter', $day_entry->starting_meter, ['class' => 'form-control
                    starting_meter input_number', 'id' => 'starting_meter', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.starting_meter' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('closing_meter', __( 'petrogeneral::lang.closing_meter' ) . ':*') !!}
                    {!! Form::text('closing_meter', $day_entry->closing_meter, ['class' => 'form-control closing_meter
                    input_number', 'id' => 'closing_meter', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.closing_meter' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('testing_ltr', __( 'petrogeneral::lang.testing_ltr' ) . ':*') !!}
                    {!! Form::text('testing_ltr', @format_quantity($day_entry->testing_ltr), ['class' => 'form-control
                    fuel_tank_date', 'id' => 'testing_ltr',
                    'required', 'placeholder' => __(
                    'petrogeneral::lang.testing_ltr' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('sold_ltr', __( 'petrogeneral::lang.sold_ltr' ) . ':*') !!}
                    {!! Form::text('sold_ltr', @format_quantity($day_entry->sold_ltr), ['class' => 'form-control
                    fuel_tank_date', 'id' => 'sold_ltr',
                    'required', 'placeholder' => __(
                    'petrogeneral::lang.sold_ltr' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('amount', __( 'petrogeneral::lang.amount' ) . ':*') !!}
                    {!! Form::text('amount', @num_format($day_entry->amount), ['class' => 'form-control fuel_tank_date',
                    'id' => 'amount', 'required', 'placeholder' => __(
                    'petrogeneral::lang.amount' ) ]); !!}
                </div>
            </div>

            <input type="hidden" name="sale_price" id="sale_price" value="{{$pump_details->default_sell_price}}">
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $('.date').datepicker('setDate', '{{@format_date($day_entry->date)}}')

        $('#closing_meter').change(function () {
            let sale_price = parseFloat($('#sale_price').val());
            let closing_meter = parseFloat($(this).val());
            let starting_meter = parseFloat($('#starting_meter').val());
            let testing_ltr = parseFloat($('#testing_ltr').val()) || 0;
            
            if(closing_meter < starting_meter){
                toastr.error('Closing meter value should not less then starting meter value');
                $(this).val(''); // Clear invalid value
                $('#sold_ltr').val('');
                $('#amount').val('');
                return false;
            }
            
            let sold_ltr = closing_meter - starting_meter - testing_ltr;
            $('#sold_ltr').val(sold_ltr);
            let total_amount = sale_price * sold_ltr;
            __write_number($('#amount'), total_amount);
        });
        
        // Add form validation on submit
        $('#edit_pumper_day_entry').on('submit', function(e) {
            let closing_meter = parseFloat($('#closing_meter').val());
            let starting_meter = parseFloat($('#starting_meter').val());
            
            if(closing_meter < starting_meter){
                e.preventDefault();
                toastr.error('Closing meter value should not less then starting meter value');
                return false;
            }
        });
    </script>