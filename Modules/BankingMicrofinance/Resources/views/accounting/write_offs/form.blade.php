@extends('layouts.app')
@section('title','Write-Offs Form')
@section('content')<div class="container-fluid"><h3>Write-Offs Form</h3><form method="post">@csrf <div class="alert alert-warning">Use this shell to capture fields without affecting existing working modules.</div><button class="btn btn-primary">Save</button></form></div>@endsection
