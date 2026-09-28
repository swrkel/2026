@extends('layouts.app')
@section('title','Distribution New Production Audit')
@section('content')
<section class="content-header"><h1>Distribution New <small>Production Audit</small></h1></section>
<section class="content disnew-pos-skin">
 <div class="row">
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-shield"></i></span><div class="info-box-content"><span class="info-box-text">Module</span><span class="info-box-number">Distribution New</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-database"></i></span><div class="info-box-content"><span class="info-box-text">Prefix</span><span class="info-box-number">disnew_</span></div></div></div>
  <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Audit</span><span class="info-box-number">Ready</span></div></div></div>
  <div class="col-md-3"><form method="post" action="{{ route('distributionnew.audit.run') }}">@csrf<button class="btn btn-primary btn-block" style="color:#fff;margin-top:25px;">Run Audit</button></form></div>
 </div>
 <div class="box box-solid"><div class="box-header"><h3 class="box-title">Audit Sections</h3></div><div class="box-body">
  <a href="{{ route('distributionnew.audit.permissions') }}" class="btn btn-info" style="color:#fff">Permissions</a>
  <a href="{{ route('distributionnew.audit.menu') }}" class="btn btn-success" style="color:#fff">Menu</a>
  <a href="{{ route('distributionnew.audit.routes') }}" class="btn btn-warning" style="color:#fff">Routes</a>
  <a href="{{ route('distributionnew.audit.sql') }}" class="btn btn-danger" style="color:#fff">SQL</a>
  <a href="{{ route('distributionnew.audit.ui') }}" class="btn btn-primary" style="color:#fff">UI</a>
 </div></div>
</section>
@endsection
