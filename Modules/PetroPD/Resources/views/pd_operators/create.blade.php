<div class="modal-dialog petropd-operator-dialog" role="document">
    <div class="modal-content petropd-operator-modal-content">
        {!! Form::open([
            'url' => action('\\Modules\\PetroPD\\Http\\Controllers\\PDOperatorController@store'),
            'method' => 'post',
            'id' => 'add_pumps_form',
            'class' => 'petropd-operator-create-form',
            'autocomplete' => 'off'
        ]) !!}

        <div class="modal-header petropd-operator-modal-header">
            <a href="{{ action('\\Modules\\PetroPD\\Http\\Controllers\\PDOperatorController@index') }}" class="close" aria-label="Close" style="text-decoration:none;">
                <span aria-hidden="true">&times;</span>
            </a>
            <h4 class="modal-title">@lang('petropd::lang.add_pump_operator')</h4>
        </div>

        <div class="modal-body petropd-operator-modal-body">
            <style>
                /* ZIP 317 - Add Pump Operator font normalized to match Edit modal. */
                .view_modal .modal-dialog.petropd-operator-dialog,
                .modal .modal-dialog.petropd-operator-dialog,
                .petropd-operator-dialog {
                    width: 720px !important;
                    max-width: 720px !important;
                    margin: 30px auto !important;
                }
                .petropd-operator-modal-content {
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                    border: 0 !important;
                    border-radius: 14px !important;
                    overflow: hidden !important;
                    background: #ffffff !important;
                    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.22) !important;
                }
                .petropd-operator-modal-header {
                    background: #ffffff !important;
                    border-bottom: 1px solid #eeeeee !important;
                    padding: 16px 20px !important;
                }
                .petropd-operator-modal-header .modal-title {
                    color: #1f2937 !important;
                    font-size: 20px !important;
                    font-weight: 500 !important;
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                    line-height: 26px !important;
                    margin: 0 !important;
                }
                .petropd-operator-modal-header .close {
                    color: #9ca3af !important;
                    opacity: 1 !important;
                    text-shadow: none !important;
                    font-size: 26px !important;
                    font-weight: 700 !important;
                    line-height: 1 !important;
                    margin-top: -2px !important;
                    padding: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    box-shadow: none !important;
                }
                .petropd-operator-modal-body {
                    background: #ffffff !important;
                    padding: 18px 20px 8px 20px !important;
                    max-height: calc(100vh - 205px) !important;
                    overflow-y: auto !important;
                    overflow-x: hidden !important;
                }
                .petropd-operator-create-form .row {
                    display: flex !important;
                    flex-wrap: wrap !important;
                    margin-left: -12px !important;
                    margin-right: -12px !important;
                }
                .petropd-operator-create-form .row > .col-md-6 {
                    float: none !important;
                    width: 50% !important;
                    max-width: 50% !important;
                    flex: 0 0 50% !important;
                    padding-left: 12px !important;
                    padding-right: 12px !important;
                    box-sizing: border-box !important;
                }
                .petropd-operator-create-form .row > .col-md-12 {
                    float: none !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    flex: 0 0 100% !important;
                    padding-left: 12px !important;
                    padding-right: 12px !important;
                    box-sizing: border-box !important;
                }
                .petropd-operator-create-form,
                .petropd-operator-create-form * {
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                }
                .petropd-operator-create-form .form-group { margin-bottom: 16px !important; }
                .petropd-operator-create-form label {
                    display: block !important;
                    color: #555555 !important;
                    font-size: 14px !important;
                    font-weight: 500 !important;
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                    margin-bottom: 7px !important;
                    line-height: 20px !important;
                }
                .petropd-operator-create-form .form-control {
                    width: 100% !important;
                    height: 44px !important;
                    min-height: 44px !important;
                    border: 1px solid #d8e0ea !important;
                    border-radius: 10px !important;
                    background: #ffffff !important;
                    color: #555555 !important;
                    font-size: 15px !important;
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                    padding: 9px 13px !important;
                    box-shadow: none !important;
                }
                .petropd-operator-create-form .form-control:focus {
                    border-color: #2c7be5 !important;
                    box-shadow: 0 0 0 3px rgba(44, 123, 229, 0.10) !important;
                }
                .petropd-operator-create-form .select2-container { width: 100% !important; }
                .petropd-operator-create-form .select2-container .select2-selection--single {
                    height: 44px !important;
                    border-radius: 10px !important;
                    border: 1px solid #d8e0ea !important;
                }
                .petropd-operator-create-form .select2-container--default .select2-selection--single .select2-selection__rendered {
                    line-height: 42px !important;
                    padding-left: 13px !important;
                    color: #555555 !important;
                    font-size: 15px !important;
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                }
                .petropd-operator-create-form .select2-container--default .select2-selection--single .select2-selection__arrow {
                    height: 42px !important;
                    right: 8px !important;
                }
                .petropd-operator-create-form .checkbox {
                    margin: 4px 0 8px 0 !important;
                    padding-left: 0 !important;
                }
                .petropd-operator-create-form .checkbox label {
                    font-size: 14px !important;
                    font-weight: 500 !important;
                    color: #555555 !important;
                    line-height: 22px !important;
                    padding-left: 28px !important;
                }
                .petropd-operator-create-form .checkbox input[type="checkbox"] {
                    margin-left: -28px !important;
                    margin-top: 4px !important;
                }
                .petropd-operator-modal-footer {
                    background: #ffffff !important;
                    border-top: 1px solid #eeeeee !important;
                    padding: 16px 20px !important;
                    text-align: right !important;
                }
                .petropd-operator-modal-footer .btn {
                    height: 42px !important;
                    min-height: 42px !important;
                    max-height: 42px !important;
                    min-width: 86px !important;
                    border-radius: 9px !important;
                    font-size: 14px !important;
                    font-weight: 500 !important;
                    font-family: Roboto, "Helvetica Neue", Arial, sans-serif !important;
                    padding: 0 20px !important;
                    line-height: 42px !important;
                    margin-left: 10px !important;
                    box-shadow: none !important;
                    box-sizing: border-box !important;
                    vertical-align: middle !important;
                }
                .petropd-operator-modal-footer .btn-primary {
                    background: #2c7be5 !important;
                    border-color: #2c7be5 !important;
                    color: #ffffff !important;
                }
                .petropd-operator-modal-footer .btn-default,
                .petropd-operator-modal-footer .petropd-close-link {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    height: 42px !important;
                    min-height: 42px !important;
                    max-height: 42px !important;
                    min-width: 86px !important;
                    border-radius: 9px !important;
                    background: #f8fafc !important;
                    border: 1px solid #cfd8e3 !important;
                    color: #333333 !important;
                    text-decoration: none !important;
                    text-align: center !important;
                    vertical-align: middle !important;
                    cursor: pointer !important;
                    padding: 0 20px !important;
                    line-height: 42px !important;
                    box-sizing: border-box !important;
                }
                .petropd-operator-modal-footer .btn-default:hover,
                .petropd-operator-modal-footer .petropd-close-link:hover,
                .petropd-operator-modal-footer .btn-default:focus,
                .petropd-operator-modal-footer .petropd-close-link:focus {
                    background: #eef2f7 !important;
                    border-color: #b8c4d1 !important;
                    color: #111827 !important;
                    text-decoration: none !important;
                }
                
                .petropd-operator-create-form .commission_ap_div.hide,
                .petropd-operator-create-form .commission_ap_div[style*="display:none"] {
                    display: none !important;
                }

                @media (max-width: 767px) {
                    .view_modal .modal-dialog.petropd-operator-dialog,
                    .modal .modal-dialog.petropd-operator-dialog,
                    .petropd-operator-dialog {
                        width: 96vw !important;
                        max-width: 96vw !important;
                        margin: 10px auto !important;
                    }
                    .petropd-operator-modal-body {
                        max-height: calc(100vh - 175px) !important;
                        padding: 16px !important;
                    }
                    .petropd-operator-create-form .row > .col-md-6,
                    .petropd-operator-create-form .row > .col-md-12 {
                        width: 100% !important;
                        max-width: 100% !important;
                        flex: 0 0 100% !important;
                    }
                    .petropd-operator-modal-footer .btn {
                        width: 100% !important;
                        margin-left: 0 !important;
                        margin-bottom: 8px !important;
                    }
                }
            </style>

            <div class="row">
                <div class="col-md-6"><div class="form-group">{!! Form::label('name', __('petropd::lang.name') . ':*') !!}{!! Form::text('name', null, ['class' => 'form-control name', 'required', 'placeholder' => __('petropd::lang.name')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('address', __('petropd::lang.address') . ':*') !!}{!! Form::text('address', null, ['class' => 'form-control address', 'required', 'placeholder' => __('petropd::lang.address')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('mobile', __('petropd::lang.mobile') . ':*') !!}{!! Form::text('mobile', null, ['class' => 'form-control mobile input_number', 'required', 'placeholder' => __('petropd::lang.mobile')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('landline', __('petropd::lang.landline')) !!}{!! Form::text('landline', null, ['class' => 'form-control landline input_number', 'placeholder' => __('petropd::lang.landline')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('dob', __('petropd::lang.dob') . ':*') !!}{!! Form::date('dob', null, ['class' => 'form-control dob', 'required', 'placeholder' => __('petropd::lang.dob')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('cnic', __('petropd::lang.cnic') . ':*') !!}{!! Form::text('cnic', null, ['class' => 'form-control cnic input_number', 'required', 'placeholder' => __('petropd::lang.cnic')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('username', __('petropd::lang.username') . ':*') !!}{!! Form::text('username', null, ['class' => 'form-control username', 'required', 'placeholder' => __('petropd::lang.username')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('password', 'Passcode:*') !!}{!! Form::text('password', $generate_passcode ?? null, ['class' => 'form-control password', 'required', 'placeholder' => 'Passcode']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('opening_balance', __('petropd::lang.opening_balance')) !!}{!! Form::text('opening_balance', 0, ['class' => 'form-control opening_balance input_number', 'placeholder' => __('petropd::lang.opening_balance')]) !!}</div></div>
                <div class="col-md-6 hide opening_balance_type_div"><div class="form-group">{!! Form::label('opening_balance_type', __('petropd::lang.opening_balance_type') . ':*') !!}{!! Form::select('opening_balance_type', ['shortage' => 'Shortage', 'excess' => 'Excess'], 'shortage', ['class' => 'form-control select2 opening_balance_type', 'placeholder' => __('petropd::lang.please_select'), 'style' => 'width: 100%;']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('location_id', __('petropd::lang.location') . ':*') !!}{!! Form::select('location_id', $locations, $default_location_id ?? null, ['class' => 'form-control select2 fuel_tank_location location_id', 'required', 'placeholder' => __('petropd::lang.please_select'), 'style' => 'width: 100%;']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('commission_type', __('petropd::lang.commission_type') . ':*') !!}{!! Form::select('commission_type', ['none' => 'None', 'fixed' => 'Fixed', 'percentage' => 'Percentage'], 'none', ['class' => 'form-control select2 commission_type', 'required', 'placeholder' => __('petropd::lang.please_select'), 'style' => 'width: 100%;']) !!}</div></div>
                <div class="col-md-6 hide commission_ap_div" style="display:none;">
                    <div class="form-group">
                        <label for="commission_ap" id="commission_ap_dynamic_label" class="commission_ap_dynamic_label">Commission Amount</label>
                        {!! Form::text('commission_ap', null, ['class' => 'form-control input_number commission_ap', 'placeholder' => 'Commission Amount']) !!}
                    </div>
                </div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('transaction_date', __('petropd::lang.transaction_date') . ':*') !!}{!! Form::date('transaction_date', date('Y-m-d'), ['class' => 'form-control transaction_date', 'required', 'placeholder' => __('petropd::lang.transaction_date')]) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('is_default', __('petropd::lang.is_default') . ':*') !!}{!! Form::select('is_default', ['0' => __('messages.no'), '1' => __('messages.yes')], 0, ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('can_fullscreen', __('petropd::lang.can_fullscreen') . ':*') !!}{!! Form::select('can_fullscreen', ['0' => __('messages.no'), '1' => __('messages.yes')], 0, ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;']) !!}</div></div>
                <div class="col-md-12">
                    <div class="checkbox">
                        <label>{!! Form::checkbox('is_petro_pd_only', 1, old('is_petro_pd_only', false)) !!} <strong>Petro PD only</strong></label>
                        <p class="help-block">This operator remains available in Petro PD and is excluded from Petro Direct, Petro and Settlement SW.</p>
                    </div>
                </div>
                <div class="col-md-12"><div class="checkbox"><label>{!! Form::hidden('hide_in_direct_settlement_if_pending_shifts', 0) !!}{!! Form::checkbox('hide_in_direct_settlement_if_pending_shifts', 1, false, ['id' => 'hide_in_direct_settlement_if_pending_shifts']) !!} Do not show in the Direct Settlement, if any Assigned Shifts are Pending in the Settlements</label></div></div>
            </div>
        </div>

        <div class="modal-footer petropd-operator-modal-footer">
            <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang('messages.save')</button>
            <a href="{{ action('\\Modules\\PetroPD\\Http\\Controllers\\PDOperatorController@index') }}" class="btn btn-default petropd-close-link" role="button">@lang('messages.close')</a>
        </div>

        {!! Form::close() !!}
    </div>
</div>


<script type="text/javascript">
    /**
     * PetroPD Add Pump Operator modal close helper.
     * This intentionally does not depend only on Bootstrap data attributes because
     * the modal content is loaded through AJAX into .view_modal on some installs.
     */
    window.petropdForceCloseOperatorModal = function(event, element) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }
        }

        var btn = element || (event ? event.target : null);
        var modal = null;

        if (btn && btn.closest) {
            modal = btn.closest('.modal');
        }

        try {
            if (window.bootstrap && modal && window.bootstrap.Modal) {
                var bsModal = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
                bsModal.hide();
            }
        } catch (e) {}

        if (window.jQuery) {
            var $ = window.jQuery;
            var $modal = modal ? $(modal) : $(btn).closest('.modal');

            if ($modal.length && $.fn.modal) {
                try { $modal.modal('hide'); } catch (e) {}
            }

            if ($('.view_modal').length) {
                if ($.fn.modal) {
                    try { $('.view_modal').modal('hide'); } catch (e) {}
                }
                $('.view_modal').empty().hide().removeClass('show in').attr('aria-hidden', 'true');
            }

            $('.modal.show, .modal.in').hide().removeClass('show in').attr('aria-hidden', 'true');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({'padding-right': '', 'overflow': ''});
        }

        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show', 'in');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) { backdrop.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';

        return false;
    };

    document.addEventListener('click', function(event) {
        var target = event.target && event.target.closest ? event.target.closest('.petropd-force-close-modal') : null;
        if (target) {
            window.petropdForceCloseOperatorModal(event, target);
        }
    }, true);
</script>

<script type="text/javascript">
    (function($) {
        var $form = $('#add_pumps_form');
        var $modal = $form.closest('.modal');
        var redirectUrl = "{{ action('\\Modules\\PetroPD\\Http\\Controllers\\PDOperatorController@index') }}";

        if ($.fn.select2) {
            $form.find('.select2').select2({ dropdownParent: $modal.length ? $modal : $('body'), width: '100%' });
        }

        // Date of Birth must be enterable and must also show a picker.
        if ($.fn.datepicker) {
            $form.find('.dob').datepicker({ autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true });
        }

        $(document).off('submit.petropdCreateOperator', '#add_pumps_form')
            .on('submit.petropdCreateOperator', '#add_pumps_form', function(e) {
                e.preventDefault();
                var $submitBtn = $form.find('button[type="submit"]');
                $submitBtn.prop('disabled', true);
                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    success: function(result) {
                        if (result && result.success === false) {
                            if (typeof toastr !== 'undefined') { toastr.error(result.msg || 'Unable to save.'); }
                            $submitBtn.prop('disabled', false);
                            return;
                        }
                        if (typeof toastr !== 'undefined' && result && result.msg) { toastr.success(result.msg); }
                        window.location.href = redirectUrl;
                    },
                    error: function(xhr) {
                        var msg = 'Unable to save. Please check required fields.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            msg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var keys = Object.keys(xhr.responseJSON.errors);
                            if (keys.length && xhr.responseJSON.errors[keys[0]].length) {
                                msg = xhr.responseJSON.errors[keys[0]][0];
                            }
                        }
                        if (typeof toastr !== 'undefined') { toastr.error(msg); }
                        $submitBtn.prop('disabled', false);
                    }
                });
                return false;
            });

        $(document).off('change.petropdOpeningBalance', '#add_pumps_form .opening_balance')
            .on('change.petropdOpeningBalance', '#add_pumps_form .opening_balance', function() {
                var val = parseFloat($(this).val());
                if (!isNaN(val) && val > 0) {
                    $form.find('.opening_balance_type_div').removeClass('hide');
                    $form.find('.opening_balance_type').prop('required', true);
                } else {
                    $form.find('.opening_balance_type_div').addClass('hide');
                    $form.find('.opening_balance_type').prop('required', false).val('').trigger('change');
                    if (isNaN(val) || val < 0) {
                        if (typeof toastr !== 'undefined') { toastr.error('Please enter non negative number'); }
                        $(this).val('0');
                    }
                }
            });

        $(document).off('change.petropdCommissionType', '#add_pumps_form .commission_type')
            .on('change.petropdCommissionType', '#add_pumps_form .commission_type', function() {
                var value = $(this).val();
                if (value === 'none' || value === '') {
                    $form.find('.commission_ap_div').addClass('hide');
                    $form.find('.commission_ap').prop('required', false).val('');
                } else {
                    $form.find('.commission_ap_div').removeClass('hide');
                    $form.find('.commission_ap').prop('required', true);
                }
                $form.find('.commission_percentage').toggleClass('hide', value !== 'percentage');
                $form.find('.commission_fixed').toggleClass('hide', value !== 'fixed');
            });

        $(document).off('change.petropdUsernameCheck', '#add_pumps_form .username')
            .on('change.petropdUsernameCheck', '#add_pumps_form .username', function() {
                var $input = $(this);
                var username = $input.val();
                if (!username) { return; }
                $.ajax({ method: 'get', url: "{{ action('\\Modules\\PetroPD\\Http\\Controllers\\PDOperatorController@checUsername') }}", data: { username: username }, success: function(result) { if (result && !result.success) { toastr.error(result.msg); $input.val(''); } } });
            });

        $(document).off('change.petropdPasscodeCheck', '#add_pumps_form .password')
            .on('change.petropdPasscodeCheck', '#add_pumps_form .password', function() {
                var $input = $(this);
                var passcode = $input.val();
                if (!passcode) { return; }
                $.ajax({ method: 'get', url: "{{ action('\\Modules\\PetroPD\\Http\\Controllers\\PDOperatorController@checPasscode') }}", data: { passcode: passcode }, success: function(result) { if (result && !result.success) { toastr.error(result.msg); $input.val(''); } } });
            });

        $form.find('.opening_balance').trigger('change');
        $form.find('.commission_type').trigger('change');
    })(jQuery);
</script>





<script type="text/javascript">
    (function($) {
        function petropdCreateCommissionVisibilityFix() {
            var $form = $('#add_pumps_form');
            if (!$form.length) {
                return;
            }

            var commissionType = $form.find('.commission_type').val();
            var $wrapper = $form.find('.commission_ap_div');
            var $input = $form.find('.commission_ap');
            var $label = $form.find('#commission_ap_dynamic_label');

            if (commissionType === 'percentage') {
                $wrapper.removeClass('hide').show();
                $input.prop('required', true);
                $label.text('Commission Percentage on Income:*');
            } else if (commissionType === 'fixed') {
                $wrapper.removeClass('hide').show();
                $input.prop('required', true);
                $label.text('Fixed Amount per liter:*');
            } else {
                $wrapper.addClass('hide').hide();
                $input.prop('required', false).val('');
                $label.text('Commission Amount');
            }
        }

        $(document)
            .off('change.is1450CommissionFix', '#add_pumps_form .commission_type')
            .on('change.is1450CommissionFix', '#add_pumps_form .commission_type', function() {
                petropdCreateCommissionVisibilityFix();
            });

        petropdCreateCommissionVisibilityFix();
        setTimeout(petropdCreateCommissionVisibilityFix, 100);
        setTimeout(petropdCreateCommissionVisibilityFix, 500);
    })(jQuery);
</script>
