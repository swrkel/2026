@extends('layouts.app')

@section('title', 'Repayment Report')

@section('content')

<section class="content-header">

    <h1>
        Repayment Report
    </h1>

</section>

<section class="content">

<div class="box box-success">

    <div class="box-body">

        <table class="table table-bordered">

            <thead>

                <tr>

                    <th>Loan</th>
                    <th>Payment Date</th>
                    <th>Amount</th>

                </tr>

            </thead>

            <tbody>

                @foreach($repayments as $repayment)

                <tr>

                    <td>
                        {{ optional($repayment->loan)->loan_no }}
                    </td>

                    <td>
                        {{ $repayment->payment_date }}
                    </td>

                    <td>
                        {{ number_format($repayment->amount, 2) }}
                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

        {{ $repayments->links() }}

    </div>

</div>

</section>

@endsection