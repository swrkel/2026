@extends('layouts.app')
@section('title', 'Hotel City Ledger')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-credit-card"></i> City Ledger <small>Company, travel agent and house account billing control</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Ledger Balance</div><div class="hm-kpi-value">{{ number_format($cityLedger['total_balance'], 2) }}</div><div class="hm-kpi-sub">Outstanding company/agent accounts</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Credit Limit</div><div class="hm-kpi-value">{{ number_format($cityLedger['total_credit_limit'], 2) }}</div><div class="hm-kpi-sub">Approved direct billing limit</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Available Credit</div><div class="hm-kpi-value">{{ number_format($cityLedger['available_credit'], 2) }}</div><div class="hm-kpi-sub">Remaining credit capacity</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Overdue</div><div class="hm-kpi-value">{{ $cityLedger['overdue_invoice_count'] }}</div><div class="hm-kpi-sub">Invoices past due date</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Ledger Account</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.city-ledger.account') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Account Code</label><input name="account_code" class="form-control" required placeholder="CORP001"></div>
                    <div class="form-group"><label>Account Name</label><input name="account_name" class="form-control" required></div>
                    <div class="form-group"><label>Account Type</label><select name="account_type" class="form-control"><option value="corporate">Corporate</option><option value="travel_agent">Travel Agent</option><option value="house_account">House Account</option><option value="government">Government</option></select></div>
                    <div class="form-group"><label>Credit Days</label><input type="number" name="credit_days" class="form-control" value="30"></div>
                    <div class="form-group"><label>Contact Person</label><input name="contact_person" class="form-control"></div>
                    <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                    <div class="form-group"><label>Credit Limit</label><input type="number" step="0.01" name="credit_limit" class="form-control" value="0"></div>
                    <div class="form-group"><label>Opening Balance</label><input type="number" step="0.01" name="current_balance" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="hold">Hold</option><option value="closed">Closed</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Account</button></div>
            </form>
        </div></div></div>

        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Post Invoice</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.city-ledger.invoice') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Account</label><select name="ledger_account_id" class="form-control" required><option value="">Select</option>@foreach($cityLedger['accounts'] as $a)<option value="{{ $a->id }}">{{ $a->account_code }} - {{ $a->account_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Invoice Amount</label><input type="number" step="0.01" name="invoice_amount" class="form-control" required></div>
                    <div class="form-group"><label>Folio No</label><input name="folio_no" class="form-control"></div>
                    <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control"></div>
                    <div class="form-group"><label>Invoice Date</label><input type="date" name="invoice_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Due Date</label><input type="date" name="due_date" class="form-control"></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-file-text-o"></i> Post Invoice</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Receipt Collection</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.city-ledger.receipt') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Invoice</label><select name="invoice_id" class="form-control" required><option value="">Select</option>@foreach($cityLedger['invoices'] as $i)@if(($i->balance_amount ?? 0) > 0)<option value="{{ $i->id }}">{{ $i->invoice_no }} - {{ number_format($i->balance_amount,2) }}</option>@endif @endforeach</select></div>
                    <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="receipt_amount" class="form-control" required></div>
                    <div class="form-group"><label>Receipt Date</label><input type="date" name="receipt_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Method</label><select name="payment_method" class="form-control"><option value="cash">Cash</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option></select></div>
                    <div class="form-group"><label>Reference No</label><input name="reference_no" class="form-control"></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-money"></i> Record Receipt</button></div>
            </form>
        </div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Debit / Credit Adjustment</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.city-ledger.adjustment') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Account</label><select name="ledger_account_id" class="form-control" required><option value="">Select</option>@foreach($cityLedger['accounts'] as $a)<option value="{{ $a->id }}">{{ $a->account_code }} - {{ $a->account_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="adjustment_amount" class="form-control" required></div>
                    <div class="form-group"><label>Date</label><input type="date" name="adjustment_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Type</label><select name="adjustment_type" class="form-control"><option value="debit">Debit</option><option value="credit">Credit</option></select></div>
                </div>
                <div class="form-group"><label>Reason</label><textarea name="reason" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-edit"><i class="fa fa-adjust"></i> Save Adjustment</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Ledger Accounts</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th class="text-right">Credit Limit</th><th class="text-right">Balance</th><th class="text-right">Available</th><th>Status</th></tr></thead><tbody>
        @forelse($cityLedger['accounts'] as $a)<tr><td>{{ $a->account_code }}</td><td>{{ $a->account_name }}</td><td>{{ ucfirst(str_replace('_',' ', $a->account_type)) }}</td><td class="text-right">{{ number_format($a->credit_limit,2) }}</td><td class="text-right">{{ number_format($a->current_balance,2) }}</td><td class="text-right">{{ number_format(max(0, $a->credit_limit - $a->current_balance),2) }}</td><td><span class="label label-info">{{ ucfirst($a->status) }}</span></td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No city ledger accounts yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Invoices</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Invoice No</th><th>Account</th><th>Guest/Folio</th><th>Date</th><th>Due</th><th class="text-right">Invoice</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th></tr></thead><tbody>
        @forelse($cityLedger['invoices'] as $i)<tr><td>{{ $i->invoice_no }}</td><td>{{ $i->ledger_account_id }}</td><td>{{ $i->guest_name }}<br><small>{{ $i->folio_no }}</small></td><td>{{ $i->invoice_date }}</td><td>{{ $i->due_date }}</td><td class="text-right">{{ number_format($i->invoice_amount,2) }}</td><td class="text-right">{{ number_format($i->paid_amount,2) }}</td><td class="text-right">{{ number_format($i->balance_amount,2) }}</td><td><span class="label label-primary">{{ ucfirst($i->status) }}</span></td></tr>@empty<tr><td colspan="9" class="text-center text-muted">No invoices posted yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>
</section>
@endsection
