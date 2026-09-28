@extends('stockadjustmentnew::layouts.app')
@section('san_title','Create Reason')
@section('san_content')
@include('stockadjustmentnew::reasons.form',['reason'=>null])
@endsection
