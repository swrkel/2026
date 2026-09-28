@extends('layouts.app')

@section('content')
<section class="content-header stn-tester-header"><h1>{{ __('stocktransfernew::tester_support.tester_support') }}</h1></section>
<section class="content stn-tester-page">

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@foreach($groups as $group => $cases)
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">{{ $group }}</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Test Case</th><th>Status</th><th>Remarks</th><th>Save</th></tr></thead><tbody>
@foreach($cases as $case)
<tr><form method="POST" action="{{ route('stock-transfer-new.tester-support.test-cases.status') }}">@csrf
<td><input type="hidden" name="test_key" value="{{ $case['key'] }}">{{ $case['title'] }}</td>
<td><select name="status" class="form-control input-sm"><option value="pending">Pending</option><option value="passed">Passed</option><option value="failed">Failed</option><option value="blocked">Blocked</option></select></td>
<td><input type="text" name="remarks" class="form-control input-sm" placeholder="Tester remarks"></td>
<td><button class="btn btn-primary btn-sm stn-tester-save" type="submit">Save</button></td>
</form></tr>
@endforeach
</tbody></table></div></div>
@endforeach
</section>
@endsection
