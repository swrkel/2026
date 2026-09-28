@extends('layouts.app')

@section('title', __('Recovery Reports'))

@section('content')

<section class="content-header">
    <h1>
        Recovery Reports
        <small>Loan Recovery Performance & Collection Intelligence</small>
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green">
                    <i class="fa fa-money"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Total Collections</span>
                    <span class="info-box-number">0.00</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red">
                    <i class="fa fa-warning"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Overdue Amount</span>
                    <span class="info-box-number">0.00</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow">
                    <i class="fa fa-users"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Recovery Officers</span>
                    <span class="info-box-number">0</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua">
                    <i class="fa fa-line-chart"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Recovery Rate</span>
                    <span class="info-box-number">0%</span>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-success">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-filter"></i>
                Recovery Report Filters
            </h3>
        </div>

        <div class="box-body">

            <div class="row">

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Branch</label>
                        <select class="form-control select2" style="width: 100%;">
                            <option value="">All Branches</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Recovery Officer</label>
                        <select class="form-control select2" style="width: 100%;">
                            <option value="">All Officers</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="text" class="form-control datepicker" placeholder="From Date">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="text" class="form-control datepicker" placeholder="To Date">
                    </div>
                </div>

                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-success btn-block">
                        <i class="fa fa-search"></i>
                        Generate
                    </button>
                </div>

            </div>

        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-table"></i>
                Recovery Summary
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-primary">
                        <th>Branch</th>
                        <th>Officer</th>
                        <th class="text-right">Assigned Amount</th>
                        <th class="text-right">Collected Amount</th>
                        <th class="text-right">Overdue Amount</th>
                        <th class="text-right">Recovery %</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            No recovery report data available yet.
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    </div>

</section>

@endsection