@extends('layouts.app')
@section('title', 'Clinical Alerts')
@section('content')
<section class="content-header"><h1>My Health <small>Clinical Alerts</small></h1></section>
<section class="content">
    @include('myhealthmembers::ai_clinical._filters')
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Clinical Alert Register</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>Member Code</th><th>Member</th><th>Type</th><th>Severity</th><th>Title</th><th>Message</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($data as $alert)
                    <tr>
                        <td>{{ !empty($alert->created_at) ? @format_datetime($alert->created_at) : '' }}</td>
                        <td>{{ $alert->member_code ?? '' }}</td>
                        <td>{{ $alert->member_name ?? '' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $alert->alert_type ?? '')) }}</td>
                        <td>{{ ucfirst($alert->severity ?? '') }}</td>
                        <td>{{ $alert->title ?? '' }}</td>
                        <td>{{ $alert->message ?? '' }}</td>
                        <td>{{ ucfirst($alert->status ?? '') }}</td>
                        <td>
                            @if(($alert->status ?? '') == 'open')
                                <form method="POST" action="{{ route('myhealth.ai_clinical.alerts.acknowledge', $alert->id) }}">@csrf<button class="btn btn-xs btn-success">Acknowledge</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
