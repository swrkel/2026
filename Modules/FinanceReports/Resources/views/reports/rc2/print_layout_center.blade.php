@extends('layouts.app')
@section('title', 'Finance Reports - Print Layout Center')
@section('content')
<section class="content-header"><h1>Print Layout Center - New</h1></section>
<section class="content">
    @include('financereports::layouts.filter')
@include('financereports::layouts.toolbar')
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Standard Print Header</h3></div>
        <div class="box-body">
            <table class="table table-bordered">
                @foreach($header as $key => $value)
                    <tr><th>{{ ucwords(str_replace('_', ' ', $key)) }}</th><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
</section>
@endsection
