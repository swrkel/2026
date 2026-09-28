@extends('layouts.app')

@section('title', 'Balance Sheet - Comparison')

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-8">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Balance Sheet - Comparison</h4>
                <ul class="breadcrumbs pull-left" style="margin-top:15px">
                    <li><a href="#">Finance Module</a></li>
                    <li><span>Balance Sheet Comparison</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <form method="GET" action="{{ url()->current() }}">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Location:</label>
                    <select name="location_id" class="form-control select2" style="width:100%">
                        <option value="all">All Locations / Consolidated</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ (string)$locationId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @foreach($dates as $index => $date)
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Comparison Date {{ $index + 1 }}:</label>
                        <input type="date" class="form-control" name="{{ $index === 0 ? 'end_date' : 'end_date_' . ($index + 1) }}" value="{{ $date }}">
                    </div>
                </div>
            @endforeach
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Compare</button>
                </div>
            </div>
        </form>
    @endcomponent

    @foreach(['assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity'] as $key => $title)
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">{{ $title }}</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Account</th>
                            @foreach($dates as $date)<th class="text-right">{{ $date }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections[$key] as $row)
                            <tr>
                                <td>{{ $row->name }} @if($row->account_number)<small>({{ $row->account_number }})</small>@endif</td>
                                <td class="text-right">{{ number_format($row->balance_1, 4) }}</td>
                                <td class="text-right">{{ number_format($row->balance_2, 4) }}</td>
                                <td class="text-right">{{ number_format($row->balance_3, 4) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">@lang('account.no_data_available_in_table')</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray font-17">
                            <th>Total {{ $title }}</th>
                            @foreach($totals[$key] as $total)<th class="text-right">{{ number_format($total, 4) }}</th>@endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Balance Check</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th></th>@foreach($dates as $date)<th class="text-right">{{ $date }}</th>@endforeach</tr></thead>
                <tbody>
                    <tr><th>Assets</th>@foreach($totals['assets'] as $v)<td class="text-right">{{ number_format($v, 4) }}</td>@endforeach</tr>
                    <tr><th>Liabilities + Equity</th>@for($i=0;$i<3;$i++)<td class="text-right">{{ number_format($totals['liabilities'][$i] + $totals['equity'][$i], 4) }}</td>@endfor</tr>
                    <tr><th>Difference</th>@for($i=0;$i<3;$i++)<td class="text-right">{{ number_format($totals['assets'][$i] - ($totals['liabilities'][$i] + $totals['equity'][$i]), 4) }}</td>@endfor</tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
