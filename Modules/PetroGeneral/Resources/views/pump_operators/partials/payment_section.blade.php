<style>
    .btn-large {
        padding: 18px 28px;
        font-size: 22px; //change this to your desired size
        line-height: normal;
    }

    .active {
        background: #666 !important;
    }

    #key_pad input {
        border: none;
    }

    #key_pad button {
        height: 80px;
        width: 30%;
        font-size: 25px;
        margin: 2px 1px;
        border: none !important;
    }

    .payment_type_checkbox {
        display: none;
    }

    .btn-disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
<form name="calculator">
    <div class="clearfix"></div>
    <br />
    <div class="">
        <div class="col-md-8 col-lg-8">
            <div class="row">
                <h2 style="color: red; text-align: center;">
                    @if (session('status') && is_array(session('status')))
                        @php
                            $output = session('status');
                            if (! empty($output['success']) && isset($output['collection_form_no'])) {
                                $collection_form_no = $output['collection_form_no'];
                            }
                        @endphp
                    @endif
                    Shift NO: {{ $shift_number }}
                    | Form No.: {{ $collection_form_no }}
                </h2>
            </div>
            <div class="row">
                <div class="col-md-5">
                    <h2>@lang('petrogeneral::lang.payments')</h2>
                </div>
                <div class="col-md-6">
                    <input name="display" class="form-control input-lg amount input_number"
                        style="margin-top: 10px; background: #fff; border: 2px solid #333;" id="amount"
                        value="" />
                    <input type="hidden" name="payment_type" id="payment_type" value="" />
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="@if ($pop_up) col-md-12 @else col-md-8 @endif">
            <div class="col-md-5 col-lg-5">
                <div class="row">
                    <div class="btn-group-vertical">
                        <label class="payment_type_btn cash_payment_btn btn btn-large btn-flat btn-block btn-primary">
                            <input
                                class="payment_type_checkbox @if (!empty($enter_cash_denoms) && $enter_cash_denoms == 'yes') cash_denoms_enter @endif"
                                type="checkbox" name="payment_type" value="cash" autocomplete="off" />
                            @lang('petrogeneral::lang.cash')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat card_payment_btn btn-block btn-info">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="card"
                                autocomplete="off" />
                            @lang('petrogeneral::lang.card')
                            <input type="hidden" id="sub_card_type">
                            <input type="hidden" id="sub_slip_no">
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-danger add_cheque_payment">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="cheque"
                                autocomplete="off" /> @lang('petrogeneral::lang.cheque')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-warning">
                            <!-- <input class="payment_type_checkbox @if (!empty($direct_cr) && $direct_cr === 'yes') po_credit_payment @endif" type="checkbox" name="payment_type" value="credit" autocomplete="off" /> @lang('petrogeneral::lang.credit') -->
                            <input class="payment_type_checkbox  po_credit_payment" type="checkbox" name="payment_type"
                                value="credit" autocomplete="off" /> @lang('petrogeneral::lang.credit')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-success">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type"
                                value="multiple_credit" autocomplete="off" /> @lang('petrogeneral::lang.multiple_credit')
                        </label>

                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-danger">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="other"
                                autocomplete="off" /> @lang('petrogeneral::lang.other')
                        </label>

                    </div>
                </div>
            </div>
            <div id="key_pad" class="row col-md-6 text-center" style="margin-left: 7px;">
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
        @if (!$pop_up)
            <div class="col-md-3">
                <div class="row">

                    <!--<h5 class="text-danger">@lang('petrogeneral::lang.balance_to_settle'): {{ @num_format($balance_to_deposit) }}</h5>-->

                    <button class="btn btn-flat btn-lg btn-block add_other_sales" type="button"
                        style="background: #8F3A84; color: #ffffff;">@lang('petrogeneral::lang.enter_meters')</button>
                    <br />

                    <button id="amount_correct_btn" class="btn btn-success btn-flat btn-lg btn-block amount-correct"
                        type="button">@lang('petrogeneral::lang.amount_correct_click_here')</button>
                    <br />

                    <a href="{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@dashboard') }}"><input
                            value="Dashboard" class="btn btn-flat btn-lg btn-block"
                            style="color: #fff; background-color: #810040;" type="button" /> </a>

                    <br />
                    <button disabled value="save" id="payment_submit" name="submit"
                        class="btn btn-flat btn-lg btn-block" style="color: #fff; background-color: #2874a6;"
                        type="button">@lang('lang_v1.save')</button>
                    <br />
                    <span onclick="reset()">
                        <button type="button" class="btn btn-flat btn-lg btn-block"
                            style="color: #fff; background-color: #cc0000;" type="button"><i class="fa fa-refresh"
                                aria-hidden="true"></i> @lang('petrogeneral::lang.cancel')</button>
                    </span>
                    <br />
                    <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}"
                        class="btn btn-flat btn-block btn-lg pull-right"
                        style="background-color: orange; color: #fff;">@lang('petrogeneral::lang.logout')</a>
                </div>
            </div>
        @endif
    </div>
</form>
@php
    $collection_form_no = '';
    $show_payment_followup_modal = false;
@endphp
@if (session('status') && is_array(session('status')))
    @php
        $output = session('status');
        if (! empty($output['success']) && isset($output['collection_form_no'])) {
            $collection_form_no = $output['collection_form_no'] ?? '';
        }
        if (! empty($output['success']) && ! empty($collection_form_no) && empty($output['meter_sale_saved'])) {
            $show_payment_followup_modal = true;
        }
    @endphp
@endif
<input type="hidden" class="collection_form_no" id="collection_form_no" value="{{ $collection_form_no }}">
<input type="hidden" id="pump_operator" value="{{ Auth::user()->pump_operator_id ?? '' }}">
<div id="reloadConfirmationModal" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="reloadConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reloadConfirmationModalLabel">Confirm Another Payment for Form No.</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Need to Enter Another Payment?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary pull-right" id="cancelReload">Yes</button>
                {{-- <button type="button" class="btn btn-secondary" id="confirmReload">No</button> --}}
                {{-- <a href="/petro-general/pump-operators/dashboard" class="btn btn-secondary pull-left" style="background-color: #810040; color: white;">No</a> --}}
                <button type="button" class="btn btn-secondary pull-left go-dashboard"
                    style="background-color: #810040; color: white;">No</button>
            </div>
        </div>
    </div>
</div>
@if ($show_payment_followup_modal)
    <script>
        $(document).ready(function() {
            var formNo = "{{ $collection_form_no }}";
            console.log("Form number from session:", formNo);
            if (formNo && formNo !== "" && formNo !== "0") {
                $("#reloadConfirmationModalLabel").html("Confirm Another Payment for Form No. " + formNo);
                $("#reloadConfirmationModal").modal("show");
            } else {
                console.error("Form number is empty or invalid:", formNo);
            }
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        $("#confirmReload").on("click", function() {
            location.reload();
        });

        $("#cancelReload").off().on("click", function() {
            $("#meter_sales_compulsory").val("no");
            $("#reloadConfirmationModal").modal("hide");
            $("#reloadConfirmationModal").css("display", "none");
            
            // Show all buttons in active mode
            $(".payment_type_btn").removeClass("active");
            if (typeof reset === 'function') {
                reset();
            }
        });

        $(document).on('click', '.go-dashboard', function(e) {
            e.preventDefault();
            $("#meter_sales_compulsory").val("yes");
            $("#reloadConfirmationModal").modal("hide");
            $("#reloadConfirmationModal").css("display", "none");

            // Show all buttons in inactive mode
            $(".payment_type_btn").addClass("active");
            $(".payment_type_checkbox").attr("checked", false);
        });

    });
</script>
<input type="hidden" id="meter_sales_compulsory" value="{{ $meter_sales_compulsory ? 'yes' : 'no' }}">
<script>
    $(document).ready(function() {
        $(".payment_type_btn").each(function(i, ele) {
            var meter_sales_compulsory = $("#meter_sales_compulsory").val();
            if (meter_sales_compulsory == "yes") {
                $(ele).addClass("active");
                $(this).find(".payment_type_checkbox").prop("checked", false);
            }
        });

        $(document).on("click", ".payment_type_btn", function(e) {
            var meter_sales_compulsory = $("#meter_sales_compulsory").val();
            if (meter_sales_compulsory == "yes") {
                e.preventDefault();
                toastr.error("First Enter Meters");
                return false;
            }
        });
    });
</script>
