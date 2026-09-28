@extends('layouts.app')

@section('title', 'Finance Risk Alerts')

@section('content')

<section class="content-header">
    <h1>
        Finance Risk Alerts
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-red">
                    <i class="fa fa-warning"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Open Risk Alerts
                    </span>

                    <span class="info-box-number">
                        {{ number_format($total_open_alerts) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="info-box">
                <span class="info-box-icon bg-maroon">
                    <i class="fa fa-exclamation-circle"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">
                        Critical Alerts
                    </span>

                    <span class="info-box-number">
                        {{ number_format($critical_alerts) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                Risk Filters
            </h3>
        </div>

        <div class="box-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control">

                                <option value="all">
                                    All Branches
                                </option>

                                @foreach($locations as $id => $name)

                                    <option value="{{ $id }}"
                                        {{ request()->location_id == $id ? 'selected' : '' }}>

                                        {{ $name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Risk Level</label>

                            <select name="risk_level"
                                    class="form-control">

                                <option value="">
                                    All Levels
                                </option>

                                <option value="low" {{ request()->risk_level == 'low' ? 'selected' : '' }}>
                                    Low
                                </option>

                                <option value="medium" {{ request()->risk_level == 'medium' ? 'selected' : '' }}>
                                    Medium
                                </option>

                                <option value="high" {{ request()->risk_level == 'high' ? 'selected' : '' }}>
                                    High
                                </option>

                                <option value="critical" {{ request()->risk_level == 'critical' ? 'selected' : '' }}>
                                    Critical
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Status</label>

                            <select name="status"
                                    class="form-control">

                                <option value="">
                                    All Status
                                </option>

                                <option value="open" {{ request()->status == 'open' ? 'selected' : '' }}>
                                    Open
                                </option>

                                <option value="reviewing" {{ request()->status == 'reviewing' ? 'selected' : '' }}>
                                    Reviewing
                                </option>

                                <option value="cleared" {{ request()->status == 'cleared' ? 'selected' : '' }}>
                                    Cleared
                                </option>

                                <option value="escalated" {{ request()->status == 'escalated' ? 'selected' : '' }}>
                                    Escalated
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Module</label>

                            <input type="text"
                                   name="module"
                                   class="form-control"
                                   value="{{ request()->module }}">

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-2">

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="fa fa-search"></i>
                            Filter

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="box box-danger">

        <div class="box-header with-border">

            <h3 class="box-title">
                Risk Alert Register
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Alert No</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Risk Level</th>
                        <th>Module</th>
                        <th>Subject</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Created At</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($risk_alerts as $alert)

                        <tr>

                            <td>
                                {{ $alert->alert_no }}
                            </td>

                            <td>
                                {{ optional($alert->location)->name }}
                            </td>

                            <td>
                                {{ $alert->alert_type }}
                            </td>

                            <td>

                                <span class="label label-{{
                                    $alert->risk_level == 'critical'
                                        ? 'danger'
                                        : (
                                            $alert->risk_level == 'high'
                                            ? 'warning'
                                            : 'primary'
                                        )
                                }}">

                                    {{ ucfirst($alert->risk_level) }}

                                </span>

                            </td>

                            <td>
                                {{ $alert->module }}
                            </td>

                            <td>
                                {{ $alert->subject }}
                            </td>

                            <td class="text-right">
                                {{ number_format($alert->amount, 2) }}
                            </td>

                            <td>

                                <span class="label label-info">
                                    {{ ucfirst($alert->status) }}
                                </span>

                            </td>

                            <td>
                                {{ $alert->created_at }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <div class="text-center">

                {{ $risk_alerts->links() }}

            </div>

        </div>

    </div>

</section>

@endsection