@extends('layouts.app')

@section('title', 'Loan Penalties')

@section('content')

<section class="content-header">

    <h1>
        Loan Penalties
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Penalty Information
            </h3>

        </div>

        <div class="box-body">

            <p>
                Loan ID:
                {{ $id }}
            </p>

            <p>
                Borrower penalty workspace initialized successfully.
            </p>

        </div>

    </div>

</section>

@endsection