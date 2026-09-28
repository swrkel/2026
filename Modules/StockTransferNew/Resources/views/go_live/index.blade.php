@extends('layouts.app')
@section('title', 'Stock Transfer-New Go-Live Readiness')
@section('content')
<section class="content-header stn-go-live-header">
    <h1>Stock Transfer-New Go-Live Readiness</h1>
    <p>Final server validation before production use.</p>
</section>
<section class="content stn-go-live">
    @foreach($readiness as $group => $checks)
        <div class="box stn-go-live-card">
            <div class="box-header with-border"><h3 class="box-title">{{ ucwords(str_replace('_', ' ', $group)) }}</h3></div>
            <div class="box-body">
                @if(is_array($checks))
                    <table class="table table-bordered table-striped">
                        @foreach($checks as $name => $status)
                            <tr><td>{{ $name }}</td><td><strong>{{ is_array($status) ? json_encode($status) : $status }}</strong></td></tr>
                        @endforeach
                    </table>
                @else
                    <p>{{ $checks }}</p>
                @endif
            </div>
        </div>
    @endforeach
</section>
@endsection
