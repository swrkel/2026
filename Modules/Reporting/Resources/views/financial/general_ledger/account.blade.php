@extends('layouts.app')

@section('title', __('General Ledger'))

@section('content')

<section class="content-header">
    <h1>
        General Ledger
        <small>{{ $account->name }}</small>
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-book"></i>
                {{ $account->name }}
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-primary">
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Note</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Running Balance</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ @format_date($transaction->operation_date) }}</td>
                            <td>{{ ucfirst($transaction->type) }}</td>
                            <td>{{ $transaction->transaction_id ?? '-' }}</td>
                            <td>{{ $transaction->note ?? '-' }}</td>

                            <td class="text-right">
                                @if($transaction->type == 'debit')
                                    {{ number_format($transaction->amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-right">
                                @if($transaction->type == 'credit')
                                    {{ number_format($transaction->amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-right">
                                {{ number_format($transaction->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No ledger transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="bg-gray">
                        <th colspan="6" class="text-right">Closing Balance</th>
                        <th class="text-right">{{ number_format($running_balance, 2) }}</th>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>

</section>

@endsection