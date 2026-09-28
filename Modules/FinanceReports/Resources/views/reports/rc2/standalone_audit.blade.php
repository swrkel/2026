@extends('layouts.app')
@section('title', 'Finance Reports - Standalone Audit')
@section('content')
<section class="content-header"><h1>Finance Reports - Standalone Audit</h1></section>
<section class="content">
    @include('financereports::layouts.toolbar')
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Final RC-2 Verification</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>#</th><th>Check</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($checklist as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['item'] }}</td>
                            <td><span class="label label-success">{{ strtoupper($row['status']) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
