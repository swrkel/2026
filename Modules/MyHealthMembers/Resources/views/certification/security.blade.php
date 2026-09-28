@extends('layouts.app')
@section('title', 'My Health Security Review')
@section('content')
<section class="content-header"><h1>My Health <small>Security Review</small></h1></section>
<section class="content">
@include('myhealthmembers::certification._nav')
<div class="box box-danger"><div class="box-header with-border"><h3 class="box-title">Security Production Checklist</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Item</th><th>Status</th><th>Note</th></tr></thead><tbody>@foreach($checklist as $item)<tr><td>{{ $item['item'] }}</td><td><span class="label label-warning">{{ $item['status'] }}</span></td><td>{{ $item['note'] }}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
