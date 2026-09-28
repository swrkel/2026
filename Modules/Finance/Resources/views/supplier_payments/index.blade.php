@extends('layouts.app')

@section('title', 'Supplier Payments')

@section('content')

<section class="content-header">
    <h1>
        Supplier Payments
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                Supplier Payment Filters
            </h3>
        </div>

        <div class="box-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>

                            <select name="location_id" class="form-control">
                                <option value="all">All Branches</option>

                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ request()->location_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Supplier Name</label>

                            <input type="text"
                                   name="supplier_name"
                                   class="form-control"
                                   value="{{ request()->supplier_name }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>From Date</label>

                            <input type="date"
                                   name="from_date"
                                   class="form-control"
                                   value="{{ request()->from_date }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>To Date</label>

                            <input type="date"
                                   name="to_date"
                                   class="form-control"
                                   value="{{ request()->to_date }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i>
                                Filter
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="info-box">
        <span class="info-box-icon bg-red">
            <i class="fa fa-credit-card"></i>
        </span>

        <div class="info-box-content">
            <span class="info-box-text">Total Supplier Payments</span>
            <span class="info-box-number">
                {{ number_format($total_supplier_payments, 2) }}
            </span>
        </div>
    </div>

    <div class="box box-danger">

        <div class="box-header with-border">
            <h3 class="box-title">
                Supplier Payment Register
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Supplier</th>
                        <th>Purchase Ref No</th>
                        <th>Payment Ref</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($supplier_payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_on }}</td>
                            <td>{{ $payment->location_name }}</td>
                            <td>{{ $payment->supplier_name }}</td>
                            <td>{{ $payment->ref_no }}</td>
                            <td>{{ $payment->payment_ref_no }}</td>
                            <td>{{ ucfirst($payment->method) }}</td>
                            <td class="text-right">
                                {{ number_format($payment->amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="text-center">
                {{ $supplier_payments->links() }}
            </div>

        </div>

    </div>

</section>

@endsection