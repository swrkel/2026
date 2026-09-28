@php
    $business_id = session()->get('user.business_id');
@endphp
<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\DailyCollectionSW\Http\Controllers\DailyCollectionSWController@store'),
            'method' => 'post',
            'id' => 'add_pumps_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('petro::lang.add_collection')</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('petro::lang.transaction_date') . ':*') !!}
                            {!! Form::text('transaction_date', date('m/d/Y'), [
                                'class' => 'form-control transaction_date',
                                'required',
                                'placeholder' => __('petro::lang.transaction_date'),
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('collection_form_no', __('petro::lang.collection_form_no') . ':*') !!}
                            {!! Form::text('collection_form_no', $collection_form_no, [
                                'class' => 'form-control collection_form_no',
                                'required',
                                'placeholder' => __('petro::lang.collection_form_no'),
                                'readonly',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('pump_operator', __('petro::lang.pump_operator') . ':*') !!}
                            {!! Form::select('pump_operator_id', $pump_operators, null, [ {{-- Changed from $assigned_operators to $pump_operators --}}
                                'class' => 'form-control select2 pump_operator',
                                'required',
                                'id' => 'pump_operator_id',
                                'placeholder' => __('petro::lang.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('daily_shift_no', 'Daily Shift no:*') !!}
                            <select name="daily_shift_no" id="daily_shift_no" class="form-control select2" required
                                style="width: 100%;">
                                <option value="">{{ __('petro::lang.please_select') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('balance_collection', __('petro::lang.balance_collection') . ':*') !!}
                            {!! Form::text('balance_collection', null, [
                                'class' => 'form-control balance_collection input_number',
                                'required',
                                'placeholder' => __('petro::lang.balance_collection'),
                                'readonly' => 'readonly',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('current_amount', __('petro::lang.current_amount') . ':*') !!}
                            {!! Form::text('current_amount', null, [
                                'class' => 'form-control current_amount input_number',
                                'required',
                                'placeholder' => __('petro::lang.current_amount'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('location_id', __('petro::lang.location') . ':*') !!}
                            {!! Form::select('location_id', $locations, !empty($default_location) ? $default_location : null, [
                                'class' => 'form-control select2 location_id',
                                'required',
                                'placeholder' => __('petro::lang.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>

                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $(document).ready(function() {
            $('.location_id').select2();
            $('.pump_operator').select2();
            $('#daily_shift_no').select2();

            // Operator to shift mapping - using the corrected structure from backend
            const operatorShiftsMap = @json($operatorShiftsMap);

            setTimeout(() => {
                fetchBalance();
            }, 200);

            $('#pump_operator_id').on('change', function() {
                const operatorId = $(this).val();
                const $shiftSelect = $('#daily_shift_no');


                // Clear and reset shift dropdown
                $shiftSelect.empty();
                $shiftSelect.append('<option value="">{{ __('petro::lang.please_select') }}</option>');

                if (operatorId && operatorShiftsMap[operatorId]) {
                    const shiftValue = operatorShiftsMap[operatorId];
                    
                    // If shift value exists and is not empty
                    if (shiftValue && shiftValue !== '') {
                        $shiftSelect.append(
                            $('<option>', {
                                value: shiftValue,
                                text: shiftValue,
                                selected: true
                            })
                        );
                    } else {
                    }

                    // Refresh Select2
                    $shiftSelect.trigger('change.select2');
                } else {
                    $shiftSelect.trigger('change.select2');
                }

                // Get balance collection via AJAX
                if (operatorId) {
                    $.ajax({
                        method: 'get',
                        url: '/dailycollectionsw/daily-collection-sw/get-balance-collection/' + operatorId,
                        data: {
                            shift_no: $('#daily_shift_no').val()
                        },
                        success: function(result) {
                            if (result) {
                                $('#balance_collection').val(result.balance_collection);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching balance collection:', error);
                        }
                    });
                }
            });

            // Auto-select first operator if only one exists
            @if (count($pump_operators) == 1)
                const firstOperatorId = Object.keys(@json($pump_operators))[0];
                $('#pump_operator_id').val(firstOperatorId).trigger('change');
            @endif

            // Trigger change on page load if there's a preselected operator
            @if (!empty(old('pump_operator_id')))
                $('#pump_operator_id').val('{{ old('pump_operator_id') }}').trigger('change');
            @endif
        });

        function fetchBalance() {
    const operatorId = $('#pump_operator_id').val();
    const shiftNo = $('#daily_shift_no').val();

    if (operatorId && shiftNo) {
        $.ajax({
            method: 'get',
            url: '/dailycollectionsw/daily-collection-sw/get-balance-collection/' + operatorId,
            data: { shift_no: shiftNo },
            success: function (result) {
                $('#balance_collection').val(result.balance_collection ?? 0);
            },
            error: function (xhr) {
                console.error('Error fetching balance:', xhr.responseText);
            }
        });
    }
}
    </script>
</div>