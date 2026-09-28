@extends('layouts.app')
@section('title', 'Fixed Asset Dashboard - New')
@section('content')
<section class="content-header"><h1>Fixed Asset Dashboard - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
<div class="row">
@foreach(['assets' => 'Total Assets', 'cost' => 'Total Cost', 'depreciation' => 'Accumulated Depreciation', 'book_value' => 'Book Value', 'categories' => 'Categories'] as $key => $label)
<div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ is_numeric($report['cards'][$key] ?? 0) && $key != 'assets' && $key != 'categories' ? number_format($report['cards'][$key] ?? 0, 4) : ($report['cards'][$key] ?? 0) }}</h3><p>{{ $label }}</p></div><div class="icon"><i class="fa fa-building"></i></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Asset Category Summary</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Category</th><th class="text-right">Assets</th><th class="text-right">Cost</th><th class="text-right">Depreciation</th><th class="text-right">Book Value</th></tr></thead><tbody>
@foreach($report['categories'] ?? [] as $row)<tr><td>{{ $row->category }}</td><td class="text-right">{{ $row->records }}</td><td class="text-right">{{ number_format($row->cost, 4) }}</td><td class="text-right">{{ number_format($row->depreciation, 4) }}</td><td class="text-right">{{ number_format($row->book_value, 4) }}</td></tr>@endforeach
</tbody></table></div></div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Recent Assets</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Asset Code</th><th>Asset Name</th><th>Category</th><th>Branch / Location</th><th class="text-right">Book Value</th><th>Status</th></tr></thead><tbody>
@foreach($report['recent_assets'] ?? [] as $row)<tr><td>{{ $row->asset_code }}</td><td>{{ $row->asset_name }}</td><td>{{ $row->category }}</td><td>{{ $row->location_name }}</td><td class="text-right">{{ number_format($row->book_value, 4) }}</td><td>{{ $row->status }}</td></tr>@endforeach
</tbody></table></div></div>
</section>
@endsection
