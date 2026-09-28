@extends('bankingui::layouts.master', ['title' => 'Banking Route Health'])
@section('banking_content')
<div class="box box-warning"><div class="box-body table-responsive">
<table class="table table-bordered"><thead><tr><th>Route</th><th>Status</th><th>URL</th></tr></thead><tbody>
@foreach($routes as $route)
<tr><td>{{ $route['name'] }}</td><td>{!! $route['registered'] ? '<span class="label label-success">Registered</span>' : '<span class="label label-danger">Missing</span>' !!}</td><td>{{ $route['url'] }}</td></tr>
@endforeach
</tbody></table>
</div></div>
@endsection
