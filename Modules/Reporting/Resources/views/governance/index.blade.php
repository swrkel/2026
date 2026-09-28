@extends('layouts.app')

@section('title', __('Governance Reports'))

@section('content')

<section class="content-header">
    <h1>
        Governance Reports
        <small>Enterprise Governance, Audit & Strategic Oversight</small>
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua">
                    <i class="fa fa-university"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Governance Policies</span>
                    <span class="info-box-number">0</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red">
                    <i class="fa fa-warning"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Audit Findings</span>
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
                    <span class="info-box-text">Compliance Passed</span>
                    <span class="info-box-number">0%</span>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow">
                    <i class="fa fa-building"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Branches Audited</span>
                    <span class="info-box-number">0</span>
                </div>
            </div>
        </div>

    </div>

    <div class="box box-warning">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-filter"></i>
                Governance Report Filters
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
                        <label>Governance Type</label>

                        <select class="form-control select2" style="width: 100%;">
                            <option value="">All Types</option>
                            <option value="audit">Audit</option>
                            <option value="policy">Policy</option>
                            <option value="risk">Risk</option>
                            <option value="compliance">Compliance</option>
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
                            class="btn btn-warning btn-block">
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
                Governance Monitoring Summary
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr class="bg-primary">
                        <th>Branch</th>
                        <th>Governance Area</th>
                        <th>Audit Status</th>
                        <th>Risk Rating</th>
                        <th>Compliance Status</th>
                        <th>Last Reviewed</th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td colspan="6"
                            class="text-center text-muted">
                            No governance report data available yet.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

    <div class="box box-info">

        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-info-circle"></i>
                Enterprise Governance Notes
            </h3>
        </div>

        <div class="box-body">

            <ul>
                <li>Supports multi-tenant governance reporting</li>
                <li>Supports branch wise audit monitoring</li>
                <li>Supports consolidated governance reporting</li>
                <li>Integrated with enterprise compliance engine</li>
                <li>Integrated with recovery escalation governance</li>
                <li>Prepared for future AI-based governance analytics</li>
            </ul>

        </div>

    </div>

</section>

@endsection