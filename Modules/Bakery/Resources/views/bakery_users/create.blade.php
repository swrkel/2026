<style>
    .bakery-user-modal-dialog {
        width: 95%;
        max-width: 1000px;
    }

    .bakery-user-modal-dialog .modal-body {
        max-height: calc(100vh - 220px);
        overflow-y: auto;
    }

    @media (max-width: 991px) {
        .bakery-user-modal-dialog {
            width: 98%;
            max-width: 98vw;
        }
    }

    .daterangepicker {
        z-index: 1060 !important;
    }
</style>

<div class="modal-dialog modal-lg bakery-user-modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\Bakery\Http\Controllers\BakeryUserController@store'),
            'method' => 'post',
            'id' => 'add_pumps_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Add User</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('name', __('petro::lang.name') . ':*') !!}
                            {!! Form::text('name', null, [
                                'class' => 'form-control name',
                                'required',
                                'placeholder' => __('petro::lang.name'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('address', __('petro::lang.address') . ':*') !!}
                            {!! Form::text('address', null, [
                                'class' => 'form-control address',
                                'required',
                                'placeholder' => __('petro::lang.address'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('mobile', __('petro::lang.mobile') . ':*') !!}
                            {!! Form::text('mobile', null, [
                                'class' => 'form-control mobile input_number',
                                'required',
                                'placeholder' => __('petro::lang.mobile'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('landline', __('petro::lang.landline')) !!}
                            {!! Form::text('landline', null, [
                                'class' => 'form-control landline input_number',
                                'placeholder' => __('petro::lang.landline'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('dob', __('petro::lang.dob') . ':*') !!}
                            {!! Form::text('dob', !empty(old('dob')) ? old('dob') : date('m/d/Y'), [
                                'class' => 'form-control dob',
                                'required',
                                'placeholder' => __('petro::lang.dob'),
                                'id' => 'dob',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('cnic', __('petro::lang.cnic') . ':*') !!}
                            {!! Form::text('cnic', null, [
                                'class' => 'form-control cnic',
                                'required',
                                'placeholder' => __('petro::lang.cnic'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('email', __('petro::lang.email') . ':*') !!}
                            {!! Form::email('email', null, [
                                'class' => 'form-control email ',
                                'required',
                                'placeholder' => __('petro::lang.email'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('username', __('petro::lang.username') . ':*') !!}
                            {!! Form::text('username', null, [
                                'class' => 'form-control username ',
                                'required',
                                'placeholder' => __('petro::lang.username'),
                            ]) !!}
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('password', __('petro::lang.passcode') . ':*') !!}
                            {!! Form::text('password', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('business.password'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('confirm_password', __('petro::lang.confirm_passcode') . ':*') !!}
                            {!! Form::text('confirm_password', null, [
                                'class' => 'form-control',
                                'required',
                                'placeholder' => __('business.password'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('opening_balance', __('petro::lang.opening_balance')) !!}
                            {!! Form::text('opening_balance', null, [
                                'class' => 'form-control opening_balance
                                                        input_number',
                                'placeholder' => __('petro::lang.opening_balance'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6 opening_balance_type_div">
                        <div class="form-group">
                            {!! Form::label('opening_balance_type', 'Opening Balance Type:*') !!}
                            {!! Form::select('opening_balance_type', ['shortage' => 'Shortage', 'excess' => 'Excess'], null, [
                                'class' => 'form-control select2
                                                        opening_balance_type',
                                'placeholder' => __('petro::lang.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('location_id', __('petro::lang.location') . ':*') !!}
                            {!! Form::select('location_id', $locations, !empty(old('location_id')) ? old('location_id') : $default_location_id, [
                                'class' => 'form-control select2
                                                        fuel_tank_location',
                                'required',
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('commission_type', __('petro::lang.commission_type') . ':*') !!}
                            {!! Form::select('commission_type', ['none' => 'None', 'fixed' => 'Fixed', 'percentage' => 'Percentage'], 'none', [
                                'class' => 'form-control select2
                                                        commission_type',
                                'required',
                                'placeholder' => __('petro::lang.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('petro::lang.transaction_date') . ':*') !!}
                            {!! Form::input('text', 'transaction_date', null, [
                                'class' => 'form-control transaction_date',
                                'required',
                                'placeholder' => __('petro::lang.transaction_date'),
                            ]) !!}
                        </div>
                    </div>




                    @if ($commission_type_permission)
                        <div class="col-md-6 hide commission_ap_div">
                            <div class="form-group">
                                {!! Form::label('commission_ap', __('petro::lang.commission_percentage') . ':*', [
                                    'class' => 'commission_percentage hide',
                                ]) !!}
                                {!! Form::label('commission_ap', __('petro::lang.commission_fixed') . ':*', [
                                    'class' => 'commission_fixed hide',
                                ]) !!}
                                {!! Form::text('commission_ap', null, [
                                    'class' => 'form-control input_number
                                                            commission_ap',
                                    'placeholder' => __('petro::lang.commission_ap'),
                                ]) !!}
                            </div>
                        </div>
                    @endif
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
            let dobInput = $('.dob');

            dobInput.off().data('daterangepicker')?.remove();
            dobInput.daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                minYear: 1900,
                maxYear: 2100,
                autoUpdateInput: true,
                autoApply: true,
                locale: {
                    format: 'MM/DD/YYYY'
                }
            });
        });

        $(document).off('change.bakeryUserOpeningBalance', '.opening_balance').on('change.bakeryUserOpeningBalance', '.opening_balance', function(e) {
            if ($(this).val() === '' || parseFloat($(this).val()) >= 0) {
                $('.opening_balance_type_div').removeClass('hide');
            } else {
                toastr.error('Please enter none negative number');
                $(this).val('');
            }
        });
        $('.commission_type').change(function() {
            if ($(this).val() == 'none' || $(this).val() == '') {
                $('.commission_ap_div').addClass('hide');
            } else {
                $('.commission_ap_div').removeClass('hide');
            }
            if ($(this).val() == 'percentage') {
                $('.commission_percentage').removeClass('hide');
            } else {
                $('.commission_percentage').addClass('hide');
            }
            if ($(this).val() == 'fixed') {
                $('.commission_fixed').removeClass('hide');
            } else {
                $('.commission_fixed').addClass('hide');
            }
        });
        $('.location_id').select2();
        $('.dob').datepicker({
            autoclose: true,
            format: 'mm/dd/yyyy'
        });

        $('#username').change(function() {
            let username = $(this).val();
            $.ajax({
                method: 'get',
                url: "{{ action('\Modules\Bakery\Http\Controllers\BakeryUserController@checUsername') }}",
                data: {
                    username
                },
                success: function(result) {
                    if (!result.success) {
                        toastr.error(result.msg);
                        $(this).val('');
                    }
                },
            });

        })

        function generateRandomFourDigitString() {
            return String(Math.floor(Math.random() * 10000)).padStart(4, '0');
        }
        let passcode = generateRandomFourDigitString();
        console.log(passcode);
        $.ajax({
            method: 'get',
            url: "{{ action('\Modules\Bakery\Http\Controllers\BakeryUserController@checPasscode') }}",
            data: {
                passcode
            },
            success: function(result) {
                if (!result.success) {
                    $('#password').val('');
                    $('#confirm_password').val('');
                    toastr.error(result.msg);
                } else {
                    $('#password').val(passcode);
                    $('#confirm_password').val(passcode);
                }
            },
        });
        $('#password').change(function() {
            let passcode = $(this).val();
            $('#confirm_password').val(passcode);
            $.ajax({
                method: 'get',
                url: "{{ action('\Modules\Bakery\Http\Controllers\BakeryUserController@checPasscode') }}",
                data: {
                    passcode
                },
                success: function(result) {
                    if (!result.success) {
                        $('#password').val('');
                        $('#confirm_password').val('');
                        toastr.error(result.msg);
                    }
                },
            });

        });

        $('.commission_type').trigger('change');
    </script>

    <script>
        $(document).ready(function() {
            // Destroy any old instances
            $('.transaction_date').off().data('daterangepicker')?.remove();

            // Init New DatePicker with year dropdown
            $('.transaction_date').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true, // <-- IMPORTANT
                minYear: 1900,
                maxYear: 2100,
                drops: 'up',
                autoUpdateInput: true,
                autoApply: true,
                locale: {
                    format: 'MM/DD/YYYY'
                }
            });
        });
    </script>
