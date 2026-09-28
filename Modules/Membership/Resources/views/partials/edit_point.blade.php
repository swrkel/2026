@extends('layouts.app')
@section('title', __('membership::lang.edit_points'))

@section('content')
    @php
        $step = '0.01';
        $format = '0.00';

        if (isset($currency_precision)) {
            $step = '0.' . str_repeat('0', $currency_precision - 1) . '1';
            $format = '0.' . str_repeat('0', $currency_precision);
        }
    @endphp

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('membership::lang.membership_point')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('membership::lang.membership_point')</a></li>
                        <li><span>@lang('membership::lang.edit_membership_points')</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        {!! Form::open([
            'route' => ['membership.update-points', $point->id],
            'method' => 'PUT',
            'id' => 'membership_point_form',
            'onsubmit' => 'return validateForm();'
        ]) !!}


        <div class="box box-solid">
            <div class="box-body">

                {{-- Row 1 --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('form_number', __('membership::lang.form_number')) !!}
                            {!! Form::text('form_number', $point->form_number, ['class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date', __('membership::lang.date') . ':*') !!}
                            {!! Form::text('date', @format_date($point->date), ['class' => 'form-control', 'required', 'id' => 'date']) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('member_name', __('membership::lang.member_name') . ':*') !!}
                            <select class="form-control select2" name="member_id" required id="member_name">
                                <option value="">@lang('messages.please_select')</option>
                                @foreach ($allMembers as $member)
                                    <option value="{{ $member->id }}" 
                                        data-member-number="{{ $member->member_number ?? '' }}"
                                        {{ $point->member_id == $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('member_number', __('membership::lang.member_number')) !!}
                            {!! Form::text('member_number', $currentMemberNumber ?? '', [
                                'class' => 'form-control',
                                'readonly',
                                'id' => 'member_number',
                            ]) !!}
                        </div>
                    </div>
                </div>

                {{-- Row 2 --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('membership_business_type_id', __('membership::lang.business_type') . ':*') !!}
                            {!! Form::select('membership_business_type_id', $membershipBusinessTypes, $point->business_type_id, [
                                'class' => 'form-control select2',
                                'required',
                                'id' => 'membership_business_type_id',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('business_name_id', __('membership::lang.business_name')) !!}
                            <select id="business_name_id" name="business_name_id" class="form-control select2">
                                <option value="">@lang('messages.please_select')</option>
                                @foreach($businessNames as $businessName)
                                    <option value="{{ $businessName->id }}"
                                        {{ (isset($selectedBusinessNameId) && $selectedBusinessNameId == $businessName->id) ? 'selected' : '' }}>
                                        {{ $businessName->business_name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="business_name" id="business_name_hidden" value="{{ $point->business_name }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('bill_number', __('membership::lang.bill_number')) !!}
                            {!! Form::text('bill_number', $point->bill_number, ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('amount_display', __('membership::lang.amount')) !!}

                            {{-- Visible formatted field --}}
                            <input type="text" id="amount_display" class="form-control"
                                value="{{ number_format($point->amount, $currency_precision) }}">

                            {{-- Actual numeric value --}}
                            <input type="hidden" name="amount" id="amount" value="{{ $point->amount }}">
                        </div>

                    </div>
                </div>

                {{-- Row 3 --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('current_points', __('membership::lang.current_points')) !!}
                            {!! Form::text(
                                'current_points',
                                number_format($currentBalance, $currency_precision),
                                [
                                    'class' => 'form-control',
                                    'readonly',
                                    'id' => 'current_points',
                                    'data-current-points' => $currentBalance,
                                ],
                            ) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('earned_points', __('membership::lang.earned_points')) !!}
                            {!! Form::number('earned_points', $point->earned_points, [
                                'class' => 'form-control',
                                'readonly',
                                'step' => $step,
                                'id' => 'earned_points',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('redeemed_points', __('membership::lang.redeemed_points')) !!}
                            {!! Form::number('redeemed_points', number_format($point->redeemed_points, $currency_precision), [
                                'class' => 'form-control',
                                'step' => $step,
                                'id' => 'redeemed_points',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('point_balance', __('membership::lang.point_balance')) !!}
                            {!! Form::number('point_balance', $point->point_balance, [
                                'class' => 'form-control',
                                'readonly',
                                'step' => $step,
                                'id' => 'point_balance',
                            ]) !!}
                        </div>
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                <a href="{{ action('\Modules\Membership\Http\Controllers\MembershipPointController@getListPoints') }}"
                    class="btn btn-default">@lang('messages.cancel')</a>
            </div>
        </div>

        {!! Form::close() !!}
    </section>
@endsection
@section('javascript')
    <script>
        $(document).on('input', '#amount_display', function() {
            let raw = $(this).val().replace(/,/g, '');
            $('#amount').val(raw);
        });

        // Function to calculate and update point balance
        function calculatePointBalance() {
            // Get current points (before this transaction) - use data attribute for raw value
            let currentPoints = parseFloat($('#current_points').data('current-points')) || 0;
            
            // Get earned points - remove commas if present
            let earnedPointsVal = $('#earned_points').val() || '0';
            let earnedPoints = parseFloat(earnedPointsVal.toString().replace(/,/g, '')) || 0;
            
            // Get redeemed points - remove commas if present
            let redeemedPointsVal = $('#redeemed_points').val() || '0';
            let redeemedPoints = parseFloat(redeemedPointsVal.toString().replace(/,/g, '')) || 0;
            
            // Calculate new point balance: current_points + earned_points - redeemed_points
            let newPointBalance = currentPoints + earnedPoints - redeemedPoints;
            
            // Update the point balance field with proper formatting
            $('#point_balance').val(newPointBalance.toFixed({{ $currency_precision }}));
        }

        // Calculate point balance when redeemed points change
        $(document).on('input change', '#redeemed_points', function() {
            calculatePointBalance();
            updateBalanceViaAjax();
        });

        // Real-time balance update via AJAX
        function updateBalanceViaAjax() {
            let memberId = $('#member_name').val();
            let redeemedPoints = parseFloat($('#redeemed_points').val()) || 0;
            let excludePointId = {{ $point->id }};

            if (!memberId) return;

            $.ajax({
                url: '/membership/get-point-balance-for-edit',
                method: 'GET',
                data: {
                    member_id: memberId,
                    exclude_point_id: excludePointId,
                    redeemed_points: redeemedPoints
                },
                success: function(response) {
                    if (response.success && response.balance !== undefined) {
                        $('#point_balance').val(parseFloat(response.balance).toFixed({{ $currency_precision }}));
                    } else if (response.error) {
                        console.error('Balance calculation error:', response.error);
                        toastr.warning('Unable to update balance: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating balance:', error, xhr.responseText);
                    let errorMessage = 'Failed to update balance';
                    
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage += ': ' + xhr.responseJSON.error;
                    }
                    
                    toastr.error(errorMessage);
                }
            });
        }

        // Calculate point balance when earned points change (if it becomes editable)
        $(document).on('input change', '#earned_points', function() {
            calculatePointBalance();
        });

        // Calculate initial point balance on page load
        $(document).ready(function() {
            $('.select2').select2();
            calculatePointBalance();
            
            // Populate member number when member is selected
            $('#member_name').on('change', function() {
                let selectedOption = $(this).find(':selected');
                let memberNumber = selectedOption.data('member-number');
                if (memberNumber) {
                    $('#member_number').val(memberNumber);
                } else {
                    $('#member_number').val('');
                }
            });
            
            // Trigger change on page load to populate member number
            $('#member_name').trigger('change');
            
            // Business Type change - Load business names
            $('#membership_business_type_id').on('change', function () {
                let typeId = $(this).val();
                let $businessNameSelect = $('#business_name_id');
                
                // Clear existing options
                $businessNameSelect.empty().append('<option value="">@lang('messages.please_select')</option>');
                
                if (!typeId) {
                    // Re-initialize select2 to refresh the dropdown
                    try {
                        $businessNameSelect.select2('destroy');
                    } catch(e) {
                        // Select2 might not be initialized yet, ignore error
                    }
                    $businessNameSelect.select2();
                    return;
                }
                
                $.ajax({
                    url: '/membership/get-business-names-by-type',
                    method: 'GET',
                    data: { 
                        membership_business_type_id: typeId 
                    },
                    success: function (res) {
                        if (res && res.length > 0) {
                            res.forEach(businessName => {
                                $businessNameSelect.append(
                                    `<option value="${businessName.id}">${businessName.business_name}</option>`
                                );
                            });
                        } else {
                            $businessNameSelect.append(
                                `<option value="">@lang('messages.please_select') - No business names found</option>`
                            );
                        }
                        // Re-initialize select2 to show the new options
                        try {
                            $businessNameSelect.select2('destroy');
                        } catch(e) {
                            // Select2 might not be initialized yet, ignore error
                        }
                        $businessNameSelect.select2();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading business names:', error, xhr.responseText);
                        toastr.error('Failed to load business names. Please try again.');
                        // Re-initialize select2 even on error
                        try {
                            $businessNameSelect.select2('destroy');
                        } catch(e) {
                            // Select2 might not be initialized yet, ignore error
                        }
                        $businessNameSelect.select2();
                    }
                });
            });
            
            // Business name change - update hidden field
            $('#business_name_id').on('change', function() {
                let selectedText = $(this).find(':selected').text();
                $('#business_name_hidden').val(selectedText);
            });
            
            // Initialize business name on page load if business type is already selected
            if ($('#membership_business_type_id').val()) {
                $('#membership_business_type_id').trigger('change');
                // After a short delay, select the correct business name
                setTimeout(function() {
                    @if(isset($selectedBusinessNameId) && $selectedBusinessNameId)
                        $('#business_name_id').val('{{ $selectedBusinessNameId }}').trigger('change');
                    @endif
                }, 500);
            }
        });

        // Form validation and submit handler
        function validateForm() {
            let memberId = $('#member_name').val();
            let businessTypeId = $('#membership_business_type_id').val();
            let amount = parseFloat($('#amount').val()) || 0;
            
            if (!memberId) {
                toastr.error('Please select a member');
                $('#member_name').focus();
                return false;
            }
            
            if (!businessTypeId) {
                toastr.error('Please select a business type');
                $('#membership_business_type_id').focus();
                return false;
            }
            
            if (amount <= 0) {
                toastr.error('Please enter a valid amount');
                $('#amount_display').focus();
                return false;
            }
            
            // Show loading indicator
            $('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            
            return true;
        }
    </script>
@endsection
