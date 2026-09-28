@extends('layouts.app')

@section('title', __('Branch Balance Sheet'))

@section('content')

@php

    $total_assets = isset($total_assets)
        ? round($total_assets, 2)
        : 0;

    $total_liabilities = isset($total_liabilities)
        ? round($total_liabilities, 2)
        : 0;

    $total_equity = isset($total_equity)
        ? round($total_equity, 2)
        : 0;

@endphp

<section class="content-header">

    <h1>
        Branch Balance Sheet
        <small>Branch Wise Assets, Liabilities & Equity</small>
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">

                <i class="fa fa-balance-scale"></i>

                Branch Balance Sheet Report

            </h3>

        </div>

        <div class="box-body">

            <form method="GET"
                  action="{{ route('reporting.branch.balance_sheet') }}">

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

                <div class="col-md-4">

                    <div class="info-box">

                        <span class="info-box-icon bg-aqua">

                            <i class="fa fa-bank"></i>

                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">

                                Total Assets

                            </span>

                            <span class="info-box-number">

                                {{ number_format($total_assets, 2) }}

                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="info-box">

                        <span class="info-box-icon bg-red">

                            <i class="fa fa-credit-card"></i>

                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">

                                Total Liabilities

                            </span>

                            <span class="info-box-number">

                                {{ number_format($total_liabilities, 2) }}

                            </span>

                        </div>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="info-box">

                        <span class="info-box-icon bg-green">

                            <i class="fa fa-line-chart"></i>

                        </span>

                        <div class="info-box-content">

                            <span class="info-box-text">

                                Total Equity

                            </span>

                            <span class="info-box-number">

                                {{ number_format($total_equity, 2) }}

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            @if(empty($selected_location_id))

                <div class="alert alert-info">

                    <i class="fa fa-info-circle"></i>

                    Please select a branch to generate the balance sheet.

                </div>

            @endif

            <div class="row">

                <div class="col-md-6">

                    <div class="box box-info">

                        <div class="box-header with-border">

                            <h3 class="box-title">

                                Assets

                            </h3>

                        </div>

                        <div class="box-body table-responsive no-padding">

                            <table class="table table-bordered table-striped">

                                <thead>

                                    <tr class="bg-info">

                                        <th>Code</th>
                                        <th>Account</th>
                                        <th>Location</th>

                                        <th class="text-right">
                                            Balance
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @if(
                                        !empty($selected_location_id)
                                        && isset($asset_accounts)
                                        && $asset_accounts->count() > 0
                                    )

                                        @foreach($asset_accounts as $account)

                                            <tr>

                                                <td>
                                                    {{ $account->account_number ?? '-' }}
                                                </td>

                                                <td>
                                                    {{ $account->name }}
                                                </td>

                                                <td>
                                                    {{ $account->location_id }}
                                                </td>

                                                <td class="text-right">

                                                    {{ number_format($account->balance, 2) }}

                                                </td>

                                            </tr>

                                        @endforeach

                                    @else

                                        <tr>

                                            <td colspan="4"
                                                class="text-center text-muted">

                                                No asset data available.

                                            </td>

                                        </tr>

                                    @endif

                                </tbody>

                                <tfoot>

                                    <tr>

                                        <th colspan="3"
                                            class="text-right">

                                            Total Assets

                                        </th>

                                        <th class="text-right">

                                            {{ number_format($total_assets, 2) }}

                                        </th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="box box-danger">

                        <div class="box-header with-border">

                            <h3 class="box-title">

                                Liabilities & Equity

                            </h3>

                        </div>

                        <div class="box-body table-responsive no-padding">

                            <table class="table table-bordered table-striped">

                                <thead>

                                    <tr class="bg-red">

                                        <th>Code</th>
                                        <th>Account</th>
                                        <th>Location</th>

                                        <th class="text-right">
                                            Balance
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @if(
                                        !empty($selected_location_id)
                                        && (
                                            (isset($liability_accounts)
                                            && $liability_accounts->count() > 0)

                                            ||

                                            (isset($equity_accounts)
                                            && $equity_accounts->count() > 0)
                                        )
                                    )

                                        @foreach($liability_accounts as $account)

                                            <tr>

                                                <td>
                                                    {{ $account->account_number ?? '-' }}
                                                </td>

                                                <td>
                                                    {{ $account->name }}
                                                </td>

                                                <td>
                                                    {{ $account->location_id }}
                                                </td>

                                                <td class="text-right">

                                                    {{ number_format(abs($account->balance), 2) }}

                                                </td>

                                            </tr>

                                        @endforeach

                                        @foreach($equity_accounts as $account)

                                            <tr>

                                                <td>
                                                    {{ $account->account_number ?? '-' }}
                                                </td>

                                                <td>
                                                    {{ $account->name }}
                                                </td>

                                                <td>
                                                    {{ $account->location_id }}
                                                </td>

                                                <td class="text-right">

                                                    {{ number_format(abs($account->balance), 2) }}

                                                </td>

                                            </tr>

                                        @endforeach

                                    @else

                                        <tr>

                                            <td colspan="4"
                                                class="text-center text-muted">

                                                No liability or equity data available.

                                            </td>

                                        </tr>

                                    @endif

                                </tbody>

                                <tfoot>

                                    <tr>

                                        <th colspan="3"
                                            class="text-right">

                                            Total Liabilities & Equity

                                        </th>

                                        <th class="text-right">

                                            {{ number_format($total_liabilities + $total_equity, 2) }}

                                        </th>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection