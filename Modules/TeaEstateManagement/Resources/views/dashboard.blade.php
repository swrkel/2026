@extends('teaestate::layouts.app',['title'=>'Tea Estate Dashboard','heading'=>'Tea Estate Dashboard'])
@section('tea_content')
<div class="tea-grid">
 <div class="tea-kpi"><b>{{ number_format($metrics['estates']) }}</b><span>Active estate records</span></div>
 <div class="tea-kpi"><b>{{ number_format($metrics['green_leaf'],3) }} kg</b><span>Green leaf inventory</span></div>
 <div class="tea-kpi"><b>{{ number_format($metrics['made_tea'],3) }} kg</b><span>Made / packed tea inventory</span></div>
 <div class="tea-kpi"><b>{{ number_format($metrics['sales'],2) }}</b><span>Tea sales value</span></div>
</div>
<div class="tea-card"><div class="tea-card-h">Finance Integration</div><div class="tea-card-b"><b>{{ number_format($metrics['finance_pending']) }}</b> pending/error financial event(s). Tea transactions remain in tea_* tables even when Finance is temporarily unavailable. <a href="{{ route('teaestate.finance.index') }}">Open integration status</a>.</div></div>
@endsection
