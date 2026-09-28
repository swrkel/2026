@extends('distributionnew::layouts.app')
@section('title','Create')
@section('module_content')<div class="disnew-card"><form method="POST">@csrf<button class="disnew-btn">Save</button></form></div>@endsection
