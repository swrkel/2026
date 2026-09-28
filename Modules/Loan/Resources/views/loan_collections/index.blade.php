@extends('layouts.app')

@section('title', 'Collections Command Center')

@section('content')

<style>

    .collection-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-top: 4px solid #3c8dbc;
        min-height: 135px;
    }

    .collection-card h2 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        color: #2c3e50;
    }

    .collection-card p {
        margin-top: 10px;
        font-size: 14px;
        color: #7f8c8d;
        font-weight: 600;
    }

    .collection-icon {
        font-size: 38px;
        opacity: 0.12;
        float: right;
        margin-top: -45px;
    }

    .governance-panel {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .section-title {
        font-size: 22px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .modern-table thead {
        background: #f4f6f9;
    }

    .modern-table th {
        border: none !important;
        font-size: 13px;
        text-transform: uppercase;
        color: #7f8c8d;
    }

    .modern-table td {
        vertical-align: middle !important;
        font-size: 14px;
    }

    .timeline-modern {
        list-style: none;
        padding: 0;
    }

    .timeline-modern li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 25px;
        border-left: 2px solid #d2d6de;
    }

    .timeline-modern li:before {
        content: '';
        width: 12px;
        height: 12px;
        background: #3c8dbc;
        border-radius: 50%;
        position: absolute;
        left: -7px;
        top: 5px;
    }

    .timeline-card {
        background: #f9fafc;
        padding: 15px;
        border-radius: 10px;
    }

    .risk-alert {
        border-radius: 10px;
        padding: 15px;
        background: #fff8e1;
        border-left: 4px solid #f39c12;
        margin-top: 20px;
    }

    .progress {
        height: 10px !important;
        border-radius: 20px;
        background: #ecf0f1;
    }

</style>

<section class="content-header">

    <h1>

        Enterprise Collections Command Center

        <small>
            Recovery Intelligence • Workforce Governance • Escalation Oversight
        </small>

    </h1>

</section>

<section class="content">

    <!-- ===================================================== -->
    <!-- Executive KPI Cards -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-lg-3 col-md-6">

            <div class="collection-card"
                 style="border-top-color:#00c0ef;">

                <h2>
                    {{ $total_collection_actions }}
                </h2>

                <p>
                    Collection Actions
                </p>

                <div class="collection-icon">
                    <i class="fa fa-phone"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="collection-card"
                 style="border-top-color:#00a65a;">

                <h2>
                    {{ $promise_to_pay_count }}
                </h2>

                <p>
                    Promise To Pay
                </p>

                <div class="collection-icon">
                    <i class="fa fa-calendar-check-o"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="collection-card"
                 style="border-top-color:#f39c12;">

                <h2>
                    {{ $follow_up_pending }}
                </h2>

                <p>
                    Pending Follow Ups
                </p>

                <div class="collection-icon">
                    <i class="fa fa-clock-o"></i>
                </div>

            </div>

        </div>

        <div class="col-lg-3 col-md-6">

            <div class="collection-card"
                 style="border-top-color:#dd4b39;">

                <h2>
                    {{ $field_visits }}
                </h2>

                <p>
                    Field Visits
                </p>

                <div class="collection-icon">
                    <i class="fa fa-map-marker"></i>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Governance Intelligence -->
    <!-- ===================================================== -->

    <div class="row">

        <div class="col-md-6">

            <div class="governance-panel">

                <div class="section-title">

                    <i class="fa fa-line-chart"></i>

                    Recovery Intelligence

                </div>

                <div class="progress-group">

                    <span>
                        Recovery Monitoring
                    </span>

                    <span class="pull-right">
                        Active
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-danger"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Escalation Engine
                    </span>

                    <span class="pull-right">
                        Operational
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-warning"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <br>

                <div class="progress-group">

                    <span>
                        Collections Governance
                    </span>

                    <span class="pull-right">
                        Enabled
                    </span>

                    <div class="progress">

                        <div class="progress-bar progress-bar-success"
                             style="width:100%">
                        </div>

                    </div>

                </div>

                <div class="risk-alert">

                    <strong>
                        Governance Intelligence:
                    </strong>

                    <br><br>

                    Monitor escalations,
                    collector productivity,
                    field recovery efficiency,
                    and enterprise collections risk exposure.

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- Workforce -->
        <!-- ===================================================== -->

        <div class="col-md-6">

            <div class="governance-panel">

                <div class="section-title">

                    <i class="fa fa-users"></i>

                    Workforce Recovery Optimization

                </div>

                <table class="table modern-table">

                    <tbody>

                        <tr>

                            <th width="50%">
                                Collections Workforce
                            </th>

                            <td>
                                Active
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Follow-Up Operations
                            </th>

                            <td>
                                Running
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Recovery Intelligence
                            </th>

                            <td>
                                Operational
                            </td>

                        </tr>

                        <tr>

                            <th>
                                Autonomous Monitoring
                            </th>

                            <td>
                                Enabled
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Collection Actions -->
    <!-- ===================================================== -->

    <div class="governance-panel">

        <div class="section-title">

            <i class="fa fa-database"></i>

            Enterprise Collection Actions

        </div>

        <!-- ============================================= -->
        <!-- Search & Filter -->
        <!-- ============================================= -->

        <div class="row"
             style="margin-bottom:20px;">

            <div class="col-md-4">

                <input type="text"
                       id="collectionSearch"
                       class="form-control"
                       placeholder="Search Collection Actions">

            </div>

            <div class="col-md-3">

                <select id="collectionTypeFilter"
                        class="form-control">

                    <option value="">
                        All Collection Types
                    </option>

                    <option value="call">
                        Call
                    </option>

                    <option value="sms">
                        SMS
                    </option>

                    <option value="email">
                        Email
                    </option>

                    <option value="field_visit">
                        Field Visit
                    </option>

                    <option value="legal_notice">
                        Legal Notice
                    </option>

                </select>

            </div>

        </div>

        <!-- ============================================= -->
        <!-- Table -->
        <!-- ============================================= -->

        <div class="table-responsive">

            <table class="table modern-table"
                   id="collectionTable">

                <thead>

                    <tr>

                        <th>Date</th>
                        <th>Loan No</th>
                        <th>Customer</th>
                        <th>Collection Type</th>
                        <th>Officer</th>
                        <th>Promise To Pay</th>
                        <th>Follow Up</th>
                        <th>Priority</th>
                        <th>Escalation</th>
                        <th width="120">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($collection_notes as $note)

                    <tr data-type="{{ $note->collection_type }}">

                        <td>
                            {{ $note->created_at }}
                        </td>

                        <td>
                            {{ optional($note->loan)->loan_no }}
                        </td>

                        <td>
                            {{ optional($note->customer)->name }}
                        </td>

                        <td>

                            <span class="label label-primary">

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $note->collection_type
                                        )
                                    )
                                }}

                            </span>

                        </td>

                        <td>

                            {{
                                optional($note->createdBy)
                                    ->username
                            }}

                        </td>

                        <td>

                            {{ $note->promise_to_pay_date ?? '-' }}

                        </td>

                        <td>

                            {{ $note->follow_up_date ?? '-' }}

                        </td>

                        <td>

                            @if(($note->priority_level ?? 'normal') == 'critical')

                                <span class="label label-danger">
                                    Critical
                                </span>

                            @elseif(($note->priority_level ?? 'normal') == 'high')

                                <span class="label label-warning">
                                    High
                                </span>

                            @else

                                <span class="label label-success">
                                    Normal
                                </span>

                            @endif

                        </td>

                        <td>

                            @if($note->escalation_required)

                                <span class="label label-danger">
                                    Escalated
                                </span>

                            @else

                                <span class="label label-success">
                                    Normal
                                </span>

                            @endif

                        </td>

                        <td>

                            <a href="/loan/loan-collections/{{ $note->id }}/show"
                               class="btn btn-xs btn-primary">

                                <i class="fa fa-eye"></i>

                                View

                            </a>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="10"
                            class="text-center">

                            No collection actions found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="text-right">

            {{ $collection_notes->links() }}

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- Timeline -->
    <!-- ===================================================== -->

    <div class="governance-panel">

        <div class="section-title">

            <i class="fa fa-history"></i>

            Recovery Action Timeline

        </div>

        <ul class="timeline-modern">

            @foreach($collection_notes->take(10) as $note)

            <li>

                <div class="timeline-card">

                    <strong>

                        {{
                            optional($note->createdBy)
                                ->username
                        }}

                    </strong>

                    performed

                    {{
                        ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $note->collection_type
                            )
                        )
                    }}

                    <br><br>

                    <small class="text-muted">

                        {{ $note->created_at }}

                    </small>

                    <hr>

                    {{ $note->note }}

                </div>

            </li>

            @endforeach

        </ul>

    </div>

</section>

@endsection

@section('javascript')

<script>

$(document).ready(function() {

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    $('#collectionSearch').on('keyup', function() {

        var value = $(this).val().toLowerCase();

        $('#collectionTable tbody tr').filter(function() {

            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) > -1
            );

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Collection Type Filter
    |--------------------------------------------------------------------------
    */

    $('#collectionTypeFilter').on('change', function() {

        var type = $(this).val();

        $('#collectionTable tbody tr').each(function() {

            if (
                type == '' ||
                $(this).data('type') == type
            ) {

                $(this).show();

            } else {

                $(this).hide();
            }

        });

    });

});

</script>

@endsection