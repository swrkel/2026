@extends('layouts.app')
@section('title', $title ?? 'Communication Hub')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Communication Hub' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php($title='Communication Hub Reports')
<div class="row">@foreach($summary as $key=>$value)<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-bar-chart"></i></span><div class="info-box-content"><span class="info-box-text">{{ ucwords(str_replace('_',' ',$key)) }}</span><span class="info-box-number">{{ $value }}</span></div></div></div>@endforeach</div>
<div class="box"><div class="box-header"><a class="btn btn-primary btn-sm" href="{{ route('communicationhub.reports.export') }}">CSV Export</a></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Cost</th><th>Date</th></tr></thead><tbody>@foreach($messages as $message)<tr><td>{{ $message->id }}</td><td>{{ $message->channel }}</td><td>{{ $message->recipient }}</td><td>{{ $message->status }}</td><td>{{ $message->cost }}</td><td>{{ $message->created_at }}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
