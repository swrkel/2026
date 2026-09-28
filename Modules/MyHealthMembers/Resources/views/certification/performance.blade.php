@extends('layouts.app')
@section('title', 'My Health Performance Review')
@section('content')
<section class="content-header"><h1>My Health <small>Performance Review</small></h1></section>
<section class="content">
@include('myhealthmembers::certification._nav')
<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Performance Checklist</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Item</th><th>Status</th></tr></thead><tbody>@foreach($checklist as $item)<tr><td>{{ $item['item'] }}</td><td><span class="label label-info">{{ $item['status'] }}</span></td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
