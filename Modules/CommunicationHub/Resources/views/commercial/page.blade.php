@extends('communicationhub::layout')

@section('communicationhub_title', $title ?? 'Communication Hub')

@section('communicationhub_content')
<div class="ch-card">
    <div class="ch-card-header">
        <h3 class="ch-card-title"><i class="fa fa-sitemap"></i> {{ $title ?? 'Communication Hub' }}</h3>
        <span class="label label-primary">Module-owned UI</span>
    </div>
    <div class="ch-card-body">
        <p class="lead" style="margin-bottom:10px;">{{ $description ?? '' }}</p>
        <div class="alert alert-info" style="border-radius:10px;">
            <i class="fa fa-info-circle"></i> This page is loaded from <strong>Modules/CommunicationHub</strong>. It is ready for tester review and will be connected page-by-page to the commercial SMS selling workflow.
        </div>
        <div class="row">
            @foreach($features ?? [] as $feature)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="ch-kpi">
                        <div class="label-text"><i class="fa fa-check-circle"></i> Workflow</div>
                        <div class="value" style="font-size:17px;line-height:1.25;">{{ $feature }}</div>
                        <div class="hint">UI ready for operation wiring</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="ch-card-body" style="border-top:1px solid #edf2f7;background:#fbfdff;">
        <a href="{{ route('communicationhub.dashboard') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back to Communication Hub</a>
    </div>
</div>
@endsection
