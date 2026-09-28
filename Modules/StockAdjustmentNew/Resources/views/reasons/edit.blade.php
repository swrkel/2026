@extends('stockadjustmentnew::layouts.app')
@section('san_title','Edit Reason')
@section('san_content')
@include('stockadjustmentnew::reasons.form',['reason'=>$reason])
@endsection
