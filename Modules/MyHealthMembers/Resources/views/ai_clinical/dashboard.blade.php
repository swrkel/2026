@extends('layouts.app')
@section('title', 'AI Clinical Assistant')
@section('content')
<section class="content-header">
    <h1>My Health <small>AI Clinical Assistant</small></h1>
</section>
<section class="content">
    @include('myhealthmembers::ai_clinical._filters')
    @include('myhealthmembers::ai_clinical._kpi_cards')

    <div class="row">
        <div class="col-md-8">
            <div class="box box-danger">
                <div class="box-header with-border"><h3 class="box-title">Priority Clinical Alerts</h3></div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-striped">
                        <thead><tr><th>Date</th><th>Member</th><th>Type</th><th>Severity</th><th>Alert</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse($data['alerts'] ?? [] as $alert)
                                <tr>
                                    <td>{{ !empty($alert->created_at) ? @format_datetime($alert->created_at) : '' }}</td>
                                    <td>{{ $alert->member_code ?? '' }}<br><small>{{ $alert->member_name ?? '' }}</small></td>
                                    <td>{{ ucwords(str_replace('_', ' ', $alert->alert_type ?? '')) }}</td>
                                    <td><span class="label label-{{ ($alert->severity ?? '') == 'critical' ? 'danger' : (($alert->severity ?? '') == 'high' ? 'warning' : 'info') }}">{{ ucfirst($alert->severity ?? '') }}</span></td>
                                    <td><strong>{{ $alert->title ?? '' }}</strong><br><small>{{ $alert->message ?? '' }}</small></td>
                                    <td>{{ ucfirst($alert->status ?? '') }}</td>
                                    <td>
                                        @if(($alert->status ?? '') == 'open')
                                            <form method="POST" action="{{ route('myhealth.ai_clinical.alerts.acknowledge', $alert->id) }}">@csrf<button class="btn btn-xs btn-success">Acknowledge</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No clinical alerts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="box-footer"><a href="{{ route('myhealth.ai_clinical.alerts.index') }}" class="btn btn-primary btn-sm">View All Alerts</a></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">High Risk Members</h3></div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered">
                        <thead><tr><th>Member</th><th>Alerts</th><th>High</th></tr></thead>
                        <tbody>
                        @forelse($data['high_risk_members'] ?? [] as $row)
                            <tr><td>{{ $row->member_code }}<br><small>{{ $row->member_name }}</small></td><td>{{ $row->alert_count }}</td><td>{{ $row->high_alert_count }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No high risk members found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="box box-success">
                <div class="box-header with-border"><h3 class="box-title">Preventive Care / Follow-ups</h3></div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered">
                        <thead><tr><th>Due Date</th><th>Member</th><th>Reminder</th></tr></thead>
                        <tbody>
                        @forelse($data['reminders'] ?? [] as $row)
                            <tr><td>{{ $row->due_date ?? '' }}</td><td>{{ $row->member_code ?? '' }}<br><small>{{ $row->member_name ?? '' }}</small></td><td>{{ $row->title ?? '' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No reminders found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
