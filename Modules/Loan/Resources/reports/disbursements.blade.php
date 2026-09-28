@extends('layouts.app')

@section('title', 'Disbursement Report')

@section('content')

<section class="content-header">

    <h1>
        Disbursement Report
    </h1>

</section>

<section class="content">

<div class="box box-primary">

    <div class="box-body">

        <table class="table table-bordered">

            <thead>

                <tr>

                    <th>Loan No</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Amount</th>
                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                @foreach($loans as $loan)

                <tr>

                    <td>
                        {{ $loan->loan_no }}
                    </td>

                    <td>
                        {{ optional($loan->customer)->name }}
                    </td>

                    <td>
                        {{ optional($loan->loanProduct)->name }}
                    </td>

                    <td>
                        {{ number_format($loan->principal_amount, 2) }}
                    </td>

                    <td>
                        {{ ucfirst($loan->status) }}
                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

        {{ $loans->links() }}

    </div>

</div>

</section>

@endsection