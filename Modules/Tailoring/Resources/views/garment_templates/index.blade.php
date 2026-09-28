@extends('tailoring::layouts.app')
@section('title','Garment Templates')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Garment Templates','createRoute'=>route('tailoring.garment-templates.create')])
<div class="row">@foreach($templates as $template)<div class="col-md-4"><div class="card mb-3"><div class="card-body"><h4>{{ $template->name }}</h4><p>{{ $template->category }}</p><span class="badge badge-info">{{ $template->edition ?? 'basic' }}</span></div></div></div>@endforeach</div>{{ $templates->links() }}
@endsection
