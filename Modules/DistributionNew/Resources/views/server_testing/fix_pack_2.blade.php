@extends('layouts.app')

@section('title', __('distributionnew::lang.server_testing_fix_pack_2'))

@section('content')
<section class="content-header disnew-stage27-header">
    <h1>Distribution New - Server Testing Fix Pack 2</h1>
</section>

<section class="content disnew-stage27-page">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box disnew-pos-card">
                <span class="info-box-icon"><i class="fa fa-database"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Tenant DB</span>
                    <span class="info-box-number">{{ $results['database'] ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box disnew-pos-card">
                <span class="info-box-icon"><i class="fa fa-building"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Business</span>
                    <span class="info-box-number">{{ $businessId }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid disnew-pos-box">
        <div class="box-header with-border">
            <h3 class="box-title">Required Tables</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-stage27-table">
                <thead><tr><th>Table</th><th>Status</th></tr></thead>
                <tbody>
                @foreach(($results['tables'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['table'] }}</td>
                        <td>{!! $row['exists'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-solid disnew-pos-box">
        <div class="box-header with-border">
            <h3 class="box-title">Required Routes</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped disnew-stage27-table">
                <thead><tr><th>Route</th><th>Status</th></tr></thead>
                <tbody>
                @foreach(($results['routes'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['route'] }}</td>
                        <td>{!! $row['registered'] ? '<span class="label label-success">OK</span>' : '<span class="label label-warning">Check Route Load</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
