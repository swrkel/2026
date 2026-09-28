@extends('layouts.app')

@section('content')
<section class="content-header stn-tester-header"><h1>{{ __('stocktransfernew::tester_support.tester_support') }}</h1></section>
<section class="content stn-tester-page">

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row">
@foreach($summary as $label => $value)
    <div class="col-md-3 col-sm-6"><div class="stn-tester-card"><span>{{ ucwords(str_replace('_',' ', $label)) }}</span><strong>{{ $value }}</strong></div></div>
@endforeach
</div>
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Cleanup Preview</h3></div><div class="box-body table-responsive">
<p class="text-muted">This screen is preview/log only. It does not delete transfers.</p>
<table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Transfer No</th><th>Business</th><th>Status</th><th>Created</th></tr></thead><tbody>
@foreach($transfers as $transfer)<tr><td>{{ $transfer->id }}</td><td>{{ $transfer->transfer_no }}</td><td>{{ $transfer->business_id }}</td><td>{{ $transfer->status }}</td><td>{{ $transfer->created_at }}</td></tr>@endforeach
</tbody></table>
<form method="POST" action="{{ route('stock-transfer-new.tester-support.cleanup-log') }}">@csrf
<div class="form-group"><label>Cleanup / tester note</label><textarea name="cleanup_note" class="form-control" rows="3" required></textarea></div>
<button class="btn btn-primary stn-tester-save" type="submit">Save Note</button>
</form>
</div></div>
</section>
@endsection
