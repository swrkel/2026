@extends('airlineticketingnew::layouts.app')
@section('atn-title','Analytics & Forecast')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Date</th><th>Forecast Sales</th></tr></thead><tbody>
@foreach($forecast as $row)<tr><td>{{ $row['date'] }}</td><td class="text-right">{{ number_format((float)$row['forecast_sales'],4) }}</td></tr>@endforeach
</tbody></table></div></div>
@endsection
