@extends('layouts.app')
@section('title', __('stocktransfernew::lang.export_queue'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.export_queue')</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-solid"><div class="box-body">
<form method="POST" action="{{ route('stock-transfer-new.performance.export-queue.store') }}" class="form-inline">@csrf
<input name="report_type" class="form-control" placeholder="Report type" required>
<button class="btn btn-primary">Queue Export</button>
</form></div></div>
<div class="box box-solid"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Report</th><th>Status</th><th>Requested By</th><th>Created</th></tr></thead><tbody>
@forelse($items as $item)<tr><td>{{ $item->id }}</td><td>{{ $item->report_type }}</td><td>{{ $item->status }}</td><td>{{ $item->requested_by }}</td><td>{{ $item->created_at }}</td></tr>@empty<tr><td colspan="5">No export queue records.</td></tr>@endforelse
</tbody></table></div></div>
</section>
@endsection
