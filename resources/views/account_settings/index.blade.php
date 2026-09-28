<style>
    .text-red {
        color: red;
    }

    .field-error {
        border: 2px solid #dc3545 !important;
        background-color: #fff5f5 !important;
    }

    .field-error:focus {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .s354-save-btn{min-width:120px; min-height:48px; padding:12px 24px; font-size:120%; font-weight:800;}
    .error-message {
        color: #dc3545;
        font-size: 12px;
        margin-top: 3px;
        display: block;
    }

    .field-success {
        border: 1px solid #28a745 !important;
    }

    .shake {
        animation: shake 0.5s;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        10%,
        30%,
        50%,
        70%,
        90% {
            transform: translateX(-5px);
        }

        20%,
        40%,
        60%,
        80% {
            transform: translateX(5px);
        }
    }
</style>

{!! Form::open([
    'url' => action('AccountSettingController@store'),
    'method' => 'post',
    'id' => 'account_settings_add_form',
]) !!}

<!--<input type="hidden" id="customer_id" name="customer_id" required>-->
<input type="hidden" id="cheque_amount" name="cheque_amount">
<input type="hidden" id="cheque_date" name="cheque_date">
<input type="hidden" id="bank_name" name="bank_name">
<input type="hidden" id="cheque_number" name="cheque_number">

<div class="row">
    <div class="col-md-12">
        <h3>@lang('lang_v1.account_opening_balances')</h3>
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('date', __('lang_v1.date') . ':') !!}
                    {!! Form::text('date', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.date')]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('account_type', __('Account Type') . ':') !!}
                    {!! Form::select('account_type', $account_types_opts, null, [
    'id' => 'account_type',
    'class' => 'form-control select2',
    'required',
    'style' => 'width:100%',
    'placeholder' => __('messages.please_select'),
]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('account_sub_type', __('Account Sub Type') . ':') !!}
                    {!! Form::select('account_sub_type', $sub_acn_arr, null, [
    'id' => 'account_sub_type',
    'class' => 'form-control select2',
    'required',
    'style' => 'width:100%',
    'placeholder' => __('messages.please_select'),
]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('group_id', __('lang_v1.account_group') . ':') !!}
                    {!! Form::select('group_id', $account_groups, null, [
    'placeholder' => __('messages.please_select'),
    'style' => 'width: 100%',
    'class' => 'form-control
                                                                                                                                                                                    select2',
]) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('account_id', __('lang_v1.account') . ':') !!}
                    {!! Form::select('account_id', [], null, [
    'placeholder' => __('messages.please_select'),
    'required',
    'style' => 'width: 100%',
    'class' => 'cheque_acc_id form-control
                                                                                                                                                                                    select2',
]) !!}
                </div>
            </div>

            <div class="col-md-3 chequeRelated" hidden>
                <div class="form-group">
                    {!! Form::label('customer_id', 'Customer:') !!}
                    {!! Form::select('customer_id', $customers, null, [
    'placeholder' => __('messages.please_select'),
    'style' => 'width: 100%',
    'class' => 'cheque_customer_id form-control
                                                                                                                                                                                    select2',
]) !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('amount', __('lang_v1.amount') . ':') !!}
                    {!! Form::text('amount', null, ['required', 'class' => 'form-control', 'placeholder' => __('lang_v1.amount')]) !!}
                    <small class="text-red chequeRelated" hidden><b>Added: </b><span
                            id="addedTotal">0</span>&nbsp;<b>Balance: </b><span id="balTotal">0</span> <span
                            class="badge bg-danger" onClick="calculateTotals();popupModal();"
                            style="cursor: pointer;">Edit</span> </small>
                </div>
            </div>

        </div>
    </div>
    <div class="clearfix"></div>
    <div class="col-md-12">
        {{-- Modified by Engr. Alex -- task 7889: Default Date Range button --}}
        <button type="button" id="defaultDateRangeBtn" class="btn btn-info pull-left">
            <i class="fa fa-calendar"></i> <span id="defaultDateRangeBtnLabel">Default Date Range</span>
        </button>
        <button type="submit" id="saveForm" class="btn btn-primary pull-right s354-save-btn" disabled><i class="fa fa-save"></i> @lang('lang_v1.save')</button>
    </div>
</div>

{!! Form::close() !!}

{{-- Modified by Engr. Alex -- task 7889: Default Date Range Modal --}}
<div class="modal fade" id="defaultDateRangeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-calendar"></i> Default Date Range</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Date &amp; Time (Select Date Range)</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        <input type="text" id="defaultDateRangePicker" class="form-control" readonly
                               placeholder="Select date range..." style="background:#fff; cursor:pointer;">
                    </div>
                    <input type="hidden" id="defaultDateRangeStart">
                    <input type="hidden" id="defaultDateRangeEnd">
                    <input type="hidden" id="defaultDateRangeLabel">
                    <small class="text-muted">Uses the system standard date range picker (same as account books)</small>
                </div>
                <div class="form-group">
                    <label>Added User</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->username }}" disabled>
                </div>
                <hr>
                <h5><strong>History (latest applied first):</strong></h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th>Date Range</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Added By</th>
                                <th>Saved At</th>
                            </tr>
                        </thead>
                        <tbody id="defaultDateRangeHistoryBody">
                            <tr><td colspan="5" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" id="saveDefaultDateRange" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<br>
<div class="clearfix"></div>
<br>
<div class="col-md-12">

    @component('components.filters', ['title' => __('report.filters')])
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('date1', __('lang_v1.date') . ':') !!}
                {!! Form::text('date1', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.date'), 'readonly']) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('account_type1', __('Account Type') . ':') !!}
                {!! Form::select('account_type1', $account_types_opts, null, [
    'id' => 'account_type1',
    'class' => 'form-control select2',
    'style' => 'width:100%',
    'placeholder' => __('lang_v1.all'),
]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('account_sub_type1', __('Account Sub Type') . ':') !!}
                {!! Form::select('account_sub_type1', $sub_acn_arr, null, [
    'id' => 'account_sub_type1',
    'class' => 'form-control select2',
    'style' => 'width:100%',
    'placeholder' => __('lang_v1.all'),
]) !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('group_id2', __('lang_v1.account_group') . ':') !!}
                {!! Form::select('group_id2', $account_groups, null, [
    'placeholder' => __('messages.please_select'),
    'required',
    'style' => 'width: 100%',
    'class' => 'form-control select2',
]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('account_id2', __('lang_v1.account') . ':') !!}
                {!! Form::select('account_id2', [], null, [
    'placeholder' => __('messages.please_select'),
    'required',
    'style' => 'width: 100%',
    'class' => 'form-control select2',
]) !!}
            </div>
        </div>
    </div>
    @endcomponent

</div>

<div class="row">
    <div class="col-md-12">

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="account_setting_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.date')</th>
                        <th>@lang('lang_v1.account_type')</th>
                        <th>@lang('lang_v1.account_sub_type')</th>
                        <th>@lang('lang_v1.account_group')</th>
                        <th>@lang('lang_v1.account')</th>
                        <th>@lang('lang_v1.amount')</th>
                        <th>@lang('lang_v1.added_by')</th>
                        @if (!empty($can_edit_ob))
                            <th class="notexport">@lang('messages.action')</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<div class="modal" id="viewCheques">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">
                    <span class="text-red"><b>Customer: </b><span id="customerName"></span></span>
                </h4>
                <button type="button" class="btn btn-danger pull-right" data-dismiss="modal">X</button>
            </div>
            <div class="modal-header" style="padding-top: 0;">
                <div class="row col-md-12">
                    <div class="col-md-6">
                        <span class="text-red"><b>Cheque Amount: </b><span id="chAmt"></span></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-red"><b>Balance Amount to Enter: </b><span id="balAmt"></span></span>
                    </div>
                </div>
            </div>

            <div class="modal-body">

                <div class="card">
                    <form id="chequeDetailsForm">
                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('cheque_amount', 'Amount:') !!} <span class="text-red">*</span>
                                    {!! Form::text('cheque_amount[]', null, [
    'class' => 'form-control cheque_amount',
    'placeholder' => 'Amount',
    'required',
]) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('cheque_date', 'Cheque Date:') !!} <span class="text-red">*</span>
                                    {!! Form::date('cheque_date[]', null, [
    'class' => 'cheque_date form-control',
    'placeholder' => __('lang_v1.date'),
    'required',
]) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('cheque_bank', 'Bank:') !!} <span class="text-red">*</span>
                                    {!! Form::text('bank_name[]', null, [
    'class' => 'cheque_bank form-control',
    'placeholder' => 'Bank',
    'required',
]) !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    {!! Form::label('cheque_number', 'Cheque Number:') !!} <span
                                        class="text-red">*</span>
                                    {!! Form::text('cheque_number[]', null, [
    'class' => 'cheque_number form-control',
    'placeholder' => 'Cheque Number',
    'required',
]) !!}
                                </div>
                            </div>

                            <div class="col-md-1">
                                <a href="#" id="addRow" class="btn btn-success"><i class="fa fa-plus"></i></a>
                            </div>

                        </div>
                        <div class="addedRows"></div>
                    </form>
                </div>

            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addChequeDetails">Add</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>

        </div>
    </div>
</div>

<script>
    // Populate account dropdown based on group selection
    $('select[name="group_id"]').change(function () {
        var group_id = $(this).val();
        var account_dropdown = $('select[name="account_id"]');

        if (group_id) {
            $.ajax({
                url: '/accounting-module/get-account-by-group',
                method: 'GET',
                data: {
                    group_id: group_id
                },
                success: function (response) {
                    account_dropdown.empty();
                    account_dropdown.append('<option value="">Select Account</option>');

                    $.each(response, function (id, name) {
                        account_dropdown.append('<option value="' + id + '">' + name +
                            '</option>');
                    });

                    account_dropdown.trigger('change');
                }
            });
        } else {
            account_dropdown.empty();
            account_dropdown.append('<option value="">Select Account</option>');
        }
    });

    $('.cheque_acc_id').change(function () {
        if ($(this).val() == "{{ $chequeId }}") {
            $('#cheques_details').removeAttr('hidden');
            $('.chequeRelated').removeAttr('hidden');
            $('.cheque_customer_id').attr('required', true);
            $('#cheque_date').attr('required', true);
            $('#cheque_bank').attr('required', true);
            $('#cheque_number').attr('required', true);
        } else {
            $('#cheques_details').attr('hidden', true);
            $('.chequeRelated').attr('hidden', true);

            $('.cheque_customer_id').removeAttr('required');
            $('#cheque_date').removeAttr('required');
            $('#cheque_bank').removeAttr('required');
            $('#cheque_number').removeAttr('required');
        }
    });


    function updateModal() {
        // Get customer name from main form dropdown
        $("#customerName").text($('.cheque_customer_id option:selected').text());

        var amount = 0;
        amount = $("#amount").val();
        var added = 0;
        $("input[name='cheque_amount[]']").each(function () {
            if (parseFloat($(this).val())) {
                added += parseFloat($(this).val());
            }

        });

        var bal = amount - added;

        $("#chAmt").text(added);
        $("#balAmt").text(bal);

    }



    function popupModal() {
        // Check if customer is selected before opening modal
        if (!$('.cheque_customer_id').val() || $('.cheque_customer_id').val() === "") {
            toastr.error("Please select a customer first!");
            return;
        }
        $("#viewCheques").modal('show');
    }

    function calculateTotals() {
        var amount = 0;
        amount = $("#amount").val();
        var added = 0;
        $("input[name='cheque_amount[]']").each(function () {
            if (parseFloat($(this).val())) {
                added += parseFloat($(this).val());
            }

        });

        var bal = amount - added;

        $("#addedTotal").text(added);
        $("#balTotal").text(bal);

        if (bal > 0 || bal < 0) {
            $('#saveForm').hide();
        } else {
            $('#saveForm').show();
        }

    }



    $("#amount").blur(function () {
        if ($('.cheque_acc_id').val() == "{{ $chequeId }}") {
            calculateTotals();
            popupModal();
        }
    });


    $(document).on('keyup', ".cheque_amount", function () {
        updateModal();
    });


    $('#viewCheques').on('show.bs.modal', function (e) {
        updateModal();
    });


    $('#viewCheques').on('hidden.bs.modal', function () {
        calculateTotals();
    });


    function addchequeRow() {
        var html = "";


        html += "<div class='row'> \
                <div class='col-md-3'> \
                    <div class='form-group'> \
                        <label for='cheque_amount'>Amount: <span class='text-red'>*</span></label> \
                        <input class='form-control cheque_amount' type='text' name='cheque_amount[]' placeholder='Amount' required /> \
                    </div> \
                </div> \
                <div class='col-md-3'> \
                    <div class='form-group'> \
                        <label for='cheque_date'>Cheque Date: <span class='text-red'>*</span></label> \
                        <input class='cheque_date form-control' type='date' name='cheque_date[]' placeholder='Date' required /> \
                    </div> \
                </div> \
                <div class='col-md-3'> \
                    <div class='form-group'> \
                        <label for='bank_name'>Bank: <span class='text-red'>*</span></label> \
                        <input class='cheque_bank form-control' type='text' name='bank_name[]' placeholder='Bank' required /> \
                    </div> \
                </div> \
                <div class='col-md-2'> \
                    <div class='form-group'> \
                        <label for='cheque_number'>Cheque Number: <span class='text-red'>*</span></label> \
                        <input class='cheque_number form-control' type='text' name='cheque_number[]' placeholder='Cheque Number' required /> \
                    </div> \
                </div> \
                <div class='col-md-1'> \
                    <a href='#' class='btn btn-danger removeRow'><i class='fa fa-minus'></i></a>\
                </div>\
            </div>";


        $(".addedRows").append(html);
        $(".select2").select2();
    }

    $(document).on('click', '.removeRow', function () {
        $(this).closest('.row').remove();
    });

    $(document).on('click', '#addRow', function () {
        addchequeRow();
    });

    // Update modal when main customer dropdown changes
    $(document).on('change', '.cheque_customer_id', function () {
        updateModal();
    });

    // Function to validate cheque form fields with visual feedback
    function validateChequeFields() {
        var isValid = true;
        var firstErrorField = null;

        // Remove all previous error styling and messages
        $('.field-error').removeClass('field-error shake');
        $('.error-message').remove();
        $('.field-success').removeClass('field-success');

        // Validate all amount fields
        $("input[name='cheque_amount[]']").each(function () {
            if (!$(this).val() || $(this).val().trim() === '') {
                $(this).addClass('field-error shake');
                if (!$(this).next('.error-message').length) {
                    $(this).closest('.form-group').append('<span class="error-message">Amount is required</span>');
                }
                isValid = false;
                if (!firstErrorField) firstErrorField = $(this);
            } else if (isNaN($(this).val()) || parseFloat($(this).val()) <= 0) {
                $(this).addClass('field-error shake');
                if (!$(this).next('.error-message').length) {
                    $(this).closest('.form-group').append('<span class="error-message">Amount must be a valid number</span>');
                }
                isValid = false;
                if (!firstErrorField) firstErrorField = $(this);
            } else {
                $(this).addClass('field-success');
            }
        });

        // Validate all cheque date fields
        $("input[name='cheque_date[]']").each(function () {
            if (!$(this).val() || $(this).val().trim() === '') {
                $(this).addClass('field-error shake');
                if (!$(this).next('.error-message').length) {
                    $(this).closest('.form-group').append('<span class="error-message">Cheque Date is required</span>');
                }
                isValid = false;
                if (!firstErrorField) firstErrorField = $(this);
            } else {
                $(this).addClass('field-success');
            }
        });

        // Validate all bank name fields
        $("input[name='bank_name[]']").each(function () {
            if (!$(this).val() || $(this).val().trim() === '') {
                $(this).addClass('field-error shake');
                if (!$(this).next('.error-message').length) {
                    $(this).closest('.form-group').append('<span class="error-message">Bank Name is required</span>');
                }
                isValid = false;
                if (!firstErrorField) firstErrorField = $(this);
            } else {
                $(this).addClass('field-success');
            }
        });

        // Validate all cheque number fields
        $("input[name='cheque_number[]']").each(function () {
            if (!$(this).val() || $(this).val().trim() === '') {
                $(this).addClass('field-error shake');
                if (!$(this).next('.error-message').length) {
                    $(this).closest('.form-group').append('<span class="error-message">Cheque Number is required</span>');
                }
                isValid = false;
                if (!firstErrorField) firstErrorField = $(this);
            } else {
                $(this).addClass('field-success');
            }
        });

        // Focus on first error field
        if (firstErrorField) {
            firstErrorField.focus();
            // Remove shake animation after it completes
            setTimeout(function () {
                $('.shake').removeClass('shake');
            }, 500);
        }

        return isValid;
    }

    // Remove error styling when user starts typing
    $(document).on('input change', 'input[name="cheque_amount[]"], input[name="cheque_date[]"], input[name="bank_name[]"], input[name="cheque_number[]"]', function () {
        $(this).removeClass('field-error');
        $(this).closest('.form-group').find('.error-message').remove();
    });

    // Handle Add button click
    $(document).on('click', '#addChequeDetails', function (e) {
        e.preventDefault();

        if (validateChequeFields()) {
            // Check balance
            var amount = parseFloat($("#amount").val()) || 0;
            var added = 0;
            $("input[name='cheque_amount[]']").each(function () {
                if (parseFloat($(this).val())) {
                    added += parseFloat($(this).val());
                }
            });

            var bal = amount - added;

            if (Math.abs(bal) > 0.005) {
                toastr.warning("Balance amount must be zero. Current balance: " + bal.toFixed(2));
                return false;
            }

            // All validations passed
            toastr.success("Cheque details added successfully!");
            $('#viewCheques').modal('hide');
            calculateTotals();
        } else {
            toastr.error("Please fill all required fields!");
        }
    });

    $('#account_settings_add_form').on('submit', function (e) {
        e.preventDefault();

        if ($('.cheque_acc_id').val() == "{{ $chequeId }}") {
            var chequeForm = $("#chequeDetailsForm").serialize();

            const params = new URLSearchParams(chequeForm);

            // create an empty object to store the data
            const formData = {};

            // loop through the parameter entries and populate the formData object
            for (const [key, value] of params.entries()) {
                const match = key.match(/^(.+)\[\]$/);
                if (match) {
                    const name = match[1];
                    if (!formData[name]) {
                        formData[name] = [];
                    }
                    formData[name].push(value);
                } else {
                    formData[key] = value;
                }
            }

            // Check if customer is selected in main form
            if (!$('.cheque_customer_id').val() || $('.cheque_customer_id').val() === "") {
                toastr.error("Please select a customer in the main form!");
                return;
            }

            // Validate all cheque fields are filled
            var hasEmptyField = false;
            var emptyFieldName = '';

            $("input[name='cheque_amount[]']").each(function () {
                if (!$(this).val() || $(this).val().trim() === '') {
                    hasEmptyField = true;
                    emptyFieldName = 'Amount';
                    return false;
                }
            });

            if (!hasEmptyField) {
                $("input[name='cheque_date[]']").each(function () {
                    if (!$(this).val() || $(this).val().trim() === '') {
                        hasEmptyField = true;
                        emptyFieldName = 'Cheque Date';
                        return false;
                    }
                });
            }

            if (!hasEmptyField) {
                $("input[name='bank_name[]']").each(function () {
                    if (!$(this).val() || $(this).val().trim() === '') {
                        hasEmptyField = true;
                        emptyFieldName = 'Bank Name';
                        return false;
                    }
                });
            }

            if (!hasEmptyField) {
                $("input[name='cheque_number[]']").each(function () {
                    if (!$(this).val() || $(this).val().trim() === '') {
                        hasEmptyField = true;
                        emptyFieldName = 'Cheque Number';
                        return false;
                    }
                });
            }

            if (hasEmptyField) {
                toastr.error("Please fill all fields! " + emptyFieldName + " is required.");
                calculateTotals();
                popupModal();
            } else {
                // Customer ID is already set in main form, no need to update it

                $("#cheque_amount").val(formData['cheque_amount']);
                $("#cheque_date").val(formData['cheque_date']);
                $("#bank_name").val(formData['bank_name']);
                $("#cheque_number").val(formData['cheque_number']);

                $('#account_settings_add_form')[0].submit();

            }
        } else {
            $('#account_settings_add_form')[0].submit();
        }


    });

    // Modified by Engr. Alex -- task 7889: Default Date Range button logic using system daterangepicker
    $(document).ready(function () {

        function s354OpeningBalanceReady(){
            var isCheque = $('.cheque_acc_id').val() == "{{ $chequeId }}";
            var baseReady = $('#date').val() && $('#account_type').val() && $('#account_sub_type').val() && $('#group_id').val() && $('.cheque_acc_id').val() && $.trim($('#amount').val()) !== '';
            if (isCheque) {
                var amount = parseFloat($('#amount').val()) || 0;
                var added = 0;
                $('.cheque_amount').each(function(){ added += parseFloat($(this).val()) || 0; });
                baseReady = baseReady && $('.cheque_customer_id').val() && Math.abs(amount - added) <= 0.005;
            }
            $('#saveForm').prop('disabled', !baseReady);
        }
        $(document).on('change input', '#date,#account_type,#account_sub_type,#group_id,.cheque_acc_id,.cheque_customer_id,#amount,.cheque_amount,.cheque_date,.cheque_bank,.cheque_number', s354OpeningBalanceReady);
        setInterval(s354OpeningBalanceReady, 800);
        $('#account_settings_add_form').on('submit.s354', function(){
            var $btn = $('#saveForm');
            if ($btn.prop('disabled')) { return false; }
            $btn.prop('disabled', true).addClass('disabled');
        });

        // Initialise the system standard daterangepicker on the modal input
        $('#defaultDateRangePicker').daterangepicker(
            dateRangeSettings,
            function (start, end, label) {
                $('#defaultDateRangePicker').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                $('#defaultDateRangeStart').val(start.format('YYYY-MM-DD'));
                $('#defaultDateRangeEnd').val(end.format('YYYY-MM-DD'));
                $('#defaultDateRangeLabel').val(label);
            }
        );

        // Intercept Custom Date Range selection if system supports it
        $('#defaultDateRangePicker').on('apply.daterangepicker', function (ev, picker) {
            $('#defaultDateRangeStart').val(picker.startDate.format('YYYY-MM-DD'));
            $('#defaultDateRangeEnd').val(picker.endDate.format('YYYY-MM-DD'));
            var lbl = picker.chosenLabel || (picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(moment_date_format));
            $('#defaultDateRangeLabel').val(lbl);
            $('#defaultDateRangePicker').val(
                picker.startDate.format(moment_date_format) + ' ~ ' + picker.endDate.format(moment_date_format)
            );
        });

        // Load current setting and history when button is clicked
        $('#defaultDateRangeBtn').on('click', function () {
            $.ajax({
                method: 'GET',
                url: '/accounting-module/account-settings/default-date-range',
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        // Pre-fill picker with current saved range
                        if (result.current) {
                            var s = moment(result.current.start_date);
                            var e = moment(result.current.end_date);
                            $('#defaultDateRangePicker').data('daterangepicker').setStartDate(s);
                            $('#defaultDateRangePicker').data('daterangepicker').setEndDate(e);
                            $('#defaultDateRangePicker').val(
                                s.format(moment_date_format) + ' ~ ' + e.format(moment_date_format)
                            );
                            $('#defaultDateRangeStart').val(result.current.start_date);
                            $('#defaultDateRangeEnd').val(result.current.end_date);
                            $('#defaultDateRangeLabel').val(result.current.date_range_label);
                        }
                        // Render history table
                        var historyHtml = '';
                        $.each(result.history, function (i, row) {
                            var badge = i === 0 ? ' <span class="label label-success">Active</span>' : '';
                            historyHtml += '<tr>'
                                + '<td><strong>' + row.date_range_label + '</strong>' + badge + '</td>'
                                + '<td>' + row.start_date + '</td>'
                                + '<td>' + row.end_date + '</td>'
                                + '<td>' + (row.added_user || '') + '</td>'
                                + '<td>' + row.created_at + '</td>'
                                + '</tr>';
                        });
                        $('#defaultDateRangeHistoryBody').html(
                            historyHtml || '<tr><td colspan="5" class="text-center">No history yet</td></tr>'
                        );
                    }
                },
            });
            $('#defaultDateRangeModal').modal('show');
        });

        // Save
        $('#saveDefaultDateRange').on('click', function () {
            var start = $('#defaultDateRangeStart').val();
            var end   = $('#defaultDateRangeEnd').val();
            var label = $('#defaultDateRangeLabel').val() || (start + ' ~ ' + end);

            if (!start || !end) {
                toastr.error('Please select a date range first.');
                return;
            }

            $.ajax({
                method: 'POST',
                url: '/accounting-module/account-settings/default-date-range',
                data: {
                    start_date:       start,
                    end_date:         end,
                    date_range_label: label,
                    _token:           '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('#defaultDateRangeBtnLabel').text('Default Date Range – ' + result.date_range_label);
                        $('#defaultDateRangeModal').modal('hide');
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    });

</script>