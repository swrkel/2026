@extends('layouts.app')

@section('title', 'Loan Reports & Analytics')

@section('content')
<section class="content-header">
    <h1>Loan Reports & Analytics <small>Portfolio, branch, product, officer and arrears performance</small></h1>
</section>

<section class="content loan-reports-analytics-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('loan.reports.analytics') }}">
                <div class="row">
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $start_date }}">
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $end_date }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label>Business Location</label>
                            <select name="location_id" class="form-control select2">
                                <option value="">All Locations</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['location_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label>Loan Product</label>
                            <select name="loan_product_id" class="form-control select2">
                                <option value="">All Products</option>
                                @foreach($products as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['loan_product_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label>Loan Officer</label>
                            <select name="loan_officer_id" class="form-control select2">
                                <option value="">All Officers</option>
                                @foreach($officers as $id => $name)
                                    <option value="{{ $id }}" {{ ($filters['loan_officer_id'] ?? '') == $id ? 'selected' : '' }}>{{ $name ?: 'User #'.$id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <a href="{{ route('loan.reports.analytics') }}" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset</a>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-file-text"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Applications</span>
                    <span class="info-box-number">{{ number_format($portfolio['total_applications']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Approved</span>
                    <span class="info-box-number">{{ number_format($portfolio['approved_applications']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-bank"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Loans</span>
                    <span class="info-box-number">{{ number_format($portfolio['active_loans']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Rejected</span>
                    <span class="info-box-number">{{ number_format($portfolio['rejected_applications']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title">Portfolio Summary</h3></div>
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr><th>Disbursed Amount</th><td class="text-right">{{ number_format($portfolio['disbursed_amount'], 2) }}</td></tr>
                            <tr><th>Repaid Amount</th><td class="text-right">{{ number_format($portfolio['repaid_amount'], 2) }}</td></tr>
                            <tr><th>Outstanding Amount</th><td class="text-right"><strong>{{ number_format($portfolio['outstanding_amount'], 2) }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Branch Performance</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped loan-analytics-table">
                        <thead><tr><th>Branch</th><th class="text-right">Loans</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                        @forelse($branchRows as $row)
                            <tr><td>{{ $row->name }}</td><td class="text-right">{{ number_format($row->total_loans) }}</td><td class="text-right">{{ number_format($row->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No branch data found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Product Performance</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped loan-analytics-table">
                        <thead><tr><th>Product</th><th class="text-right">Loans</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                        @forelse($productRows as $row)
                            <tr><td>{{ $row->name }}</td><td class="text-right">{{ number_format($row->total_loans) }}</td><td class="text-right">{{ number_format($row->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No product data found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">Loan Officer Performance</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped loan-analytics-table">
                        <thead><tr><th>Loan Officer</th><th class="text-right">Loans</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                        @forelse($officerRows as $row)
                            <tr><td>{{ $row->name ?: 'Not Assigned' }}</td><td class="text-right">{{ number_format($row->total_loans) }}</td><td class="text-right">{{ number_format($row->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No officer data found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-danger">
        <div class="box-header with-border"><h3 class="box-title">Top Overdue Loans</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped loan-analytics-table">
                <thead>
                    <tr>
                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Oldest Due Date</th>
                        <th class="text-right">Overdue Amount</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($arrearsRows as $row)
                    <tr>
                        <td>{{ $row->loan_id }}</td>
                        <td>{{ $row->customer_name }}</td>
                        <td>{{ $row->location_name }}</td>
                        <td>{{ $row->oldest_due_date }}</td>
                        <td class="text-right">{{ number_format($row->overdue_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No overdue data found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
.loan-reports-analytics-page .box { border-radius: 8px; }
.loan-reports-analytics-page .box-header { padding: 14px 16px; }
.loan-reports-analytics-page .box-body { padding: 16px; }
.loan-reports-analytics-page .table > thead > tr > th { white-space: nowrap; }
.loan-reports-analytics-page .text-right { text-align: right; }
@media (max-width: 767px) {
    .loan-reports-analytics-page .text-right { text-align: left; }
}
</style>
@endsection
