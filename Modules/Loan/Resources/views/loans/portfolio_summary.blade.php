@extends('layouts.app')

@section('title', 'Portfolio Summary')

@section('content')

<section class="content-header">

    <h1>
        Portfolio Summary
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-4">

            <div class="info-box">

                <span class="info-box-icon bg-blue">

                    <i class="fa fa-money"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Total Portfolio
                    </span>

                    <span class="info-box-number">

                        {{ number_format(
                            $total_portfolio,
                            2
                        ) }}

                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="info-box">

                <span class="info-box-icon bg-red">

                    <i class="fa fa-warning"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Overdue Amount
                    </span>

                    <span class="info-box-number">

                        {{ number_format(
                            $overdue_amount,
                            2
                        ) }}

                    </span>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="info-box">

                <span class="info-box-icon bg-green">

                    <i class="fa fa-line-chart"></i>

                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        PAR %
                    </span>

                    <span class="info-box-number">

                        {{ number_format(
                            $par_percentage,
                            2
                        ) }} %

                    </span>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection