<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form action="{{ action('\Modules\Membership\Http\Controllers\MembershipController@updateMember', $member->id) }}"
              method="POST" id="member_edit_form">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('membership::lang.edit_member') }}</h4>
            </div>

            <div class="modal-body">
                <!-- Row 1: Date & Time, Region, Date Joined -->
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>{{ __('membership::lang.date_time') }}:</label>
                            <input type="text" class="form-control" value="{{ $member->created_at ? $member->created_at->format('j M Y H:i') : '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>{{ __('membership::lang.region') }}:</label>
                            <input type="text" class="form-control" value="{{ $member->region }}" readonly>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('date_joined', __('membership::lang.date_joined').':') !!}
                            @php
                                $dateJoinedValue = '';
                                $dateJoinedOriginal = '';
                                if ($member->date_joined) {
                                    try {
                                        $parsedDate = \Carbon\Carbon::parse($member->date_joined);
                                        $dateJoinedOriginal = $parsedDate->format('Y-m-d');
                                        $dateJoinedValue = $parsedDate->format('j M Y');  // e.g. 23 Jan 2026
                                    } catch (\Exception $e) {
                                        $dateJoinedValue = $member->date_joined;
                                        $dateJoinedOriginal = $member->date_joined;
                                    }
                                }
                            @endphp
                            {!! Form::text('date_joined', $dateJoinedValue, ['class' => 'form-control', 'id' => 'date_joined', 'placeholder' => 'Select date', 'data-original-date' => $dateJoinedOriginal]) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 2: Title, Member Name, Name (other language), Member Address -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('title', __('membership::lang.title').':') !!}
                            {!! Form::select('title', [
                                'Mr.' => 'Mr.',
                                'Mrs.' => 'Mrs.',
                                'Miss' => 'Miss',
                                'Rev.' => 'Rev.',
                                'Priest' => 'Priest',
                                'Imam' => 'Imam'
                            ], $member->title, ['class' => 'form-control', 'id' => 'title', 'placeholder' => 'Select title']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('member_name', __('membership::lang.member_name').':*') !!}
                            {!! Form::text('member_name', $member->member_name, ['class' => 'form-control', 'required', 'id' => 'member_name']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('member_name_other', __('membership::lang.member_name_other').':') !!}
                            {!! Form::text('member_name_other', $member->member_name_other ?? '', ['class' => 'form-control', 'id' => 'member_name_other']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('member_address', __('membership::lang.member_address').':') !!}
                            {!! Form::textarea('member_address', $member->member_address, ['class' => 'form-control', 'id' => 'member_address', 'rows' => 2]) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 3: NIC No, Date of Birth, Gender, Member Default Mobile -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('nic_no', __('membership::lang.nic_no').':') !!}
                            {!! Form::text('nic_no', $member->nic_no, ['class' => 'form-control', 'id' => 'nic_no']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('date_of_birth', __('membership::lang.date_of_birth').':') !!}
                            @php
                                $dateOfBirthValue = '';
                                $dateOfBirthOriginal = '';
                                if ($member->date_of_birth) {
                                    try {
                                        $parsedDate = \Carbon\Carbon::parse($member->date_of_birth);
                                        $dateOfBirthOriginal = $parsedDate->format('Y-m-d');
                                        $dateOfBirthValue = $parsedDate->format('j M Y');  // e.g. 23 Jan 2026
                                    } catch (\Exception $e) {
                                        $dateOfBirthValue = $member->date_of_birth;
                                        $dateOfBirthOriginal = $member->date_of_birth;
                                    }
                                }
                            @endphp
                            {!! Form::text('date_of_birth', $dateOfBirthValue, ['class' => 'form-control', 'id' => 'date_of_birth', 'placeholder' => 'Select date', 'data-original-date' => $dateOfBirthOriginal]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('gender', __('membership::lang.gender').':') !!}
                            {!! Form::select('gender', [
                                'Male' => 'Male',
                                'Female' => 'Female'
                            ], $member->gender, ['class' => 'form-control', 'id' => 'gender', 'placeholder' => 'Select gender']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('default_mobile_number', __('membership::lang.default_mobile_number').':*') !!}
                            {!! Form::text('default_mobile_number', $member->default_mobile_number, ['class' => 'form-control', 'required', 'id' => 'default_mobile_number']) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 4: Other Mobile Numbers, Business Type, Membership Type, No of Shares -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('other_mobile_numbers', __('membership::lang.other_mobile_numbers').':') !!}
                            {!! Form::textarea('other_mobile_numbers', $member->other_mobile_numbers, ['class' => 'form-control', 'id' => 'other_mobile_numbers', 'rows' => 2, 'placeholder' => __('membership::lang.enter_one_mobile_per_line')]) !!}
                            <small class="text-muted">{{ __('membership::lang.enter_one_mobile_per_line') }}</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('membership_business_type_id', __('membership::lang.business_type').':*') !!}
                            {!! Form::select('membership_business_type_id', $businessTypes, $member->membership_business_type_id, ['class' => 'form-control select2', 'required', 'id' => 'membership_business_type_id', 'placeholder' => __('membership::lang.please_select')]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('membership_type_id', __('membership::lang.membership_type').':') !!}
                            <div class="input-group">
                                <select name="membership_type_id" id="membership_type_id" class="form-control select2">
                                    <option value="">{{ __('membership::lang.please_select') }}</option>
                                    @if(!empty($membershipTypes))
                                        @foreach($membershipTypes as $id => $name)
                                            <option value="{{ $id }}" {{ $member->membership_type_id == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" id="add_membership_type_btn" title="Add new membership type">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('no_of_shares', __('membership::lang.no_of_shares').':') !!}
                            {!! Form::number('no_of_shares', $member->no_of_shares ?? 0, ['class' => 'form-control', 'id' => 'no_of_shares', 'min' => 0, 'step' => 1]) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 5: Total Share Value, Membership Status -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('total_share_value', __('membership::lang.total_share_value').':') !!}
                            {!! Form::number('total_share_value', $member->total_share_value ?? 0, ['class' => 'form-control', 'id' => 'total_share_value', 'min' => 0, 'step' => 0.01]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('membership_status_id', __('membership::lang.membership_status').':') !!}
                            <div class="input-group">
                                <select name="membership_status_id" id="membership_status_id" class="form-control select2">
                                    <option value="">{{ __('membership::lang.please_select') }}</option>
                                    @if(!empty($membershipStatuses))
                                        @foreach($membershipStatuses as $id => $name)
                                            <option value="{{ $id }}" {{ $member->membership_status_id == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" id="add_membership_status_btn" title="Add new membership status">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 6: Renewal -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('renewal_period', __('membership::lang.renewal_period').':') !!}
                            {!! Form::select('renewal_period', [
                                'days' => __('membership::lang.days'),
                                'weeks' => __('membership::lang.weeks'),
                                'months' => __('membership::lang.months'),
                                'years' => __('membership::lang.years')
                            ], $member->renewal_period, ['class' => 'form-control', 'id' => 'renewal_period', 'placeholder' => __('membership::lang.please_select')]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3" id="renewal_cycles_group" style="{{ $member->renewal_period ? '' : 'display:none;' }}">
                        <div class="form-group">
                            {!! Form::label('renewal_cycles', __('membership::lang.renewal_cycles').':') !!}
                            {!! Form::number('renewal_cycles', $member->renewal_cycles, ['class' => 'form-control', 'id' => 'renewal_cycles', 'min' => 1, 'step' => 1, 'inputmode' => 'numeric']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3" id="renewal_date_group" style="{{ $member->renewal_period ? '' : 'display:none;' }}">
                        <div class="form-group">
                            {!! Form::label('renewal_date', __('membership::lang.renewal_date').':') !!}
                            {!! Form::text('renewal_date', !empty($member->renewal_date) ? \Carbon\Carbon::parse($member->renewal_date)->format('j M Y') : '', ['class' => 'form-control', 'id' => 'renewal_date', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('registration_renewal_amount', __('membership::lang.registration_renewal_amount').':') !!}
                            {!! Form::number('registration_renewal_amount', $member->registration_renewal_amount, ['class' => 'form-control', 'id' => 'registration_renewal_amount', 'min' => 0, 'step' => 0.01]) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </form>
    </div>
</div>

<style>
    #select2-membership_type_id-results .select2-results__option,
    #select2-membership_status_id-results .select2-results__option {
        padding-top: 6px;
        padding-bottom: 6px;
        border-bottom: 1px solid #999999;
    }

    #select2-membership_type_id-results .select2-results__option:last-child,
    #select2-membership_status_id-results .select2-results__option:last-child {
        border-bottom: none;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#membership_business_type_id').select2({
            dropdownParent: $('.modal')
        });
        var membershipTypesData = @json($membershipTypes ?? []);
        var membershipTypesOptions = $.map(membershipTypesData, function (text, id) {
            return { id: id, text: text };
        });

        $('#membership_type_id').select2({
            dropdownParent: $('.modal'),
            placeholder: 'Select or type to search',
            allowClear: true,
            minimumResultsForSearch: 0,
            data: membershipTypesOptions
        });
        var membershipStatusesData = @json($membershipStatuses ?? []);
        var membershipStatusesOptions = $.map(membershipStatusesData, function (text, id) {
            return { id: id, text: text };
        });

        $('#membership_status_id').select2({
            dropdownParent: $('.modal'),
            placeholder: 'Select or type to search',
            allowClear: true,
            minimumResultsForSearch: 0,
            data: membershipStatusesOptions
        });

        function toggleRenewalCyclesField() {
            var hasRenewalPeriod = !!$('#renewal_period').val();
            $('#renewal_cycles_group').toggle(hasRenewalPeriod);
            $('#renewal_date_group').toggle(hasRenewalPeriod);
            $('#renewal_cycles').prop('required', hasRenewalPeriod);

            if (!hasRenewalPeriod) {
                $('#renewal_cycles').val('');
                $('#renewal_date').val('');
            }
        }

        function calculateRenewalDate() {
            var dateJoined = $('#date_joined').val();
            var renewalPeriod = $('#renewal_period').val();
            var renewalCycles = $('#renewal_cycles').val();

            if (!dateJoined || !renewalPeriod || !renewalCycles) {
                $('#renewal_date').val('');
                return;
            }

            var joinedMoment = moment(dateJoined, ['D MMM YYYY', 'YYYY-MM-DD', 'DD/MM/YYYY'], true);
            var cycles = parseInt(renewalCycles, 10);

            if (!joinedMoment.isValid() || !cycles || cycles < 1) {
                $('#renewal_date').val('');
                return;
            }

            var renewalMoment = joinedMoment.clone();

            if (renewalPeriod === 'days') {
                renewalMoment.add(cycles, 'days');
            } else if (renewalPeriod === 'weeks') {
                renewalMoment.add(cycles, 'weeks');
            } else if (renewalPeriod === 'months') {
                renewalMoment.add(cycles, 'months');
            } else if (renewalPeriod === 'years') {
                renewalMoment.add(cycles, 'years');
            }

            $('#renewal_date').val(renewalMoment.format('D MMM YYYY'));
        }

        $('#renewal_period').on('change', function() {
            toggleRenewalCyclesField();
            calculateRenewalDate();
        });
        $('#renewal_cycles').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            calculateRenewalDate();
        });
        toggleRenewalCyclesField();

        // Date Joined picker
        var dateJoinedValue = $('#date_joined').val();
        var dateJoinedOriginalDate = $('#date_joined').data('original-date'); // Get original YYYY-MM-DD format from database
        var dateJoinedPickerOptions = {
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
            locale: {
                format: 'D MMM YYYY',   // e.g. 23 Jan 2026
                cancelLabel: 'Clear'
            }
        };
        
        // Set initial date if value exists - prefer original date format from database
        if (dateJoinedOriginalDate && dateJoinedOriginalDate.trim() !== '') {
            try {
                // Use the original YYYY-MM-DD format from database
                var parsedDate = moment(dateJoinedOriginalDate, 'YYYY-MM-DD', true);
                if (parsedDate.isValid()) {
                    dateJoinedPickerOptions.startDate = parsedDate;
                    dateJoinedPickerOptions.endDate = parsedDate;
                }
            } catch(e) {
                console.log('Error parsing date_joined from original date:', e);
            }
        } else if (dateJoinedValue && dateJoinedValue.trim() !== '') {
            try {
                var parsedDate = moment(dateJoinedValue, 'D MMM YYYY', true);
                if (!parsedDate.isValid()) {
                    parsedDate = moment(dateJoinedValue, ['YYYY-MM-DD', 'DD/MM/YYYY', 'D MMM YYYY'], true);
                }
                if (parsedDate.isValid()) {
                    dateJoinedPickerOptions.startDate = parsedDate;
                    dateJoinedPickerOptions.endDate = parsedDate;
                }
            } catch(e) {
                console.log('Error parsing date_joined:', e);
            }
        }
        
        $('#date_joined').daterangepicker(dateJoinedPickerOptions, function(start) {
            $('#date_joined').val(start.format('D MMM YYYY'));
            calculateRenewalDate();
        });
        
        // Handle cancel
        $('#date_joined').on('cancel.daterangepicker', function(ev, picker) {
            $('#date_joined').val('');
            $('#renewal_date').val('');
        });
        $('#date_joined').on('change', function() {
            calculateRenewalDate();
        });
        calculateRenewalDate();

        // Date of Birth picker
        var dateOfBirthValue = $('#date_of_birth').val();
        var dateOfBirthOriginalDate = $('#date_of_birth').data('original-date'); // Get original YYYY-MM-DD format from database
        var dateOfBirthPickerOptions = {
            singleDatePicker: true,
            showDropdowns: true,
            autoUpdateInput: false,
            maxDate: moment(),
            locale: {
                format: 'D MMM YYYY',   // e.g. 23 Jan 2026
                cancelLabel: 'Clear'
            }
        };
        
        // Set initial date if value exists - prefer original date format from database
        if (dateOfBirthOriginalDate && dateOfBirthOriginalDate.trim() !== '') {
            try {
                // Use the original YYYY-MM-DD format from database
                var parsedDate = moment(dateOfBirthOriginalDate, 'YYYY-MM-DD', true);
                if (parsedDate.isValid()) {
                    dateOfBirthPickerOptions.startDate = parsedDate;
                    dateOfBirthPickerOptions.endDate = parsedDate;
                }
            } catch(e) {
                console.log('Error parsing date_of_birth from original date:', e);
            }
        } else if (dateOfBirthValue && dateOfBirthValue.trim() !== '') {
            try {
                var parsedDate = moment(dateOfBirthValue, 'D MMM YYYY', true);
                if (!parsedDate.isValid()) {
                    parsedDate = moment(dateOfBirthValue, ['YYYY-MM-DD', 'DD/MM/YYYY', 'D MMM YYYY'], true);
                }
                if (parsedDate.isValid()) {
                    dateOfBirthPickerOptions.startDate = parsedDate;
                    dateOfBirthPickerOptions.endDate = parsedDate;
                }
            } catch(e) {
                console.log('Error parsing date_of_birth:', e);
            }
        }
        
        $('#date_of_birth').daterangepicker(dateOfBirthPickerOptions, function(start) {
            $('#date_of_birth').val(start.format('D MMM YYYY'));
        });

        // Add Membership Type button click
        $('#add_membership_type_btn').on('click', function() {
            $('#new_membership_type_name').val('');
            $('#add_membership_type_modal').modal('show');
        });

        // Save Membership Type
        $('#save_membership_type_btn').on('click', function() {
            var $btn = $(this);
            var typeName = $('#new_membership_type_name').val().trim();
            if (!typeName) {
                toastr.error('Please enter a membership type name');
                return;
            }

            $btn.prop('disabled', true);
            $btn.find('.btn-text').hide();
            $btn.find('.btn-loading').show();

            $.ajax({
                url: '/membership/membership-types',
                method: 'POST',
                data: {
                    type_name: typeName,
                    _token: '{{ csrf_token() }}'
                },
                cache: false,
                success: function(response) {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').show();
                    $btn.find('.btn-loading').hide();

                    if (response.success) {
                        var newOption = new Option(response.type_name, response.id, true, true);
                        $('#membership_type_id').append(newOption).trigger('change');
                        $('#add_membership_type_modal').modal('hide');
                        $('#new_membership_type_name').val('');
                        toastr.success(response.msg);
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').show();
                    $btn.find('.btn-loading').hide();

                    var message = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    }
                    toastr.error(message);
                }
            });
        });

        // Add Membership Status button click
        $('#add_membership_status_btn').on('click', function() {
            $('#new_membership_status_name').val('');
            $('#add_membership_status_modal').modal('show');
        });

        // Save Membership Status
        $('#save_membership_status_btn').on('click', function() {
            var $btn = $(this);
            var statusName = $('#new_membership_status_name').val().trim();
            if (!statusName) {
                toastr.error('Please enter a membership status name');
                return;
            }

            $btn.prop('disabled', true);
            $btn.find('.btn-text').hide();
            $btn.find('.btn-loading').show();

            $.ajax({
                url: '/membership/membership-statuses',
                method: 'POST',
                data: {
                    status_name: statusName,
                    _token: '{{ csrf_token() }}'
                },
                cache: false,
                success: function(response) {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').show();
                    $btn.find('.btn-loading').hide();

                    if (response.success) {
                        var newOption = new Option(response.status_name, response.id, true, true);
                        $('#membership_status_id').append(newOption).trigger('change');
                        $('#add_membership_status_modal').modal('hide');
                        $('#new_membership_status_name').val('');
                        toastr.success(response.msg);
                    } else {
                        toastr.error(response.msg);
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').show();
                    $btn.find('.btn-loading').hide();

                    var message = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    }
                    toastr.error(message);
                }
            });
        });

        $('#member_edit_form').submit(function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            var $submitBtn = form.find('button[type="submit"].btn-primary');
            var originalText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                method: 'PUT',
                url: url,
                dataType: 'json',
                data: form.serialize(),
                success: function(result) {
                    $submitBtn.prop('disabled', false).html(originalText);
                    if (result.success) {
                        $('div.member_modal').modal('hide');
                        toastr.success(result.msg);
                        member_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).html(originalText);
                    var message = 'Something went wrong';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join('<br>');
                    }
                    toastr.error(message);
                }
            });
        });
    });
</script>

<!-- Modal for adding new Membership Type -->
<div class="modal fade" id="add_membership_type_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New Membership Type</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="new_membership_type_name">Membership Type Name:*</label>
                    <input type="text" class="form-control" id="new_membership_type_name" placeholder="Enter membership type name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save_membership_type_btn">
                        <span class="btn-text">Save</span>
                        <span class="btn-loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Saving...</span>
                    </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for adding new Membership Status -->
<div class="modal fade" id="add_membership_status_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New Membership Status</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="new_membership_status_name">Membership Status Name:*</label>
                    <input type="text" class="form-control" id="new_membership_status_name" placeholder="Enter membership status name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save_membership_status_btn">
                        <span class="btn-text">Save</span>
                        <span class="btn-loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Saving...</span>
                    </button>
            </div>
        </div>
    </div>
</div>
