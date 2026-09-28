@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<div class="box box-primary"><div class="box-header"><h3 class="box-title">{{ $module['name'] }}</h3></div><div class="box-body">
<p>Status: <span class="label label-info">{{ $module['status'] }}</span></p>
<div class="row">@foreach($module['items'] as $item)<div class="col-md-3 col-sm-6"><div class="bkg-page-tile"><i class="fa fa-file-o"></i><br>{{ $item }}<br><small>Tester page shell</small></div></div>@endforeach</div>
</div></div>
@endsection
