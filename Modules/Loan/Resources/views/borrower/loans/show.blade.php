@extends('layouts.app')

@section('title', 'Loan Details')

@section('content')

<section class="content-header">

    <h1>
        Loan Details
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Loan Details
            </h3>

        </div>

        <div class="box-body">

            <p>
                Loan ID:
                {{ $id }}
            </p>

        </div>

    </div>

</section>

@endsection