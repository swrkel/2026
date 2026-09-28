@extends('layouts.app')

@section('title', 'Overdue Report')

@section('content')

<section class="content-header">

    <h1>
        Overdue Report
    </h1>

</section>

<section class="content">

<div class="box box-danger">

    <div class="box-body">

        <table class="table table-bordered">

            <thead>

                <tr>

                    <th>Loan</th>
                    <th>Due Date</th>
                    <th>Balance</th>

                </tr>

            </thead>

            <tbody>

                @foreach($overdues as $overdue)

                <tr>

                    <td>
                        {{ optional($overdue->loan)->loan_no }}
                    </td>

                    <td>
                        {{ $overdue->due_date }}
                    </td>

                    <td>

                        {{ number_format(
                            $overdue->balance_amount,
                            2
                        ) }}

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

        {{ $overdues->links() }}

    </div>

</div>

</section>

@endsection