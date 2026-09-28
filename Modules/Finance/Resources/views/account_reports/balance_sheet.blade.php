@extends('layouts.app')
@section('title', __('account.balance_sheet'))

@section('content')
<div class="page-title-area no-print">
    <div class="row align-items-center">
        <div class="col-sm-8">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.balance_sheet')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top:15px">
                    <li><a href="#">Finance Module</a></li>
                    <li><span>@lang('account.balance_sheet')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content">
    @if(!empty($load_error))
        <div class="alert alert-danger">{{ $load_error }}</div>
    @endif

    <form method="GET" action="{{ route('finance.legacy.balance-sheet') }}" class="no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-4">
                <div class="form-group">
                    <label for="location_id">@lang('purchase.business_location'):</label>
                    <select name="location_id" id="location_id" class="form-control select2" style="width:100%">
                        <option value="all">@lang('lang_v1.all')</option>
                        @foreach($business_locations as $id => $name)
                            <option value="{{ $id }}" {{ (string)$selected_location === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="to_date">@lang('messages.filter_by_date'):</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $to_date }}">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> @lang('report.apply_filters')</button>
                </div>
            </div>
        @endcomponent
    </form>

    <div class="box box-solid">
        <div class="box-header text-center">
            <h3 class="box-title">{{ session('business.name') }} - @lang('account.balance_sheet')</h3>
            <div><strong>{{ $to_date }}</strong></div>
        </div>
        <div class="box-body">
            @php
                $sections = [
                    ['title' => __('account.assets'), 'rows' => $assets, 'total' => $total_assets],
                    ['title' => __('account.liabilities'), 'rows' => $liabilities, 'total' => $total_liabilities],
                    ['title' => __('account.equity'), 'rows' => $equity, 'total' => $total_equity],
                ];
            @endphp

            @foreach($sections as $section)
                <div class="table-responsive" style="margin-bottom:25px">
                    <table class="table table-bordered table-striped finance-balance-table">
                        <thead>
                            <tr class="bg-gray">
                                <th colspan="2">{{ $section['title'] }}</th>
                            </tr>
                            <tr>
                                <th>@lang('account.account')</th>
                                <th class="text-right" style="width:25%">@lang('lang_v1.balance')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($section['rows'] as $account)
                                <tr>
                                    <td>
                                        {{ $account->name }}
                                        @if(!empty($account->account_number))
                                            <small class="text-muted">({{ $account->account_number }})</small>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ (float)$account->balance }}">{{ (float)$account->balance }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">@lang('account.no_data_available_in_table')</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray font-17">
                                <th>@lang('sale.total')</th>
                                <th class="text-right"><span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $section['total'] }}">{{ $section['total'] }}</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach

            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr class="bg-info font-17">
                        <th>Total Liabilities + Equity</th>
                        <th class="text-right" style="width:25%"><span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $total_liabilities + $total_equity }}">{{ $total_liabilities + $total_equity }}</span></th>
                    </tr>
                    <tr class="{{ abs($balance_difference) < 0.0001 ? 'bg-success' : 'bg-danger' }} font-17">
                        <th>Balance Difference</th>
                        <th class="text-right"><span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $balance_difference }}">{{ $balance_difference }}</span></th>
                    </tr>
                </table>
            </div>
        </div>
        <div class="box-footer no-print text-right">
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> @lang('messages.print')</button>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(function () {
    $('.select2').select2();
    __currency_convert_recursively($('.content'));
});
</script>
@endsection
