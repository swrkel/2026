@extends('communicationhub::layout')

@section('communicationhub_title', 'Enterprise Communication Centre')
@section('communicationhub_content')
<div class="row">
@foreach([
'SMS Today'=>$stats['sms_today'] ?? 0, 'Emails Today'=>$stats['email_today'] ?? 0, 'WhatsApp Today'=>$stats['whatsapp_today'] ?? 0, 'Push Today'=>$stats['push_today'] ?? 0,
'OTP Generated'=>$stats['otp_generated'] ?? 0, 'OTP Verified'=>$stats['otp_verified'] ?? 0, 'Queue Size'=>$stats['queue_size'] ?? 0, 'Failed Messages'=>$stats['failed_messages'] ?? 0
] as $label=>$value)
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-comments"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ number_format($value) }}</span></div></div></div>
@endforeach
</div>
<div class="row">
    <div class="col-md-6"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Queue Status</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><tbody>@foreach($queueStats as $status=>$count)<tr><td>{{ ucwords(str_replace('_',' ', $status)) }}</td><td class="text-right">{{ number_format($count) }}</td></tr>@endforeach</tbody></table></div></div></div>
    <div class="col-md-6"><div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Cost Summary</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><tbody>@foreach($costStats as $label=>$amount)<tr><td>{{ ucwords(str_replace('_',' ', $label)) }}</td><td class="text-right">{{ number_format((float)$amount, 4) }}</td></tr>@endforeach</tbody></table></div></div></div>
</div>
<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Provider Health</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Provider</th><th>Channel</th><th>Status</th><th>Today</th><th>This Month</th><th>Last Error</th></tr></thead><tbody>@forelse($providerHealth as $provider)<tr><td>{{ $provider['name'] }}</td><td>{{ strtoupper($provider['channel']) }}</td><td><span class="label label-{{ ($provider['status'] == 'online' || $provider['is_active']) ? 'success' : 'danger' }}">{{ $provider['status'] }}</span></td><td>{{ $provider['daily_usage'] }}</td><td>{{ $provider['monthly_usage'] }}</td><td>{{ $provider['last_error'] }}</td></tr>@empty<tr><td colspan="6" class="text-center">No providers configured.</td></tr>@endforelse</tbody></table></div></div>
@endsection
