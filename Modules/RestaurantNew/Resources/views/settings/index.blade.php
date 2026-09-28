@extends('restaurantnew::layouts.app')
@section('rest_title','Restaurant Settings')
@section('rest_subtitle','Business-specific numbering, printing, pricing, workflow and user screen assignments.')
@section('rest_content')
<form method="post" action="{{ route('restaurant-new.settings.update') }}">
    @csrf @method('put')
    <div class="rest-grid-2">
        <section class="rest-card">
            <div class="rest-card-head"><h3>General Settings</h3></div>
            <div class="rest-form-grid rest-form-grid-2">
                <label>Restaurant Display Name<input name="restaurant_name" value="{{ $settings['restaurant_name']??'' }}"></label>
                <label>Default Location<select name="default_location_id"><option value="">Use current location</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string)($settings['default_location_id']??'')===(string)$location->id)>{{ $location->name }}{{ $location->location_id?' ('.$location->location_id.')':'' }}</option>@endforeach</select></label>
                <label>Currency Decimals<input type="number" min="0" max="6" name="currency_decimals" value="{{ $settings['currency_decimals']??4 }}"></label>
                <label>Quantity Decimals<input type="number" min="0" max="6" name="quantity_decimals" value="{{ $settings['quantity_decimals']??4 }}"></label>
                <label>Default Service Charge %<input type="number" step="0.0001" min="0" name="service_charge_rate" value="{{ $settings['service_charge_rate']??0 }}"></label>
                <label>Default Tax %<input type="number" step="0.0001" min="0" name="tax_rate" value="{{ $settings['tax_rate']??0 }}"></label>
            </div>
        </section>
        <section class="rest-card">
            <div class="rest-card-head"><h3>Printing & Workflow</h3></div>
            <div class="rest-form-grid rest-form-grid-2">
                <label>KOT Paper<select name="kot_paper_size"><option @selected(($settings['kot_paper_size']??'80mm')==='58mm')>58mm</option><option @selected(($settings['kot_paper_size']??'80mm')==='80mm')>80mm</option><option @selected(($settings['kot_paper_size']??'80mm')==='A4')>A4</option></select></label>
                <label>Bill Paper<select name="bill_paper_size"><option @selected(($settings['bill_paper_size']??'80mm')==='58mm')>58mm</option><option @selected(($settings['bill_paper_size']??'80mm')==='80mm')>80mm</option><option @selected(($settings['bill_paper_size']??'80mm')==='A4')>A4</option></select></label>
                <label class="rest-check"><input type="checkbox" name="auto_print_kot" value="1" @checked(($settings['auto_print_kot']??'0')==='1')> Auto print KOT</label>
                <label class="rest-check"><input type="checkbox" name="auto_print_bill" value="1" @checked(($settings['auto_print_bill']??'0')==='1')> Auto print bill</label>
                <label class="rest-check"><input type="checkbox" name="require_customer_for_takeaway" value="1" @checked(($settings['require_customer_for_takeaway']??'0')==='1')> Require takeaway customer</label>
            </div>
        </section>
    </div>
    <button class="rest-btn rest-btn-primary"><i class="fa fa-save"></i> Save Restaurant Settings</button>
</form>

@can('restaurant_new.screen_assignments.manage')
<div class="rest-grid-2 rest-settings-assignments">
    <section class="rest-card">
        <div class="rest-card-head"><div><h3>User Screen Assignment</h3><p>Assign each operational user to a dedicated screen and location.</p></div></div>
        <form method="post" action="{{ route('restaurant-new.settings.assignments.store') }}">
            @csrf
            <div class="rest-form-grid rest-form-grid-2">
                <label>User<select name="user_id" required><option value="">Select user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ trim($user->surname.' '.$user->first_name.' '.$user->last_name) ?: 'User #'.$user->id }}</option>@endforeach</select></label>
                <label>Screen Role<select name="screen_role" id="rest-screen-role" required><option value="waiter">Waiter</option><option value="cashier">Cashier</option><option value="kitchen">Kitchen</option><option value="takeaway">Takeaway</option><option value="collection">Collection Centre</option></select></label>
                <label>Business Location<select name="location_id"><option value="">All permitted locations</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></label>
                <label>Kitchen Station<select name="station_id" id="rest-screen-station"><option value="">All stations</option>@foreach($stations as $station)<option value="{{ $station->id }}">{{ $station->name }}</option>@endforeach</select></label>
                <label class="rest-check"><input type="checkbox" name="is_active" value="1" checked> Assignment active</label>
            </div>
            <button class="rest-btn rest-btn-success"><i class="fa fa-user-plus"></i> Save Assignment</button>
        </form>
    </section>
    <section class="rest-card">
        <div class="rest-card-head"><div><h3>Current Assignments</h3><p>{{ $assignments->count() }} active/inactive mappings</p></div></div>
        <div class="table-responsive">
            <table class="rest-table">
                <thead><tr><th>User</th><th>Screen</th><th>Location / Station</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>{{ trim(($assignment->user?->surname??'').' '.($assignment->user?->first_name??'').' '.($assignment->user?->last_name??'')) ?: 'User #'.$assignment->user_id }}</td>
                        <td>{{ ucwords(str_replace('_',' ',$assignment->screen_role)) }}</td>
                        <td>{{ $assignment->location?->name ?: 'All permitted locations' }}<small>{{ $assignment->station?->name }}</small></td>
                        <td>@include('restaurantnew::partials.status',['status'=>$assignment->is_active?'active':'inactive'])</td>
                        <td><form method="post" action="{{ route('restaurant-new.settings.assignments.destroy',$assignment) }}" onsubmit="return confirm('Remove this screen assignment?')">@csrf @method('delete')<button class="rest-icon-btn rest-danger"><i class="fa fa-trash"></i></button></form></td>
                    </tr>
                @empty<tr><td colspan="5" class="rest-empty">No user screen assignments yet.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endcan
@endsection
