@extends('layouts.app')
@section('title', __('stocktransfernew::messages.exceptions'))
@section('content')
<section class="content-header"><h1>{{ __('stocktransfernew::messages.exceptions') }}</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Transfer</th><th>Type</th><th>Severity</th><th>Status</th><th>Remarks</th><th>Created</th></tr></thead><tbody>
@foreach(($exceptions ?? []) as $item)
<tr><td>{{ $item->id }}</td><td>{{ $item->transfer_id }}</td><td>{{ $item->exception_type }}</td><td>{{ $item->severity }}</td><td>{{ $item->status }}</td><td>{{ $item->remarks }}</td><td>{{ $item->created_at }}</td></tr>
@endforeach
</tbody></table></div></div></section>
@endsection
