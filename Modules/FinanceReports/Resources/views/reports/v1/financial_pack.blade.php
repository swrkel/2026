@extends('layouts.app')
@section('title', 'Financial Pack - New')
@section('content')
<section class="content-header"><h1>Financial Pack - New <small>{{ $pack['mode'] ?? '' }}</small></h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Financial Pack - New'])
<div class="box box-primary"><div class="box-body">
<p><strong>Period:</strong> {{ $pack['period'] ?? '' }}</p>
<div class="row">
@foreach(($pack['sections'] ?? []) as $section)
<div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h4>{{ $section }}</h4><p>Included in pack</p></div></div></div>
@endforeach
</div>
<h4>Statement Pack</h4>
<table class="table table-bordered table-striped"><thead><tr><th>Report</th><th>Status</th></tr></thead><tbody>
@foreach(($pack['reports'] ?? []) as $name => $data)
<tr><td>{{ $name }}</td><td>Prepared for selected branch/consolidated context</td></tr>
@endforeach
</tbody></table>
</div></div>
</section>
@endsection
