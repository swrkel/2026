<div class="disnew-pos-card">
<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <div class="row">
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.vehicle_no') }}</label><input name="vehicle_no" class="form-control" value="{{ old('vehicle_no', optional($vehicle)->vehicle_no) }}" required></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.vehicle_name') }}</label><input name="vehicle_name" class="form-control" value="{{ old('vehicle_name', optional($vehicle)->vehicle_name) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.vehicle_type') }}</label><input name="vehicle_type" class="form-control" value="{{ old('vehicle_type', optional($vehicle)->vehicle_type) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.status') }}</label><select name="status" class="form-control"><option value="active">Active</option><option value="maintenance">Maintenance</option><option value="inactive">Inactive</option></select></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.capacity_qty') }}</label><input name="capacity_qty" class="form-control input_number" value="{{ old('capacity_qty', optional($vehicle)->capacity_qty) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.capacity_volume') }}</label><input name="capacity_volume" class="form-control input_number" value="{{ old('capacity_volume', optional($vehicle)->capacity_volume) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.driver_name') }}</label><input name="driver_name" class="form-control" value="{{ old('driver_name', optional($vehicle)->driver_name) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.driver_mobile') }}</label><input name="driver_mobile" class="form-control" value="{{ old('driver_mobile', optional($vehicle)->driver_mobile) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.helper_name') }}</label><input name="helper_name" class="form-control" value="{{ old('helper_name', optional($vehicle)->helper_name) }}"></div></div>
        <div class="col-md-3"><div class="form-group"><label>{{ __('distributionnew::lang.helper_mobile') }}</label><input name="helper_mobile" class="form-control" value="{{ old('helper_mobile', optional($vehicle)->helper_mobile) }}"></div></div>
        <div class="col-md-6"><div class="form-group"><label>{{ __('distributionnew::lang.note') }}</label><textarea name="note" class="form-control">{{ old('note', optional($vehicle)->note) }}</textarea></div></div>
    </div>
    <button class="btn btn-primary">{{ __('distributionnew::lang.save') }}</button>
    <a href="{{ route('distributionnew.vehicles.index') }}" class="btn btn-default">{{ __('distributionnew::lang.cancel') }}</a>
</form>
</div>
