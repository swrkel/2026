@extends('communicationhub::layout')
@section('communicationhub_title', 'CommunicationHub Release Notes')
@section('communicationhub_content')
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $version }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('communicationhub.certification.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="box-body">
        <ul class="list-group">
            @foreach($notes as $note)
                <li class="list-group-item"><i class="fa fa-check text-green"></i> {{ $note }}</li>
            @endforeach
        </ul>
        <div class="alert alert-info">
            CommunicationHub Enterprise v1.0 is intended to remain independent from the legacy SMS and Wallet modules. Future modules should integrate through the CommunicationHub service/API layer.
        </div>
    </div>
</div>
@endsection
