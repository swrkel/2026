@extends('layouts.app')

@section('title', 'Loan Schedule')

@section('content')

<section class="content-header">

    <h1>
        Loan Repayment Schedule
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Repayment Schedule
            </h3>

        </div>

        <div class="box-body">

            <p>
                Loan ID:
                {{ $id }}
            </p>

            <p>
                Borrower repayment schedule workspace initialized successfully.
            </p>

        </div>

    </div>

</section>

@endsection