@extends('layouts.app')
@section('title', __('distributionnew::lang.exceptions'))
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.exceptions') }}</h1></section>
<section class="content disnew-pos-page">
<div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>#</th><th>{{__('distributionnew::lang.type')}}</th><th>{{__('distributionnew::lang.reference')}}</th><th>{{__('distributionnew::lang.status')}}</th><th>{{__('distributionnew::lang.note')}}</th><th>{{__('distributionnew::lang.action')}}</th></tr></thead><tbody>
@foreach($items as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->exception_type }}</td><td>{{ $row->reference_no }}</td><td>{{ $row->status }}</td><td>{{ $row->message }}</td><td>@if($row->status!='resolved')<form method="post" action="{{ route('distributionnew.production-stabilization.exceptions.resolve',$row->id) }}">@csrf<input name="resolution_note" class="form-control" placeholder="Resolution note"><button class="btn btn-success btn-sm">Resolve</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $items->links() }}</div></div></section>
@endsection
