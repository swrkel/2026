@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::messages.dependency_scan'))

@section('content')
<div class="restnew-page">
    <div class="restnew-toolbar"><h3>{{ __('restaurantnew::messages.dependency_scan') }}</h3></div>
    <div class="restnew-card">
        <p><strong>Status:</strong> {{ strtoupper($scan['status']) }}</p>
        <p>{{ $scan['message'] }}</p>
        @if(!empty($scan['hits']))
            <table class="table table-bordered"><thead><tr><th>File</th><th>Blocked Dependencies</th></tr></thead><tbody>
            @foreach($scan['hits'] as $file => $hits)<tr><td>{{ $file }}</td><td>{{ implode(', ', $hits) }}</td></tr>@endforeach
            </tbody></table>
        @endif
    </div>
</div>
@endsection
