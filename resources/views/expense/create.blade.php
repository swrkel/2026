@extends('layouts.app')
@section('title', __('expense.add_expense'))

@section('content')
<style>
/* S290: remove the duplicated/unprofessional summary block from Add Expense. Detail rows remain the source of truth. */
.s290-expense-clean-form .expense-summary-field{display:none !important;}
.s301-hidden-summary{display:none !important;}
#expense_items_table th,#expense_items_table td{vertical-align:middle;}
.exp315-add-row-wrap{display:flex;justify-content:flex-end;align-items:center;margin-top:8px;margin-bottom:6px;}
#add_expense_item_row.exp315-add-btn{font-size:125%;padding:8px 22px;min-width:105px;}

/* S338 final UI corrections */
.s338-add-row-toolbar{display:flex!important;justify-content:flex-end!important;align-items:center!important;margin:8px 0 6px!important;}
.s338-add-row-toolbar #add_expense_item_row,.exp315-add-row-wrap #add_expense_item_row{font-size:125%!important;padding:10px 28px!important;min-width:120px!important;}
/* S421: keep the row Add button clearly on the far-right as requested. */
.s338-add-row-toolbar{padding-right:0!important;}
.s338-add-row-toolbar #add_expense_item_row{margin-left:auto!important;}
.s338-hide-guidance,.expense-row-help,.expense-items-help,.add-expense-rows-help{display:none!important;}
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
	<h1>@lang('expense.add_expense')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action('ExpenseController@store'), 'method' => 'post', 'id' => 'add_expense_form', 'files'
	=> true ]) !!}
	<div class="box box-solid">
		<div class="box-body">
			<div class="row s290-expense-clean-form" style="margin-top: -10px;">

				@if(count($business_locations) == 1)
				@php
				$default_location = current(array_keys($business_locations->toArray()))
				@endphp
				@else
				@php $default_location = null; @endphp
				@endif
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('location_id', __('purchase.business_location').':*') !!}
						{!! Form::select('location_id', $business_locations,
						!empty($temp_data->location_id)?$temp_data->location_id: $default_location, ['class' =>
						'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
					</div>
				</div>

				{{-- SW Shift No. Renders nothing unless SW is enabled for this
				     business. Cash expenses reduce Balance In Hand. --}}
				<div class="col-sm-6">
					@includeIf('sw::partials.shift_field', ['bindLocation' => 'select[name="location_id"]'])
				</div>

				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date',
							@format_datetime(!empty($temp_data->transaction_date) ? $temp_data->transaction_date : \Carbon\Carbon::now()),
							['class' => 'form-control', 'readonly', 'required', 'id' => 'expense_transaction_date', 'autocomplete' => 'off']);
							!!}
						</div>
					</div>
				</div>

				<div class="col-sm-12">
					<h4 class="box-title" style="margin-top: 0px; margin-bottom: 5px;">@lang('expense.add_expense') @lang('lang_v1.details')</h4>
				</div>

				<div class="col-sm-3 expense-summary-field">
					<div class="form-group">
						{!! Form::label('expense_category_id', __('expense.expense_category').':') !!}
						<div class="input-group">
							{!! Form::select('expense_category_id', $expense_categories,
							!empty($temp_data->expense_category_id)?$temp_data->expense_category_id: null, ['class' =>
							'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
							<span class="input-group-btn">
								<button type="button" class="btn
                                btn-default
                                bg-white btn-flat btn-modal" data-href="{{action('ExpenseCategoryController@create', ['quick_add' => true])}}" title="@lang('lang_v1.add_expense_category')" data-container=".expense_category_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
							</span>
						</div>
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
					<div class="form-group">
						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
						{!! Form::text('ref_no', !empty($temp_data->ref_no)?$temp_data->ref_no: $ref_no, ['class' =>
						'form-control']); !!}
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
					<div class="form-group">
						{!! Form::label('expense_account', __('sale.expense_account') . ':*') !!}
						{!! Form::select('expense_account', $expense_accounts, $expense_account_id, ['class' =>
						'form-control select2', 'placeholder' => __('lang_v1.please_select')]) !!}
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
            		<div class="form-group">
            			{!! Form::label('is_vat', __('lang_v1.is_vat')) !!}
            			{!! Form::select('is_vat', ['0' => __('lang_v1.no'),'1' => __('lang_v1.yes')],null, ['class' => 'form-control
            			select2', 'required']); !!}
            		</div>
            	</div>

				<div class="s301-hidden-summary col-md-3">
					<div class="form-group">
						{!! Form::label('tax_id', __('product.applicable_tax') . ':' ) !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-info"></i>
							</span>
							{!! Form::select('tax_id', $taxes['tax_rates'],
							!empty($temp_data->tax_id)?$temp_data->tax_id:null, ['class' => 'form-control'],
							$taxes['attributes']); !!}

							<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount" value="{{!empty($temp_data->tax_calculation_amount)?$temp_data->tax_calculation_amount:0}}">
						</div>
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
					<div class="form-group">
						{!! Form::label('final_total', __('sale.total_amount') . ':*') !!}
						{!! Form::text('final_total', !empty($temp_data->final_total)?$temp_data->final_total:null,
						['class' => 'form-control input_number', 'placeholder' => __('sale.total_amount'), 'required', 'readonly']);
						!!}
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
					<div class="form-group">
						{!! Form::label('expense_for', __('expense.expense_for').':') !!}
						@show_tooltip(__('tooltip.expense_for'))
						{!! Form::select('expense_for', $employees,!empty($temp_data->expense_for)?$temp_data->expense_for:
						null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
					<div class="form-group">
						{!! Form::label('payee', 'Select Payee' . ':*') !!}
						{!! Form::text('payee', !empty($payee_name->name)?$payee_name->name: 'Payee Not Selected', ['class' =>
						'form-control', 'readonly']); !!}
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-3">
                    <div class="form-group">
                        {{-- IS1522 Daily Shift No --}}
                        {!! Form::label('shift_number', 'Daily Shift No:') !!}
                        {!! Form::select('shift_number', [], !empty($expense->shift_number) ? $expense->shift_number : (!empty($transaction->shift_number) ? $transaction->shift_number : null), [
                            'class' => 'form-control select2',
                            'id' => 'expense_daily_shift_no',
                            'placeholder' => __('messages.please_select'),
                            'style' => 'width:100%'
                        ]) !!}
                    </div>
                </div>

                <div class="s301-hidden-summary col-sm-3">
					<div class="form-group">
						{!! Form::label('contact_id', __('lang_v1.expense_for_contact').':') !!}
						<select name="contact_id" id="supplier_id" class="form-control select2 select2_contact_id" placeholder="{{ __('messages.please_select') }}">
							<option value="">{{ __('messages.please_select') }}</option>
						</select>
					</div>
				</div>

				<div class="s301-hidden-summary col-sm-6">
					<div class="form-group">
						{!! Form::label('additional_notes', __('expense.expense_note') . ':') !!}
						{!! Form::text('additional_notes',
						!empty($temp_data->additional_notes)?$temp_data->additional_notes:null, ['class' =>
						'form-control']); !!}
					</div>
				</div>

				<div class="col-sm-12 exp315-add-row-wrap s338-add-row-toolbar">
					<button type="button" class="btn btn-primary exp315-add-btn" id="add_expense_item_row">
						<i class="fa fa-plus"></i> @lang('messages.add')
					</button>
				</div>

				<div class="clearfix"></div>

				<div class="col-sm-12 table-responsive" style="margin-top: 10px;">
					<table class="table table-bordered" id="expense_items_table">
						<thead>
							<tr>
								<th style="width: 20%;">@lang('expense.expense_category')</th>
								<th style="width: 10%;">@lang('purchase.ref_no')</th>
								<th style="width: 20%;">@lang('sale.expense_account')</th>
								<th style="width: 9%;">@lang('lang_v1.is_vat')</th>
								<th style="width: 10%;">@lang('product.applicable_tax')</th>
								<th style="width: 8%;">@lang('sale.amount')</th>
								<th style="width: 20%;">@lang('expense.expense_note')</th>
								<th style="width: 4%;">@lang('messages.action')</th>
							</tr>
						</thead>
						<tbody id="expense_items_body"></tbody>
					</table>
				</div>

			</div>
		</div>
	</div>
	@include('expense.recur_expense_form_part')
	<!--box end-->
	<div class="box box-solid">
		<div class="box-header">
			<h3 class="box-title">@lang('sale.add_payment')</h3>
		</div>
		<div class="box-body">
			<div class="row">
				<div class="col-md-12 payment_row" data-row_id="0">
					<div id="payment_rows_div">
            			@if (!empty($temp_data->payment))
            			@include('sale_pos.partials.payment_row_form_expense', ['row_index' => 0, 'payment' => $temp_data->payment[0]])
            			@else
            			@include('sale_pos.partials.payment_row_form_expense', ['row_index' => 0])
            			@endif
            			<hr>
            		</div>
				</div>
			</div>
		</div>
	</div>
	<!--box end-->
	<div class="col-sm-12">
		{!! Form::hidden('is_print',0, ['id'=>'print_and_save']) !!}
		<button id="submitBtn" type="submit" class="btn btn-primary pull-right m-8">@lang('messages.save')</button> <!-- @eng 15/2 -->
		<button id="printBtnSave" type="submit" class="btn btn-success pull-right m-8">@lang('messages.save_and_print')</button>
		
	</div>
	{!! Form::close() !!}

	<div class="modal fade expense_category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	</div>
</section>


@endsection

@section('javascript')
<script>
    // Keep the page compact as requested without affecting global layout.
    $('.content-header').css({'padding-bottom': '0', 'padding-top': '0'});
    $('.content').css('padding-top', '5px');
    $('section.content .box').css('margin-bottom', '8px');
    $('section.content .form-group').css('margin-bottom', '8px');
    $('section.content .box-header').css({'padding-top': '8px', 'padding-bottom': '8px'});
    $('section.content .box-body').css({'padding-top': '8px', 'padding-bottom': '8px'});

    let expenseCategoryOptionsHtml = `{!! collect($expense_categories)->map(function($name, $id){ return '<option value="' . $id . '">' . e($name) . '</option>'; })->implode('') !!}`;
    const expenseAccountOptionsHtml = `{!! collect($expense_accounts)->map(function($name, $id){ return '<option value="' . $id . '">' . e($name) . '</option>'; })->implode('') !!}`;
    const vatOptionsHtml = `
        <option value="0">{{ __('lang_v1.no') }}</option>
        <option value="1">{{ __('lang_v1.yes') }}</option>
    `;
    let expenseItemIndex = 0;

    function fetchExpenseCategoryMeta(categoryId, onDone) {
        if (!categoryId) {
            if (typeof onDone === 'function') {
                onDone(null);
            }
            return;
        }
        $.ajax({
            method: 'get',
            url: '/get-expense-account-category-id/' + categoryId,
            data: {},
            cache: false,
            success: function(result) {
                if (typeof onDone === 'function') {
                    onDone(result || null);
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') { toastr.error('Unable to load expense account for selected category.'); }
                if (typeof onDone === 'function') {
                    onDone(null);
                }
            }
        });
    }

    function buildExpenseItemRow(initialValues = {}) {
        const categoryId = initialValues.expense_category_id || $('#expense_category_id').val() || '';
        const amount = initialValues.amount || '';
        const expenseAccount = initialValues.expense_account || $('#expense_account').val() || '';
        const isVat = typeof initialValues.is_vat !== 'undefined' ? initialValues.is_vat : ($('#is_vat').val() || 0);
        const taxId = initialValues.tax_id || ($('#tax_id').val() || '');
        const refNo = initialValues.ref_no || ($('#ref_no').val() || '');
        const additionalNotes = initialValues.additional_notes || '';
        const rowHtml = `
            <tr data-index="${expenseItemIndex}">
                <td>
                    <select class="form-control select2 expense-item-category" name="expense_items[${expenseItemIndex}][expense_category_id]" required>
                        <option value="">{{ __('messages.please_select') }}</option>
                        ${expenseCategoryOptionsHtml}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control expense-item-ref" name="expense_items[${expenseItemIndex}][ref_no]" value="${refNo}">
                </td>
                <td>
                    <select class="form-control select2 expense-item-account" name="expense_items[${expenseItemIndex}][expense_account]" required>
                        <option value="">{{ __('messages.please_select') }}</option>
                        ${expenseAccountOptionsHtml}
                    </select>
                </td>
                <td>
                    <select class="form-control select2 expense-item-vat" name="expense_items[${expenseItemIndex}][is_vat]">
                        ${vatOptionsHtml}
                    </select>
                </td>
                <td>
                    <select class="form-control select2 expense-item-tax" name="expense_items[${expenseItemIndex}][tax_id]">
                        <option value="">{{ __('messages.please_select') }}</option>
                        {!! collect($taxes['tax_rates'])->map(function($name, $id){ return '<option value="' . $id . '">' . e($name) . '</option>'; })->implode('') !!}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control input_number expense-item-amount" name="expense_items[${expenseItemIndex}][amount]" value="${amount}" required>
                </td>
                <td>
                    <input type="text" class="form-control expense-item-note" name="expense_items[${expenseItemIndex}][additional_notes]" value="${additionalNotes}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-xs remove-expense-item-row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#expense_items_body').append(rowHtml);
        const $row = $('#expense_items_body tr:last');
        if ($.fn.select2) {
            $row.find('.select2').select2({ width: '100%' });
        }
        $row.find('.expense-item-category').val(categoryId).trigger('change');
        $row.find('.expense-item-account').val(expenseAccount).trigger('change');
        $row.find('.expense-item-vat').val(String(isVat)).trigger('change');
        $row.find('.expense-item-tax').val(taxId).trigger('change');
        expenseItemIndex++;
    }

    function syncSummaryFromExpenseItems() {
        let total = 0;
        $('#expense_items_body .expense-item-amount').each(function() {
            const amount = parseFloat(String($(this).val() || '').replace(/,/g, '')) || 0;
            total += amount;
        });
        const firstRow = $('#expense_items_body tr:first');
        if (firstRow.length) {
            $('#expense_category_id').val(firstRow.find('.expense-item-category').val()).trigger('change');
            $('#expense_account').val(firstRow.find('.expense-item-account').val()).trigger('change');
            $('#is_vat').val(firstRow.find('.expense-item-vat').val()).trigger('change');
            $('#tax_id').val(firstRow.find('.expense-item-tax').val()).trigger('change');
            $('#ref_no').val(firstRow.find('.expense-item-ref').val());
            $('#additional_notes').val(firstRow.find('.expense-item-note').val());
        }
        $('#final_total').val(total ? __currency_trans_from_en(total, false, false) : '');
        $('#amount_0').val(total ? __currency_trans_from_en(total, false, false) : '');
        $('#amount_0').trigger('change');
    }

    jQuery.validator.addMethod("greaterThanZero", function(value, element) {
        return (parseFloat(value) > 0);
    });
    $.validator.messages.greaterThanZero = 'Zero Values not accepted. Please correct';
    jQuery.validator.addClassRules("payment-amount", {
        required: true,
        greaterThanZero: true
    });

    $('form#add_expense_form').validate({
        rules: {

        },
        messages: {

        },
    });

    $(document).on('click','#printBtnSave',function () {
        $('#print_and_save').val(1);
    });
    $(document).on('click','#submitBtn',function () {
        $('#print_and_save').val(0);
    });

    $('#add_expense_form').on('submit', function(e) {
        syncSummaryFromExpenseItems();
        if ($('#expense_items_body tr').length === 0 || !$('#final_total').val()) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') { toastr.error('Please add at least one expense detail row with amount.'); }
            return false;
        }
    });

    $(document).ready(function() {

    // S285: make Add Expense date calendar open on the selected month immediately.
    if ($.fn.daterangepicker && $('#expense_transaction_date').length) {
        var existingExpenseDate = $('#expense_transaction_date').val();
        var expenseStartDate = moment();
        if (existingExpenseDate) {
            var parsedExpenseDate = moment(existingExpenseDate, moment_date_format + ' ' + moment_time_format, true);
            if (!parsedExpenseDate.isValid()) {
                parsedExpenseDate = moment(existingExpenseDate, moment_date_format, true);
            }
            if (!parsedExpenseDate.isValid()) {
                parsedExpenseDate = moment(existingExpenseDate);
            }
            if (parsedExpenseDate.isValid()) {
                expenseStartDate = parsedExpenseDate;
            }
        }
        $('#expense_transaction_date').val(expenseStartDate.format(moment_date_format + ' ' + moment_time_format));
        $('#expense_transaction_date').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: true,
            timePicker24Hour: false,
            autoUpdateInput: true,
            startDate: expenseStartDate,
            locale: { format: moment_date_format + ' ' + moment_time_format }
        }, function(start) {
            $('#expense_transaction_date').val(start.format(moment_date_format + ' ' + moment_time_format));
        });
    }
        $('#location_id').trigger('change');
        $('.payment_types_dropdown').trigger('change');
        toggle_expense_pd_cheque_fields();
    });

    function toggle_expense_pd_cheque_fields() {
        // console.log('masoook');
        var $row = $('#add_expense_form').find('.payment_row').first();
        var payment_method = ($row.find('.payment_types_dropdown').val() || '').toLowerCase();

        if (payment_method === 'cheque') {
            $row.find('.post_dated_cheque').addClass('hide');
            $row.find('.bank_transfer_fields, .cheque_payment_details').addClass('hide');
            $row.find('.cheque_payment_details_only').removeClass('hide');
        
            if (typeof get_cheques_list === 'function') {
                console.log('masok func');
                get_cheques_list('cheque', $row);
            }
        } else {
            var show_bank_fields = ['bank_transfer', 'direct_bank_deposit', 'bank'].indexOf(payment_method) !== -1;
            $row.find('.post_dated_cheque').toggleClass('hide', !show_bank_fields);
            $row.find('.cheque_payment_details_only').addClass('hide');
            if (show_bank_fields) {
                $row.find('.bank_transfer_fields, .cheque_payment_details').removeClass('hide');
            } else {
                $row.find('.bank_transfer_fields, .cheque_payment_details').addClass('hide');
            }
        }
    }

    $(document).on('change', '#add_expense_form .payment_types_dropdown', function() {
        setTimeout(toggle_expense_pd_cheque_fields, 0);
    });

    // Function to handle print preview
    function handlePrintPreview() {
        var printContents = document.getElementById('print_area').innerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }

    // Attach the print preview function to the form submission
    $('#add_expense_form').on('submit', function(event) {
        /*
        if ($('#print_and_save').val() == 1) {
            event.preventDefault();
            var formData = $(this).serialize();
            $.ajax({
                method: 'POST',
                url: '{{action("ExpenseController@store")}}',
                data: formData,
                success: function(response) {
                    // Assuming the server returns the HTML for the print area
                 //   $('#print_area').html(response.print_html);
                 //   handlePrintPreview();
                 alert($('#print_and_save').val())
                },
                error: function() {
                    toastr.error("Error saving expense");
                }
            });
        }*/
    });

    $(".expense_category_modal").on('hide.bs.modal', function() {
        $.ajax({
            method: 'get',
            url: '/expense-categories/get-drop-down',
            data: {},
            contentType: 'html',
            success: function(result) {
                $('#expense_category_id').empty().append(result);
                expenseCategoryOptionsHtml = $('#expense_category_id').html();
                $('#expense_items_body .expense-item-category').each(function() {
                    const selectedValue = $(this).val();
                    $(this).html('<option value="">{{ __('messages.please_select') }}</option>' + expenseCategoryOptionsHtml);
                    if (selectedValue) {
                        $(this).val(String(selectedValue));
                    }
                    $(this).trigger('change.select2');
                });
            },
        });
    });

    @if(auth()-> user()-> can('unfinished_form.expense'))
    setInterval(function() {
        $.ajax({
            method: 'POST',
            url: '{{action("TempController@saveAddExpenseTemp")}}',
            dataType: 'json',
            data: $('#add_expense_form').serialize(),
            success: function(data) {},
        });
    }, 10000);

    @if(!empty($temp_data))
    swal({
        title: "Do you want to load unsaved data?",
        icon: "info",
        buttons: {
            confirm: {
                text: "Yes",
                value: false,
                visible: true,
                className: "",
                closeModal: true
            },
            cancel: {
                text: "No",
                value: true,
                visible: true,
                className: "",
                closeModal: true,
            }
        },
        dangerMode: false,
    }).then((sure) => {
        if (sure) {
            window.location.href = "{{action('TempController@clearData', ['type' => 'add_expense_data'])}}";
        }
    });
    @endif
    @endif

    @if($account_module)
    $('#expense_account').select2();
    @endif

    $('#method_0').prop('disabled', false);

    $('#final_total').change(function() {
        $('#amount_0').val($('#final_total').val());
        total = parseFloat($('#final_total').val());
        paid = parseFloat($('#amount_0').val());
        due = total - paid;
        if (due > 0) {
            $('.controller_account_div').removeClass('hide')
        } else {
            $('.controller_account_div').addClass('hide')
        }
        $('#payment_due').text(__currency_trans_from_en(due, false, false));
        $('#amount_0').trigger('change');
    });

    $('#amount_0').change(function() {
        total = parseFloat($('#final_total').val());
        paid = parseFloat($('#amount_0').val());
        due = total - paid;
        if (due > 0) {
            $('.controller_account_div').removeClass('hide')
        } else {
            $('.controller_account_div').addClass('hide')
        }
        $('#payment_due').text(__currency_trans_from_en(due, false, false));

        if(paid == null) return false;
        var method = ($('#method_0').val() || '').toLowerCase();
        if (method === 'credit_expense') {
            $('button#submitBtn').prop('disabled', false);
            return;
        }

        $.ajax({
            method: 'GET',
            url: '/finance/check-insufficient-balance-for-accounts',
            success: function(result) {
                var ids = result;
                if(ids.includes(parseInt($('#account_0').val()))) {

                    $.ajax({
                       method: 'GET',
                       url: '/finance/get-account-balance/' + parseInt($('#account_0').val()),
                       success: function(result) {

                        if(parseFloat(paid) > parseFloat(result.balance) || result.balance == null){
                            swal({
                                title: 'Insufficient Balance',
                                icon: "error",
                                buttons: true,
                                dangerMode: true,
                            })

                           $('button#submitBtn').prop('disabled', true);
                            return false;
                          } else {
                              $('button#submitBtn').prop('disabled', false);
                          }
                       }
                    });
                } else {
                  $('button#submitBtn').prop('disabled', false);
                }

            }
        });

    });

    $('.cheque_date_range_input').each(function() {
        var $el = $(this);
        $el.daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $el.val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                get_cheques_list('cheque', $el.closest('.payment_row')); 
            }
        );
    });

    $('#expense_category_id').change(function() {
        fetchExpenseCategoryMeta($(this).val(), function(result) {
            if (!result) {
                return;
            }
            if (result.expense_account_id) {
                $('#expense_account').val(String(result.expense_account_id)).trigger('change');
            }
            if (typeof result.payee_name !== 'undefined') {
                $('#payee').val(result.payee_name || 'Payee Not Selected');
            }
        });
    });

    $(document).on('change', '.expense-item-category', function() {
        const $row = $(this).closest('tr');
        const selectedCategoryId = $(this).val();
        fetchExpenseCategoryMeta(selectedCategoryId, function(result) {
            if (result && result.expense_account_id) {
                $row.find('.expense-item-account').val(String(result.expense_account_id)).trigger('change');
            } else {
                $row.find('.expense-item-account').val('').trigger('change');
            }
            if (result && typeof result.payee_name !== 'undefined') {
                $('#payee').val(result.payee_name || 'Payee Not Selected');
            }
            // Fix: Category selection should load account automatically in the summary section too
            if (result && result.expense_account_id) {
                $('#expense_account').val(String(result.expense_account_id)).trigger('change');
            }
            syncSummaryFromExpenseItems();
        });
    });

    $(document).on('click', '#add_expense_item_row', function() {
        buildExpenseItemRow();
    });

    $(document).on('click', '.remove-expense-item-row', function() {
        if ($('#expense_items_body tr').length === 1) {
            return;
        }
        $(this).closest('tr').remove();
        syncSummaryFromExpenseItems();
    });

    $(document).on('change keyup', '.expense-item-amount, .expense-item-category, .expense-item-account, .expense-item-vat, .expense-item-tax, .expense-item-ref, .expense-item-note', function() {
        syncSummaryFromExpenseItems();
    });

    $('#account_0').change(function(){
        var $row = $(this).closest('.row');
        if($row.find('#method_0').val() == "cheque"){
            $row.find('.payment-amount').prop('readonly', true);
        }else{
            $row.find('.payment-amount').prop('readonly', false);
        }

        total = parseFloat($('#final_total').val());
        paid = parseFloat($('#amount_0').val());
        due = total - paid;
        if (due > 0) {
            $('.controller_account_div').removeClass('hide')
        } else {
            $('.controller_account_div').addClass('hide')
        }
        $('#payment_due').text(__currency_trans_from_en(due, false, false));

        if(paid == null) return false;
        var method = ($('#method_0').val() || '').toLowerCase();
        if (method === 'credit_expense') {
            $('button#submitBtn').prop('disabled', false);
            return;
        }

        $.ajax({
            method: 'GET',
            url: '/finance/check-insufficient-balance-for-accounts',
            success: function(result) {
                var ids = result;

                if(ids.includes(parseInt($('#account_0').val()))) {

                    $.ajax({
                       method: 'GET',
                       url: '/finance/get-account-balance/' + parseInt($('#account_0').val()),
                       success: function(result) {

                        if(parseFloat(paid) > parseFloat(result.balance) || result.balance == null){
                            swal({
                                title: 'Insufficient Balance',
                                icon: "error",
                                buttons: true,
                                dangerMode: true,
                            })

                           $('button#submitBtn').prop('disabled', true);
                            return false;
                          } else {
                              $('button#submitBtn').prop('disabled', false);
                          }
                       }
                    });
                } else {
                  $('button#submitBtn').prop('disabled', false);
                }

            }
        });

    });

    if (!$('#expense_items_body tr').length) {
        buildExpenseItemRow({
            expense_category_id: $('#expense_category_id').val(),
            amount: ($('#final_total').val() || '').replace(/,/g, ''),
            expense_account: $('#expense_account').val(),
            is_vat: $('#is_vat').val(),
            additional_notes: $('#additional_notes').val()
        });
    }
    syncSummaryFromExpenseItems();
    toggle_expense_pd_cheque_fields();
</script>
<script>
    $(document).ready(function() {
        $('.select2_contact_id').select2({
            ajax: {
                url: '/get-contacts',
                dataType: 'json',
                delay: 1000, // Wait 1000ms after the user stops typing
                data: function(params) {
                    return {
                        search: params.term,
                        page: params.page || 1,
                    };
                },
                processResults: function(data) {
                    if (!data || !data.results) {
                        console.error('Invalid response:', data);
                        toastr.error("Error Searching Contacts");
                        return { results: [] };
                    }
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more,
                        },
                    };
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    toastr.error("Error Searching Contacts");
                },
            },
            placeholder: '{{ __('messages.please_select') }}',
            minimumInputLength: 1, // Search after typing 1 character
        });
    });
</script>



<script>
$(document).ready(function () {
    function loadExpenseDailyShiftNo() {
        var operatorId = $('#expense_for').val() || $('#add_pump_operator_id').val() || $('select[name="pump_operator_id"]').val();
        var $shift = $('#expense_daily_shift_no');
        if (!$shift.length) {
            return;
        }
        $.get('{{ route('expenses.daily_shift_options') }}', {pump_operator_id: operatorId}, function (response) {
            var selected = $shift.data('selected') || $shift.val();
            $shift.empty().append('<option value="">{{ __('messages.please_select') }}</option>');
            $.each(response.results || [], function (i, row) {
                $shift.append('<option value="' + row.id + '">' + row.text + '</option>');
            });
            if (selected) {
                $shift.val(selected);
            } else if ($shift.find('option').length === 2) {
                $shift.prop('selectedIndex', 1);
            }
            $shift.trigger('change.select2');
        });
    }
    $('#expense_for').on('change', loadExpenseDailyShiftNo);
    loadExpenseDailyShiftNo();
});
</script>

@endsection
