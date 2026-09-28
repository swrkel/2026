@php
    function incrementCode(string $code): string
    {
        $parts = explode('-', $code);

        if (count($parts) !== 2) {
            error_log('Invalid code format');
            return $code;
        }

        $prefix = $parts[0];
        $number = $parts[1];
        $numberLength = strlen($number);

        $incremented = str_pad(intval($number) + 1, $numberLength, '0', STR_PAD_LEFT);

        return $prefix . '-' . $incremented;
    }
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('petro::lang.daily_voucher', ['contacts' => __('petro::lang.mange_daily_voucher')])</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petro::lang.daily_voucher')</a></li>
                    <li><span>@lang('petro::lang.daily_voucher', ['contacts' => __('petro::lang.mange_daily_voucher')])</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="form-group">
            {!! Form::hidden('open_shift', $daily_shift_id ?? null, ['id' => 'open_shift_field']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 d-flex justify-content-center align-items-center">
            <button type="button" id="open_new_shift" class="btn btn-primary btn-modal"
                @if (!empty($dailyShift) && $dailyShift->status == 1) disabled @endif>
                @lang('petro::lang.open_new_shift')
            </button>
        </div>

    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="col-sm-3">
                <div class="form-group">

                    {!! Form::label('dv_pump_operator-left', __('petro::lang.pending_assignment') . ':') !!}
                    @if (!empty($dailyShift))
                        {!! Form::select('dv_pump_operator-left[]', $pump_operators_shift, array_keys($pump_operators_shift), [
                            'class' => 'form-control',
                            'multiple' => 'multiple',
                            'size' => '8',
                            'id' => 'left-list',
                            'disabled' => $dailyShift->status == 2,
                        ]) !!}
                    @else
                        {!! Form::select('dv_pump_operator-left[]', $pump_operators_shift, array_keys($pump_operators_shift), [
                            'class' => 'form-control',
                            'multiple' => 'multiple',
                            'size' => '8',
                            'id' => 'left-list',
                        ]) !!}
                    @endif
                </div>
            </div>
            <div class="col-sm-3 d-flex flex-column justify-content-center align-items-center">
                <div class="form-group" style="width: 50%;"> <!-- Increased width -->
                    <button type="button" @disabled($dailyShift->status != 1) class="btn btn-block"
                        style="margin-bottom: 20px;margin-top: 60px; background-color: green; color: white;"
                        id="add_row-right">
                        <i class="fa fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    <button type="button" @disabled($dailyShift->status != 1) class="btn btn-block"
                        style="background-color: orange; color: white;" id="add_row-left">
                        <i class="fa fa-arrow-left" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('dv_pump_operator-right', __('petro::lang.assign_for_today') . ':') !!}
                    {!! Form::select('dv_pump_operator-right[]', $assigned_operators ?? [], null, [
                        'class' => 'form-control',
                        'multiple' => 'multiple',
                        'size' => '8',
                        'id' => 'right-list',
                    ]) !!}
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    {!! Form::label('day_counted_from', __('petro::lang.date') . ':') !!}
                    {!! Form::date('day_counted_from', \Carbon\Carbon::today()->format('Y-m-d'), [
                        'class' => 'form-control',
                        'id' => 'day_counted_from',
                    ]) !!}
                </div>
            </div>
            <div class="col-sm-1">
                <div class="form-group">
                    {!! Form::label('time_till', __('petro::lang.time') . ':') !!}
                    {!! Form::time('time_till', null, [
                        'class' => 'form-control',
                        'id' => 'time_till',
                        'style' => 'min-width: 120px;', // adjust as needed
                    ]) !!}
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const now = new Date();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const currentTime = `${hours}:${minutes}`;
                        document.getElementById('time_till').value = currentTime;
                    });
                </script>
            </div>
            <div class="col-sm-3">
                {!! Form::label('daily_shift_no', __('petro::lang.daily_shift_no') . ':') !!}
                <span id="display_shift_no" data-shift="{{ $daily_shift_no ?? 'DCSW-001' }}">
                    {{ $daily_shift_no }}
                </span>
            </div>
            <div class="col-sm-3">
                {!! Form::label('user', __('petro::lang.user') . ':') !!}
                <span class="username-display">
                    {{ auth()->user()->username }}
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="d-flex flex-row gap-4 justify-content-end align-items-center"
            style="    justify-content: right;
    gap: 30px; flex-direction: row-reverse;">

            <div class="col-sm-1 flex-start" style="margin-right:10px;">
                <button type="button" @disabled($dailyShift->status != 1) id="shift_closes"
                    class="btn {{ $dailyShift->status }} btn-primary" style="background-color: purple; color: white;"
                    @if (empty($dailyShift->pump_operator_assigned)) disabled @endif>
                    @if ($dailyShift->status == 2)
                        @lang('petro::lang.shift_closed')
                    @else
                        @lang('petro::lang.close_daily_shift')
                    @endif
                </button>
            </div>
            <div class="col-sm-1 ">
                <button type="button" class="btn btn-primary" style="background-color: green; color: white;"
                    id="editShift" @disabled($dailyShift->status != 2)
                    data-action="{{ route('daily-shift.edit-save-shift', [$daily_shift_id]) }}"
                    data-shift="{{ $daily_shift_id }}">
                    @lang('petro::lang.edit')
                </button>
            </div>
        </div>
    </div>
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmCloseShiftModal" tabindex="-1" role="dialog"
        aria-labelledby="confirmCloseShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCloseShiftModalLabel">
                        Save Daily Shift
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you Sure to Save the Daily Shift?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        No
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmCloseShift">
                        Yes
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
    const pumpOperatorsMap = @json($pump_operators);

    function incrementCode(code) {
        const parts = code.split('-');
        if (parts.length !== 2) {
            console.error('Invalid code format');
            return code;
        }
        const prefix = parts[0];
        const number = parts[1];
        const numberLength = number.length;
        const incremented = (parseInt(number, 10) + 1).toString().padStart(numberLength, '0');
        return `${prefix}-${incremented}`;
    }

    $(document).ready(function() {

        // OPEN NEW SHIFT
        $('#open_new_shift').click(function() {
            const $button = $(this);
            if ($button.prop('disabled')) return;

            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Opening...');

            // Get all currently assigned operators from the right-list
            var currentlyAssignedOperators = [];
            $('#right-list option').each(function() {
                currentlyAssignedOperators.push($(this).val());
            });

            $.ajax({
                url: "{{ route('dailycollectionsw.daily-shifts.open') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    business_id: "{{ auth()->user()->business_id }}",
                    module: "daily_collection_sw",
                    create: true,
                    dv_pump_operator_right: $('#right-list option').map(function() {
                        return this.value;
                    }).get()
                },
                success: function(response) {
                    if (response.success) {
                        // Update hidden field and displayed shift number
                        $('#open_shift_field').val(response.shift_id);
                        $('#display_shift_no').text(response.shift_no).attr('data-shift', response.shift_no);

                        // Enable assignment buttons
                        $('#add_row-right, #add_row-left').prop('disabled', false);

                        // Update pending operator list (left list)
                        const $leftList = $('#left-list');
                        $leftList.empty();

                        // Add the previously assigned operators to pending list first
                        // These are the operators from the previous shift
                        if (currentlyAssignedOperators.length > 0) {
                            currentlyAssignedOperators.forEach(function(operatorId) {
                                const operatorName = pumpOperatorsMap[operatorId] || 'Unknown';
                                $leftList.append($('<option>', {
                                    value: operatorId,
                                    text: operatorName
                                }));
                            });
                        }

                        // Then add any remaining operators from the response
                        // response.operators_with_name is id => name
                        for (const [id, name] of Object.entries(response.operators_with_name)) {
                            // Don't add operators that are already in the pending list
                            if (!currentlyAssignedOperators.includes(id)) {
                                $leftList.append($('<option>', {
                                    value: id,
                                    text: name
                                }));
                            }
                        }

                        // Clear the assigned list (right list)
                        $('#right-list').empty();

                        toastr.success('New shift opened successfully');
                        
                        // Save the new shift configuration
                        setTimeout(function() {
                            saveShiftData();
                        }, 500);
                    } else {
                        toastr.warning(response.message || 'Unable to open new shift');
                    }
                },
                error: function(xhr) {
                    toastr.error('Error creating new shift');
                    console.error(xhr.responseText);
                },
                complete: function() {
                    // Keep button disabled until shift is closed
                    $button.html("@lang('petro::lang.open_new_shift')").prop('disabled', true);
                }
            });
        });

        // SAVE SHIFT DATA (used when users move operators)
        function saveShiftData() {
            // Get all assigned operator ids from right-list
            var assignedOperators = [];
            $('#right-list option').each(function() {
                assignedOperators.push($(this).val());
            });

            // Get pending operator ids from left-list
            var pendingOperators = [];
            $('#left-list option').each(function() {
                pendingOperators.push($(this).val());
            });

            // Get other form data
            var date = $('#day_counted_from').val();
            var time = $('#time_till').val();

            // For shift number: read from the span's data attribute OR fallback to text
            var shiftNo = $('#display_shift_no').attr('data-shift') || $('#display_shift_no').text().trim();

            var openShift = $('#open_shift_field').val(); // Get the hidden field value

            // Visual feedback
            $("#shift_closes").html("assigning......");

            // AJAX request to save data
            $.ajax({
                url: "{{ route('dailycollectionsw.daily-shifts.store') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    business_id: "{{ auth()->user()->business_id }}",
                    pump_operator_pending: pendingOperators.length > 0 ? pendingOperators.join(',') :
                        '',
                    pump_operator_assigned: assignedOperators.join(','),
                    shift_no: shiftNo,
                    date: date,
                    time: time,
                    user: "{{ auth()->user()->id }}",
                    open_shift: openShift // Include the open_shift value
                },
                success: function(response) {
                    // Restore button text and state (localized text could be used)
                    $("#shift_closes").html("Close Daily shift").prop('disabled', false);

                    // Update hidden field if new ID was returned
                    if (response.shift_id) {
                        $('#open_shift_field').val(response.shift_id);
                    }

                    // Update assigned pump operators UI (if provided)
                    if (response.pump_operators_assigned && Array.isArray(response
                            .pump_operators_assigned)) {
                        const $assignedDiv = $('#operator_names');
                        $assignedDiv.empty();

                        response.pump_operators_assigned.forEach(function(operatorId) {
                            // operatorId might be number or string, ensure lookup works
                            const name = pumpOperatorsMap[String(operatorId)] ||
                                pumpOperatorsMap[operatorId] || 'Unknown';
                            $assignedDiv.append(
                                `<p data-operator-id="${operatorId}">${name}</p>`);
                        });

                        $('#per_operator_total').empty();
                        $('#pump_operators_total').text('');
                    }

                    // Update pending pump operators UI (if provided)
                    if (response.pump_operators_pending && Array.isArray(response
                            .pump_operators_pending)) {
                        const $pendingDiv = $('#pending_operator_names');
                        $pendingDiv.empty();

                        response.pump_operators_pending.forEach(function(operatorId) {
                            const name = pumpOperatorsMap[String(operatorId)] ||
                                pumpOperatorsMap[operatorId] || 'Unknown';
                            $pendingDiv.append(
                                `<p data-operator-id="${operatorId}">${name}</p>`);
                        });
                    }

                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }

        // When clicking move right / left in the visible UI, call saveShiftData
        $('#add_row-right').click(function() {
            $('#left-list option:selected').each(function() {
                $(this).remove().appendTo('#right-list');
            });
            saveShiftData();
        });

        $('#add_row-left').click(function() {
            $('#right-list option:selected').each(function() {
                $(this).remove().appendTo('#left-list');
            });
            saveShiftData();
        });

        // double-click move
        $('#left-list, #right-list').on('dblclick', 'option', function() {
            var list = $(this).parent();
            if (list.attr('id') === 'left-list') {
                $(this).remove().appendTo('#right-list');
            } else {
                $(this).remove().appendTo('#left-list');
            }
            saveShiftData();
        });

        // Keep UI consistent when edit button clicked (existing code behavior)
        $("#editShift").click(function() {
            var isLeftDisabled = $("#left-list").is(":disabled");
            if (isLeftDisabled) {
                // allow editing
                $("#left-list").prop("disabled", false);
                $('#add_row-right').prop('disabled', false);
                $('#add_row-left').prop('disabled', false);
                toastr.success('You are in edit mode');
                return;
            }
        });

        // Make the lists support multiple selection (your existing mousedown handler)
        $('#left-list, #right-list').on('mousedown', function(e) {
            e.preventDefault();
            var select = this;
            var option = $(e.target).closest('option');

            if (option.length) {
                var optionEl = option[0];

                if (e.ctrlKey || e.metaKey) {
                    optionEl.selected = !optionEl.selected;
                } else if (e.shiftKey) {
                    if (select.lastSelected) {
                        var options = $(select).find('option');
                        var start = options.index(select.lastSelected);
                        var end = options.index(optionEl);
                        options.slice(Math.min(start, end), Math.max(start, end) + 1).prop('selected',
                            true);
                    } else {
                        optionEl.selected = true;
                    }
                } else {
                    $(select).find('option').prop('selected', false);
                    optionEl.selected = true;
                }

                select.lastSelected = optionEl.selected ? optionEl : null;
                $(select).trigger('change');
            }
        });

    });

    $(document).ready(function() {
        // Show confirmation modal when close button is clicked
        $('#shift_closes').click(function() {
            $('#confirmCloseShiftModal').modal('show');
        });

        // Handle the actual closing when "Yes" is clicked
        $('#confirmCloseShift').click(function() {
            // Close the modal
            $('#confirmCloseShiftModal').modal('hide');

            // Get all necessary data from the form
            var assignedOperators = [];
            $('#right-list option').each(function() {
                assignedOperators.push($(this).val());
            });

            var pendingOperators = [];
            $('#left-list option').each(function() {
                pendingOperators.push($(this).val());
            });

            // Show loading state
            $('#shift_closes').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            //saveShiftData();
            $.ajax({
                url: "{{ route('dailycollectionsw.daily-shifts.save') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    business_id: "{{ auth()->user()->business_id }}",
                    open_shift: $('#open_shift_field').val(),
                    pump_operator_pending: pendingOperators.join(','),
                    pump_operator_assigned: assignedOperators.join(','),
                    date: $('#day_counted_from').val(),
                    time: $('#time_till').val(),
                    user: "{{ auth()->user()->id }}"
                },
                success: function(response) {
                    if (response.success) {
                        var shift_no = $('#display_shift_no').data('shift');
                        // Clear shift number
                        $('#display_shift_no').text('');
                        // $('#display_shift_no').text(response.shift_no);
                        $('#display_shift_no').text(response.shift_no)

                        // Clear pump operators
                        $('#operator_names').empty();
                        $('#per_operator_total').empty();
                        $('#pump_operators_total').text('');
                        toastr.success('Shift Saved Successfully');
                        $("#left-list").prop("disabled", true);
                        $('#add_row-right').prop('disabled', true);
                        $('#add_row-left').prop('disabled', true);
                        $('#shift_closes').prop('disabled', 'disabled')
                            .html("{{ __('petro::lang.shift_closed') }}");


                    }
                },
                error: function(xhr) {
                    toastr.error("{{ __('petro::lang.error_closing_shift') }}");
                    console.error(xhr.responseText);
                    $('#shift_closes').prop('disabled', false)
                        .html("{{ __('petro::lang.close_daily_shift') }}");
                }
            });
        });
    });
</script>

<script>
    function toggleOpenShiftButton(dailyShiftStatus) {
        var rightListCount = $('#right-list option').length;

        if (dailyShiftStatus == 2) {
            $('#shift_closes').prop('disabled', true);
        }

    }

    $(document).ready(function() {
        var dailyShiftStatus = @json($dailyShift->status);

        // When moving from left to right
        $('#add_row-right').click(function() {
            // dailyShiftStatus = 1;
            $('#left-list option:selected').each(function() {
                $(this).remove().appendTo('#right-list');
            });
            // toggleOpenShiftButton(dailyShiftStatus); // update button state
        });

        // When moving from right to left
        $('#add_row-left').click(function() {
            // dailyShiftStatus = 1
            $('#right-list option:selected').each(function() {
                $(this).remove().appendTo('#left-list');
            });
            // toggleOpenShiftButton(dailyShiftStatus); // update button state
        });

        // If right-list is edited in any way (fallback)
        $('#right-list').on('change', toggleOpenShiftButton);

    });
    $(document).ready(function() {
        let isDisabled = true; // Initial state - buttons are disabled

        $('#edit-button').click(function() {
            isDisabled = !isDisabled; // Toggle state

            $('#add_row-right, #add_row-left').prop('disabled', isDisabled);
        });
    });
</script>
