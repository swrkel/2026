@extends('leadsnew::layouts.app')
@section('title', 'Leads-New Import / Export')
@section('leadsnew_subtitle', 'Move lead data in and out of the standalone Leads-New module using controlled import and export tools.')

@section('leadsnew_content')
    <div class="row">
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-upload text-primary"></i> Import Leads</h3><div class="ch-card-subtitle">Use the approved Excel template for structured lead imports.</div></div></div>
                <div class="ch-card-body">
                    <div class="empty-state">
                        <i class="fa fa-file-excel-o" style="font-size:34px;color:#16a34a;display:block;margin-bottom:10px;"></i>
                        <strong>Excel Import Workspace</strong>
                        <p class="help-block">Import validation and row mapping remain isolated inside Leads-New.</p>
                        <a href="{{ asset('files/import_leads_csv_template.xlsx') }}" class="btn btn-success" download><i class="fa fa-download"></i> Download Template</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-download text-primary"></i> Export Leads</h3><div class="ch-card-subtitle">Create a CSV file from the active tenant's lead records.</div></div></div>
                <div class="ch-card-body">
                    <div class="empty-state">
                        <i class="fa fa-table" style="font-size:34px;color:#2563eb;display:block;margin-bottom:10px;"></i>
                        <strong>CSV Export</strong>
                        <p class="help-block">Download lead data for reporting, backup or controlled external analysis.</p>
                        <a class="btn btn-primary" href="{{ url('/leads-new/export/csv') }}"><i class="fa fa-file-text-o"></i> Export CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
