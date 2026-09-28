<style>
    /* MA-002 (IS-1925 #2) */
    .amount-correct .amount-correct-title {
        display: block;
        font-weight: 700;
        line-height: 1.25;
    }

    .amount-correct .amount-correct-sub {
        display: block;
        font-size: 0.72em;
        font-weight: 400;
        opacity: .92;
        line-height: 1.2;
    }

    .amount-correct.btn-disabled,
    .amount-correct:disabled {
        opacity: .55;
        cursor: not-allowed;
    }
</style>
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

    /* IS1759-02: the confirmation question was too small on the pumper UI. */
    #reloadConfirmationModal .modal-title {
        font-size: 22px !important;
        font-weight: 700;
    }

    #reloadConfirmationModal .modal-body {
        font-size: 21px !important;
        font-weight: 600;
        padding-top: 20px;
        padding-bottom: 20px;
    }
</style>
<form name="calculator">
    <div class="clearfix"></div>
    <br />
    <div class="">
        <div class="col-md-8 col-lg-8">
            <div class="row">
                <h2 style="color: red; text-align: center;">
                    @if (session('status'))
                        @php
                            $output = session('status');
                            if ($output['success'] && isset($output['collection_form_no'])) {
                                $collection_form_no = $output['collection_form_no'] ?? $collection_form_no;
                            }
                        @endphp
                    @endif
                    Shift NO: {{ $shift_number }}
                    | Form No.: {{ $collection_form_no }}
                </h2>
            </div>
            <div class="row">
                <div class="col-md-5">
                    <h2>@lang('petropd::lang.payments')</h2>
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
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-primary">
                            <input
                                class="payment_type_checkbox @if (!empty($enter_cash_denoms) && $enter_cash_denoms == 'yes') cash_denoms_enter @endif"
                                type="checkbox" name="payment_type" value="cash" autocomplete="off" />
                            @lang('petropd::lang.cash')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat card_payment_btn btn-block btn-info">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="card"
                                autocomplete="off" />
                            @lang('petropd::lang.card')
                            <input type="hidden" id="sub_card_type">
                            <input type="hidden" id="sub_slip_no">
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-danger add_cheque_payment">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="cheque"
                                autocomplete="off" /> @lang('petropd::lang.cheque')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-warning">
                            <!-- <input class="payment_type_checkbox @if (!empty($direct_cr) && $direct_cr === 'yes') po_credit_payment @endif" type="checkbox" name="payment_type" value="credit" autocomplete="off" /> @lang('petropd::lang.credit') -->
                            <input class="payment_type_checkbox  po_credit_payment" type="checkbox" name="payment_type"
                                value="credit" autocomplete="off" /> @lang('petropd::lang.credit')
                        </label>
                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-success">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type"
                                value="multiple_credit" autocomplete="off" /> @lang('petropd::lang.multiple_credit')
                        </label>

                        <label class="payment_type_btn btn btn-large btn-flat btn-block btn-danger">
                            <input class="payment_type_checkbox" type="checkbox" name="payment_type" value="other"
                                autocomplete="off" /> @lang('petropd::lang.other')
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

                    <!--<h5 class="text-danger">@lang('petropd::lang.balance_to_settle'): {{ @num_format($balance_to_deposit) }}</h5>-->

                    <button class="btn btn-flat btn-lg btn-block add_other_sales" type="button"
                        style="background: #8F3A84; color: #ffffff;">@lang('petropd::lang.enter_meters')</button>
                    <br />

                    {{--
                        MA-002 (IS-1925 #2): "Amount Correct" on one line and
                        "Click Here" underneath, as asked.

                        Two spans rather than a line break inside the language
                        string, so the two parts can be sized differently and the
                        translation files stay clean.

                        It also starts DISABLED. syncAmountCorrectState() unlocks
                        it the moment a real amount is entered - previously it was
                        active before anything had been typed.
                    --}}
                    <button id="amount_correct_btn"
                            class="btn btn-success btn-flat btn-lg btn-block amount-correct btn-disabled"
                            type="button" disabled>
                        <span class="amount-correct-title">@lang('petropd::lang.amount_correct')</span>
                        <span class="amount-correct-sub">@lang('petropd::lang.click_here')</span>
                    </button>
                    <br />

                    <a href="{{ action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard') }}"><input
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
                                aria-hidden="true"></i> @lang('petropd::lang.cancel')</button>
                    </span>
                    <br />
                    <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}"
                        class="btn btn-flat btn-block btn-lg pull-right"
                        style="background-color: orange; color: #fff;">@lang('petropd::lang.logout')</a>
                </div>
            </div>
        @endif
    </div>
</form>
@php
    $collection_form_no = '';
@endphp
@if (session('status'))
    @php
        $output = session('status');
        if ($output['success'] && isset($output['collection_form_no'])) {
            $collection_form_no = $output['collection_form_no'] ?? '';
        }
    @endphp
@endif
<input type="hidden" class="collection_form_no" id="collection_form_no" value="{{ $collection_form_no }}">
<input type="hidden" id="pump_operator" value="{{ $pump_operator_id ?? session('pump_operator_id') ?? session('pumper_operator_id') ?? (Auth::user()->pump_operator_id ?? '') }}">
<input type="hidden" id="active_shift_id" value="{{ $shift_id ?? '' }}">
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
                {{-- <a href="/petropd/pd-operators" class="btn btn-secondary pull-left" style="background-color: #810040; color: white;">No</a> --}}
                <button type="button" class="btn btn-secondary pull-left go-dashboard"
                    style="background-color: #810040; color: white;">No</button>
            </div>
        </div>
    </div>
</div>
@if (!empty($collection_form_no))
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
            window.location.href = window.location.pathname;

        });

        $(document).on('click', '.go-dashboard', function() {
            window.location.replace('/petropd/pd-operators');
        });

    });
</script>
<input type="hidden" id="meter_sales_compulsory" value="yes">
@if ($meter_sales_compulsory)
    <script>
        $(".payment_type_btn").each(function(i, ele) {
            var meter_sales_compulsory = $("#meter_sales_compulsory").val();
            if (meter_sales_compulsory == "yes") {
                $(ele).addClass("active");
                $(this).find(".payment_type_checkbox").attr("checked", false);
            }
        });
        $(document).on("click", ".payment_type_btn", function() {
            var meter_sales_compulsory = $("#meter_sales_compulsory").val();
            if (meter_sales_compulsory == "yes") {
                toastr.error("First Enter Meters");
            }
        });
    </script>
@endif
