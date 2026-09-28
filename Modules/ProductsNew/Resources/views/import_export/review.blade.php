@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-page-title"><div><h3>Import Review #{{ $session->id }}</h3><p>{{ $session->file_name }} — {{ $session->status }}</p></div><div class="pn-actions"><form method="POST" action="{{ route('products-new.import-export.commit',$session->id) }}">@csrf<button class="btn btn-success" {{ $session->invalid_rows > 0 ? 'disabled' : '' }}>Commit / Repair Products</button></form></div></div>
<div class="pn-card"><div class="pn-card-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Line</th><th>Product</th><th>SKU</th><th>Barcode</th><th>Status</th><th>Errors</th></tr></thead><tbody>
@foreach($lines as $line)<tr><td>{{ $line->line_no }}</td><td>{{ $line->product_name }}</td><td>{{ $line->sku }}</td><td>{{ $line->barcode }}</td><td>{{ $line->status }}</td><td><small>{{ $line->validation_errors }}</small></td></tr>@endforeach
</tbody></table>{{ $lines->links() }}
</div></div>
@endsection
