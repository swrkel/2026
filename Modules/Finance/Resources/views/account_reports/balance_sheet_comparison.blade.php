@extends('layouts.app')
@section('title', __('account.balance_sheet_comparison'))

@section('content')
<div class="page-title-area no-print">
    <div class="row align-items-center">
        <div class="col-sm-8">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.balance_sheet_comparison')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top:15px">
                    <li><a href="#">Finance Module</a></li>
                    <li><span>@lang('account.balance_sheet_comparison')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content">
    @if(!empty($load_error))
        <div class="alert alert-danger">{{ $load_error }}</div>
    @endif

    <form method="GET" action="{{ route('finance.legacy.balance-sheet-comparison') }}" class="no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    <label for="location_id">@lang('purchase.business_location'):</label>
                    <select name="location_id" id="location_id" class="form-control select2" style="width:100%">
                        <option value="all">@lang('lang_v1.all')</option>
                        @foreach($businessLocations as $id => $name)
                            <option value="{{ $id }}" {{ (string)$selectedLocation === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @foreach($dates as $index => $date)
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="comparison_date_{{ $index }}">Date {{ $index + 1 }}:</label>
                        <input type="date" id="comparison_date_{{ $index }}" name="{{ $index === 0 ? 'end_date' : 'end_date_' . ($index + 1) }}" value="{{ $date }}" class="form-control">
                    </div>
                </div>
            @endforeach
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> @lang('report.apply_filters')</button>
                </div>
            </div>
        @endcomponent
    </form>

    <div class="box box-solid">
        <div class="box-header text-center">
            <h3 class="box-title">{{ session('business.name') }} - @lang('account.balance_sheet_comparison')</h3>
        </div>
        <div class="box-body">
            @php
                $sectionTitles = [
                    'assets' => __('account.assets'),
                    'liabilities' => __('account.liabilities'),
                    'equity' => __('account.equity'),
                ];
            @endphp

            @foreach($sectionTitles as $key => $title)
                <div class="table-responsive" style="margin-bottom:25px">
                    <table class="table table-bordered table-striped finance-balance-comparison-table">
                        <thead>
                            <tr class="bg-gray"><th colspan="4">{{ $title }}</th></tr>
                            <tr>
                                <th>@lang('account.account')</th>
                                @foreach($dates as $date)
                                    <th class="text-right">{{ $date }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sections[$key] as $account)
                                <tr>
                                    <td>
                                        {{ $account->name }}
                                        @if(!empty($account->account_number))
                                            <small class="text-muted">({{ $account->account_number }})</small>
                                        @endif
                                    </td>
                                    @for($i = 1; $i <= 3; $i++)
                                        @php $property = 'balance_' . $i; @endphp
                                        <td class="text-right"><span class="display_currency" data-currency_symbol="true" data-orig-value="{{ (float)$account->{$property} }}">{{ (float)$account->{$property} }}</span></td>
                                    @endfor
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">@lang('account.no_data_available_in_table')</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray font-17">
                                <th>@lang('sale.total')</th>
                                @foreach($totals[$key] as $total)
                                    <th class="text-right"><span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $total }}">{{ $total }}</span></th>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Balance Difference</th>@foreach($dates as $date)<th class="text-right">{{ $date }}</th>@endforeach</tr></thead>
                    <tbody>
                        <tr>
                            <th>Assets - (Liabilities + Equity)</th>
                            @foreach($differences as $difference)
                                <th class="text-right {{ abs($difference) < 0.0001 ? 'bg-success' : 'bg-danger' }}"><span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $difference }}">{{ $difference }}</span></th>
                            @endforeach
                        </tr>
                    </tbody>
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
