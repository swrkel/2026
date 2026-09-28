@extends('leadsnew::layouts.app')

@section('title', 'Leads-New Health Check')

@section('leadsnew_content')
<section class="content-header">
    <h1>Leads-New Health Check</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Module Status</h3>
        </div>
        <div class="box-body">
            <p><strong>Status:</strong> {{ $validation['status'] ?? 'unknown' }}</p>
            <p>{{ $validation['message'] ?? '' }}</p>

            @if(!empty($validation['missing_tables']))
                <h4>Missing Tables</h4>
                <ul>
                    @foreach($validation['missing_tables'] as $table)
                        <li>{{ $table }}</li>
                    @endforeach
                </ul>
            @endif

            <h4>Server Test Checklist</h4>
            <ol>
                @foreach($checklist as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
@endsection
