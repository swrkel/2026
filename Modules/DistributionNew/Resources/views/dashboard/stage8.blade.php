@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-page disnew-page">
    <div class="pos-card disnew-card">
        <div class="pos-card-header d-flex justify-content-between align-items-center">
            <h4>Distribution New Dashboard</h4>
            <div class="pos-toolbar">Search | Date Range | CSV | Excel | PDF | Print | Column Visibility</div>
        </div>
        <div class="pos-card-body">
            <div class="row disnew-dashboard-widgets">@foreach($summary as $key => $value)<div class="col-md-3 mb-3"><div class="pos-widget"><span>{{ ucwords(str_replace('_',' ', $key)) }}</span><strong>{{ is_numeric($value) ? number_format($value, 2) : $value }}</strong></div></div>@endforeach</div>
        </div>
    </div>
</div>
@endsection
