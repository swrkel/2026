@extends('layouts.app')

@section('title', 'Repayment History')

@section('content')

<section class="content-header">

    <h1>
        Repayment History
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Repayment History
            </h3>

        </div>

        <div class="box-body">

            <p>
                Loan ID:
                {{ $id }}
            </p>

            <p>
                Borrower repayment history workspace initialized successfully.
            </p>

        </div>

    </div>

</section>

@endsection