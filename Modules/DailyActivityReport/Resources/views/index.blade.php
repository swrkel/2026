@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h3 class="mb-4">Daily Activity Report</h3>

        <form method="GET" action="{{ route('daily-activity-report.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3">
                    <select name="location_id" class="form-control">
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $loc->id == $selected_location_id ? 'selected' : '' }}>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success">Filter</button>
                </div>
            </div>
        </form>

        <table class="table table-bordered">
            <tr>
                <th>Business:</th>
                <td>{{ $business->name ?? '-' }}</td>
                <th>Business Location:</th>
                <td>{{ $business->location ?? '-' }}</td>
            </tr>
            <tr>
                <th>Date Range:</th>
                <td colspan="3">{{ $from_date }} to {{ $to_date }}</td>
            </tr>
        </table>

        <h5 class="mt-4">Shifts</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Shift</th>
                    <th>Morning</th>
                    <th>Evening</th>
                    <th>Full Shift</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shifts ?? [] as $shift)
                    <tr>
                        <td>{{ $shift->name }}</td>
                        <td>{{ $shift->morning }}</td>
                        <td>{{ $shift->evening }}</td>
                        <td>{{ $shift->full_shift }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h5 class="mt-4">Pump Readings</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Pump No</th>
                    <th>Opening Meter</th>
                    <th>Closing Meter</th>
                    <th>Sold Liters</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pumps ?? [] as $pump)
                    <tr>
                        <td>{{ $pump->pump_no }}</td>
                        <td>{{ $pump->starting_meter }}</td>
                        <td>{{ $pump->last_meter_reading }}</td>
                        <td>{{ $pump->temp_meter_reading }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h5 class="mt-4">Other Sales</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Total Sold Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($otherSales ?? [] as $sale)
                    <tr>
                        <td>{{ $sale->product_name }}</td>
                        <td>{{ $sale->qty }}</td>
                        <td>{{ $sale->total_qty }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h5 class="mt-4">Customer Payments</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ST No</th>
                    <th>Customer Name</th>
                    <th>Paid Amount</th>
                    <th>Balance Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customerPayments ?? [] as $payment)
                    <tr>
                        <td>{{ $payment->st_no }}</td>
                        <td>{{ $payment->customer_name }}</td>
                        <td>{{ $payment->paid_amount }}</td>
                        <td>{{ $payment->balance_due }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h5 class="mt-4">Purchases</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>PO No</th>
                    <th>Supplier Name</th>
                    <th>Due Amount</th>
                    <th>Paid Amount</th>
                    <th>Balance Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchases ?? [] as $purchase)
                    <tr>
                        <td>{{ $purchase->po_no }}</td>
                        <td>{{ $purchase->supplier }}</td>
                        <td>{{ $purchase->due_amount }}</td>
                        <td>{{ $purchase->paid_amount }}</td>
                        <td>{{ $purchase->balance_due }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h5 class="mt-4">Sales Summary</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Total Sales</th>
                    <th>Total Cash Sales</th>
                    <th>Total Credit Sales</th>
                    <th>Total Card</th>
                    <th>Total Cheques</th>
                    <th>Total Expenses</th>
                    <th>Total Shortage</th>
                    <th>Total Excess</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $salesSummary->total ?? 0 }}</td>
                    <td>{{ $salesSummary->cash ?? 0 }}</td>
                    <td>{{ $salesSummary->credit ?? 0 }}</td>
                    <td>{{ $salesSummary->card ?? 0 }}</td>
                    <td>{{ $salesSummary->cheques ?? 0 }}</td>
                    <td>{{ $salesSummary->expenses ?? 0 }}</td>
                    <td>{{ $salesSummary->shortage ?? 0 }}</td>
                    <td>{{ $salesSummary->excess ?? 0 }}</td>
                </tr>
            </tbody>
        </table>

        {{-- 7. Payments --}}
        <h5 class="mt-4">Payments</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments ?? [] as $pay)
                    <tr>
                        <td>{{ $pay->type }}</td>
                        <td>{{ $pay->amount }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 8. Credit Sales --}}
        <h5 class="mt-4">Credit Sales</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ST No</th>
                    <th>Customer Name</th>
                    <th>Sale Amount</th>
                    <th>Total Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($creditSales ?? [] as $cs)
                    <tr>
                        <td>{{ $cs->st_no }}</td>
                        <td>{{ $cs->customer }}</td>
                        <td>{{ $cs->amount }}</td>
                        <td>{{ $cs->outstanding }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 9. Expenses --}}
        <h5 class="mt-4">Expenses</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ST No</th>
                    <th>Category</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenses ?? [] as $exp)
                    <tr>
                        <td>{{ $exp->st_no }}</td>
                        <td>{{ $exp->category }}</td>
                        <td>{{ $exp->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 10. Account Summary --}}
        <h5 class="mt-4">Account Summary</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Today Balance</th>
                    <th>Deposited</th>
                    <th>Paid</th>
                    <th>Received</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($accounts ?? [] as $acc)
                    <tr>
                        <td>{{ $acc->name }}</td>
                        <td>{{ $acc->today_balance }}</td>
                        <td>{{ $acc->deposited }}</td>
                        <td>{{ $acc->paid }}</td>
                        <td>{{ $acc->received }}</td>
                        <td>{{ $acc->balance }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 11. Stock --}}
        <h5 class="mt-4">Tank Stock</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tank No</th>
                    <th>Opening Stock</th>
                    <th>Total Purchase</th>
                    <th>Total Sold</th>
                    <th>Testing Qty</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tanks ?? [] as $tank)
                    <tr>
                        <td>{{ $tank->number }}</td>
                        <td>{{ $tank->opening }}</td>
                        <td>{{ $tank->purchase }}</td>
                        <td>{{ $tank->sold }}</td>
                        <td>{{ $tank->testing }}</td>
                        <td>{{ $tank->balance }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- 12. Footer --}}
        <h5 class="mt-4">Report Approval</h5>
        <table class="table table-bordered">
            <tr>
                <th>Date</th>
                <td>{{ now()->format('d-M-Y') }}</td>
                <th>Entered By</th>
                <td>{{ auth()->user()->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Checked By</th>
                <td colspan="3"></td>
            </tr>
            <tr>
                <th>Signature</th>
                <td colspan="3"></td>
            </tr>
            <tr>
                <th>Printed Date</th>
                <td colspan="3">{{ now()->format('d-M-Y H:i') }}</td>
            </tr>
        </table>
    </div>
@endsection
