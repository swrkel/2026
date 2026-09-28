@extends('layouts.app')
@section('title', __('membership::lang.add_member'))
@section('content')
    <section class="content">
        {!! Form::open(['url' => action('\Modules\Membership\Http\Controllers\MembershipController@storeMember'), 'method' => 'post', 'id' => 'member_form']) !!}
        <div class="box box-solid">
            <div class="box-body">
                <!-- Row 1: Date & Time, Region, Date Joined -->
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('date_time', 'Date & Time:') !!}
                            {!! Form::text('date_time', now()->format('j M Y H:i'), ['class' => 'form-control', 'id' => 'date_time', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('membership_setting_id', __('membership::lang.region').':*') !!}
                            <div class="input-group">
                                {!! Form::select('membership_setting_id', $regions ?? [], null, ['class' => 'form-control select2', 'required', 'id' => 'membership_setting_id', 'placeholder' => __('membership::lang.please_select')]) !!}
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default" id="add_region_btn" title="Add new region">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('date_joined', 'Date Joined:') !!}
                            {!! Form::text('date_joined', null, ['class' => 'form-control', 'id' => 'date_joined', 'placeholder' => 'Select date']) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 2: Title, Member Name, Name (other language), Member Address -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('title', 'Title:') !!}
                            {!! Form::select('title', [
                                'Mr.' => 'Mr.',
                                'Mrs.' => 'Mrs.',
                                'Miss' => 'Miss',
                                'Rev.' => 'Rev.',
                                'Priest' => 'Priest',
                                'Imam' => 'Imam'
                            ], null, ['class' => 'form-control', 'id' => 'title', 'placeholder' => 'Select title']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('member_name', __('membership::lang.member_name').':*') !!}
                            {!! Form::text('member_name', null, ['class' => 'form-control', 'required', 'id' => 'member_name']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('member_name_other', __('membership::lang.member_name_other').':') !!}
                            {!! Form::text('member_name_other', null, ['class' => 'form-control', 'id' => 'member_name_other']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('member_address', 'Member Address:') !!}
                            {!! Form::textarea('member_address', null, ['class' => 'form-control', 'id' => 'member_address', 'rows' => 2]) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 3: NIC No, Date of Birth, Gender, Member Default Mobile -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('nic_no', 'NIC No:') !!}
                            {!! Form::text('nic_no', null, ['class' => 'form-control', 'id' => 'nic_no']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('date_of_birth', 'Date of Birth:') !!}
                            {!! Form::text('date_of_birth', null, ['class' => 'form-control', 'id' => 'date_of_birth', 'placeholder' => 'Select date']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('gender', 'Gender:') !!}
                            {!! Form::select('gender', [
                                'Male' => 'Male',
                                'Female' => 'Female'
                            ], null, ['class' => 'form-control', 'id' => 'gender', 'placeholder' => 'Select gender']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('default_mobile_number', __('membership::lang.default_mobile_number').':*') !!}
                            {!! Form::text('default_mobile_number', null, ['class' => 'form-control', 'required', 'id' => 'default_mobile_number']) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 4: Other Mobile Numbers, Business Type, Membership Type, No of Shares -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('other_mobile_numbers', __('membership::lang.other_mobile_numbers').':') !!}
                            {!! Form::textarea('other_mobile_numbers', null, ['class' => 'form-control', 'id' => 'other_mobile_numbers', 'rows' => 2, 'placeholder' => __('membership::lang.enter_one_mobile_per_line')]) !!}
                            <small class="text-muted">{{ __('membership::lang.enter_one_mobile_per_line') }}</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('membership_business_type_id', __('membership::lang.business_type').':*') !!}
                            {!! Form::select('membership_business_type_id', $businessTypes, null, ['class' => 'form-control', 'required', 'id' => 'membership_business_type_id', 'placeholder' => __('membership::lang.please_select')]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('membership_type_id', 'Membership Type:') !!}
                            <div class="input-group">
                                <select name="membership_type_id" id="membership_type_id" class="form-control select2">
                                    <option value="">Select Membership Type</option>
                                    @if(!empty($membershipTypes))
                                        @foreach($membershipTypes as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
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
                            {!! Form::label('no_of_shares', 'No of Shares:') !!}
                            {!! Form::number('no_of_shares', 0, ['class' => 'form-control', 'id' => 'no_of_shares', 'min' => 0, 'step' => 1]) !!}
                        </div>
                    </div>
                </div>

                <!-- Row 5: Total Share Value, Membership Status -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('total_share_value', 'Total Share Value Rs.:') !!}
                            {!! Form::number('total_share_value', 0, ['class' => 'form-control', 'id' => 'total_share_value', 'min' => 0, 'step' => 0.01]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('membership_status_id', 'Membership Status:') !!}
                            <div class="input-group">
                                <select name="membership_status_id" id="membership_status_id" class="form-control select2">
                                    <option value="">Select Membership Status</option>
                                    @if(!empty($membershipStatuses))
                                        @foreach($membershipStatuses as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
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
                            ], null, ['class' => 'form-control', 'id' => 'renewal_period', 'placeholder' => __('membership::lang.please_select')]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3" id="renewal_cycles_group" style="display:none;">
                        <div class="form-group">
                            {!! Form::label('renewal_cycles', __('membership::lang.renewal_cycles').':') !!}
                            {!! Form::number('renewal_cycles', null, ['class' => 'form-control', 'id' => 'renewal_cycles', 'min' => 1, 'step' => 1, 'inputmode' => 'numeric']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3" id="renewal_date_group" style="display:none;">
                        <div class="form-group">
                            {!! Form::label('renewal_date', __('membership::lang.renewal_date').':') !!}
                            {!! Form::text('renewal_date', null, ['class' => 'form-control', 'id' => 'renewal_date', 'readonly']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('registration_renewal_amount', __('membership::lang.registration_renewal_amount').':') !!}
                            {!! Form::number('registration_renewal_amount', null, ['class' => 'form-control', 'id' => 'registration_renewal_amount', 'min' => 0, 'step' => 0.01]) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                @can('add_member')
                {!! Form::submit(__('messages.save'), ['class' => 'btn btn-primary', 'id' => 'member_form_submit_btn']) !!}
                @endcan
            </div>
        </div>
        {!! Form::close() !!}
    </section>

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

    <!-- Modal for adding new Region -->
    <div class="modal fade" id="add_region_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add New Region</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="new_region_name">Region Name:*</label>
                        <input type="text" class="form-control" id="new_region_name" placeholder="Enter region name">
                    </div>
                    <div class="form-group">
                        <label for="new_region_prefix">Prefix:</label>
                        <input type="text" class="form-control" id="new_region_prefix" placeholder="Enter prefix (optional)">
                    </div>
                    <div class="form-group">
                        <label for="new_region_starting_number">Starting Number:*</label>
                        <input type="number" class="form-control" id="new_region_starting_number" placeholder="Enter starting number" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save_region_btn">
                        <span class="btn-text">Save</span>
                        <span class="btn-loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('model-scritps')
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
    <script type="text/javascript">
        $(document).ready(function () {
            // Set current date and time (display format: 23 Jan 2026 14:30)
            $('#date_time').val(moment().format('D MMM YYYY HH:mm'));

            // Initialize Select2
            $('#membership_business_type_id').select2();
            $('#membership_setting_id').select2();
            
            // Initialize Membership Type with search
            var membershipTypesData = @json($membershipTypes ?? []);
            var membershipTypesOptions = $.map(membershipTypesData, function (text, id) {
                return { id: id, text: text };
            });

            $('#membership_type_id').select2({
                placeholder: 'Select or type to search',
                allowClear: true,
                minimumResultsForSearch: 0,
                data: membershipTypesOptions
            });
            
            // Initialize Membership Status with search
            var membershipStatusesData = @json($membershipStatuses ?? []);
            var membershipStatusesOptions = $.map(membershipStatusesData, function (text, id) {
                return { id: id, text: text };
            });

            $('#membership_status_id').select2({
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

            // Date Joined picker with options
          var dateJoinedPickerInstance;

$('#date_joined').daterangepicker({
    singleDatePicker: true,
    showDropdowns: true,
    autoUpdateInput: true,
    autoApply: true,
    locale: {
        format: 'D MMM YYYY'   // e.g. 23 Jan 2026
    }
});
$('#date_joined').on('apply.daterangepicker change', function() {
    calculateRenewalDate();
});
$('#date_joined').on('cancel.daterangepicker', function() {
    $('#renewal_date').val('');
});
calculateRenewalDate();


            // Date of Birth picker
            $('#date_of_birth').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                autoUpdateInput: true,
                autoApply: true,
                locale: {
                    format: 'D MMM YYYY'   // e.g. 23 Jan 2026
                }
            });


            // Handle custom date typing modal for single date inputs
            $('#custom_date_apply_button').on('click', function() {
                // Get the target input field from modal data
                var targetInput = $('.custom_date_typing_modal').data('target-input');
                
                if (targetInput) {
                    // Get date values from "From" section (for single date, we use "From" date)
                    var fromDate1 = $('#custom_date_from_date1').val() || '0';
                    var fromDate2 = $('#custom_date_from_date2').val() || '0';
                    var fromMonth1 = $('#custom_date_from_month1').val() || '0';
                    var fromMonth2 = $('#custom_date_from_month2').val() || '0';
                    var fromYear1 = $('#custom_date_from_year1').val() || '0';
                    var fromYear2 = $('#custom_date_from_year2').val() || '0';
                    var fromYear3 = $('#custom_date_from_year3').val() || '0';
                    var fromYear4 = $('#custom_date_from_year4').val() || '0';
                    
                    // Combine into date string
                    var day = fromDate1 + fromDate2;
                    var month = fromMonth1 + fromMonth2;
                    var year = fromYear1 + fromYear2 + fromYear3 + fromYear4;
                    
                    // Validate and format the date
                    if (day && month && year && day !== '00' && month !== '00' && year !== '0000') {
                        var dateFormat = typeof moment_date_format !== 'undefined' ? moment_date_format : 'YYYY-MM-DD';
                        var dateString = year + '-' + month + '-' + day;
                        var parsedDate = moment(dateString, 'YYYY-MM-DD', true);
                        
                        if (parsedDate.isValid()) {
                            // For date of birth, check if date is not in the future
                            if (targetInput === '#date_of_birth' && parsedDate.isAfter(moment())) {
                                toastr.error('Date of birth cannot be in the future');
                                return;
                            }
                            
                            // Format and set the date
                            $(targetInput).val(parsedDate.format(dateFormat));
                            
                            // Update the daterangepicker instance if it exists
                            var picker = $(targetInput).data('daterangepicker');
                            if (picker) {
                                picker.setStartDate(parsedDate);
                                picker.setEndDate(parsedDate);
                            }
                            
                            // Close the modal
                            $('.custom_date_typing_modal').modal('hide');
                        } else {
                            toastr.error('Invalid date. Please check the date values.');
                        }
                    } else {
                        toastr.error('Please enter a valid date.');
                    }
                }
            });

            // Attach to Select2 change event for region (no extra AJAX needed)
            // Member number is generated server-side on save

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
                        type_name: typeName
                    },
                    cache: false,
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $btn.find('.btn-text').show();
                        $btn.find('.btn-loading').hide();

                        if (response.success) {
                            var newOption = $('<option></option>').attr('value', response.id).text(response.type_name);
                            $('#membership_type_id').append(newOption);
                            $('#membership_type_id').val(response.id);
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
                        status_name: statusName
                    },
                    cache: false,
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $btn.find('.btn-text').show();
                        $btn.find('.btn-loading').hide();

                        if (response.success) {
                            var newOption = $('<option></option>').attr('value', response.id).text(response.status_name);
                            $('#membership_status_id').append(newOption);
                            $('#membership_status_id').val(response.id);
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

            // Add Region button click
            $('#add_region_btn').on('click', function() {
                $('#new_region_name').val('');
                $('#new_region_prefix').val('');
                $('#new_region_starting_number').val('');
                $('#add_region_modal').modal('show');
            });

            // Save Region
            $('#save_region_btn').on('click', function() {
                var $btn = $(this);
                var regionName = $('#new_region_name').val().trim();
                var prefix = $('#new_region_prefix').val().trim();
                var startingNumber = $('#new_region_starting_number').val().trim();

                if (!regionName) {
                    toastr.error('Please enter a region name');
                    return;
                }
                if (!startingNumber || parseInt(startingNumber) < 1) {
                    toastr.error('Please enter a valid starting number');
                    return;
                }

                $btn.prop('disabled', true);
                $btn.find('.btn-text').hide();
                $btn.find('.btn-loading').show();

                $.ajax({
                    url: '/membership/setting/membership-settings',
                    method: 'POST',
                    data: {
                        region: regionName,
                        prefix: prefix,
                        starting_number: startingNumber,
                        _token: '{{ csrf_token() }}'
                    },
                    cache: false,
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $btn.find('.btn-text').show();
                        $btn.find('.btn-loading').hide();

                        if (response.success) {
                            var newOption = new Option(response.region || regionName, response.id, true, true);
                            $('#membership_setting_id').append(newOption).trigger('change');
                            $('#add_region_modal').modal('hide');
                            $('#new_region_name').val('');
                            $('#new_region_prefix').val('');
                            $('#new_region_starting_number').val('');
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
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            message = Object.values(errors).flat().join('<br>');
                        }
                        toastr.error(message);
                    }
                });
            });

            // Form submit - stay on same page with empty form
            $('#member_form').submit(function (e) {
                e.preventDefault();
                var $form = $(this);
                var $submitBtn = $('#member_form_submit_btn');
                var url = $form.attr('action');
                var originalText = $submitBtn.html();

                $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    method: 'POST',
                    url: url,
                    dataType: 'json',
                    data: $form.serialize(),
                    cache: false,
                    success: function (result) {
                        $submitBtn.prop('disabled', false).html(originalText);

                        if (result.success) {
                            toastr.success(result.msg);
                            $form[0].reset();
                            $('#date_time').val(moment().format('D MMM YYYY HH:mm'));
                            $('#membership_type_id').val('');
                            $('#membership_status_id').val('');
                            $('#renewal_period').val('');
                            $('#renewal_cycles').val('');
                            $('#renewal_date').val('');
                            $('#registration_renewal_amount').val('');
                            $('#membership_setting_id').val(null).trigger('change');
                            $('#membership_business_type_id').val(null).trigger('change');
                            toggleRenewalCyclesField();
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
@endsection
