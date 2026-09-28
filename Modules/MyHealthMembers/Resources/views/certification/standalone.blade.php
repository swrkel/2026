@extends('layouts.app')
@section('title', 'My Health Standalone Audit')
@section('content')
<section class="content-header"><h1>My Health <small>Standalone Audit</small></h1></section>
<section class="content">
@include('myhealthmembers::certification._nav')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Standalone Architecture Checklist</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Area</th><th>Target</th><th>Status</th></tr></thead><tbody>@foreach($checklist as $item)<tr><td>{{ $item['area'] }}</td><td>{{ $item['target'] }}</td><td><span class="label label-success">{{ $item['status'] }}</span></td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
