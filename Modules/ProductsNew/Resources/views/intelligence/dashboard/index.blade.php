@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-card"><div class="pn-card-header"><strong>Product Intelligence Centre</strong><span class="pn-muted">Health, workflow, duplicate control and availability summary</span></div><div class="pn-card-body">
<div class="pn-kpi-grid">
@foreach(['total'=>'Total Products','inactive'=>'Inactive','withoutSku'=>'Without SKU','withoutImage'=>'Without Image','withLowHealth'=>'Low Health','duplicateOpen'=>'Open Duplicates'] as $key=>$label)
<div class="pn-kpi"><span>{{ $label }}</span><strong>{{ number_format($summary[$key] ?? 0) }}</strong></div>
@endforeach
</div>
<div class="productsnew-actions pn-mt"><a class="pn-btn pn-btn-primary" href="{{ route('products-new.intelligence.relationships.index') }}">Relationships</a><a class="pn-btn pn-btn-success" href="{{ route('products-new.intelligence.workflow.index') }}">Workflow</a><a class="pn-btn pn-btn-warning" href="{{ route('products-new.intelligence.duplicates.index') }}">Duplicates</a><a class="pn-btn pn-btn-info" href="{{ route('products-new.intelligence.availability.index') }}">Availability Matrix</a><a class="pn-btn pn-btn-default" href="{{ route('products-new.intelligence.notes.index') }}">Notes</a></div>
</div></div>
<div class="pn-grid-2"><div class="pn-card"><div class="pn-card-header"><strong>Lowest Product Health</strong></div><div class="pn-card-body"><table class="table pn-table"><thead><tr><th>Product</th><th>SKU</th><th>Type</th><th>Health</th></tr></thead><tbody>@foreach($healthItems as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->sku }}</td><td>{{ $row->type }}</td><td><span class="pn-badge">{{ number_format((float)$row->health_score,2) }}%</span></td></tr>@endforeach</tbody></table></div></div>
<div class="pn-card"><div class="pn-card-header"><strong>Status Summary</strong></div><div class="pn-card-body"><table class="table pn-table"><thead><tr><th>Status</th><th class="text-right">Products</th></tr></thead><tbody>@foreach(($summary['statusCounts'] ?? []) as $status=>$total)<tr><td>{{ ucwords(str_replace('_',' ',$status)) }}</td><td class="text-right">{{ number_format($total) }}</td></tr>@endforeach</tbody></table></div></div></div>
@endsection
