@extends('expensesnew::layouts.app', ['heading'=>'View Expense'])
@section('module_content')
<div class="expnew-card">
    <div class="expnew-card-header">
        <h3>Expense Details - {{ $expense->expense_no }}</h3>
        <div class="expnew-actions">
            <a href="{{ route('expensesnew.expenses.edit', $expense->id) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('expensesnew.expenses.print', $expense->id) }}" class="btn btn-info" target="_blank">Print</a>
            <a href="{{ route('expensesnew.expenses.index') }}" class="btn btn-default">Back</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered expnew-details-table">
            <tbody>
                <tr><th>Expense No</th><td>{{ $expense->expense_no }}</td><th>Date</th><td>{{ optional($expense->expense_date)->format('Y-m-d') }}</td></tr>
                <tr><th>Category</th><td>{{ optional($expense->category)->name ?: '—' }}</td><th>Payee</th><td>{{ optional($expense->payee)->name ?: '—' }}</td></tr>
                <tr><th>Expense Account</th><td>{{ optional($expense->account)->name ?: '—' }}</td><th>Payment Method</th><td>{{ ucfirst(str_replace('_', ' ', (string) $expense->payment_method)) }}</td></tr>
                <tr><th>Accounting Module</th><td>{{ ucfirst((string) $expense->accounting_module) ?: '—' }}</td><th>Reference No</th><td>{{ $expense->reference_no ?: '—' }}</td></tr>
                <tr><th>Total Amount</th><td class="text-right">{{ number_format((float) $expense->total_amount, 4) }}</td><th>Paid Amount</th><td class="text-right">{{ number_format((float) $expense->paid_amount, 4) }}</td></tr>
                <tr><th>Due Amount</th><td class="text-right">{{ number_format((float) $expense->due_amount, 4) }}</td><th>Status</th><td>{{ ucfirst((string) ($expense->payment_status ?: 'due')) }}</td></tr>
                <tr><th>Cheque No</th><td>{{ $expense->cheque_no ?: '—' }}</td><th>Card No</th><td>{{ $expense->card_no ?: '—' }}</td></tr>
                <tr><th>Notes</th><td colspan="3">{{ $expense->notes ?: '—' }}</td></tr>
            </tbody>
        </table>
    </div>

    @if($expense->attachments && $expense->attachments->count())
        <h4>Attachments</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>File</th><th>Size</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($expense->attachments as $attachment)
                    <tr>
                        <td>{{ $attachment->file_name }}</td>
                        <td>{{ number_format((float) $attachment->file_size / 1024, 2) }} KB</td>
                        <td><a class="btn btn-xs btn-info" target="_blank" href="{{ asset('storage/' . $attachment->file_path) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
