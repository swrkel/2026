@extends('bankingui::layouts.master', ['title' => 'Banking Tester Dashboard'])
@section('banking_content')
<div class="row">
@foreach($summary as $card)
    <div class="col-md-3"><div class="box box-primary"><div class="box-body"><strong>{{ $card['label'] }}</strong><h3>{{ $card['value'] }}</h3></div></div></div>
@endforeach
</div>
<div class="box box-info"><div class="box-header"><h3 class="box-title">Banking Menu Groups</h3></div><div class="box-body">
@foreach($groups as $group)
    <h4>{{ $group['label'] }}</h4>
    <div class="row">
    @foreach($group['items'] as $item)
        <div class="col-md-3"><a class="btn btn-default btn-block banking-nav-tile" data-module-key="{{ $item['key'] }}" href="{{ isset($item['params']) ? route($item['route'], $item['params']) : route($item['route']) }}">{{ $item['label'] }}</a></div>
    @endforeach
    </div>
@endforeach
</div></div>
@endsection
