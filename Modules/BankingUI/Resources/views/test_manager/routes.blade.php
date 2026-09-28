@extends('bankingui::layouts.master')
@section('banking_content')
@include('bankingui::test_manager._nav')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Banking Route Health</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Method</th><th>URI</th><th>Name</th><th>Action</th><th>Status</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row['method'] }}</td><td>{{ $row['uri'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['action'] }}</td><td><span class="label label-{{ $row['status']=='OK' ? 'success' : 'warning' }}">{{ $row['status'] }}</span></td></tr>@endforeach</tbody></table></div></div>
@endsection
