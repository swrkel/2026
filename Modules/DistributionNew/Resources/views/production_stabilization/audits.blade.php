@extends('layouts.app')
@section('title', __('distributionnew::lang.audit_centre'))
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.audit_centre') }}</h1></section>
<section class="content disnew-pos-page"><div class="box box-solid"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>#</th><th>{{__('distributionnew::lang.entity')}}</th><th>{{__('distributionnew::lang.action')}}</th><th>{{__('distributionnew::lang.user')}}</th><th>{{__('distributionnew::lang.created_at')}}</th></tr></thead><tbody>
@foreach($items as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->entity_type }} #{{ $row->entity_id }}</td><td>{{ $row->action }}</td><td>{{ $row->created_by }}</td><td>{{ $row->created_at }}</td></tr>@endforeach
</tbody></table>{{ $items->links() }}</div></div></section>
@endsection
