@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<div class="bkg-row" style="margin-bottom:15px">
 <a class="bkg-btn" href="{{ route('banking.tester-ui.checks.index') }}">Checklist Results</a>
 <a class="bkg-btn" href="{{ route('banking.tester-ui.issues.index') }}">Issue Log</a>
 <a class="bkg-btn" href="{{ route('banking.tester-ui.route-health') }}">Route Health</a>
 <a class="bkg-btn" href="{{ route('banking.tester-ui.handover') }}">Tester Handover</a>
</div>
<div class="row">
@foreach($modules as $module)
    <div class="col-md-3 col-sm-6">
        <div class="bkg-card">
            <h4>{{ $module['name'] }}</h4>
            <p><span class="label label-info">{{ $module['status'] }}</span></p>
            <p>{{ count($module['items']) }} tester pages</p>
            <a class="btn btn-primary btn-sm" href="{{ url($module['url']) }}">Open</a>
        </div>
    </div>
@endforeach
</div>
@endsection
