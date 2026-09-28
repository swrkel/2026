@extends('layouts.app')
@section('title', __('vat::lang.prefix_and_starting_nos'))

@section('content')
<section class="content-header" style="padding-top: 0px !important;padding-bottom: 0px !important;">
    <h1>@lang('vat::lang.prefix_and_starting_nos')</h1>
</section>

<section class="content" style="padding-top:0px !important;">
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs">
                    <li><a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@create') }}"><i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.vat_expenses')</strong></a></li>
                    <li><a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@index') }}"><i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.list_vat_expenses')</strong></a></li>
                    <li><a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@index') }}"><i class="fa fa-list"></i> <strong>@lang('expense.expense_categories')</strong></a></li>
                    <li class="active"><a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@prefixSettings') }}"><i class="fa fa-code"></i> <strong>@lang('vat::lang.prefix_and_starting_nos')</strong></a></li>
                </ul>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('vat::lang.prefix_and_starting_nos')])
        @slot('tool')
            <div class="box-tools pull-right">
                <a href="#" data-href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@editPrefixSettings') }}" class="btn btn-primary btn-modal" data-container=".vat_expense_prefix_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add') / @lang('messages.edit')
                </a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="width:100%;">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('vat::lang.prefix')</th>
                        <th>@lang('vat::lang.starting_no')</th>
                        <th>@lang('purchase.ref_no')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prefix_records as $record)
                        <tr>
                            <td>
                                <button type="button" data-href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseController@editPrefixSettings') }}" class="btn btn-xs btn-primary btn-modal" data-container=".vat_expense_prefix_modal">
                                    <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                                </button>
                            </td>
                            <td>{{ $record->prefix }}</td>
                            <td>{{ $record->starting_no }}</td>
                            <td>{{ $record->next_ref_no }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">@lang('lang_v1.no_data')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent

    <div class="modal fade vat_expense_prefix_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    $(document).off('submit', 'form#vat_expense_prefix_settings_form');
    $(document).on('submit', 'form#vat_expense_prefix_settings_form', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        btn.prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: form.serialize(),
            success: function(result) {
                btn.prop('disabled', false);
                if (result.success == 1 || result.success == true) {
                    $('.vat_expense_prefix_modal').modal('hide');
                    toastr.success(result.msg);
                    window.location.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function() {
                btn.prop('disabled', false);
                toastr.error('@lang('messages.something_went_wrong')');
            }
        });
    });
});
</script>
@endsection
