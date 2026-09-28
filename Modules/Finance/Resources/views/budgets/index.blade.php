@extends('layouts.app')

@section('title', 'Finance Budgets')

@section('content')

<section class="content-header">
    <h1>
        Finance Budgets
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Budget Filters
            </h3>

            <div class="box-tools pull-right">

                <a href="{{ route('finance.budgets.create') }}"
                   class="btn btn-primary btn-sm">

                    <i class="fa fa-plus"></i>
                    Create Budget

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

                            <label>Budget Year</label>

                            <input type="text"
                                   name="budget_year"
                                   class="form-control"
                                   value="{{ request()->budget_year }}"
                                   placeholder="Example: 2026">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Budget Type</label>

                            <select name="budget_type"
                                    class="form-control">

                                <option value="">
                                    All Types
                                </option>

                                <option value="annual" {{ request()->budget_type == 'annual' ? 'selected' : '' }}>
                                    Annual
                                </option>

                                <option value="monthly" {{ request()->budget_type == 'monthly' ? 'selected' : '' }}>
                                    Monthly
                                </option>

                                <option value="quarterly" {{ request()->budget_type == 'quarterly' ? 'selected' : '' }}>
                                    Quarterly
                                </option>

                                <option value="project" {{ request()->budget_type == 'project' ? 'selected' : '' }}>
                                    Project
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

                                <option value="draft" {{ request()->status == 'draft' ? 'selected' : '' }}>
                                    Draft
                                </option>

                                <option value="active" {{ request()->status == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="closed" {{ request()->status == 'closed' ? 'selected' : '' }}>
                                    Closed
                                </option>

                                <option value="cancelled" {{ request()->status == 'cancelled' ? 'selected' : '' }}>
                                    Cancelled
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-3">

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

    <div class="box box-success">

        <div class="box-header with-border">

            <h3 class="box-title">
                Budget Register
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>Budget Name</th>
                        <th>Branch</th>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Type</th>
                        <th>Department</th>
                        <th>Account</th>
                        <th class="text-right">Allocated</th>
                        <th class="text-right">Utilized</th>
                        <th class="text-right">Remaining</th>
                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($budgets as $budget)

                        <tr>

                            <td>
                                {{ $budget->budget_name }}
                            </td>

                            <td>
                                {{ optional($budget->location)->name ?? 'All Branches' }}
                            </td>

                            <td>
                                {{ $budget->budget_year }}
                            </td>

                            <td>
                                {{ $budget->budget_month }}
                            </td>

                            <td>
                                {{ ucfirst($budget->budget_type) }}
                            </td>

                            <td>
                                {{ $budget->department }}
                            </td>

                            <td>
                                {{ optional($budget->account)->name }}
                            </td>

                            <td class="text-right">
                                {{ number_format($budget->allocated_amount, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($budget->utilized_amount, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format($budget->remaining_amount, 2) }}
                            </td>

                            <td>
                                <span class="label label-{{ $budget->status == 'active' ? 'success' : 'default' }}">
                                    {{ ucfirst($budget->status) }}
                                </span>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <div class="text-center">

                {{ $budgets->links() }}

            </div>

        </div>

    </div>

</section>

@endsection