@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-card"><div class="pn-card-header"><strong>Timeline - {{ $product->name }}</strong></div><div class="pn-card-body">@forelse($timeline as $row)<div class="pn-timeline"><strong>{{ ucfirst($row->event) }}</strong><small>{{ $row->created_at }}</small></div>@empty<p>No timeline records yet.</p>@endforelse {{ $timeline->links() }}</div></div>
@endsection
