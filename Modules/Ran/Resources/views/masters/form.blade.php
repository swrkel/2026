@extends('ran::layouts.app', ['title' => $title])
@section('ran-content')
<x-ran::page-header :title="$title"><a href="{{ route($routeBase.'.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a></x-ran::page-header>
<div class="ran-card"><form method="POST" action="{{ $record ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}">@csrf @if($record) @method('PUT') @endif
<div class="row">@foreach($fields as $name=>$field)<div class="col-md-6"><div class="form-group"><label>{{ $field['label'] }}</label>
@if(($field['type'] ?? 'text')==='textarea')<textarea name="{{ $name }}" class="form-control" rows="3">{{ old($name, data_get($record,$name)) }}</textarea>
@elseif(($field['type'] ?? '')==='checkbox')<div><label class="ran-switch"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" {{ old($name, data_get($record,$name,1)) ? 'checked' : '' }}><span>Enabled</span></label></div>
@else<input type="{{ $field['type'] ?? 'text' }}" name="{{ $name }}" class="form-control" step="any" value="{{ old($name, data_get($record,$name)) }}">@endif
</div></div>@endforeach</div><div class="text-right"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button></div></form></div>
@endsection
