@extends('layouts.app')
@section('title', 'Mobile Banking Reports')
@section('content')
<section class="content-header"><h1>Mobile Banking Reports</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Mobile Banking Reports</h3></div>
        <div class="box-body">

<table class="table table-bordered table-striped"><thead><tr><th>Report</th><th>Status</th></tr></thead><tbody>
@foreach($reports as $report)
<tr><td>{{ $report }}</td><td><span class="label label-info">Ready Shell</span></td></tr>
@endforeach
</tbody></table>

        </div>
    </div>
</section>
@endsection
