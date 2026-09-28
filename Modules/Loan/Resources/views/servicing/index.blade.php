@extends('layouts.app')

@section('title', 'Loan Servicing')

@section('content')

<section class="content-header">

    <h1>
        Loan Servicing
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-3">

            <div class="info-box bg-aqua">

                <span class="info-box-icon">
                    <i class="fa fa-credit-card"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Active Servicing
                    </span>

                    <span class="info-box-number">
                        Enabled
                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="info-box bg-green">

                <span class="info-box-icon">
                    <i class="fa fa-calendar"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Repayment Monitoring
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
                        Delinquency Tracking
                    </span>

                    <span class="info-box-number">
                        Running
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
                        Governance
                    </span>

                    <span class="info-box-number">
                        Enterprise
                    </span>

                </div>

            </div>

        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Loan Servicing Workspace
            </h3>

        </div>

        <div class="box-body">

            <p>
                Enterprise loan servicing module
                initialized successfully.
            </p>

            <ul>

                <li>
                    Repayment servicing
                </li>

                <li>
                    Delinquency monitoring
                </li>

                <li>
                    Schedule governance
                </li>

                <li>
                    Customer servicing
                </li>

                <li>
                    Settlement servicing
                </li>

                <li>
                    Restructuring support
                </li>

                <li>
                    Collections servicing
                </li>

                <li>
                    Recovery servicing
                </li>

            </ul>

        </div>

    </div>

</section>

@endsection