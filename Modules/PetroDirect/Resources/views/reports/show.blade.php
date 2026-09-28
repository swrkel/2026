@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header">
    <h1>{{ $title }}</h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('petrodirect.reports.export', $report) }}" class="btn btn-success btn-sm">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped petrodirect-report-table" style="width:100%;">
                <thead><tr><th>Report Data</th></tr></thead>
            </table>
        </div>
    </div>
</section>
@endsection
@push('javascript')
<script>
$(function () {
    $('.petrodirect-report-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: window.location.href,
        columns: [{data: null, name: 'id', render: function(row){ return JSON.stringify(row); }}]
    });
});
</script>
@endpush
