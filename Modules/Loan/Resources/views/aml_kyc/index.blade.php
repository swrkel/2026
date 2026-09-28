@extends('layouts.app')

@section('title', 'AML / KYC Governance')

@section('content')

<section class="content-header">

    <h1>
        Enterprise AML / KYC Governance
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-3">

            <div class="small-box bg-green">

                <div class="inner">

                    <h3>
                        {{ $profiles->where('kyc_status', 'verified')->count() }}
                    </h3>

                    <p>
                        Verified KYC
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-check"></i>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="small-box bg-yellow">

                <div class="inner">

                    <h3>
                        {{ $profiles->where('aml_status', 'review')->count() }}
                    </h3>

                    <p>
                        AML Under Review
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-search"></i>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="small-box bg-red">

                <div class="inner">

                    <h3>
                        {{ $profiles->where('sanctions_flag', 1)->count() }}
                    </h3>

                    <p>
                        Sanctions Matches
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-ban"></i>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="small-box bg-aqua">

                <div class="inner">

                    <h3>
                        {{ $profiles->count() }}
                    </h3>

                    <p>
                        Total Profiles
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-users"></i>
                </div>

            </div>

        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">

                AML / KYC Profiles

            </h3>

            <div class="box-tools">

                <a href="{{ route('loan.aml_kyc.create') }}"
                   class="btn btn-primary">

                    <i class="fa fa-plus"></i>
                    Add AML/KYC Profile

                </a>

            </div>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Borrower</th>

                        <th>KYC Status</th>

                        <th>AML Status</th>

                        <th>Risk Rating</th>

                        <th>PEP</th>

                        <th>Sanctions</th>

                        <th>Review Date</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($profiles as $profile)

                        <tr>

                            <td>
                                {{ $profile->borrower->name ?? '' }}
                            </td>

                            <td>

                                @if($profile->kyc_status == 'verified')

                                    <span class="label bg-green">
                                        VERIFIED
                                    </span>

                                @elseif($profile->kyc_status == 'rejected')

                                    <span class="label bg-red">
                                        REJECTED
                                    </span>

                                @else

                                    <span class="label bg-yellow">
                                        {{ strtoupper($profile->kyc_status) }}
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($profile->aml_status == 'blocked')

                                    <span class="label bg-red">
                                        BLOCKED
                                    </span>

                                @elseif($profile->aml_status == 'review')

                                    <span class="label bg-yellow">
                                        REVIEW
                                    </span>

                                @else

                                    <span class="label bg-green">
                                        CLEAR
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($profile->risk_rating == 'critical')

                                    <span class="label bg-red">
                                        CRITICAL
                                    </span>

                                @elseif($profile->risk_rating == 'high')

                                    <span class="label bg-orange">
                                        HIGH
                                    </span>

                                @elseif($profile->risk_rating == 'medium')

                                    <span class="label bg-yellow">
                                        MEDIUM
                                    </span>

                                @else

                                    <span class="label bg-green">
                                        LOW
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($profile->pep_flag)

                                    <span class="label bg-red">
                                        YES
                                    </span>

                                @else

                                    <span class="label bg-green">
                                        NO
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($profile->sanctions_flag)

                                    <span class="label bg-red">
                                        FLAGGED
                                    </span>

                                @else

                                    <span class="label bg-green">
                                        CLEAR
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ $profile->next_review_date }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection