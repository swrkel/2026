@extends('layouts.app')

@section('title', 'Customer List Report')

@section('content')
<section class="content-header">
    <h1>Customer List Report <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title pull-left">Customer List</h3>
            <div class="pull-right">
                <a href="{{ route('customers.reports.list.export') }}" class="btn btn-success btn-sm"><i class="fa fa-download"></i> CSV</a>
                <button type="button" onclick="window.print()" class="btn btn-default btn-sm"><i class="fa fa-print"></i> Print</button>
                <a href="{{ route('customers.reports.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Reports</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Customer Code</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th class="text-right">Credit Limit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->contact_id ?? '-' }}</td>
                            <td>{{ $customer->name ?? '-' }}</td>
                            <td>{{ $customer->mobile ?? '-' }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td class="text-right">{{ number_format((float)($customer->credit_limit ?? 0), 2) }}</td>
                            <td>
                                @if((int)($customer->active ?? 0) === 1)
                                    <span class="label label-success">Active</span>
                                @else
                                    <span class="label label-default">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No customer records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
