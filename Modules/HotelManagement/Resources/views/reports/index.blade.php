@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Reports <small>Business scoped MIS and operational reports</small></h1>
</section>

<section class="content hm-reports-landing-page">
    @include('hotelmanagement::partials.nav')

    <div class="hm-report-grid hm-pos-report-grid">
        <a class="hm-report-card hm-pos-report-card hm-report-blue"
           href="{{ route('hotel-management.reports.analytics') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-line-chart"></i>
                </span>
                <strong>Management Analytics</strong>
            </div>
            <span class="hm-report-description">
                Executive KPI dashboard, department revenue, snapshots and export.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>

        <a class="hm-report-card hm-pos-report-card hm-report-green"
           href="{{ route('hotel-management.reports.occupancy') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-bed"></i>
                </span>
                <strong>Occupancy Report</strong>
            </div>
            <span class="hm-report-description">
                Rooms, available room nights, occupied room nights and occupancy percentage.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>

        <a class="hm-report-card hm-pos-report-card hm-report-amber"
           href="{{ route('hotel-management.reports.revenue') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-money"></i>
                </span>
                <strong>Revenue / ADR / RevPAR</strong>
            </div>
            <span class="hm-report-description">
                Room revenue, guest payments, balances, ADR and RevPAR.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>

        <a class="hm-report-card hm-pos-report-card hm-report-purple"
           href="{{ route('hotel-management.reports.reservation-register') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-calendar-check-o"></i>
                </span>
                <strong>Reservation Register</strong>
            </div>
            <span class="hm-report-description">
                Reservation list for the selected business, location and date range.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>

        <a class="hm-report-card hm-pos-report-card hm-report-cyan"
           href="{{ route('hotel-management.reports.checkin-checkout') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-exchange"></i>
                </span>
                <strong>Check-In / Check-Out</strong>
            </div>
            <span class="hm-report-description">
                Arrival and departure movement register for the selected period.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>

        <a class="hm-report-card hm-pos-report-card hm-report-red"
           href="{{ route('hotel-management.reports.housekeeping') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-magic"></i>
                </span>
                <strong>Housekeeping Report</strong>
            </div>
            <span class="hm-report-description">
                Cleaning tasks, room preparation and room-status records.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>

        <a class="hm-report-card hm-pos-report-card hm-report-indigo"
           href="{{ route('hotel-management.reports.guest-ledger') }}">
            <div class="hm-report-card-head">
                <span class="hm-report-icon" aria-hidden="true">
                    <i class="fa fa-book"></i>
                </span>
                <strong>Guest Ledger</strong>
            </div>
            <span class="hm-report-description">
                Guest folios and outstanding balances for the selected period.
            </span>
            <span class="hm-report-open">Open <i class="fa fa-angle-right"></i></span>
            <span class="hm-report-trend" aria-hidden="true"></span>
        </a>
    </div>
</section>
@endsection
