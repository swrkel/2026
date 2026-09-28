@extends('communicationhub::layout')
@section('communicationhub_title', 'CommunicationHub Integration Marketplace')
@section('communicationhub_content')
<div class="row">
    @foreach([
        ['Installed Packages', $installed, 'fa-download'],
        ['Enabled Providers', $enabled, 'fa-check-circle'],
        ['Available Packages', $available, 'fa-shopping-bag'],
        ['Updates Available', $updates, 'fa-refresh'],
    ] as $card)
        <div class="col-md-3 col-sm-6">
            <div class="box box-primary">
                <div class="box-body text-center">
                    <i class="fa {{ $card[2] }} fa-2x"></i>
                    <h3>{{ number_format($card[1]) }}</h3>
                    <strong>{{ $card[0] }}</strong>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Packages by Channel</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Channel</th><th class="text-right">Packages</th></tr></thead>
                    <tbody>
                    @forelse($channels as $channel => $total)
                        <tr><td>{{ ucfirst($channel) }}</td><td class="text-right">{{ number_format($total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">No packages found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Marketplace Notes</h3></div>
            <div class="box-body">
                <p><strong>Purpose:</strong> install, enable, disable and test communication provider packages without changing the CommunicationHub core.</p>
                <p><strong>Standalone rule:</strong> this marketplace does not depend on the old SMS module, old Wallet module, My Health, Finance, CRM, or any business module.</p>
                <p><strong>Production use:</strong> configure real provider credentials only after the sandbox test passes.</p>
            </div>
        </div>
    </div>
</div>

<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">Provider Packages</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-hover table-striped">
            <thead>
                <tr>
                    <th>Package</th>
                    <th>Channel</th>
                    <th>Provider Key</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Compatibility</th>
                    <th>Cost</th>
                    <th>Last Test</th>
                    <th style="width: 260px;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($packages as $package)
                <tr>
                    <td><strong>{{ $package->name }}</strong><br><small class="text-muted">{{ $package->package_code }}</small></td>
                    <td><span class="label label-info">{{ strtoupper($package->channel) }}</span></td>
                    <td>{{ $package->provider_key ?: '-' }}</td>
                    <td>{{ $package->version }} @if($package->available_version && $package->available_version !== $package->version)<br><small class="text-warning">Update: {{ $package->available_version }}</small>@endif</td>
                    <td>
                        @if($package->is_enabled)
                            <span class="label label-success">Enabled</span>
                        @elseif($package->is_installed)
                            <span class="label label-primary">Installed</span>
                        @else
                            <span class="label label-default">Available</span>
                        @endif
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', $package->compatibility_status)) }}</td>
                    <td>{{ $package->cost_per_message !== null ? number_format($package->cost_per_message, 4) : '-' }}</td>
                    <td>{{ optional($package->last_tested_at)->format('Y-m-d H:i') ?: '-' }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            @if(!$package->is_installed)
                                <form method="POST" action="{{ route('communicationhub.marketplace.install', $package) }}" style="display:inline">@csrf<button class="btn btn-primary">Install</button></form>
                            @endif
                            @if($package->is_enabled)
                                <form method="POST" action="{{ route('communicationhub.marketplace.disable', $package) }}" style="display:inline">@csrf<button class="btn btn-warning">Disable</button></form>
                            @else
                                <form method="POST" action="{{ route('communicationhub.marketplace.enable', $package) }}" style="display:inline">@csrf<button class="btn btn-success">Enable</button></form>
                            @endif
                            <form method="POST" action="{{ route('communicationhub.marketplace.sandbox', $package) }}" style="display:inline">@csrf<button class="btn btn-info">Sandbox Test</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">No marketplace packages found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
