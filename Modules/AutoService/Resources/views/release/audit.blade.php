@extends('autoservice::layouts.master')
@section('title', 'Auto Service v1.0 Release Audit')
@section('content')
<section class="content-header"><h1>Auto Service v1.0 Release Audit</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">Release Candidate Checklist</h3></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3"><div class="well"><strong>Total</strong><br>{{ $summary['total'] }}</div></div>
                <div class="col-md-3"><div class="well"><strong>Ready</strong><br>{{ $summary['ok'] }}</div></div>
                <div class="col-md-3"><div class="well"><strong>Missing</strong><br>{{ $summary['missing'] }}</div></div>
                <div class="col-md-3"><div class="well"><strong>Version</strong><br>v1.0 RC1</div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Section</th><th>Item</th><th>Status</th><th>Message</th></tr></thead>
                    <tbody>
                    @foreach($summary['items'] as $item)
                        <tr>
                            <td>{{ $item['section'] }}</td>
                            <td>{{ $item['item'] }}</td>
                            <td><span class="label label-{{ $item['status'] === 'Missing' ? 'danger' : 'success' }}">{{ $item['status'] }}</span></td>
                            <td>{{ $item['message'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
