@extends('layouts.app')

@section('content')
<section class="content-header stn-tester-header"><h1>{{ __('stocktransfernew::tester_support.tester_support') }}</h1></section>
<section class="content stn-tester-page">

<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Troubleshooting Guide</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Issue</th><th>Check</th><th>Safe Action</th></tr></thead><tbody>
@foreach($items as $item)<tr><td>{{ $item['issue'] }}</td><td>{{ $item['check'] }}</td><td>{{ $item['safe_action'] }}</td></tr>@endforeach
</tbody></table></div></div>
</section>
@endsection
