<div class="modal-dialog" role="document" style="width: 80%;max-height: 100vh;">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\Petro\Http\Controllers\DipManagementController@saveNewDip'),
            'method' => 'post',
            'id' => 'add_new_dip_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('petro::lang.add_new_dip')</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('ref_number', __('petro::lang.ref_number') . ':*') !!}
                            {!! Form::text('ref_number', $ref_no, [
                                'class' => 'form-control ref_number',
                                'required',
                                'readonly',
                                'placeholder' => __('petro::lang.ref_number'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date_and_time', __('petro::lang.date_and_time') . ':*') !!}
                            {!! Form::text('date_and_time', @format_datetime(date('Y-m-d H:i')), [
                                'class' => 'form-control',
                                'required',
                                'readonly',
                                'placeholder' => __('petro::lang.date_and_time'),
                            ]) !!}
                        </div>
                    </div>


                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('daily_report_date', __('petro::lang.daily_report_date') . ':*') !!}
                            {!! Form::text('daily_report_date', null, [
                                'class' => 'form-control date_and_time',
                                'required',
                                'readonly',
                                'placeholder' => __('petro::lang.daily_report_date'),
                            ]) !!}
                        </div>
                    </div>


                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('petro::lang.location') . ':*') !!}
                            {!! Form::select('location_id', $business_locations, !empty($default_location) ? $default_location : null, [
                                'class' => 'form-control select2
                                                        fuel_tank_location',
                                'required',
                                'placeholder' => __('petro::lang.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('tank_id', __('petro::lang.tanks') . ':') !!}
                            {!! Form::select('tank_id', $tanks, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('petro::lang.please_select'),
                                'id' => 'add_dip_tank_id',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>

                    @if ($tank_dip_chart_permission)
                        <div class="form-group col-sm-2">
                            {!! Form::label('dip_reading', __('petro::lang.dip_reading') . ':*') !!}
                            <div class="input-group">

                                {!! Form::select('dip_reading', [], null, [
                                    'class' => 'form-control dip_reading select2',
                                    'required',
                                    'placeholder' => __('petro::lang.please_select'),
                                ]) !!}

                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat btn-modal"
                                        data-href="{{ action('\Modules\Petro\Http\Controllers\DipManagementController@addDipChart') }}?quick_add=true"
                                        data-container=".modal_dip_modal">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('dip_reading', __('petro::lang.dip_reading') . ':*') !!}
                                {!! Form::text('dip_reading', null, [
                                    'class' => 'form-control dip_reading',
                                    'placeholder' => __('petro::lang.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    @endif

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('fuel_balance_dip_reading', __('petro::lang.tank_fuel_balance_dip_reading') . ':*') !!}
                            {!! Form::text('fuel_balance_dip_reading', null, [
                                'class' => 'form-control tank_fuel_balance_dip_reading',
                                'id' => 'fuel_balance_dip_reading',
                                'readonly',
                                'placeholder' => __('petro::lang.tank_fuel_balance_dip_reading'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('current_qty', __('petro::lang.current_qty') . ':') !!}
                            {!! Form::text('current_qty', null, [
                                'class' => 'form-control current_qty',
                                'id' => 'current_qty',
                                'readonly',
                                'placeholder' => __('petro::lang.current_qty'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="row">
                            <div class="col-md-9">
                                <div class="form-group">
                                    {!! Form::label('note', __('petro::lang.note')) !!}
                                    {!! Form::textarea('note', null, [
                                        'class' => 'form-control note',
                                        'rows' => 2,
                                        'placeholder' => __('petro::lang.note'),
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" id="add_tank_button" class="btn btn-success"
                                    style="margin-top: 30px">+</button>
                            </div>
                        </div>


                    </div>

                </div>
                <div class="row">
                    <table width="100%" id="tank_table">
                        <thead>
                            <tr>
                                <th>@lang('petro::lang.tanks')</th>
                                <th> @lang('petro::lang.dip_reading')</th>
                                <th>@lang('petro::lang.tank_fuel_balance_dip_reading')</th>
                                <th>@lang('petro::lang.current_qty')</th>
                                <th>@lang('petro::lang.note')</th>
                                <th>*</th>
                            </tr>
                        </thead>

                        <tbody>
                        </tbody>

                    </table>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary add_new_dip_reading_btn">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->


    </div><!-- /.modal-dialog -->


    <div class="modal fade modal_dip_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <script>
        $('#transaction_date').datepicker("setDate", new Date());

        $('#date_and_time').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });

        $('.select2').select2();
        $('#location_id').select2();

        $('.date_and_time').datepicker("setDate", new Date());

        $('#location_id option:eq(1)').attr('selected', true).trigger('change');

        @if (!$tank_dip_chart_permission)
            $('#fuel_balance_dip_reading').attr("readonly", false);
        @endif
        $('#add_dip_tank_id').change(function() {
            let tank_id = $(this).val();
            
            // Only make AJAX call if tank_id is selected
            if (!tank_id || tank_id === '' || tank_id === null) {
                return;
            }

            $.ajax({
                method: 'get',
                url: "/petro/get-tank-balance-by-id/" + tank_id,
                data: {},
                success: function(result) {
                    $('#tank_manufacturer').val(result.details.tank_manufacturer);
                    $('#tank_capacity').val(result.details.tank_capacity);
                    $('#current_qty').val(result.current_stock).trigger('change');

                    let html = '';
                    let dip_readings = result.dip_readings;
                    for (const [key, value] of Object.entries(dip_readings)) {
                        html += '<option value="' + value + '">' + key + '</option>';
                    }
                    $('#dip_reading').empty().append(html);
                    $('#dip_reading').trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching tank balance:', error);
                    toastr.error('Error fetching tank balance. Please try again.');
                }
            });
        });

        @if ($tank_dip_chart_permission)
            $('#dip_reading').change(function() {
                let tank_dip_reading = $(this).val();

                $.ajax({
                    method: 'get',
                    url: "/superadmin/tank-dip-chart-details/get-reading-value/" + tank_dip_reading,
                    data: {},
                    success: function(result) {
                        $('#fuel_balance_dip_reading').val(result.dip_reading_value).trigger('change');
                    },
                });
            })
        @endif







        // Function to toggle save button based on whether there are tanks in the table
        function toggleSaveButton() {
            var saveButton = $('.add_new_dip_reading_btn');
            var rowCount = $('#tank_table tbody tr').length;
            
            if (rowCount > 0) {
                saveButton.prop('disabled', false);
            } else {
                saveButton.prop('disabled', true);
            }
        }

        $(document).ready(function() {
            // Button click event
            $('#add_tank_button').click(function() {
                // Get input values
                var tankId = $('#add_dip_tank_id').val();
                var dipReading = $('#dip_reading').val();
                var fuelBalanceDipReading = $('#fuel_balance_dip_reading').val();
                var currentQty = $('#current_qty').val();
                var note = $('#note').val();
                var tankName = $('#add_dip_tank_id option:selected').text();


                // Validate required fields
                if (tankId === '' || dipReading === '' || fuelBalanceDipReading === '' || currentQty ===
                    '') {
                    toastr.error('Please fill in all the required fields.');
                    return;
                }


                // Create a new row
                var newRow = '<tr>' +
                    '<td>' + tankName + '</td>' +
                    '<td>' + dipReading + '</td>' +
                    '<td>' + fuelBalanceDipReading + '</td>' +
                    '<td>' + currentQty + '</td>' +
                    '<td>' + note + '</td>' +
                    '<td>' +
                    '<input type="hidden" name="tank_ids[]" value="' + tankId + '">' +
                    '<input type="hidden" name="dip_readings[]" value="' + dipReading + '">' +
                    '<input type="hidden" name="fuel_balance_dip_readings[]" value="' +
                    fuelBalanceDipReading + '">' +
                    '<input type="hidden" name="current_qtys[]" value="' + currentQty + '">' +
                    '<input type="hidden" name="notes[]" value="' + note + '">' +
                    '<button class="btn btn-danger remove_tank_button">-</button>' +
                    '</td>' +
                    '</tr>';

                // Append the new row to the table body
                $('#tank_table tbody').append(newRow);

                $('#add_dip_tank_id option:selected').remove();

                // Clear input values
                $('#dip_reading').val('').trigger('change');
                $('#fuel_balance_dip_reading').val('').trigger('change');
                $('#current_qty').val('').trigger('change');
                $('#note').val('');
                
                // Clear tank selection without triggering change event to avoid AJAX call
                $('#add_dip_tank_id').val('').trigger('change.select2');

                // Toggle save button state
                toggleSaveButton();
            });

            // Remove row button click event
            $('#tank_table').on('click', '.remove_tank_button', function() {
                // Remove the corresponding row
                $(this).closest('tr').remove();

                // Toggle save button state
                toggleSaveButton();
            });
            
            // Initialize save button state on page load
            toggleSaveButton();
        });

        $(document).ready(function() {
            const addTankButton = $('#add_tank_button');

            // Initially disable the + button
            addTankButton.prop('disabled', true);

            // Function to check if all required fields are filled
            function checkRequiredFields() {
                const tankId = $('#add_dip_tank_id').val();
                const dipReading = $('#dip_reading').val();
                const fuelBalanceDipReading = $('#fuel_balance_dip_reading').val();
                const currentQty = $('#current_qty').val();
                // Note field is optional, so we don't check it here

                if (tankId && dipReading && fuelBalanceDipReading && currentQty) {
                    addTankButton.prop('disabled', false);
                } else {
                    addTankButton.prop('disabled', true);
                }
            }

            // Run check whenever relevant fields change (note field excluded as it's optional)
            $('#add_dip_tank_id, #dip_reading, #fuel_balance_dip_reading, #current_qty')
                .on('change keyup', checkRequiredFields);

            // Also run it once initially in case some values are prefilled
            checkRequiredFields();
        });
    </script>
