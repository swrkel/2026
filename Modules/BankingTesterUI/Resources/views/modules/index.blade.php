@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<table class="table table-bordered table-striped">
<thead><tr><th>Module</th><th>Status</th><th>Pages</th><th>Action</th></tr></thead>
<tbody>
@foreach($modules as $module)
<tr><td>{{ $module['name'] }}</td><td>{{ $module['status'] }}</td><td>{{ implode(', ', $module['items']) }}</td><td><a class="btn btn-xs btn-primary" href="{{ url($module['url']) }}">Open</a></td></tr>
@endforeach
</tbody></table>
@endsection
