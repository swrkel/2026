@extends('beautysaloons::portal.layout')
@section('portal_title', 'My Beauty Dashboard')
@section('portal_content')
<div class="row bs-kpi-row">
@foreach($summary as $label => $value)
    <div class="col-md-3">
        <div class="bs-kpi-card">
            <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
            <strong>{{ $value }}</strong>
        </div>
    </div>
@endforeach
</div>
@endsection
