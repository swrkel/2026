@extends('layouts.app')
@section('title', $reconciliation ? 'Edit Bank Reconciliation' : 'New Bank Reconciliation')

@section('content')
<section class="content-header">
    <h1>{{ $reconciliation ? 'Edit Bank Reconciliation' : 'New Bank Reconciliation' }}</h1>
</section>

<section class="content main-content-inner">
    @if(session('success'))
        <div class="alert alert-success finance-bankrec-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $reconciliation ? route('finance.bank-reconciliation.update', $reconciliation->id) : route('finance.bank-reconciliation.store') }}" id="bank_reconciliation_form">
        @csrf
        @if($reconciliation) @method('PUT') @endif

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Statement Details</h3>
                <div class="box-tools pull-right"><a href="{{ route('finance.bank-reconciliation.index') }}" class="btn btn-default btn-sm"><i class="fa fa-list"></i> Reconciliation List</a></div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bank Account <span class="text-danger">*</span></label>
                            <select name="account_id" id="bank_account_id" class="form-control select2" required style="width:100%">
                                <option value="">Please Select</option>
                                @foreach($bankAccounts as $account)
                                    @php $selectedAccount = old('account_id', $reconciliation ? $reconciliation->account_id : null); @endphp
                                    <option value="{{ $account->id }}" {{ (string)$selectedAccount === (string)$account->id ? 'selected' : '' }}>
                                        {{ $account->name }}{{ $account->account_number ? ' - '.$account->account_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bank Statement Date <span class="text-danger">*</span></label>
                            <input type="date" name="statement_date" id="statement_date" class="form-control" required value="{{ old('statement_date', $reconciliation && $reconciliation->statement_date ? $reconciliation->statement_date->format('Y-m-d') : date('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Bank Statement Ending Balance <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" name="statement_ending_balance" id="statement_ending_balance" class="form-control text-right" required value="{{ old('statement_ending_balance', $reconciliation ? number_format((float)$reconciliation->statement_ending_balance, 4, '.', '') : '0.0000') }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Note</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $reconciliation ? $reconciliation->notes : '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="row finance-bankrec-summary">
            <div class="col-md-2 col-sm-4"><div class="small-box finance-card"><div class="inner"><span>Book Balance</span><strong id="book_balance">0.0000</strong></div></div></div>
            <div class="col-md-2 col-sm-4"><div class="small-box finance-card"><div class="inner"><span>Statement Balance</span><strong id="statement_balance_display">0.0000</strong></div></div></div>
            <div class="col-md-2 col-sm-4"><div class="small-box finance-card"><div class="inner"><span>Outstanding Deposits</span><strong id="outstanding_deposits">0.0000</strong></div></div></div>
            <div class="col-md-2 col-sm-4"><div class="small-box finance-card"><div class="inner"><span>Outstanding Payments</span><strong id="outstanding_payments">0.0000</strong></div></div></div>
            <div class="col-md-2 col-sm-4"><div class="small-box finance-card"><div class="inner"><span>Adjusted Bank</span><strong id="adjusted_bank_balance">0.0000</strong></div></div></div>
            <div class="col-md-2 col-sm-4"><div class="small-box finance-card finance-difference-card"><div class="inner"><span>Difference</span><strong id="difference">0.0000</strong></div></div></div>
        </div>

        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Unreconciled Bank Transactions</h3>
                <div class="box-tools finance-bankrec-table-tools">
                    <input type="text" id="bankrec_search" class="form-control input-sm" placeholder="Search transactions">
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info finance-bankrec-help">
                    Tick only transactions that appear on the bank statement. Unticked Debit entries are treated as outstanding deposits; unticked Credit entries are treated as outstanding payments. Finalize is allowed only when Difference = 0.0000.
                </div>
                <div id="bankrec_loading" class="text-center text-muted" style="display:none"><i class="fa fa-spinner fa-spin"></i> Loading bank transactions...</div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="bankrec_transactions_table">
                        <thead>
                            <tr>
                                <th style="width:55px"><label style="margin:0"><input type="checkbox" id="select_all_transactions"> Select</label></th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-right">Deposit / Debit</th>
                                <th class="text-right">Payment / Credit</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="6" class="text-center text-muted">Select a bank account and statement date.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="box-footer text-right">
                <a href="{{ route('finance.bank-reconciliation.index') }}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary" id="save_bankrec"><i class="fa fa-save"></i> Save Draft</button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('javascript')
<script>
(function($){
    'use strict';
    var endpoint = @json(route('finance.bank-reconciliation.transactions'));
    var selectedIds = new Set((@json(array_values($selectedTransactionIds))).map(function(v){ return parseInt(v, 10); }));
    var rows = [];
    var bookBalance = 0;

    function n(v){ var x=parseFloat(v); return isFinite(x)?x:0; }
    function money(v){ return n(v).toLocaleString(undefined,{minimumFractionDigits:4,maximumFractionDigits:4}); }
    function esc(v){ return $('<div>').text(v == null ? '' : String(v)).html(); }

    function calculate(){
        var outstandingDeposits=0, outstandingPayments=0;
        rows.forEach(function(row){
            if (!selectedIds.has(parseInt(row.id,10))) {
                outstandingDeposits += n(row.deposit);
                outstandingPayments += n(row.payment);
            }
        });
        var statement=n($('#statement_ending_balance').val());
        var adjusted=statement+outstandingDeposits-outstandingPayments;
        var difference=bookBalance-adjusted;
        $('#book_balance').text(money(bookBalance));
        $('#statement_balance_display').text(money(statement));
        $('#outstanding_deposits').text(money(outstandingDeposits));
        $('#outstanding_payments').text(money(outstandingPayments));
        $('#adjusted_bank_balance').text(money(adjusted));
        $('#difference').text(money(difference));
        $('.finance-difference-card').toggleClass('is-zero', Math.abs(difference)<=0.0001).toggleClass('has-difference', Math.abs(difference)>0.0001);
    }

    function render(){
        var $body=$('#bankrec_transactions_table tbody').empty();
        if (!rows.length) {
            $body.append('<tr><td colspan="6" class="text-center text-muted">No unreconciled bank transactions found up to this statement date.</td></tr>');
            calculate(); return;
        }
        rows.forEach(function(row){
            var checked=selectedIds.has(parseInt(row.id,10))?' checked':'';
            $body.append('<tr data-search="'+esc((row.date+' '+row.reference+' '+row.description).toLowerCase())+'">'
                +'<td class="text-center"><input type="checkbox" class="bankrec-line" name="cleared_transaction_ids[]" value="'+row.id+'"'+checked+'></td>'
                +'<td>'+esc(row.date)+'</td>'
                +'<td>'+esc(row.reference)+'</td>'
                +'<td>'+esc(row.description)+'</td>'
                +'<td class="text-right">'+money(row.deposit)+'</td>'
                +'<td class="text-right">'+money(row.payment)+'</td>'
                +'</tr>');
        });
        $('#select_all_transactions').prop('checked', rows.length>0 && rows.every(function(r){return selectedIds.has(parseInt(r.id,10));}));
        calculate();
    }

    function loadTransactions(){
        var account=$('#bank_account_id').val(), date=$('#statement_date').val();
        if(!account || !date){ rows=[]; bookBalance=0; render(); return; }
        $('#bankrec_loading').show();
        $.ajax({url:endpoint, method:'GET', dataType:'json', data:{account_id:account,statement_date:date}})
            .done(function(res){ rows=res.transactions||[]; bookBalance=n(res.book_balance); var eligible=new Set(rows.map(function(r){return parseInt(r.id,10);})); selectedIds=new Set(Array.from(selectedIds).filter(function(id){return eligible.has(id);})); render(); })
            .fail(function(xhr){ rows=[];bookBalance=0;render(); var msg=(xhr.responseJSON&&xhr.responseJSON.message)?xhr.responseJSON.message:'Unable to load bank transactions.'; if(window.toastr){toastr.error(msg);}else{alert(msg);} })
            .always(function(){ $('#bankrec_loading').hide(); });
    }

    $(function(){
        if($.fn.select2) $('.select2').select2();
        $('#bank_account_id,#statement_date').on('change',loadTransactions);
        $('#statement_ending_balance').on('input',calculate);
        $(document).on('change','.bankrec-line',function(){ var id=parseInt(this.value,10); if(this.checked)selectedIds.add(id);else selectedIds.delete(id); calculate(); $('#select_all_transactions').prop('checked', rows.length>0 && rows.every(function(r){return selectedIds.has(parseInt(r.id,10));})); });
        $('#select_all_transactions').on('change',function(){ var checked=this.checked; rows.forEach(function(r){var id=parseInt(r.id,10); if(checked)selectedIds.add(id);else selectedIds.delete(id);}); render(); });
        $('#bankrec_search').on('input',function(){ var q=$(this).val().toLowerCase(); $('#bankrec_transactions_table tbody tr[data-search]').each(function(){ $(this).toggle(!q || $(this).attr('data-search').indexOf(q)!==-1); }); });
        $('#bank_reconciliation_form').on('submit',function(){ $('#save_bankrec').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Saving...'); });
        calculate(); loadTransactions();
    });
})(jQuery);
</script>
<style>
.finance-bankrec-success{background:#16a34a!important;color:#fff!important;border-color:#15803d!important}.finance-bankrec-summary .finance-card{min-height:90px;background:#f8fbff;border:1px solid #dbe7f3;border-radius:10px;box-shadow:none;color:#152238}.finance-bankrec-summary .finance-card .inner{padding:14px}.finance-bankrec-summary .finance-card span{display:block;color:#526a86;font-size:12px;font-weight:700;min-height:34px}.finance-bankrec-summary .finance-card strong{display:block;font-size:18px;text-align:right}.finance-difference-card.is-zero{background:#ecfdf3;border-color:#86efac}.finance-difference-card.has-difference{background:#fff1f2;border-color:#fda4af}.finance-bankrec-table-tools{width:220px}.finance-bankrec-help{margin-bottom:12px}#bankrec_transactions_table th{color:#526a86;text-align:center;vertical-align:middle!important}#bankrec_transactions_table td{vertical-align:middle!important}
</style>
@endsection
