@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<table class="table table-bordered"><thead><tr><th style="width:60px">#</th><th>Check Item</th><th>Status</th><th>Tester Note</th></tr></thead><tbody>
@foreach($items as $i => $item)<tr><td>{{ $i + 1 }}</td><td>{{ $item }}</td><td><span class="label label-warning">Pending</span></td><td></td></tr>@endforeach
</tbody></table>
@endsection
