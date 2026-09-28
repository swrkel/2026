@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-card"><div class="pn-card-header"><strong>Product Health - {{ $product->name }}</strong><span class="pn-badge pn-badge-large">{{ $health['score'] }}%</span></div><div class="pn-card-body"><div class="pn-health-grid">@foreach($health['checks'] as $key=>$passed)<div class="pn-health {{ $passed ? 'ok' : 'missing' }}">{{ $passed ? '✓' : '✗' }} {{ ucwords(str_replace('_',' ',$key)) }}</div>@endforeach</div></div></div>
@endsection
