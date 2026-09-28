@extends('layouts.app')
@section('title', 'Performance Center - New')
@section('content')
<section class="content-header"><h1>Performance Center - New</h1></section>
<section class="content">
    @include('financereports::layouts.filter', ['locations' => $locations, 'location_id' => $context->location_id, 'start' => $context->start_date, 'end' => $context->end_date])
@include('financereports::layouts.toolbar')
    <div class="box box-primary"><div class="box-body">
        <h4>Engine Status</h4>
        <table class="table table-bordered">
            @foreach($report['status'] as $key => $value)
                @if(!is_array($value))<tr><th>{{ ucwords(str_replace('_',' ', $key)) }}</th><td>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</td></tr>@endif
            @endforeach
        </table>
        <h4>Query Strategy</h4>
        <ul>@foreach($report['query_strategy'] as $key => $value)<li>{{ ucwords(str_replace('_',' ', $key)) }}: {{ $value ? 'Enabled' : 'Disabled' }}</li>@endforeach</ul>
        <h4>Recommended Indexes</h4>
        <ul>@foreach($report['recommended_indexes'] as $index)<li>{{ $index }}</li>@endforeach</ul>
        <h4>Report Cache Keys</h4>
        <ul>@foreach($report['cache_keys'] as $key)<li>{{ $key }}</li>@endforeach</ul>
    </div></div>
</section>
@endsection
