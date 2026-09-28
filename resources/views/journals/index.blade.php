@extends('layouts.app')
@section('title', __('account.journal'))

@section('content')

@php
use App\Account;
$cash_account_id = Account::getAccountByAccountName('Cash')->id;
@endphp

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.journal')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('account.journal')</a></li>
                    <li><span>@lang('account.journal')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('transaction_type', __('account.transaction_type') . ':') !!}
            {!! Form::select('transaction_type', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' =>
            __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2',
            'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
            {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'),
            'class' => 'form-control', 'readonly']) !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
            'placeholder' => __('petro::lang.all'), 'style' => 'width:100%']) !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('account_id', __('account.accounts') . ':') !!}
            {!! Form::select('account_id', $accounts, null, ['class' => 'form-control select2',
            'placeholder' => __('petro::lang.all'), 'style' => 'width:100%']) !!}
        </div>
    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'account.journal_list')])

    @slot('tool')
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-primary btn-modal"
            data-href="{{action('JournalController@create')}}" data-container=".add_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
    </div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="journal_table">
            <thead>
                <tr>
                    <th>@lang('account.journal_no')</th>
                    <th>@lang('account.date')</th>
                    <th>@lang('account.account')</th>
                    <th>@lang('account.debit')</th>
                    <th>@lang('account.credit')</th>
                    <th>@lang('account.note')</th>
                    <th>@lang('account.added_by')</th>
                    <th>@lang('account.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="modal fade add_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade edit_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <div class="modal fade" id="noteModal" tabindex="-1" role="dialog" aria-labelledby="noteModalLabel">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="noteModalLabel">Journal Note</h4>
                </div>
                <div class="modal-body" id="noteContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-xs btn-primary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endcomponent
</section>
@endsection

@if(!$account_access)
<style>
    .dataTables_empty {
        color: {{App\System::getProperty('not_enalbed_module_user_color')}};
        font-size: {{App\System::getProperty('not_enalbed_module_user_font_size')}}px;
    }
</style>
@endif

@section('javascript')
<script>
    var journalAccountDropdownUrl = "{{ url('/accounting-module/journals/get-account-dropdown-by-type') }}";

    if ($('#sell_list_filter_date_range').length == 1) {
        $('#sell_list_filter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
            $('#sell_list_filter_date_range').val(
                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
            );
            journal_table.ajax.reload();
        });
        $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
        });
        $('#sell_list_filter_date_range')
            .data('daterangepicker')
            .setStartDate(moment().startOf('year'));
        $('#sell_list_filter_date_range')
            .data('daterangepicker')
            .setEndDate(moment().endOf('year'));
    }

    // Journal table
    journal_table = $('#journal_table').DataTable({
        language: {
            "emptyTable": "@if(!$account_access) {{App\System::getProperty('not_enalbed_module_user_message')}} @else @lang('account.no_data_available_in_table') @endif"
        },
        processing: true,
        serverSide: true,
        pageLength: 25,
        aaSorting: [0,'desc'],
        ajax: {
            url: '{{action("JournalController@index")}}',
            dataSrc: function (json) {
                if (json && json.success === 0 && json.msg) {
                    toastr.error(json.msg);
                }

                if ($.isArray(json)) {
                    return json;
                }

                if (json && $.isArray(json.data)) {
                    return json.data;
                }

                return [];
            },
            data: function (d) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.location_id = $('#location_id').val();
                d.account_id = $('#account_id').val();
            },
            error: function (xhr) {
                let message = '@lang("messages.something_went_wrong")';

                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                }

                toastr.error(message);
            }
        },
        columnDefs: [
            {
                targets: 7,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'journal_id', name: 'journal_id' },
            { data: 'date', name: 'date' },
            { data: 'account_name', name: 'accounts.name' },
            { data: 'debit_amount', name: 'debit_amount' },
            { data: 'credit_amount', name: 'credit_amount' },
            { data: 'note_btn', name: 'note_btn' },
            { data: 'user', name: 'users.username' },
            { data: 'action', name: 'action' },
        ],
        @include('layouts.partials.datatable_export_button')
        fnDrawCallback: function (oSettings) {
        },
    });

    $('#location_id, #account_id').change(function(){
        journal_table.ajax.reload();
    });

    $(document).on('click', 'a.delete_journal', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: 'This journal will be deleted.',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            journal_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $(document).on('click', '.journal_edit', function(e) {
        e.preventDefault();
        $('div.edit_modal').load($(this).attr('href'), function() {
            $(this).modal('show');
        });
    });

    $('.add_modal').on('hidden.bs.modal', function () {
        $('.journal_rows').remove();
    });

    $(document).on('click', '.view-note', function() {
        var note = $(this).data('note');
        $('#noteContent').text(note);
        $('#noteModal').modal('show');
    });

    function calculate_total_top() {
        let debit = 0;
        let credit = 0;
        $('.debit-top').each(function () {
            if($(this).val() != ''){
                debit += parseFloat($(this).val());
            }
        });
        $('.credit-top').each(function () {
            if($(this).val() != ''){
                credit += parseFloat($(this).val());
            }
        });

        $('.debit_total_top').val(debit);
        $('.credit_total_top').val(credit);

        if(debit == credit){
            $('.add_row_create').attr('disabled', false);
        }else{
            $('.add_row_create').attr('disabled', true);
        }
    }

    function calculate_total() {
        let debit = 0;
        let credit = 0;
        $('.debit').each(function () {
            if($(this).val() != ''){
                debit += parseFloat($(this).val());
            }
        });
        $('.credit').each(function () {
            if($(this).val() != ''){
                credit += parseFloat($(this).val());
            }
        });

        $('.debit_total').val(debit);
        $('.credit_total').val(credit);

        if(debit == credit && debit > 0){
            $('.add_btn').attr('disabled', false);
        }else{
            $('.add_btn').attr('disabled', true);
        }
    }

    $('body').on('click', '.remove_row', function(e) {
        e.preventDefault();
        $(this).closest('div.row').remove();
        calculate_total();
        calculate_total_top();
    });

    var curr = 0;

    $(document).on('click','.add_row_create', function() {
        var errorOccurred = false;

        $('.journal_row').each(function(index) {
            var debit_top = $(this).find('.debit-top').val() ?? 0;
            var credit_top = $(this).find('.credit-top').val() ?? 0;
            var account_ids = $(this).find('.account_ids').val() ?? 0;

            if(account_ids == 0){
                toastr.error("Please select an account!");
                errorOccurred = true;
                return false;
            }

            if(debit_top == 0 && credit_top == 0){
                toastr.error("Fill either credit or debit for each row");
                errorOccurred = true;
                return false;
            }
        });

        if (errorOccurred) {
            return false;
        }

        var rowsHtml = '<tbody class="inserted_block">';
        $('.journal_row').each(function(index) {
            var debit_top = $(this).find('.debit-top');
            var credit_top = $(this).find('.credit-top');
            var account_ids = $(this).find('.account_ids');
            var account_ids_text = $(this).find('.account_ids option:selected').text() || '';
            var account_type_ids = $(this).find('.account_type_ids');

            var button_html = "";
            if (index === $('.journal_row').length - 1) {
                button_html = `<button type="button" class="btn btn-xs btn-danger remove_row">-</button>`;
            }

            rowsHtml += `
                <tr>
                    <td>${account_ids_text}</td>
                    <td>${debit_top.val() ?? ''}</td>
                    <td>${credit_top.val() ?? ''}</td>
                    <td>${$('#show_in_ledger option:selected').text()}</td>
                    <td>
                        ${button_html}
                    </td>
                    <input type="hidden" name="journal[account_type_id][]" value="${account_type_ids.val()}">
                    <input type="hidden" name="journal[account_id][]" value="${account_ids.val()}">
                    <input type="hidden" name="journal[credit_amount][]" value="${credit_top.val()}" class="credit">
                    <input type="hidden" name="journal[debit_amount][]" value="${debit_top.val()}" class="debit">
                </tr>
            `;
        });

        rowsHtml += `</tbody>`;
        $('#journal_details').append(rowsHtml);

        calculate_total();
        calculate_total_top();
    });

    $(document).on('click', '.remove_row', function() {
        $(this).closest('.inserted_block').remove();
        calculate_total();
        calculate_total_top();
    });

    $('body').on('change', '.debit-top, .credit-top', function() {
        var paid = $(this).val();
        var $row = $(this).closest('.row');
        var debit_top = $row.find('.debit-top');
        var credit_top = $row.find('.credit-top');

        if (debit_top.val()) {
            credit_top.attr('disabled', 'disabled');
        } else if (credit_top.val()) {
            debit_top.attr('disabled', 'disabled');
        } else {
            debit_top.attr('disabled', false);
            credit_top.attr('disabled', false);
        }
        calculate_total();
        calculate_total_top();
    });

    $(document).on('click', '.add_row', function(e) {
        e.preventDefault();
        index = parseInt($('#index').val()) +1 ;
        $('#index').val(index);
        $.ajax({
            method: 'get',
            url: "{{action('JournalController@getRow')}}",
            data: { index:index },
            type : 'html',
            success: function(result) {
                $('.dynamic_rows').append(result);
            },
        }).then(function(){
            $('.select2').select2();
            calculate_total();
        });  
    });

    $(document).on('change', '.account_type_ids',function () {
        let account_type_id = $(this).val();
        var this_row = $(this).closest('.journal_row');

        if (!account_type_id) {
            var $accountSelect = $(this_row).find('.account_ids');
            $accountSelect.empty().append('<option value="">Please select</option>').val('');
            $accountSelect.trigger('change');
            return;
        }

        $.ajax({
            method: 'get',
            url: journalAccountDropdownUrl + '/' + account_type_id,
            contentType: 'html',
            data: {  },
            success: function(result) {
                var $accountSelect = $(this_row).find('.account_ids');

                if ($accountSelect.hasClass('select2-hidden-accessible')) {
                    $accountSelect.select2('destroy');
                }

                $accountSelect.empty().append(result);
                $accountSelect.select2({
                    width: '100%'
                });
                $accountSelect.val('').trigger('change');
            },
        });
    });

    $(document).on('change', '#is_opening_balance',function () {
        if($(this).val() === 'yes'){
            $('#note').attr('required', true);
        }else{
            $('#note').attr('required', false);
        }
    });

    $(document).on('change', '#show_in_ledger',function () {
        if($(this).val() === 'no'){
            $('#show_in_fields').attr('hidden', true);
            $('#ledger_holder').attr('required', false);
            $('#show_in').prop('required', false).prop('disabled', true).val('').trigger('change');
            $('#ledger_holder_wrapper').attr('hidden', true);
            $('#ledger_holder').val('').trigger('change');
        }else{
            $('#show_in_fields').attr('hidden', false);
            $('#ledger_holder').attr('required', true);
            $('#show_in').prop('required', true).prop('disabled', false);
            $('#ledger_holder_wrapper').attr('hidden', false);
        }
    });

    $(document).on('change', '.account_ids',function () {
        var accid = $(this).val();
        var $row = $(this).closest('.row');
        var paid = $row.find('.credit').val();

        if(accid == "{{$cash_account_id}}"){
            $.ajax({
                method: 'GET',
                url: '/finance/get-account-balance/' + accid,
                success: function(result) {
                    if(parseFloat(paid) > parseFloat(result.balance) && result.balance != null){
                        swal({
                            title: "Credit amount can't be more than account balance",
                            icon: "error",
                            buttons: true,
                            dangerMode: true,
                        });
                        $('.add_btn').attr('disabled', true);
                    } else {
                        $('.add_btn').attr('disabled', false);
                    }
                }
            });
        }
    });

    $(document).on('change', '.credit-top',function () {
        var paid = $(this).val();
        var $row = $(this).closest('.row');
        var accid = $row.find('.account_ids').val();

        if(accid == "{{$cash_account_id}}"){
            $.ajax({
                method: 'GET',
                url: '/finance/get-account-balance/' + accid,
                success: function(result) {
                    if(parseFloat(paid) > parseFloat(result.balance) && result.balance != null){
                        swal({
                            title: "Credit amount can't be more than account balance",
                            icon: "error",
                            buttons: true,
                            dangerMode: true,
                        });
                        $('.add_btn').attr('disabled', true);
                    } else {
                        $('.add_btn').attr('disabled', false);
                    }
                }
            });
        }
    });
</script>
@endsection
