@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')

<section class="content-header">
    <h1>
        Trial Balance
    </h1>
</section>

<section class="content">

    @include('finance::finance_reports.partials.navigation')

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                Branch Wise / Consolidated Trial Balance
            </h3>
        </div>

        <div class="box-body">
            <form method="GET" action="{{ route('finance.reports.trial_balance') }}">
                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>
                            <select name="location_id" class="form-control">
                                <option value="all">All Branches / Consolidated</option>

                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ $location_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $from_date }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $to_date }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i>
                                Generate Trial Balance
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">
                Trial Balance Report
            </h3>
        </div>

        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td>{{ $account->name }}</td>
                            <td class="text-right">{{ number_format($account->debit, 4) }}</td>
                            <td class="text-right">{{ number_format($account->credit, 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="text-right">{{ number_format($total_debit, 4) }}</th>
                        <th class="text-right">{{ number_format($total_credit, 4) }}</th>
                    </tr>

                    <tr>
                        <th>Difference</th>
                        <th colspan="2" class="text-right">
                            {{ number_format($total_debit - $total_credit, 4) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</section>

@endsection