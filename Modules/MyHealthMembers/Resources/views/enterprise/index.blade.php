@extends('layouts.app')
@section('title', 'My Health Enterprise Release')
@section('content')
<section class="content-header">
    <h1>My Health <small>Enterprise Release</small></h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">{{ $summary['release'] }}</h3></div>
        <div class="box-body">
            <p><strong>Status:</strong> {{ $summary['status'] }}</p>
            <p><strong>Focus:</strong> {{ $summary['focus'] }}</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Area</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($modules as $area => $status)
                        <tr><td>{{ $area }}</td><td><span class="label label-success">{{ $status }}</span></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <a href="{{ route('myhealth.enterprise.e2e') }}" class="btn btn-primary">Open E2E Checklist</a>
            <a href="{{ route('myhealth.enterprise.security') }}" class="btn btn-default">Security Review</a>
            <a href="{{ route('myhealth.enterprise.performance') }}" class="btn btn-default">Performance Review</a>
        </div>
    </div>
</section>
@endsection
