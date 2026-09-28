@extends('layouts.app')

@section('content')
<section class="content-header stn-support-header"><h1>{{ __('stocktransfernew::lang.production_support') }}</h1></section>
<section class="content stn-support-page">

<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Tenant / Business / Store Scope Check</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Check</th><th>Status</th></tr></thead><tbody>
@foreach($items as $item)<tr><td>{{ $item['check'] }}</td><td><span class="label label-{{ $item['status'] === 'OK' ? 'success' : 'danger' }}">{{ $item['status'] }}</span></td></tr>@endforeach
</tbody></table></div></div>
</section>
@endsection
