@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-page disnew-page">
    <div class="pos-card disnew-card">
        <div class="pos-card-header d-flex justify-content-between align-items-center">
            <h4>Distribution New Business Limits</h4>
            <div class="pos-toolbar">Search | Date Range | CSV | Excel | PDF | Print | Column Visibility</div>
        </div>
        <div class="pos-card-body">
            <form method="POST">@csrf<div class="row">@foreach(['max_vehicles','max_sales_reps','max_territories','max_routes','max_warehouses','max_delivery_users','max_customer_portal_users','max_active_orders','max_daily_deliveries'] as $field)<div class="col-md-4"><label>{{ ucwords(str_replace('_',' ', $field)) }}</label><input class="form-control" name="{{ $field }}" value="{{ old($field, $limits->$field) }}"></div>@endforeach</div><button class="btn btn-primary mt-3">Save Limits</button></form>
        </div>
    </div>
</div>
@endsection
