@extends('layouts.' . $layout)
@section('title', __('pumperdashboard::lang.closing_meter'))
@section('css')
    @parent
    <link rel="stylesheet" href="{{ asset('css/pumper-dashboard-remaining-modern.css') }}?v=20260726-is1779-1">
@endsection
<style>
    .side-label {
        font-size: 21px;
        font-weight: bold;
        padding-top: 5px;
    }

    #key_pad input {
        border: none
    }

    #key_pad button {
        height: 80px;
        width: 80px;
        font-size: 25px;
        margin: 2px 1px;
        border: none !important;
    }

    :focus {
        outline: 0 !important
    }

    .disabled-link {
        pointer-events: none;
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* IS1604: Close Pump enter/arrow button must be green. Keep it green even when disabled by JS. */
    .pd-close-meter-enter-arrow,
    #calculate_total_btn {
        background: #00a65a !important;
        background-color: #00a65a !important;
        color: #fff !important;
        border-color: #008d4c !important;
        font-weight: 900 !important;
    }
</style>
@section('content')
<style>
/* IS2166: the total sale amount, larger and clearly visible.
   It is the figure the operator checks before closing, and it read the same
   size as every field around it. */
/* IS2166: the close-pump form, larger and more compact.

   The labels and fields read small while the page carried a great deal of
   empty space - the <br> between every row plus Bootstrap's own margins. Less
   gap, larger text: the same information in less height. */
.side-label {
    font-size: 115% !important;
    line-height: 1.3;
}
form .form-control.input-lg {
    font-size: 115% !important;
    height: auto !important;
    padding: 8px 12px !important;
}
form br { display: none; }
form .row { margin-bottom: 8px; }
form .form-group { margin-bottom: 6px; }

/* IS2166: the reconfirm popup, 15% larger.

   Scoped to #reconfirmPopup - this is the dialogue where an operator re-enters
   the meter to catch a typing error, and the figures were small enough that
   checking them was the hard part. */
#reconfirmPopup .modal-body label,
#reconfirmPopup .modal-body .control-label,
#reconfirmPopup .modal-body h4,
#reconfirmPopup .modal-body strong {
    font-size: 115% !important;
}
#reconfirmPopup .modal-body input.form-control {
    font-size: 115% !important;
    height: auto !important;
    padding: 8px 12px !important;
}

/* IS2166: the action buttons, 5% taller.

   Padding rather than a fixed height, so a button still fits its label in any
   language - a height in pixels would clip a longer word. */
#dashboard, #save, #cancel, #logout {
    padding-top: 13px !important;
    padding-bottom: 13px !important;
    font-size: 17px !important;
}

.sw-pd-total {
    font-size: 26px !important;
    font-weight: 700 !important;
    color: #1a5632 !important;
    background: #eaf7ef !important;
    border: 2px solid #2e7d4f !important;
    height: auto !important;
    padding: 10px 14px !important;
    text-align: right;
}
</style>
    @include('pumperdashboard::partials.pumper_dashboard_ui_standard')
    <div class="container pumper-ui-standard">
        <div class="col-md-12">
            <br>
            <br>
            <h4 class="text-center" style="color: red;">After entering the Testing Meter (if any) and Closing Meter, then it
                is necessary to click the Green Colour Arrow Button</h4>
            <br>
            {!! Form::open([
                'url' => route('pumperdashboard.close-pump.submit', [
                    'pump_id' => (int) $pump->id,
                    'route_assignment_id' => (int) $assignment->id,
                ]),
                'method' => 'post',
                'id' => 'closing_meter_form',
            ]) !!}
            <input type="hidden" name="pumper_assignment_id" value="{{ $assignment->id }}">
            <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
            <input type="hidden" name="shift_id_snapshot" value="{{ (int) ($assignment->shift_id ?? 0) }}">
            <input type="hidden" name="starting_meter_snapshot" value="{{ number_format($starting_meter, 3, '.', '') }}">
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('pump_no', __('pumperdashboard::lang.pump_no') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('pump_no', $pump->pump_no, ['class' => 'form-control input-lg', 'readonly']) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('sale_price', __('pumperdashboard::lang.sale_price') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('sale_price', number_format($pump->sell_price_inc_tax, $currency_precision, '.', ''), [
                                'class' => 'form-control input-lg',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('starting_meter', __('pumperdashboard::lang.starting_meter') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text(
                                'starting_meter',
                                number_format($starting_meter, 3, '.', ''),
                                ['class' => 'form-control input-lg', 'readonly', 'required'],
                            ) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('testing_ltr', __('pumperdashboard::lang.testing_liters') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('testing_ltr', 0.0, ['class' => 'form-control input-lg inputcalculater']) !!}
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('closing_meter', __('pumperdashboard::lang.closing_meter') . ':', ['class' => 'side-label']) !!}
                        </div>
                        <div class="col-md-7">
                            <div class="input-group">
                                {!! Form::text(
                                    'closing_meter',
                                    null,
                                    [
                                    'class' => 'form-control input-lg inputcalculater',
                                    'required',
                                    'oninput' => "this.value = this.value.match(/^\\d+(\\.\\d{0,3})?/)?.[0] || ''",
                                    ],
                                ) !!}

                                <div class="input-group-addon calculate_total pd-close-meter-enter-arrow disabled-link"
                                    style="background: #00a65a !important; background-color: #00a65a !important; color: #fff !important; cursor: pointer" id="calculate_total_btn"
                                    disabled>
                                    ⏎
                                </div>

                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-5">
                            {!! Form::label('amount', __('pumperdashboard::lang.total_amount') . ':', [
                                'class' => 'side-label
                                                    text-red',
                            ]) !!}
                        </div>
                        <div class="col-md-7">
                            {!! Form::text('amount', 0.0, ['class' => 'form-control input-lg sw-pd-total', 'readonly', 'required']) !!}
                        </div>
                        <input type="hidden" name="sold_ltr" id="sold_ltr" value="0">
                        <input type="hidden" name="amount_hidden" id="amount_hidden" value="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row">
                        <div id="key_pad" tabindex="1">
                            <div class="row text-center" id="calc">
                                <div class="calcBG col-md-12 text-center">
                                    <div class="row">
                                        <button id="7" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">7</button>
                                        <button id="8" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">8</button>
                                        <button id="9" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">9</button>

                                    </div>
                                    <div class="row">
                                        <button id="4" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">4</button>
                                        <button id="5" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">5</button>
                                        <button id="6" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">6</button>
                                    </div>
                                    <div class="row">
                                        <button id="1" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">1</button>
                                        <button id="2" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">2</button>
                                        <button id="3" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">3</button>
                                    </div>
                                    <div class="row">
                                        <button id="backspace" type="button" class="btn btn-danger"
                                            onclick="enterVal(this.id)">⌫</button>
                                        <button id="0" type="button" class="btn btn-primary btn-sm"
                                            onclick="enterVal(this.id)">0</button>
                                        <button id="precision" type="button" class="btn btn-success"
                                            onclick="enterVal(this.id)">.</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard') }}"
                        class="btn btn-flat btn-block btn-lg" style="color: #fff; background-color:#810040;"
                        id="dashboard">@lang('pumperdashboard::lang.dashboard')</a>
                    <br><br>
                    <button type="submit" class="btn btn-flat btn-block btn-lg"
                        style="color: #fff; background-color:#2874A6;" id="save"
                        disabled>@lang('pumperdashboard::lang.save')</button><br><br>

                    <a href="#" class="btn btn-flat btn-block btn-lg"
                        style="color: #fff; background-color:#CC0000;" id="cancel">@lang('pumperdashboard::lang.cancel')</a><br><br>
                    <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}"
                        class="btn btn-flat btn-block btn-lg pull-right disabled-link"
                        style=" background-color: orange; color: #fff;" id="logout" disabled>@lang('pumperdashboard::lang.logout')</a>
                </div>
            </div>
            {!! Form::close() !!}

            {{-- Presentation-only Close Pump verification / print preview. --}}
            @include('pumperdashboard::actions.partials.close_pump_verification')
        </div>
    </div>

    {{--
        MA-002: rebuilt to the supplied design.

        The design's own class names are used - form-card, close-btn,
        info-box, section, field-row, btn-confirm, btn-cancel - so this reads
        the same as the HTML you sent.

        WHAT HAD TO SURVIVE, and did: every reconf_ id, both oninput decimal
        filters, and the reconfirm_button id the enable/disable logic uses.
        The scripts below are untouched.

        Two adjustments to your CSS, and only two:
          form-card sits on modal-content, so the modal supplies the panel -
            its max-width and margin:auto are dropped, as the modal handles
            width and centring
          every rule is prefixed with .pd-reconfirm-close-modal so it cannot
            affect the other modal on this screen
    --}}
    <style>
        .pd-reconfirm-close-modal .form-card {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            padding: 25px 30px;
            position: relative;
        }
        .pd-reconfirm-close-modal .close-btn {
            position: absolute;
            top: 20px;
            right: 25px;
            background-color: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .pd-reconfirm-close-modal .close-btn:hover { background-color: #c7c7c7; }
        .pd-reconfirm-close-modal .form-card h2 {
            text-align: center;
            margin: 0 0 20px;
            color: #222;
            font-size: 20px;
            font-weight: 600;
        }
        .pd-reconfirm-close-modal .info-box {
            background-color: #f0f7ff;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #333;
        }
        .pd-reconfirm-close-modal .info-box strong { font-weight: 600; }
        .pd-reconfirm-close-modal .section { margin-bottom: 20px; }
        .pd-reconfirm-close-modal .section h3 {
            font-size: 16px;
            margin: 0 0 10px;
            color: #333;
            font-weight: 600;
        }
        .pd-reconfirm-close-modal .field-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .pd-reconfirm-close-modal .field-row label {
            flex: 1;
            font-size: 14px;
            color: #444;
            margin: 0;
            font-weight: normal;
        }
        .pd-reconfirm-close-modal .field-row input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            text-align: right;
        }
        .pd-reconfirm-close-modal .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .pd-reconfirm-close-modal .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .pd-reconfirm-close-modal .btn-confirm { background-color: #4CAF50; color: #fff; }
        .pd-reconfirm-close-modal .btn-confirm:hover { background-color: #388E3C; }
        .pd-reconfirm-close-modal .btn-cancel { background-color: #e0e0e0; color: #333; }
        .pd-reconfirm-close-modal .btn-cancel:hover { background-color: #c7c7c7; }
        /* A disabled Confirm must look disabled. */
        .pd-reconfirm-close-modal .btn-confirm:disabled {
            background-color: #a5d6a7;
            cursor: not-allowed;
        }
        /* ================================================================
           IS-PD-RECONFIRM-LAYOUT
           The design specifies a 500px card, but Bootstrap's .modal-dialog
           and the global design-system stylesheet were both widening this to
           the full viewport (~1470px measured), which stretched every input
           across the dialog and left the info box as a tall empty band. The
           rules below restore a compact, professional card and lay each
           section out as three columns instead of three stacked rows.
           Everything is scoped to .pd-reconfirm-close-modal, so no other
           modal or form on the dashboard is affected. !important is required
           throughout because the global rules being overridden use it too.
           ================================================================ */
        .pd-reconfirm-close-modal .modal-dialog {
            width: 322px !important;
            max-width: 94% !important;
            margin: 30px auto !important;
        }
        .pd-reconfirm-close-modal .form-card {
            padding: 16px 18px !important;
        }
        .pd-reconfirm-close-modal h2 {
            font-size: 16px !important;
            margin: 0 0 12px !important;
            padding-right: 30px;
            line-height: 1.3 !important;
        }
        .pd-reconfirm-close-modal .close-btn {
            top: 12px !important;
            right: 12px !important;
            width: 24px !important;
            height: 24px !important;
            font-size: 15px !important;
            line-height: 1 !important;
        }
        .pd-reconfirm-close-modal .info-box {
            min-height: 0 !important;
            height: auto !important;
            padding: 7px 10px !important;
            margin-bottom: 12px !important;
            font-size: 13px !important;
            line-height: 1.45 !important;
        }
        .pd-reconfirm-close-modal .section {
            margin-bottom: 12px !important;
        }
        .pd-reconfirm-close-modal .section h3 {
            font-size: 14px !important;
            margin: 0 0 6px !important;
            padding-bottom: 4px !important;
            border-bottom: 1px solid #eee !important;
        }
        .pd-reconfirm-close-modal .section .field-row {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 8px !important;
            margin-bottom: 6px !important;
        }
        .pd-reconfirm-close-modal .section .field-row label {
            flex: 0 0 auto !important;
            margin: 0 !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #444 !important;
            white-space: nowrap !important;
        }
        .pd-reconfirm-close-modal .section .field-row input {
            flex: 0 0 auto !important;
            width: 126px !important;
            max-width: 126px !important;
            padding: 5px 7px !important;
            height: auto !important;
            font-size: 13px !important;
            line-height: 1.3 !important;
            text-align: right !important;
            border: 1px solid #ccc !important;
            border-radius: 5px !important;
            box-shadow: none !important;
        }
        .pd-reconfirm-close-modal .section .field-row input[readonly] {
            background-color: #f7f9fb !important;
        }
        .pd-reconfirm-close-modal .buttons {
            margin-top: 2px !important;
            gap: 8px !important;
        }
        .pd-reconfirm-close-modal .btn {
            padding: 7px 14px !important;
            font-size: 13px !important;
        }
    </style>
    <div id="reconfirmPopup" class="modal fade pd-reconfirm-close-modal" tabindex="-1" role="dialog" aria-labelledby="reconfirmModalLabel">
        {{--
            IS-PD-RECONFIRM-LAYOUT: the width below is INLINE and !important
            deliberately - do not move it into the stylesheet.

            This dialog was rendering at ~1470px despite a plain inline
            "width: 500px". A plain inline style loses to a stylesheet rule
            marked !important, and something in the global design-system CSS is
            forcing modal dialogs wide. Class-scoped rules in the <style> block
            above were tried twice and lost the same way.

            An inline declaration that is itself !important sits at the top of
            the cascade and cannot be overridden by any stylesheet, which is why
            the sizing that must hold is pinned here rather than in CSS. The
            <style> block above still carries the typography and spacing; it is
            the widths that needed this treatment.
        --}}
        <div class="modal-dialog" role="document"
             style="width: 345px !important; max-width: 94% !important; margin: 30px auto !important;">
            <div class="modal-content form-card">

                <button type="button" class="close-btn" data-dismiss="modal" aria-label="Close">&times;</button>

                <h2 id="reconfirmModalLabel">@lang('pumperdashboard::lang.reconfirm_close_pump')</h2>

                <div class="info-box">
                    <strong>Operator:</strong> {{ $pumper_name ?? '' }} &nbsp;&nbsp;&nbsp;
                    <strong>Shift No:</strong> {{ sprintf("%04d", $shift_number ?? 0) }}
                </div>

                <div class="section">
                    <h3>Meter</h3>
                    <div class="field-row" style="display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:space-between !important;margin-bottom:6px !important;">
                        <label for="reconf_closed" style="flex:0 0 auto !important;margin:0 !important;font-size:13px !important;font-weight:600 !important;color:#444 !important;white-space:nowrap !important;">Closed:</label>
                        <input type="text" id="reconf_closed" style="width:121px !important;max-width:121px !important;padding:5px 7px !important;height:auto !important;font-size:13px !important;line-height:1.3 !important;text-align:right !important;border:1px solid #ccc !important;border-radius:5px !important;box-shadow:none !important;" readonly>
                    </div>
                    <div class="field-row" style="display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:space-between !important;margin-bottom:6px !important;">
                        <label for="reconf_reentered_closed" style="flex:0 0 auto !important;margin:0 !important;font-size:13px !important;font-weight:600 !important;color:#444 !important;white-space:nowrap !important;">Re-enter:</label>
                        <input type="text" id="reconf_reentered_closed" style="width:121px !important;max-width:121px !important;padding:5px 7px !important;height:auto !important;font-size:13px !important;line-height:1.3 !important;text-align:right !important;border:1px solid #ccc !important;border-radius:5px !important;box-shadow:none !important;" placeholder="Enter value"
                            oninput="this.value=this.value.match(/^\d+(\.\d{0,3})?/)?.[0]||'';">
                    </div>
                    <div class="field-row" style="display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:space-between !important;margin-bottom:6px !important;">
                        <label for="reconf_diff_closed" style="flex:0 0 auto !important;margin:0 !important;font-size:13px !important;font-weight:600 !important;color:#444 !important;white-space:nowrap !important;">Difference:</label>
                        <input type="text" id="reconf_diff_closed" style="width:121px !important;max-width:121px !important;padding:5px 7px !important;height:auto !important;font-size:13px !important;line-height:1.3 !important;text-align:right !important;border:1px solid #ccc !important;border-radius:5px !important;box-shadow:none !important;" placeholder="Auto-calc" readonly>
                    </div>
                </div>

                <div class="section">
                    <h3>Testing</h3>
                    <div class="field-row" style="display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:space-between !important;margin-bottom:6px !important;">
                        <label for="reconf_testing_entered" style="flex:0 0 auto !important;margin:0 !important;font-size:13px !important;font-weight:600 !important;color:#444 !important;white-space:nowrap !important;">Entered:</label>
                        <input type="text" id="reconf_testing_entered" style="width:121px !important;max-width:121px !important;padding:5px 7px !important;height:auto !important;font-size:13px !important;line-height:1.3 !important;text-align:right !important;border:1px solid #ccc !important;border-radius:5px !important;box-shadow:none !important;" readonly>
                    </div>
                    <div class="field-row" style="display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:space-between !important;margin-bottom:6px !important;">
                        <label for="reconf_reentered_testing" style="flex:0 0 auto !important;margin:0 !important;font-size:13px !important;font-weight:600 !important;color:#444 !important;white-space:nowrap !important;">Re-enter:</label>
                        <input type="text" id="reconf_reentered_testing" style="width:121px !important;max-width:121px !important;padding:5px 7px !important;height:auto !important;font-size:13px !important;line-height:1.3 !important;text-align:right !important;border:1px solid #ccc !important;border-radius:5px !important;box-shadow:none !important;" placeholder="Enter value"
                            oninput="this.value=this.value.match(/^\d+(\.\d{0,2})?/)?.[0]||'';">
                    </div>
                    <div class="field-row" style="display:flex !important;flex-direction:row !important;align-items:center !important;justify-content:space-between !important;margin-bottom:6px !important;">
                        <label for="reconf_diff_testing" style="flex:0 0 auto !important;margin:0 !important;font-size:13px !important;font-weight:600 !important;color:#444 !important;white-space:nowrap !important;">Difference:</label>
                        <input type="text" id="reconf_diff_testing" style="width:121px !important;max-width:121px !important;padding:5px 7px !important;height:auto !important;font-size:13px !important;line-height:1.3 !important;text-align:right !important;border:1px solid #ccc !important;border-radius:5px !important;box-shadow:none !important;" placeholder="Auto-calc" readonly>
                    </div>
                </div>

                <div class="buttons">
                    <button id="reconfirm_button" type="button" class="btn btn-confirm" disabled>Confirm</button>
                    <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
                </div>

            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm zero Total Amount</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Total Amount is zero, No Values. Are you sure to save?
                </div>
                <div class="modal-footer">
                    <button id="confirmYes" class="btn btn-success" style="float:left;">Yes</button>
                    <button id="confirmNo" class="btn btn-danger" style="float:right;">No</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('javascript')
    <script type="text/javascript">
        $('#closing_meter_form').validate();

        // Prevent double taps/double submissions from creating duplicate day entries.
        var closePumpSubmitting = false;
        $('#closing_meter_form').on('submit', function(e) {
            if (closePumpSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!$(this).valid()) {
                return true;
            }

            closePumpSubmitting = true;
            $('#save')
                .prop('disabled', true)
                .addClass('disabled-link')
                .attr('aria-disabled', 'true')
                .text('Processing...');
        });

        var current_id = null;
        $('.inputcalculater').on('focus', function() {
            //console.log(this.id);
            current_id = this.id;
        });

        function enterVal(val) {


            $('#' + current_id).focus();

            if (val === 'enter') {
                $('#' + current_id).next('.form-control')
                toggle_calculate_total_btn();
                return;
            }
            if (val === 'precision') {
                str = $('#' + current_id).val();
                str = str + '.';
                $('#' + current_id).val(str);
                toggle_calculate_total_btn();
                return;
            }
            if (val === 'backspace') {
                str = $('#' + current_id).val();
                str = str.substring(0, str.length - 1);
                $('#' + current_id).val(str);
                toggle_calculate_total_btn();
                return;
            }
            let closing_meter = $('#' + current_id).val() + val;
            closing_meter = closing_meter.replace(',', '');
            $('#' + current_id).val(closing_meter);
            toggle_calculate_total_btn();
        };




        $('.calculate_total').click(function() {
            let closing_meter = $('#closing_meter').val().replace(',', '');
            if (closing_meter === '' || closing_meter === undefined || closing_meter === NaN) {
                toastr.error('Closing meter value is required');
                $("#save").attr('disabled', true);
                $("#logout").attr('disabled', true);
                $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
                return false;
            }
            let starting_meter = parseFloat($('#starting_meter').val());
            closing_meter = parseFloat($('#closing_meter').val().replace(',', ''));
            let testing_ltr = parseFloat($('#testing_ltr').val().replace(',', '')) || 0;
            let sold_ltr = closing_meter - starting_meter - testing_ltr;

            // S411 urgent: Sufficient quantity / stock availability restriction temporarily disabled
            // as requested, so Close Pumper can be saved even when available quantity is lower.
            // let availableQty = parseFloat(@json($pump->qty_available ?? null));
            // let allowoverselling = ($("#allowoverselling").val() + '').toLowerCase() === 'true';
            // let shouldCheckStock = !isNaN(availableQty) && availableQty > 0 && !allowoverselling;
            // if (shouldCheckStock && sold_ltr > availableQty) {
            //     $('#closing_meter').val(0);
            //
            //     $("#save").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
            //     $("#logout").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
            //
            //     return false;
            // }
            if (closing_meter < starting_meter) {
                toastr.error('Closing meter value should not less then starting meter value');
                $('#closing_meter').val(0);
                $("#save").attr('disabled', true);
                $("#logout").attr('disabled', true);
                $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
                return false;
            }

            calculateTotal();
        });

        function calculateTotal() {
            let sale_price = parseFloat($('#sale_price').val()) || 0;
            let starting_meter = parseFloat($('#starting_meter').val()) || 0;
            let closing_meter = parseFloat($('#closing_meter').val()) || 0;
            let testing_ltr = parseFloat($('#testing_ltr').val()) || 0;
            let sold_ltr = closing_meter - starting_meter - testing_ltr

            let total = sale_price * (sold_ltr);
            __write_number($('#amount'), total);
            $('#sold_ltr').val(sold_ltr);
            $('#amount_hidden').val(total);
            if (total >= 0) {
                if (total == 0) {
                    $("#confirmationModal").modal("show");
                } else {
                    $("#save").attr('disabled', false);
                    $("#logout").attr('disabled', false);
                    $("#save").removeClass('disabled-link').removeAttr('aria-disabled');
                    $("#logout").removeClass('disabled-link').removeAttr('aria-disabled');
                }
            } else {
                $("#save").attr('disabled', true);
                $("#logout").attr('disabled', true);
                $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
            }
        }

        $('#cancel').click(function() {
            $('#closing_meter').val(0);
            $('#testing_ltr').val(0);
            __write_number($('#amount'), 0);
            $('#sold_ltr').val(0);
            $('#amount_hidden').val(0);
            $("#save").attr('disabled', true);
            $("#logout").attr('disabled', true);
            $("#save").addClass('disabled-link').attr('aria-disabled', 'true');
            $("#logout").addClass('disabled-link').attr('aria-disabled', 'true');
        });

        $('#closing_meter').on('input change', function() {
            toggle_calculate_total_btn();
        });

        function toggle_calculate_total_btn() {
            let closing_meter = $('#closing_meter').val().replace(',', '');
            if (closing_meter === '' || closing_meter === undefined || closing_meter === NaN) {
                $("#calculate_total_btn").attr('disabled', true);
                $("#calculate_total_btn").addClass('disabled-link').attr('aria-disabled', 'true');
                return;
            }
            let starting_meter = parseFloat($('#starting_meter').val());
            closing_meter = parseFloat($('#closing_meter').val().replace(',', ''));
            let testing_ltr = parseFloat($('#testing_ltr').val().replace(',', '')) || 0;
            let sold_ltr = closing_meter - starting_meter - testing_ltr

            if (closing_meter < starting_meter) {
                $("#calculate_total_btn").attr('disabled', true);
                $("#calculate_total_btn").addClass('disabled-link').attr('aria-disabled', 'true');
            } else {
                $("#calculate_total_btn").attr('disabled', false).css("background-color", "#2db74c");;

                $("#calculate_total_btn").removeClass('disabled-link').removeAttr('aria-disabled');
            }
        }




        $('#confirmYes').click(function() {
            $("#confirmationModal").modal("hide");
            $("#save").attr('disabled', false);
            $("#logout").attr('disabled', false);
            $("#save").removeClass('disabled-link').removeAttr('aria-disabled');
            $("#logout").removeClass('disabled-link').removeAttr('aria-disabled');
        });

        $('#confirmNo').click(function() {
            $("#confirmationModal").modal("hide");
        });
        // override calculate_total behaviour to include reconfirmation popup
        $(document).ready(function() {
            // utility helpers
            function disableDashboardButtons() {
                $("#save").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
                $("#logout").attr('disabled', true).addClass('disabled-link').attr('aria-disabled', 'true');
            }
            function enableDashboardButtons() {
                $("#save").attr('disabled', false).removeClass('disabled-link').removeAttr('aria-disabled');
                $("#logout").attr('disabled', false).removeClass('disabled-link').removeAttr('aria-disabled');
            }

            function updateReconfDiff() {
                let closed = parseFloat($('#reconf_closed').val() || 0);
                let reclosed = parseFloat($('#reconf_reentered_closed').val() || 0);
                let diff_closed = (closed - reclosed).toFixed(3);
                $('#reconf_diff_closed').val(diff_closed);

                let entered = parseFloat($('#reconf_testing_entered').val() || 0);
                let reenteredtest = parseFloat($('#reconf_reentered_testing').val() || 0);
                let diff_test = (entered - reenteredtest).toFixed(2);
                $('#reconf_diff_testing').val(diff_test);

                let dc = parseFloat($('#reconf_diff_closed').val());
                let dt = parseFloat($('#reconf_diff_testing').val());
                if (dc === 0 && dt === 0) {
                    $('#reconfirm_button').prop('disabled', false);
                } else {
                    $('#reconfirm_button').prop('disabled', true);
                }
            }

            $('#reconf_reentered_closed, #reconf_reentered_testing').on('input blur', function(e) {
                if (e.type === 'blur') {
                    let id = $(this).attr('id');
                    let val = $(this).val();
                    if (val !== '') {
                        let num = parseFloat(val);
                        if (!isNaN(num)) {
                            if (id === 'reconf_reentered_closed') {
                                $(this).val(num.toFixed(3));
                            } else {
                                $(this).val(num.toFixed(2));
                            }
                        }
                    }
                }
                updateReconfDiff();
            });

            $('#reconfirm_button').click(function() {
                $('#reconfirmPopup').modal('hide');
                calculateTotal();
            });

            // attach new handler
            $('.calculate_total').off('click').on('click', function(e) {
                e.preventDefault();
                let closing_meter_raw = $('#closing_meter').val();
                let closing_meter = closing_meter_raw.replace(',', '');
                if (closing_meter === '' || closing_meter === undefined || isNaN(closing_meter)) {
                    toastr.error('Closing meter value is required');
                    disableDashboardButtons();
                    return false;
                }
                let starting_meter = parseFloat($('#starting_meter').val());
                closing_meter = parseFloat(closing_meter);
                let testing_ltr = parseFloat($('#testing_ltr').val().replace(',', '') || 0);

                // validate overselling and closing meter
                let sold_ltr = closing_meter - starting_meter - testing_ltr;
                // S411 urgent: Sufficient quantity / stock availability restriction temporarily disabled
                // as requested, so Close Pumper can be saved even when available quantity is lower.
                // let availableQty = parseFloat(@json($pump->qty_available ?? null));
                // let allowoverselling = ($("#allowoverselling").val() + '').toLowerCase() === 'true';
                // let shouldCheckStock = !isNaN(availableQty) && availableQty > 0 && !allowoverselling;
                // if (shouldCheckStock && sold_ltr > availableQty) {
                //     $('#closing_meter').val(0);
                //     disableDashboardButtons();
                //     return false;
                // }
                if (closing_meter < starting_meter) {
                    toastr.error('Closing meter value should not less then starting meter value');
                    $('#closing_meter').val(0);
                    disableDashboardButtons();
                    return false;
                }

                $('#reconf_closed').val(closing_meter.toFixed(3));
                $('#reconf_testing_entered').val(testing_ltr.toFixed(2));
                $('#reconf_reentered_closed').val('');
                $('#reconf_reentered_testing').val('');
                $('#reconf_diff_closed').val('');
                $('#reconf_diff_testing').val('');
                $('#reconfirm_button').prop('disabled', true);

                $('#reconfirmPopup').modal('show');
            });
        });    </script>
@endsection

