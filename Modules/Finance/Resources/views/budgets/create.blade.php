@extends('layouts.app')

@section('title', 'Create Finance Budget')

@section('content')

<section class="content-header">
    <h1>
        Create Finance Budget
    </h1>
</section>

<section class="content">

    <form method="POST"
          action="{{ route('finance.budgets.store') }}">

        @csrf

        <div class="box box-primary">

            <div class="box-header with-border">

                <h3 class="box-title">
                    Budget Details
                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Budget Name *</label>

                            <input type="text"
                                   name="budget_name"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control">

                                <option value="">
                                    All Branches
                                </option>

                                @foreach($locations as $id => $name)

                                    <option value="{{ $id }}">
                                        {{ $name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Budget Year *</label>

                            <input type="text"
                                   name="budget_year"
                                   class="form-control"
                                   value="{{ date('Y') }}"
                                   required>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Budget Month</label>

                            <input type="text"
                                   name="budget_month"
                                   class="form-control"
                                   placeholder="Example: May">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Budget Type *</label>

                            <select name="budget_type"
                                    class="form-control"
                                    required>

                                <option value="monthly">Monthly</option>
                                <option value="annual">Annual</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="project">Project</option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Department</label>

                            <input type="text"
                                   name="department"
                                   class="form-control"
                                   placeholder="Example: Operations">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Account</label>

                            <select name="account_id"
                                    class="form-control">

                                <option value="">
                                    Select Account
                                </option>

                                @foreach($accounts as $id => $name)

                                    <option value="{{ $id }}">
                                        {{ $name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Allocated Amount *</label>

                            <input type="number"
                                   step="0.01"
                                   name="allocated_amount"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Utilized Amount</label>

                            <input type="number"
                                   step="0.01"
                                   name="utilized_amount"
                                   class="form-control"
                                   value="0">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Alert Threshold %</label>

                            <input type="number"
                                   step="0.01"
                                   name="alert_threshold"
                                   class="form-control"
                                   value="80">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Status</label>

                            <select name="status"
                                    class="form-control">

                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>

            <div class="box-footer">

                <button type="submit"
                        class="btn btn-primary">

                    <i class="fa fa-save"></i>
                    Save Budget

                </button>

                <a href="{{ route('finance.budgets.index') }}"
                   class="btn btn-default">

                    Cancel

                </a>

            </div>

        </div>

    </form>

</section>

@endsection