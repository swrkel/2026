@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::haccp.corrective_actions'))
@section('content')
<div class="restnew-page">
    <h1>{{ __('restaurantnew::haccp.corrective_actions') }}</h1>
    @include('restaurantnew::components.list-toolbar')
    <table class="table table-bordered table-striped">
        <thead><tr><th>Due</th><th>Title</th><th>Priority</th><th>Status</th><th>Assigned To</th></tr></thead>
        <tbody>
        @foreach($actions as $action)
            <tr><td>{{ $action->due_at }}</td><td>{{ $action->title }}</td><td>{{ $action->priority }}</td><td>{{ $action->status }}</td><td>{{ $action->assigned_to }}</td></tr>
        @endforeach
        </tbody>
    </table>
    {{ $actions->links() }}
</div>
@endsection
