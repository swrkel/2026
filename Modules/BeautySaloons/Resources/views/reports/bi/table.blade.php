@extends('beautysaloons::layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('modules/beautysaloons/css/reports.css') }}">
<div class="container-fluid bs-reports-page">
    <h3>{{ $title }}</h3>
    @include('beautysaloons::reports.partials.filters')
    <div class="box box-solid">
        <div class="box-header with-border">
            <h4 class="box-title">{{ $title }}</h4>
            <div class="pull-right"><strong>Records:</strong> {{ $rows->count() }}</div>
        </div>
        <div class="box-body table-responsive bs-report-table-wrap">
            <table class="table table-bordered table-striped bs-report-table">
                <thead>
                    <tr>
                        @php $first = $rows->first(); @endphp
                        @if($first)
                            @foreach((array) $first as $column => $value)
                                <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                            @endforeach
                        @else
                            <th>No records found for {{ $table }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach((array) $row as $value)
                                <td>{{ is_numeric($value) ? $value : Str::limit((string) $value, 80) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="{{ asset('modules/beautysaloons/js/reports.js') }}"></script>
@endsection
