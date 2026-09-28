@extends('layouts.app')

@section('title', __('Compliance Reports'))

@section('content')

<section class="content-header">
    <h1>
        Compliance Reports
        <small>AML, KYC, Regulatory & Risk Monitoring</small>
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red">
                    <i class="fa fa-shield"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">AML Alerts</span>
                    <span class="info-box-number">0</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow">
                    <i class="fa fa-user-secret"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">High Risk Customers</span>
                    <span class="info-box-number">0</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua">
                    <i class="fa fa-id-card"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Pending KYC</span>
                    <span class="info-box-number">0</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green">
                    <i class="fa fa-check-circle"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Compliance Score</span>
                    <span class="info-box-number">0%</span>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-danger">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-filter"></i>
                Compliance Report Filters
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
                        <label>Risk Category</label>

                        <select class="form-control select2" style="width: 100%;">
                            <option value="">All Categories</option>
                            <option value="low">Low Risk</option>
                            <option value="medium">Medium Risk</option>
                            <option value="high">High Risk</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>From Date</label>

                        <input type="text"
                               class="form-control datepicker"
                               placeholder="From Date">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>To Date</label>

                        <input type="text"
                               class="form-control datepicker"
                               placeholder="To Date">
                    </div>
                </div>

                <div class="col-md-2">
                    <label>&nbsp;</label>

                    <button type="button"
                            class="btn btn-danger btn-block">
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
                Compliance Monitoring Summary
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr class="bg-primary">
                        <th>Branch</th>
                        <th>Customer</th>
                        <th>Risk Level</th>
                        <th>KYC Status</th>
                        <th>AML Status</th>
                        <th>Compliance Status</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td colspan="6"
                            class="text-center text-muted">
                            No compliance report data available yet.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection