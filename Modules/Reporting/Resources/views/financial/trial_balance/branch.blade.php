@extends('layouts.app')

@section('title', __('Branch Trial Balance'))

@section('content')

@php
    $total_accounts = isset($accounts)
        ? $accounts->count()
        : 0;

    $total_debit = isset($total_debit)
        ? round($total_debit, 2)
        : 0;

    $total_credit = isset($total_credit)
        ? round($total_credit, 2)
        : 0;

    $difference = isset($difference)
        ? round($difference, 2)
        : 0;
@endphp

<section class="content-header">
    <h1>
        Branch Trial Balance
        <small>Branch Wise Debit & Credit Summary</small>
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-list-alt"></i>
                Branch Trial Balance Report
            </h3>
        </div>

        <div class="box-body">

            <form method="GET"
                  action="{{ route('reporting.branch.trial_balance') }}">

                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control select2"
                                    style="width: 100%;"
                                    required>

                                <option value="">Select Branch</option>

                                @foreach($locations as $location_id => $location_name)

                                    <option value="{{ $location_id }}"
                                        @if($selected_location_id == $location_id) selected @endif>

                                        {{ $location_name }} - {{ $location_id }}

                                    </option>

                                @endforeach

                            </select>

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>As At Date</label>

                            <input type="text"
                                   name="as_at_date"
                                   class="form-control datepicker"
                                   value="{{ $as_at_date ?? '' }}"
                                   placeholder="As At Date">

                        </div>
                    </div>

                    <div class="col-md-3">
                        <label>&nbsp;</label>

                        <button type="submit"
                                class="btn btn-primary btn-block">

                            <i class="fa fa-search"></i>
                            Generate Report

                        </button>
                    </div>

                </div>

            </form>

            <hr>

            <div class="row">

                <div class="col-md-3">

                    <div class="info-box">

                        <span class="info-box-icon bg-blue">
                            <i class="fa fa-book"></i>
                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">
                                Total Accounts
                            </span>

                            <span class="info-box-number">
                                {{ $total_accounts }}
                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="info-box">

                        <span class="info-box-icon bg-green">
                            <i class="fa fa-plus"></i>
                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">
                                Total Debit
                            </span>

                            <span class="info-box-number">
                                {{ number_format($total_debit, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="info-box">

                        <span class="info-box-icon bg-red">
                            <i class="fa fa-minus"></i>
                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">
                                Total Credit
                            </span>

                            <span class="info-box-number">
                                {{ number_format($total_credit, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="info-box">

                        <span class="info-box-icon bg-aqua">
                            <i class="fa fa-balance-scale"></i>
                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">
                                Difference
                            </span>

                            <span class="info-box-number">
                                {{ number_format($difference, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            @if(empty($selected_location_id))

                <div class="alert alert-info">

                    <i class="fa fa-info-circle"></i>

                    Please select a branch to generate the trial balance.

                </div>

            @endif

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead>

                        <tr class="bg-primary">

                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Account Type</th>
                            <th>Location</th>

                            <th class="text-right">
                                Debit
                            </th>

                            <th class="text-right">
                                Credit
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @if(
                            !empty($selected_location_id)
                            && isset($accounts)
                            && $accounts->count() > 0
                        )

                            @foreach($accounts as $account)

                                <tr>

                                    <td>
                                        {{ $account->account_number ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $account->name }}
                                    </td>

                                    <td>

                                        {{ optional($account->account_type)->name }}

                                    </td>

                                    <td>
                                        {{ $account->location_id }}
                                    </td>

                                    <td class="text-right">

                                        @if($account->debit > 0)

                                            {{ number_format($account->debit, 2) }}

                                        @else

                                            -

                                        @endif

                                    </td>

                                    <td class="text-right">

                                        @if($account->credit > 0)

                                            {{ number_format($account->credit, 2) }}

                                        @else

                                            -

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        @else

                            <tr>

                                <td colspan="6"
                                    class="text-center text-muted">

                                    No branch trial balance data available.

                                </td>

                            </tr>

                        @endif

                    </tbody>

                    <tfoot>

                        <tr class="bg-gray">

                            <th colspan="4"
                                class="text-right">

                                Totals

                            </th>

                            <th class="text-right">

                                {{ number_format($total_debit, 2) }}

                            </th>

                            <th class="text-right">

                                {{ number_format($total_credit, 2) }}

                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>

</section>

@endsection