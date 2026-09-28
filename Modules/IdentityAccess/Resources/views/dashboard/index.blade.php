@extends('layouts.app')
@section('title', 'Identity Access')
@section('content')
<section class="content-header"><h1>Identity Access <small>Security Platform</small></h1></section>
<section class="content">
    <div class="row">
        @foreach($stats as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="small-box bg-aqua">
                    <div class="inner"><h3>{{ $value }}</h3><p>{{ ucwords(str_replace('_', ' ', $label)) }}</p></div>
                    <div class="icon"><i class="fa fa-shield"></i></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Foundation</h3></div>
        <div class="box-body">
            <p>Identity Access is the standalone platform for authentication, authorization, MFA, sessions, trusted devices and portal access.</p>
        </div>
    </div>
</section>
@endsection
