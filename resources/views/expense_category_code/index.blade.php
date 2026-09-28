@extends('layouts.app')
@section('title', __('expense.expense_categories_code'))

@section('content')

<!-- Content Header (Page header) -->
<br/>
<ul class="nav nav-tabs">
    <li class="@if(empty(session('status.tab'))) active @endif">
        <a href="#category_settings" class="category_settings" data-toggle="tab">
            <i class="fa fa-file-text-o"></i>
            <strong>@lang('expense.category_settings')</strong>
        </a>
    </li>
    <li class="@if(!empty(session('status.tab')) && session('status.tab') =='note_settings') active @endif">
        <a href="#note_settings" class="note_settings" data-toggle="tab">
            <i class="fa fa-sticky-note-o"></i>
            <strong>Notes</strong>
        </a>
    </li>
</ul>

<!-- Main content -->
<section class="content">
    <div class="tab-content">

        <div class="tab-pane @if(empty(session('status.tab'))) active @endif" id="category_settings">
            @component('components.widget', ['class' => 'box-primary', 'title' => __( 'expense.category_settings' )])
                @slot('tool')
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-primary btn-lg btn-modal" style="font-size: 175%; padding: 8px 24px;" id="expense_category_code_add_btn" 
                        data-href="{{action('ExpenseCategoryCodeController@create')}}" 
                        data-container=".expense_category_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add')</button>
                    </div>
                @endslot

            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="expense_category_code_table">
                    <thead>
                        <tr>
                            <th>@lang( 'expense.date' )</th>
                            <th>@lang( 'expense.prefix' )</th>
                            <th>@lang( 'expense.starting_no' )</th>
                            <th>@lang( 'expense.user' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="tab-pane @if(!empty(session('status.tab')) && session('status.tab') =='note_settings') active @endif" id="note_settings">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Notes'])
                {!! Form::open(['url' => route('expense-categories-code.note-settings'), 'method' => 'post']) !!}
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('show_expense_note_in_print', 1, !empty($show_expense_note_in_print), ['class' => 'input-icheck']) !!}
                            Show Expense Note in the Print
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('show_payment_note_in_print', 1, !empty($show_payment_note_in_print), ['class' => 'input-icheck']) !!}
                            Show Payment Note in the Print
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="modal fade expense_category_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>


<!-- /.content -->

@endsection

@section('javascript')
<script>
$(document).ready(function(){
    // S374: initialise the Expense Settings category-code table from the correct
    // AJAX endpoint. Without this explicit initialisation the page showed the
    // DataTables tn/7 warning on tenants where the legacy global script did not bind it.
    if ($('#expense_category_code_table').length && !$.fn.DataTable.isDataTable('#expense_category_code_table')) {
        $('#expense_category_code_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action('ExpenseCategoryCodeController@index') }}',
            columns: [
                { data: 'date', name: 'expense_categories_codes.date' },
                { data: 'prefix', name: 'expense_categories_codes.prefix' },
                { data: 'starting_no', name: 'expense_categories_codes.starting_no' },
                { data: 'username', name: 'users.username' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });
    }
});

$(document).on('click', '#expense_category_code_add_btn', function(e) {
    e.preventDefault();
    var href = $(this).data('href');
    var container = $(this).data('container') || '.expense_category_modal';
    $.ajax({
        url: href,
        dataType: 'html',
        success: function(result) {
            $(container).html(result).modal('show');
            $(container).find('.select2').select2({ dropdownParent: $(container) });
        },
        error: function(xhr) {
            toastr.error((LANG && LANG.something_went_wrong) ? LANG.something_went_wrong : 'Something went wrong');
        }
    });
});
</script>
@endsection
