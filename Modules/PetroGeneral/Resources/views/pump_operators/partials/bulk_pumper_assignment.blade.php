<div class="modal-dialog" role="document" style="width: 65%;">
    <div class="modal-content">

        {!! Form::open(['url' =>
        action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@storeBulk'), 'method' =>
        'post',
        'id' =>
        'receive_pump_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <div style="display: flex">
                <h4 class="modal-title" style="width: 80%">@lang( 'petrogeneral::lang.assign_pumps' )</h4>
                {!! Form::label('shift_number', __( 'petrogeneral::lang.shift_number' ) . ' : ' . (sprintf("%04d", $shift_number + 1)), ['style' => 'font-size: 23px; color: red; font-weight: bold;']) !!}
            </div>
        </div>

        <div class="modal-body">
            @if($open_shift_assignments->count())
                <div>
                    <h4 style="color: red"><strong>Shifts Not Closed</strong></h4>

                    <div class="row">
                        @foreach($open_shift_assignments->groupBy('shift_number') as $shift_no => $rows)
                            <div class="col-md-4">
                                <p style="color: rgb(0, 179, 255)">
                                    <strong>Shift No:</strong> {{ sprintf('%04d', $shift_no) }}<br>
                                    <strong>Pump Operator:</strong> {{ optional($rows->first()->pumpOperator)->name }}<br>
                                    <strong>Pumps Assigned:</strong>
                                    {{ $rows->pluck('pump.pump_name')->implode(', ') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12">
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('date', __( 'petrogeneral::lang.date' ) . ':*') !!}
                                {!! Form::input('datetime-local', 'date', 
                                    \Carbon\Carbon::now()->format('Y-m-d\TH:i'), 
                                    ['class' => 'form-control', 'required', 'placeholder' => __('petrogeneral::lang.please_select'), 'style' => 'width: 100%;']) !!}

                            </div>
                        </div>


                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('work_shift', __('petrogeneral::lang.work_shift') . ':') !!}
                                {!! Form::select('work_shift', $work_shifts, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('petrogeneral::lang.please_select'),
                                    'style' => 'width: 100%;',
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('pump_operator', __( 'petrogeneral::lang.pump_operator' ) . ':*') !!}
                                {!! Form::select('pump_operator', $available_pump_operators->pluck('name', 'id'),
                                null , ['class' => 'form-control select2', 'id' => 'pump_operator_selector','required',
                                'placeholder' => __(
                                'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('pump', __( 'petrogeneral::lang.pump' ) . ':*') !!}
                                {!! Form::select('pump[]', $pumps,
                                null , ['id' => 'pump-selector', 'class' => 'form-control select2','multiple', 'style' => 'width: 100%;','required']); !!}
                            </div>
                        </div>
                    
                    </div>

                   
                </div>
            </div>
            <br>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary confirm_meter_reading_btn">@lang('messages.submit' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

   
    <script>
        $(".select2").select2();
        
        var isUpdating = false; // Flag to prevent loop
        $(document).ready(function () {
            // Reset button state when modal is shown
            $('.modal').on('shown.bs.modal', function () {
                $('.confirm_meter_reading_btn').prop('disabled', false).text('@lang('messages.submit')');
            });

            // Intercept form submit for reconfirmation
            $('#receive_pump_form').on('submit', function(e) {
                if ($(this).data('confirmed') === true) {
                    $('.confirm_meter_reading_btn').prop('disabled', true).text('Processing...');
                    return true;
                }
                
                e.preventDefault();

                var dateVal = $('input[name="date"]').val();
                var operatorVal = $('#pump_operator_selector option:selected').text();
                var pumpsVal = [];
                $('#pump-selector option:selected').each(function() {
                    pumpsVal.push($(this).text());
                });

                if (dateVal) {
                    var dt = new Date(dateVal);
                    if (!isNaN(dt.getTime())) {
                        var yyyy = dt.getFullYear();
                        var mm = String(dt.getMonth() + 1).padStart(2, '0');
                        var dd = String(dt.getDate()).padStart(2, '0');
                        var hh = String(dt.getHours()).padStart(2, '0');
                        var min = String(dt.getMinutes()).padStart(2, '0');
                        dateVal = yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + min;
                    }
                }

                $('#reconfirm_date').text(dateVal || 'N/A');
                $('#reconfirm_operator').text(operatorVal || 'N/A');
                $('#reconfirm_pumps').text(pumpsVal.join(', ') || 'N/A');

                $('#assign_pumps_reconfirm_modal').modal('show');
                return false;
            });

            $('#btn_reconfirm_yes').on('click', function() {
                var form = $('#receive_pump_form');
                form.data('confirmed', true);
                $('#assign_pumps_reconfirm_modal').modal('hide');
                form.submit();
            });

            $('#pump_operator_selector').change(function (e) {
                $('#pump-selector').val("").change();
            })
            
            $('#pump-selector').change(function (e) {
                if (isUpdating) return; // Prevent re-triggering
                
                let pumps = $(this).val();
                if (pumps && pumps.length) {
                    let pump_operator_id = $('#pump_operator_selector').val();
                    pump_operator_id = pump_operator_id ? parseInt(pump_operator_id) : "";
                
                    const pump_operators = @json($pump_operators);
                    const pump_assignments = @json($pump_assignments); // Get pump assignments data
                
                    // Find the current pump operator
                    const pump_operator = pump_operators.find(po => po.id === pump_operator_id) || null;
                
                    if (pump_operator) {
                        const all_pumps = @json($pumps);
                
                        // Iterate through selected pumps
                        let revert_pumps = []
                        pumps.forEach(pump_id => {
                            pump_id = parseInt(pump_id);
                            const assigned_operator = pump_assignments.find(pa => pa.pump_id === pump_id) || null;
                            if(assigned_operator) {
                                const assigned_pump_operator = pump_operators.find(apo => apo.id === assigned_operator.pump_operator_id);
                                // Only block if the operator has an assignment that is NOT settled.
                                // If they have a settlement_no, it means the shift was closed and settled.
                                if(assigned_pump_operator && !assigned_pump_operator.settlement_no) {
                                    revert_pumps.push(pump_id);
                                }
                            }
    
                        });
                        if(revert_pumps.length) {
                            // Prevent infinite loop by setting flag
                            isUpdating = true;
                            pumps = pumps.filter(pump => !revert_pumps.includes(pump));
                            $(this).val(pumps).trigger('change');
                            isUpdating = false; 
                            
                            const unsettled_pumps = revert_pumps.map(pump_id => all_pumps[pump_id])
            
                            alert(`Please complete the Settlement for ${unsettled_pumps.join(", ")} to select this pump again.`);
                        }
                    
                    }
                }

           }) 
        });
    </script>

<div class="modal fade" id="assign_pumps_reconfirm_modal" tabindex="-1" role="dialog" aria-labelledby="reconfirmModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="reconfirmModalLabel" style="font-weight: bold; color: red;">Reconfirmation</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 16px; color: black !important;">
                <p>Please confirm the pump assignment details:</p>
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 40%;">Date & Time:</th>
                        <td id="reconfirm_date"></td>
                    </tr>
                    <tr>
                        <th>Pump Operator:</th>
                        <td id="reconfirm_operator"></td>
                    </tr>
                    <tr>
                        <th>Assigned Pumps:</th>
                        <td id="reconfirm_pumps"></td>
                    </tr>
                </table>
                <p style="font-weight: bold; margin-top: 15px;">Are you sure you want to assign these pumps?</p>
            </div>
            <div class="modal-footer" style="display: block; overflow: hidden;">
                <button type="button" class="btn btn-success" id="btn_reconfirm_yes" style="float: left; min-width: 100px;">Yes</button>
                <button type="button" class="btn btn-danger" id="btn_reconfirm_no" data-dismiss="modal" style="float: right; min-width: 100px;">No</button>
            </div>
        </div>
    </div>
</div>