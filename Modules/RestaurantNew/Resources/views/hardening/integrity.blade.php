@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::messages.integrity_hardening'))

@section('content')
<div class="restnew-page">
    <div class="restnew-toolbar">
        <h3>{{ __('restaurantnew::messages.integrity_hardening') }}</h3>
        <form method="POST" action="{{ route('restaurantnew.hardening.integrity.run') }}">@csrf<button class="btn btn-primary">{{ __('restaurantnew::messages.run_checks') }}</button></form>
    </div>
    <div class="restnew-card">
        <table class="table table-bordered restnew-datatable">
            <thead><tr><th>Code</th><th>Group</th><th>Status</th><th>Message</th><th>Checked At</th></tr></thead>
            <tbody>
            @foreach($checks as $check)
                <tr><td>{{ $check->check_code }}</td><td>{{ $check->check_group }}</td><td>{{ strtoupper($check->status) }}</td><td>{{ $check->message }}</td><td>{{ optional($check->checked_at)->format('Y-m-d H:i') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
