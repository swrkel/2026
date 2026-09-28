@extends('layouts.app')
@section('title', 'Communication Providers')
@section('content')
<section class="content-header">
    <h1>Communication Providers <small>Enterprise Provider Framework</small></h1>
</section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $providers->total() }}</h3><p>Total Providers</p></div><div class="icon"><i class="fa fa-plug"></i></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="small-box bg-green"><div class="inner"><h3>{{ $providers->where('is_active', true)->count() }}</h3><p>Active on this page</p></div><div class="icon"><i class="fa fa-check"></i></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="small-box bg-yellow"><div class="inner"><h3>{{ count($driverOptions ?? []) }}</h3><p>Channels</p></div><div class="icon"><i class="fa fa-random"></i></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="small-box bg-red"><div class="inner"><h3>{{ $providers->where('health_status', 'failed')->count() }}</h3><p>Failed Health</p></div><div class="icon"><i class="fa fa-exclamation-triangle"></i></div></div></div>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Provider Register</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('communicationhub.providers.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Provider</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Channel</th>
                    <th>Driver</th>
                    <th>Country</th>
                    <th>Priority</th>
                    <th>Cost</th>
                    <th>Health</th>
                    <th>Last Used</th>
                    <th>Status</th>
                    <th style="width:180px;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($providers as $provider)
                <tr>
                    <td><strong>{{ $provider->name }}</strong><br><small class="text-muted">{{ $provider->last_response_code }}</small></td>
                    <td><span class="label label-info">{{ strtoupper($provider->channel) }}</span></td>
                    <td>{{ $provider->driver }}</td>
                    <td>{{ $provider->country_code ?: 'Any' }}</td>
                    <td>{{ $provider->priority }}</td>
                    <td>{{ number_format((float) $provider->cost_per_message, 4) }}</td>
                    <td>
                        @php($health = $provider->health_status ?: 'unknown')
                        <span class="label label-{{ $health === 'healthy' ? 'success' : ($health === 'failed' ? 'danger' : 'default') }}">{{ ucfirst($health) }}</span>
                        @if($provider->average_response_ms)<br><small>{{ $provider->average_response_ms }} ms</small>@endif
                    </td>
                    <td>{{ optional($provider->last_used_at)->format('Y-m-d H:i') ?: '-' }}</td>
                    <td><span class="label label-{{ $provider->is_active ? 'success' : 'default' }}">{{ $provider->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <a href="{{ route('communicationhub.providers.edit',$provider) }}" class="btn btn-xs btn-info"><i class="fa fa-edit"></i> Edit</a>
                        <form method="POST" action="{{ route('communicationhub.providers.health',$provider) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-warning"><i class="fa fa-heartbeat"></i> Health</button></form>
                        <form method="POST" action="{{ route('communicationhub.providers.toggle',$provider) }}" style="display:inline;">@csrf<button class="btn btn-xs btn-default">{{ $provider->is_active ? 'Disable' : 'Enable' }}</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">No providers configured yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $providers->links() }}
    </div>
</div>
</section>
@endsection
