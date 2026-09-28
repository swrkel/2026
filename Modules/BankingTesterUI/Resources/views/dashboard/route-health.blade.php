@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<table class="table table-bordered table-striped"><thead><tr><th>Method</th><th>URI</th><th>Name</th></tr></thead><tbody>
@forelse($routes as $route)<tr><td>{{ $route['method'] }}</td><td>{{ $route['uri'] }}</td><td>{{ $route['name'] }}</td></tr>@empty<tr><td colspan="3">No Banking routes found.</td></tr>@endforelse
</tbody></table>
@endsection
