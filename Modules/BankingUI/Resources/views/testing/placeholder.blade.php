@extends('bankingui::layouts.master', ['title' => $title])
@section('banking_content')
<div class="box box-info"><div class="box-header"><h3 class="box-title">{{ $title }}</h3></div><div class="box-body">
<p>This tester-facing placeholder confirms that the Banking sidebar, route and permission path are working for <strong>{{ $module }}</strong>.</p>
<p>Replace this placeholder with the actual standalone module page after that module parcel is installed.</p>
</div></div>
@endsection
