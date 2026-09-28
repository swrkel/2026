@extends('layouts.app')

@section('title', 'Finance Approval Workflows')

@section('content')

<section class="content-header">
    <h1>
        Finance Approval Workflows
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Workflow Filters
            </h3>

            <div class="box-tools pull-right">

                <a href="{{ route('finance.approvals.create') }}"
                   class="btn btn-primary btn-sm">

                    <i class="fa fa-plus"></i>
                    Add Workflow

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
                                   value="{{ request()->module }}"
                                   placeholder="Example: Journals">

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

                                <option value="active"
                                    {{ request()->status == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="inactive"
                                    {{ request()->status == 'inactive' ? 'selected' : '' }}>
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

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
                Approval Workflow Register
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Workflow</th>
                        <th>Branch</th>
                        <th>Module</th>
                        <th>Approval Type</th>
                        <th>Amount Range</th>
                        <th>Levels</th>
                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($workflows as $workflow)

                        <tr>

                            <td>
                                {{ $workflow->workflow_name }}
                            </td>

                            <td>
                                {{ optional($workflow->location)->name ?? 'All Branches' }}
                            </td>

                            <td>
                                {{ $workflow->module }}
                            </td>

                            <td>
                                {{ $workflow->approval_type }}
                            </td>

                            <td>
                                {{ number_format($workflow->minimum_amount, 2) }}
                                -
                                {{ number_format($workflow->maximum_amount, 2) }}
                            </td>

                            <td>
                                {{ $workflow->approval_levels }}
                            </td>

                            <td>
                                <span class="label label-{{ $workflow->status == 'active' ? 'success' : 'default' }}">
                                    {{ ucfirst($workflow->status) }}
                                </span>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <div class="text-center">
                {{ $workflows->links() }}
            </div>

        </div>

    </div>

</section>

@endsection