@extends('layouts.app')
@section('title', __('expense.expense_categories'))

@section('content')
<section class="content-header">
    <h1>@lang('expense.expense_categories') <small>@lang('expense.manage_your_expense_categories')</small></h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs">
                    <li>
                        <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@create') }}">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_expenses')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@index') }}">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.list_vat_expenses')</strong>
                        </a>
                    </li>
                    <li class="active">
                        <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@index') }}">
                            <i class="fa fa-list"></i> <strong>@lang('expense.expense_categories')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@prefixSettings') }}">
                            <i class="fa fa-code"></i> <strong>@lang('vat::lang.prefix_and_starting_nos')</strong>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('expense.all_your_expense_categories')])
        @slot('tool')
            <div class="box-tools pull-right">
                <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="vat_expense_category_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('expense.category_name')</th>
                        <th>@lang('vat::lang.expense_code')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade expense_category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    (function($) {
        'use strict';

        function loadVatExpenseCategoryTable() {
            if ($.fn.DataTable.isDataTable('#vat_expense_category_table')) {
                $('#vat_expense_category_table').DataTable().clear().destroy();
                $('#vat_expense_category_table').empty().append(
                    '<thead><tr>' +
                    '<th>@lang('expense.category_name')</th>' +
                    '<th>@lang('vat::lang.expense_code')</th>' +
                    '<th>@lang('messages.action')</th>' +
                    '</tr></thead>'
                );
            }

            window.vat_expense_category_table = $('#vat_expense_category_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@index') }}',
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'expense_code', name: 'expense_code' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                drawCallback: function() {
                    $('.dropdown-toggle').dropdown();
                }
            });
        }

        $(document).ready(function() {
            loadVatExpenseCategoryTable();

            $(document).off('submit', 'form#expense_category_add_form');
            $(document).on('submit', 'form#expense_category_add_form', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true);

                $.ajax({
                    method: form.attr('method') || 'POST',
                    url: form.attr('action'),
                    dataType: 'json',
                    data: form.serialize(),
                    success: function(result) {
                        submitBtn.prop('disabled', false);
                        if (result.success == true) {
                            $('.expense_category_modal').modal('hide');
                            toastr.success(result.msg);
                            if ($.fn.DataTable.isDataTable('#vat_expense_category_table')) {
                                $('#vat_expense_category_table').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function() {
                        submitBtn.prop('disabled', false);
                        toastr.error('@lang('messages.something_went_wrong')');
                    }
                });
            });

            $(document).off('click', 'a.delete_expense_category');
            $(document).on('click', 'a.delete_expense_category', function(e) {
                e.preventDefault();
                var href = $(this).data('href');

                swal({
                    title: LANG.sure,
                    text: LANG.confirm_delete_expense_category || LANG.confirm_delete_category || '',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function(willDelete) {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    $('#vat_expense_category_table').DataTable().ajax.reload(null, false);
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });
    })(jQuery);
</script>
<style>
    #vat_expense_category_table_wrapper .dropdown-menu { z-index: 9999; }
    .table-responsive { overflow: visible !important; }
</style>
@endsection
