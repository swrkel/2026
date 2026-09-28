@extends('layouts.app')
@section('title','Distribution New Audit')
@section('content')
<section class="content-header"><h1>Distribution New Audit</h1></section>
<section class="content disnew-pos-skin"><div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped disnew-datatable"><thead><tr><th>Name</th><th>Status</th><th>Note</th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ $row['name'] }}</td><td><span class="label label-warning">{{ $row['status'] }}</span></td><td>{{ $row['note'] }}</td></tr>@endforeach
</tbody></table></div></div></section>
@endsection
