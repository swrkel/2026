@php
    $title='Saved Filters';
@endphp
@php
    $rows=$filters;
@endphp
@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="products-new-page products-new-framework">
  <div class="products-new-header"><div><h3>{{ $title ?? 'Products New' }}</h3><p class="text-muted">Universal product framework - POS standard layout.</p></div></div>
  <div class="card products-new-card"><div class="card-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="products-new-toolbar mb-3">
      <form method="get" class="form-inline"><input name="search" class="form-control mr-2" placeholder="Search"><button class="btn btn-primary">Search</button></form>
    </div>
    <div class="table-responsive"><table class="table table-bordered table-striped products-new-table">
      <thead><tr><th>ID</th><th>Name</th><th>Code / SKU</th><th>Status</th><th>Updated</th></tr></thead>
      <tbody>
      @foreach(($rows ?? $types ?? $fields ?? $rules ?? $templates ?? $filters ?? $sessions ?? $versions ?? $items ?? collect()) as $row)
        <tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->name ?? $row->label ?? $row->product_name ?? $row->operation ?? '' }}</td><td>{{ $row->code ?? $row->field_key ?? $row->sku ?? $row->rule_group ?? '' }}</td><td>{{ $row->status ?? (($row->is_active ?? true) ? 'Active' : 'Inactive') }}</td><td>{{ $row->updated_at ?? $row->created_at ?? '' }}</td></tr>
      @endforeach
      </tbody>
    </table></div>
    @if(isset($summary))<div class="row mt-3">@foreach($summary as $k=>$v)<div class="col-md-3"><div class="products-new-kpi"><span>{{ ucwords(str_replace('_',' ',$k)) }}</span><strong>{{ $v }}</strong></div></div>@endforeach</div>@endif
  </div></div>
</div>
@endsection
