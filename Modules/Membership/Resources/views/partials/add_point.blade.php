@extends('layouts.app')
@section('title', __('membership::lang.add_points'))

@section('content')
@php
    $step = '0.' . str_repeat('0', $currency_precision - 1) . '1';
    $format = '0.' . str_repeat('0', $currency_precision);
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('membership::lang.membership_point')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('membership::lang.membership_point')</a></li>
                    <li><span>@lang('membership::lang.add_membership_points')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content">
{!! Form::open([
    'url' => action('\Modules\Membership\Http\Controllers\MembershipPointController@addPoint'),
    'method' => 'post',
    'id' => 'membership_point_form',
    'onsubmit' => 'return validateForm();'
]) !!}

@if(session('status'))
    <div class="alert alert-{{ session('status') == __('membership::lang.membership_point_added_successfully') ? 'success' : 'danger' }}">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-solid">
<div class="box-body">

{{-- ROW 1 --}}
<div class="row">
    <div class="col-md-3">
        {!! Form::label('form_number', __('membership::lang.form_number')) !!}
        {!! Form::text('form_number', $formNumber, ['class'=>'form-control','readonly','id'=>'form_number']) !!}
    </div>

    <div class="col-md-3">
        {!! Form::label('date', __('membership::lang.date')) !!}
        {!! Form::text('date', now()->format('Y-m-d'), ['class'=>'form-control','readonly','id'=>'date']) !!}
    </div>

    <div class="col-md-3">
        {!! Form::label('member_id', __('membership::lang.member_name')) !!}
        <select id="member_id" name="member_id" class="form-control select2" required>
            <option value="">@lang('messages.please_select')</option>
            @foreach($allMembers as $member)
                <option value="{{ $member->id }}"
                        data-member-number="{{ $member->member_number }}">
                    {{ $member->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        {!! Form::label('member_number', __('membership::lang.member_number')) !!}
        {!! Form::text('member_number', null, ['class'=>'form-control','readonly','id'=>'member_number']) !!}
    </div>
</div>

{{-- ROW 2 --}}
<div class="row">
    <div class="col-md-3">
        {!! Form::label('membership_business_type_id', __('membership::lang.business_type')) !!}
        {!! Form::select(
            'membership_business_type_id',
            $membershipBusinessTypes,
            null,
            ['class'=>'form-control select2','id'=>'membership_business_type_id']
        ) !!}
    </div>

    <div class="col-md-3">
        {!! Form::label('business_name_id', __('membership::lang.business_name')) !!}
        <select id="business_name_id" name="business_name_id" class="form-control select2">
            <option value="">@lang('messages.please_select')</option>
        </select>
    </div>

    <div class="col-md-3">
        {!! Form::label('bill_number', __('membership::lang.bill_number')) !!}
        <input type="text" 
               id="bill_number" 
               name="bill_number" 
               class="form-control"
               placeholder="Type or select bill number"
               list="bill_number_list"
               autocomplete="off">
        <datalist id="bill_number_list">
        </datalist>
        <small class="text-muted" id="bill_helper" style="display:none;">
            <i class="fa fa-info-circle"></i> <span id="bill_count">0</span> bill(s) available
        </small>
    </div>

    <div class="col-md-3">
        {!! Form::label('amount', __('membership::lang.amount')) !!}
        {!! Form::number('amount', 0, [
            'class'=>'form-control',
            'step'=>$step,
            'id'=>'amount'
        ]) !!}
    </div>
</div>

{{-- ROW 3 --}}
<div class="row">
    <div class="col-md-3">
        {!! Form::label('current_points', __('membership::lang.current_points')) !!}
        {!! Form::text('current_points', number_format(0, $currency_precision), [
            'class'=>'form-control',
            'readonly',
            'id'=>'current_points',
            'data-current-points'=>0
        ]) !!}
    </div>

    <div class="col-md-3">
        {!! Form::label('earned_points', __('membership::lang.earned_points')) !!}
        {!! Form::number('earned_points', 0, [
            'class'=>'form-control',
            'readonly',
            'step'=>$step,
            'id'=>'earned_points'
        ]) !!}
    </div>

    <div class="col-md-3">
        {!! Form::label('redeemed_points', __('membership::lang.redeemed_points')) !!}
        {!! Form::number('redeemed_points', 0, [
            'class'=>'form-control',
            'step'=>$step,
            'id'=>'redeemed_points',
            'oninput'=>"limitDecimals(this, $currency_precision)"
        ]) !!}
    </div>

    <div class="col-md-3">
        {!! Form::label('point_balance', __('membership::lang.point_balance')) !!}
        {!! Form::number('point_balance', 0, [
            'class'=>'form-control',
            'readonly',
            'step'=>$step,
            'id'=>'point_balance'
        ]) !!}
    </div>
</div>

</div>

<div class="box-footer">
    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
</div>
</div>

{!! Form::close() !!}
</section>
@endsection

{{-- ===================== SCRIPT ===================== --}}
@section('javascript')
<script>
$(document).ready(function () {

    $('.select2').select2();
    
    // Store bill amounts for later use
    window.billAmounts = {};

    /* Member change */
    $('#member_id').on('change', function () {
        let selectedOption = $(this).find(':selected');
        let memberNumber = selectedOption.data('member-number');
        let memberId = $(this).val();
        
        console.log('Member selected - ID:', memberId, 'Number:', memberNumber);
        
        // Set member number
        if (memberNumber) {
            $('#member_number').val(memberNumber);
        } else {
            $('#member_number').val('');
        }

        if (!memberId) {
            $('#member_number').val('');
            $('#current_points').val((0).toFixed({{ $currency_precision }})).data('current-points', 0);
            calculatePointBalance();
            return;
        }

        /* Load current points */
        $.ajax({
            url: '/membership/member-points',
            method: 'GET',
            data: { member_id: memberId },
            success: function (res) {
                console.log('Member points response:', res);
                let points = parseFloat(res.points || 0);
                $('#current_points')
                    .val(points.toFixed({{ $currency_precision }}))
                    .data('current-points', points);
                calculatePointBalance();
            },
            error: function(xhr, status, error) {
                console.error('Error loading member points:', error, xhr.responseText);
                toastr.error('Failed to load member points. Please try again.');
                $('#current_points')
                    .val((0).toFixed({{ $currency_precision }}))
                    .data('current-points', 0);
                calculatePointBalance();
            }
        });

        /* Load bills */
        $.get('/membership/member-bills', { member_id: memberId }, function (res) {
            // Clear the datalist and bill amounts
            $('#bill_number_list').empty();
            $('#bill_number').val(''); // Clear the input field
            window.billAmounts = {};
            
            // Check if we got any bills
            if (res && res.length > 0) {
                // Populate datalist with bill options
                res.forEach(bill => {
                    $('#bill_number_list').append(
                        `<option value="${bill.invoice_no}">${bill.invoice_no} - ${bill.final_total}</option>`
                    );
                    // Store the amount for this bill
                    window.billAmounts[bill.invoice_no] = bill.final_total;
                });
                
                // Show helper text
                $('#bill_count').text(res.length);
                $('#bill_helper').show();
            } else {
                $('#bill_helper').hide();
            }
        });
    });

    /* Bill number input change */
    $('#bill_number').on('input change blur', function () {
        let billNumber = $(this).val().trim();
        
        // Check if this bill number exists in our loaded bills
        if (billNumber && window.billAmounts && window.billAmounts[billNumber]) {
            let amount = window.billAmounts[billNumber];
            $('#amount').val(parseFloat(amount).toFixed({{ $currency_precision }}));
            calculateEarnedPoints();
        }
    });

    /* Business Type change - Load business names */
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

    $('#amount, #membership_business_type_id').on('keyup change', calculateEarnedPoints);
    $('#redeemed_points').on('keyup change', calculatePointBalance);
});

/* ================= FUNCTIONS ================= */

function calculateEarnedPoints() {
    let typeId = $('#membership_business_type_id').val();
    let amount = parseFloat($('#amount').val()) || 0;

    if (!typeId || amount <= 0) {
        $('#earned_points').val((0).toFixed({{ $currency_precision }}));
        calculatePointBalance();
        return;
    }

    $.get('/membership/get-reward-percent', {
        membership_business_type_id: typeId
    }, function (res) {
        let percent = parseFloat(res.reward_point_percent || 0);
        let earned = (amount * percent / 100).toFixed({{ $currency_precision }});
        $('#earned_points').val(earned);
        calculatePointBalance();
    });
}

function calculatePointBalance() {
    let current = parseFloat($('#current_points').data('current-points')) || 0;
    let earned = parseFloat($('#earned_points').val()) || 0;
    let redeemed = parseFloat($('#redeemed_points').val()) || 0;

    let balance = current + earned - redeemed;
    $('#point_balance').val(balance.toFixed({{ $currency_precision }}));
}

function limitDecimals(input, precision) {
    let regex = new RegExp("^\\d+(\\.\\d{0," + precision + "})?$");
    if (!regex.test(input.value)) {
        input.value = input.value.slice(0, -1);
    }
}

function validateForm() {
    let memberId = $('#member_id').val();
    let amount = parseFloat($('#amount').val()) || 0;
    let businessTypeId = $('#membership_business_type_id').val();
    
    if (!memberId) {
        toastr.error('Please select a member');
        $('#member_id').focus();
        return false;
    }
    
    if (!businessTypeId) {
        toastr.error('Please select a business type');
        $('#membership_business_type_id').focus();
        return false;
    }
    
    if (amount <= 0) {
        toastr.error('Please enter a valid amount');
        $('#amount').focus();
        return false;
    }
    
    // Show loading indicator
    $('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    
    return true;
}
</script>
@endsection
