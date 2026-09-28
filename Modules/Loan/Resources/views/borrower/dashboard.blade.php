@extends('layouts.app')

@section('title', 'Borrower Dashboard')

@section('content')

<section class="content-header">

    <h1>
        Borrower Dashboard
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-3">

            <div class="info-box bg-aqua">

                <span class="info-box-icon">
                    <i class="fa fa-money"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Active Loans
                    </span>

                    <span class="info-box-number">
                        0
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="info-box bg-green">

                <span class="info-box-icon">
                    <i class="fa fa-check"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Repayment Status
                    </span>

                    <span class="info-box-number">
                        Active
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="info-box bg-yellow">

                <span class="info-box-icon">
                    <i class="fa fa-warning"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Penalties
                    </span>

                    <span class="info-box-number">
                        0.00
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="info-box bg-red">

                <span class="info-box-icon">
                    <i class="fa fa-balance-scale"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Outstanding Balance
                    </span>

                    <span class="info-box-number">
                        0.00
                    </span>

                </div>

            </div>

        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Self-Service Portal
            </h3>

        </div>

        <div class="box-body">

            <p>
                Enterprise borrower portal initialized successfully.
            </p>

            <ul>

                <li>
                    Loan visibility
                </li>

                <li>
                    Repayment tracking
                </li>

                <li>
                    Schedule monitoring
                </li>

                <li>
                    Penalty visibility
                </li>

                <li>
                    Settlement requests
                </li>

                <li>
                    Restructuring requests
                </li>

                <li>
                    Customer self-service
                </li>

                <li>
                    Secure loan isolation
                </li>

            </ul>

        </div>

    </div>

</section>

@endsection