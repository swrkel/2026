@extends('layouts.app')

@section('title', 'Finance Escalations')

@section('content')

<section class="content-header">
    <h1>
        Finance Escalations
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Escalation Filters
            </h3>

            <div class="box-tools pull-right">

                <a href="{{ route('finance.escalations.create') }}"
                   class="btn btn-primary btn-sm">

                    <i class="fa fa-plus"></i>
                    Create Escalation

                </a>

            </div>

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

                            <label>Module</label>

                            <input type="text"
                                   name="module"
                                   class="form-control"
                                   value="{{ request()->module }}">

                        </div>

                    </div>

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>Severity</label>

                            <select name="severity"
                                    class="form-control">

                                <option value="">
                                    All
                                </option>

                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>Status</label>

                            <select name="status"
                                    class="form-control">

                                <option value="">
                                    All
                                </option>

                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>&nbsp;</label>

                            <button type="submit"
                                    class="btn btn-primary btn-block">

                                <i class="fa fa-search"></i>
                                Filter

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="box box-danger">

        <div class="box-header with-border">

            <h3 class="box-title">
                Escalation Register
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>No</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Module</th>
                        <th>Severity</th>
                        <th>Subject</th>
                        <th>Assigned To</th>
                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($escalations as $escalation)

                        <tr>

                            <td>
                                {{ $escalation->escalation_no }}
                            </td>

                            <td>
                                {{ $escalation->created_at }}
                            </td>

                            <td>
                                {{ optional($escalation->location)->name }}
                            </td>

                            <td>
                                {{ $escalation->module }}
                            </td>

                            <td>

                                <span class="label label-{{ $escalation->severity == 'critical' ? 'danger' : ($escalation->severity == 'high' ? 'warning' : 'primary') }}">

                                    {{ ucfirst($escalation->severity) }}

                                </span>

                            </td>

                            <td>
                                {{ $escalation->subject }}
                            </td>

                            <td>
                                {{ optional($escalation->assignedUser)->username }}
                            </td>

                            <td>

                                <span class="label label-info">

                                    {{ ucfirst(str_replace('_', ' ', $escalation->status)) }}

                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <div class="text-center">

                {{ $escalations->links() }}

            </div>

        </div>

    </div>

</section>

@endsection