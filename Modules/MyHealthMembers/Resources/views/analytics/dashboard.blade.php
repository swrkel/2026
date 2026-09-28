@extends('layouts.app')
@section('title', 'My Health Analytics')
@section('content')
<section class="content-header">
    <h1>My Health <small>Advanced Analytics & Executive Dashboard</small></h1>
</section>
<section class="content">
    @include('myhealthmembers::analytics._filters')
    @include('myhealthmembers::analytics._kpi_cards')

    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">Clinical Intelligence</h3></div>
                <div class="box-body">
                    <h4>Top Diagnoses</h4>
                    @include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['clinical']['top_diagnoses'] ?? []])
                    <h4>Most Prescribed Medicines</h4>
                    @include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['clinical']['top_medicines'] ?? []])
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title">Operational Analytics</h3></div>
                <div class="box-body">
                    <h4>Laboratory Status</h4>
                    @include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['operations']['lab_status'] ?? []])
                    <h4>Radiology Status</h4>
                    @include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['operations']['radiology_status'] ?? []])
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">Financial Analytics</h3></div>
                <div class="box-body">
                    <table class="table table-bordered table-striped">
                        <tr><th>Invoice Total</th><td class="text-right">{{ number_format($analytics['financial']['invoice_total'] ?? 0, 2) }}</td></tr>
                        <tr><th>Payment Total</th><td class="text-right">{{ number_format($analytics['financial']['payment_total'] ?? 0, 2) }}</td></tr>
                        <tr><th>Claim Settlements</th><td class="text-right">{{ number_format($analytics['financial']['claim_total'] ?? 0, 2) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Public Health Analytics</h3></div>
                <div class="box-body">
                    <h4>Gender Distribution</h4>
                    @include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['public_health']['gender_distribution'] ?? []])
                    <h4>Blood Groups</h4>
                    @include('myhealthmembers::analytics._summary_table', ['rows' => $analytics['public_health']['blood_groups'] ?? []])
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
