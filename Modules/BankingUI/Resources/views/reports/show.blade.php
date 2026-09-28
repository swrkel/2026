@extends('layouts.app')

@section('content')
<section class="content-header"><h1>{{ $report['label'] }}</h1></section>
<section class="content">
    <form method="GET" id="bkg-report-filter-form">
        @include('bankingui::partials.report-toolbar')
    </form>
    <div class="box box-solid">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped bkg-report-table" data-report-key="{{ $report['key'] }}">
                <thead>
                    <tr>
                        <th>Date</th><th>Reference</th><th>Branch</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7" class="text-center text-muted">Report shell ready. Connect module-specific data provider.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
