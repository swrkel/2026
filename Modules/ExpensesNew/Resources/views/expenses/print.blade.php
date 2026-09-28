@extends('expensesnew::layouts.app', ['heading'=>'Expense Voucher'])
@section('module_content')
<div class="expnew-card expnew-print-card">
    <h3>Expense Voucher - {{ $expense->expense_no }}</h3>
    <table class="table table-bordered">
        <tr><th>Date</th><td>{{ optional($expense->expense_date)->format('Y-m-d') }}</td><th>Status</th><td>{{ ucfirst($expense->payment_status) }}</td></tr>
        <tr><th>Category</th><td>{{ optional($expense->category)->name }}</td><th>Payee</th><td>{{ optional($expense->payee)->name }}</td></tr>
        <tr><th>Expense Account</th><td>{{ optional($expense->account)->name }}</td><th>Payment Method</th><td>{{ ucfirst(str_replace('_',' ',$expense->payment_method)) }}</td></tr>
        <tr><th>Total</th><td class="text-right">{{ number_format($expense->total_amount,4) }}</td><th>Paid</th><td class="text-right">{{ number_format($expense->paid_amount,4) }}</td></tr>
        <tr><th>Due</th><td class="text-right">{{ number_format($expense->due_amount,4) }}</td><th>Reference</th><td>{{ $expense->reference_no }}</td></tr>
        {{--
            MA-002 (S-621): both notes now follow the Settings page.

            Each defaults to SHOWING when the setting has never been saved,
            because that is what the voucher did before - the expense note was
            printed unconditionally. Defaulting to hidden would have quietly
            dropped it from every voucher.

            The PAYMENT note was not printed at all before; it appears only
            when its setting is on, and only when a payment actually carries
            one, so a voucher with no payment notes gains no empty row.
        --}}
        @if($showExpenseNote ?? true)
            <tr><th>Notes</th><td colspan="3">{{ $expense->notes }}</td></tr>
        @endif

        @if(($showPaymentNote ?? true) && ! empty($expense->payments))
            @foreach($expense->payments as $expensePayment)
                @if(! empty($expensePayment->notes))
                    <tr>
                        <th>Payment Note</th>
                        <td colspan="3">
                            {{ $expensePayment->notes }}
                            @if(! empty($expensePayment->payment_date))
                                <small style="color:#777;">
                                    ({{ \Carbon\Carbon::parse($expensePayment->payment_date)->format('Y-m-d') }})
                                </small>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        @endif
    </table>
    <button onclick="window.print()" class="btn btn-primary no-print">Print</button>
</div>
@endsection
