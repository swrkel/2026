@extends('layouts.app')

@section('title', 'Finance Audit Logs')

@section('content')

<section class="content-header">
    <h1>
        Finance Audit Logs
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Audit Filters
            </h3>

        </div>

        <div class="box-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-2">

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

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>Module</label>

                            <select name="module"
                                    class="form-control">

                                <option value="">
                                    All Modules
                                </option>

                                @foreach($modules as $module)

                                    <option value="{{ $module }}"
                                        {{ request()->module == $module ? 'selected' : '' }}>

                                        {{ $module }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>Action</label>

                            <select name="action"
                                    class="form-control">

                                <option value="">
                                    All Actions
                                </option>

                                @foreach($actions as $action)

                                    <option value="{{ $action }}"
                                        {{ request()->action == $action ? 'selected' : '' }}>

                                        {{ $action }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>From Date</label>

                            <input type="date"
                                   name="from_date"
                                   class="form-control"
                                   value="{{ request()->from_date }}">

                        </div>

                    </div>

                    <div class="col-md-2">

                        <div class="form-group">

                            <label>To Date</label>

                            <input type="date"
                                   name="to_date"
                                   class="form-control"
                                   value="{{ request()->to_date }}">

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

    <div class="box box-success">

        <div class="box-header with-border">

            <h3 class="box-title">
                Finance Audit Trail
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Branch</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($audit_logs as $log)

                        <tr>

                            <td>

                                {{ $log->created_at }}

                            </td>

                            <td>

                                {{ optional($log->user)->username }}

                            </td>

                            <td>

                                {{ optional($log->location)->name }}

                            </td>

                            <td>

                                {{ $log->module }}

                            </td>

                            <td>

                                {{ $log->action }}

                            </td>

                            <td>

                                {{ $log->description }}

                            </td>

                            <td>

                                {{ $log->ip_address }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <div class="text-center">

                {{ $audit_logs->links() }}

            </div>

        </div>

    </div>

</section>

@endsection