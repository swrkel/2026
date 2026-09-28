@extends('layouts.app')

@section('title', 'Sanctions & Watchlist Governance')

@section('content')

<section class="content-header">

    <h1>
        Enterprise Sanctions & Watchlist Governance
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-3">

            <div class="small-box bg-red">

                <div class="inner">

                    <h3>
                        {{ $watchlists->count() }}
                    </h3>

                    <p>
                        Total Watchlist Records
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-ban"></i>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="small-box bg-yellow">

                <div class="inner">

                    <h3>
                        {{ $watchlists->where('screening_status', 'pending')->count() }}
                    </h3>

                    <p>
                        Pending Screening
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-search"></i>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="small-box bg-green">

                <div class="inner">

                    <h3>
                        {{ $watchlists->where('screening_status', 'cleared')->count() }}
                    </h3>

                    <p>
                        Cleared Profiles
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-check"></i>
                </div>

            </div>

        </div>

        <div class="col-md-3">

            <div class="small-box bg-aqua">

                <div class="inner">

                    <h3>
                        {{ $watchlists->where('risk_level', 'high')->count() }}
                    </h3>

                    <p>
                        High Risk Matches
                    </p>

                </div>

                <div class="icon">
                    <i class="fa fa-warning"></i>
                </div>

            </div>

        </div>

    </div>

    <div class="box box-danger">

        <div class="box-header with-border">

            <h3 class="box-title">

                Watchlist Screening Results

            </h3>

            <div class="box-tools">

                <a href="{{ route('loan.sanctions_watchlist.create') }}"
                   class="btn btn-danger">

                    <i class="fa fa-plus"></i>
                    Add Watchlist Entry

                </a>

            </div>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Entity</th>

                        <th>Type</th>

                        <th>Country</th>

                        <th>Match Score</th>

                        <th>Risk Level</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($watchlists as $watchlist)

                        <tr>

                            <td>
                                {{ $watchlist->entity_name }}
                            </td>

                            <td>
                                {{ strtoupper($watchlist->watchlist_type) }}
                            </td>

                            <td>
                                {{ $watchlist->country }}
                            </td>

                            <td>
                                {{ $watchlist->match_score }}%
                            </td>

                            <td>

                                @if($watchlist->risk_level == 'high')

                                    <span class="label bg-red">
                                        HIGH
                                    </span>

                                @elseif($watchlist->risk_level == 'medium')

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

                                @if($watchlist->screening_status == 'blocked')

                                    <span class="label bg-red">
                                        BLOCKED
                                    </span>

                                @elseif($watchlist->screening_status == 'pending')

                                    <span class="label bg-yellow">
                                        PENDING
                                    </span>

                                @else

                                    <span class="label bg-green">
                                        CLEARED
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection