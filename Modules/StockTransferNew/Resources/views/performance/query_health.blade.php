@extends('layouts.app')
@section('title', __('stocktransfernew::lang.query_health'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-performance.css') }}">
<section class="content-header"><h1>@lang('stocktransfernew::lang.query_health')</h1></section>
<section class="content">
<div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Table</th><th>Rows</th><th>Status</th></tr></thead><tbody>
@foreach($tables as $row)<tr><td>{{ $row['table'] }}</td><td>{{ number_format($row['rows']) }}</td><td>{{ $row['status'] }}</td></tr>@endforeach
</tbody></table>
</div></div>
<div class="box box-solid"><div class="box-header"><h3 class="box-title">Recommended Index Coverage</h3></div><div class="box-body">
@foreach($recommendations as $table => $cols)<p><strong>{{ $table }}</strong>: {{ $cols }}</p>@endforeach
</div></div>
</section>
@endsection
