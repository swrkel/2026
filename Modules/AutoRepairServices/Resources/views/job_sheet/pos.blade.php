@extends('layouts.app')

@section('title', 'Repair POS Bills')

@section('content')
    <section class="content-header">
        <h1>Repair POS Bills</h1>
    </section>
    <section class="content">
        <div class="box">
            <div class="box-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Invoice No</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->contact->name ?? 'N/A' }}</td>
                                <td>{{ $transaction->invoice_no }}</td>
                                <td>{{ $transaction->final_total }}</td>
                                <td>{{ $transaction->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $transactions->links() }}
            </div>
        </div>
    </section>
@endsection
