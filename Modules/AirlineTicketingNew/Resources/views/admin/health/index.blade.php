@extends('airlineticketingnew::layouts.app')
@section('atn-title','Airline Ticketing Health Check')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="atn-panel-header"><h4>Status: {{ $health['status'] }}</h4></div><div class="atn-panel-body"><p><strong>Database:</strong> {{ $health['database_connection'] }}</p><p><strong>Checked:</strong> {{ $health['checked_at'] }}</p><p><strong>Missing tables:</strong> {{ empty($health['missing_tables']) ? 'None' : implode(', ',$health['missing_tables']) }}</p></div></div>
@endsection
