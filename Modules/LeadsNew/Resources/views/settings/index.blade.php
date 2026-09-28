@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.settings'))
@section('leadsnew_subtitle', 'Configure lead numbering and operational defaults for the active business.')

@section('leadsnew_content')
    <div class="row">
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header">
                    <div>
                        <h3 class="ch-card-title"><i class="fa fa-sort-numeric-asc text-primary"></i> Numbering</h3>
                        <div class="ch-card-subtitle">Control the automatic lead number format.</div>
                    </div>
                </div>
                <div class="ch-card-body">
                    <form method="post" action="{{ url('/leads-new/settings/numbering') }}">@csrf
                        <div class="form-group">
                            <label>Prefix</label>
                            <input name="prefix" class="form-control" value="{{ $numbering['prefix'] ?? 'LN' }}" placeholder="LN">
                        </div>
                        <div class="form-group">
                            <label>Next Number</label>
                            <input name="next_no" type="number" min="1" class="form-control" value="{{ $numbering['next_no'] ?? 1 }}">
                        </div>
                        <div class="text-right"><button class="btn btn-primary"><i class="fa fa-save"></i> Save Numbering</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="ch-card">
                <div class="ch-card-header">
                    <div>
                        <h3 class="ch-card-title"><i class="fa fa-sliders text-primary"></i> Operational Defaults</h3>
                        <div class="ch-card-subtitle">Set the default status and source for new leads.</div>
                    </div>
                </div>
                <div class="ch-card-body">
                    <form method="post" action="{{ url('/leads-new/settings/defaults') }}">@csrf
                        <div class="form-group">
                            <label>Status</label>
                            <input name="status" class="form-control" value="{{ $defaults['status'] ?? 'New' }}" placeholder="New">
                        </div>
                        <div class="form-group">
                            <label>Source</label>
                            <input name="source" class="form-control" value="{{ $defaults['source'] ?? 'Direct' }}" placeholder="Direct">
                        </div>
                        <div class="text-right"><button class="btn btn-primary"><i class="fa fa-save"></i> Save Defaults</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
