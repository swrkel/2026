@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')

<section class="content-header">
    <h1>
        Balance Sheet
    </h1>
</section>

<section class="content">

    @include('finance::finance_reports.partials.navigation')

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                Branch Wise / Consolidated Balance Sheet
            </h3>
        </div>

        <div class="box-body">
            <form method="GET" action="{{ url()->current() }}">
                <div class="row">

                    <div class="col-md-4">
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

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>As At Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $to_date }}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i>
                                Generate Balance Sheet
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="row">

        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Assets</h3>
                </div>

                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($assets as $account)
                                <tr>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-right">{{ number_format($account->balance, 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total Assets</th>
                                <th class="text-right">{{ number_format($total_assets, 4) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Liabilities</h3>
                </div>

                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($liabilities as $account)
                                <tr>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-right">{{ number_format($account->balance, 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total Liabilities</th>
                                <th class="text-right">{{ number_format($total_liabilities, 4) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Equity</h3>
                </div>

                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($equity as $account)
                                <tr>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-right">{{ number_format($account->balance, 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th>Total Equity</th>
                                <th class="text-right">{{ number_format($total_equity, 4) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">
                Balance Check
            </h3>
        </div>

        <div class="box-body">
            <h4>
                Assets:
                <strong>{{ number_format($total_assets, 4) }}</strong>
            </h4>

            <h4>
                Liabilities + Equity:
                <strong>{{ number_format($total_liabilities + $total_equity, 4) }}</strong>
            </h4>

            <h4>
                Difference:
                <strong>{{ number_format($total_assets - ($total_liabilities + $total_equity), 4) }}</strong>
            </h4>
        </div>
    </div>

</section>

@endsection