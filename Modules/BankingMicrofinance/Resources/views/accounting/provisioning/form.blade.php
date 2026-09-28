@extends('layouts.app')
@section('title','Provisioning Form')
@section('content')<div class="container-fluid"><h3>Provisioning Form</h3><form method="post">@csrf <div class="alert alert-warning">Use this shell to capture fields without affecting existing working modules.</div><button class="btn btn-primary">Save</button></form></div>@endsection
