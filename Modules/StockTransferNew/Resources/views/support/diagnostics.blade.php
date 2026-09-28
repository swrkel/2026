@extends('layouts.app')

@section('content')
<section class="content-header stn-support-header"><h1>{{ __('stocktransfernew::lang.production_support') }}</h1></section>
<section class="content stn-support-page">

<div class="row">
    @foreach($summary as $label => $value)
        <div class="col-md-3 col-sm-6"><div class="stn-support-card"><span>{{ $label }}</span><strong>{{ $value }}</strong></div></div>
    @endforeach
</div>
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Diagnostics</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Item</th><th>Table</th><th>Status</th></tr></thead><tbody>
@foreach($checks as $check)<tr><td>{{ $check['item'] }}</td><td>{{ $check['table'] }}</td><td><span class="label label-{{ $check['status'] === 'OK' ? 'success' : 'danger' }}">{{ $check['status'] }}</span></td></tr>@endforeach
</tbody></table></div></div>
</section>
@endsection
