@extends('layouts.app')
@section('title', __('stocktransfernew::lang.add_schedule'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.add_schedule')</h1></section>
<section class="content stn-page">
<form method="post" action="{{ route('stock-transfer-new.schedules.store') }}">@csrf
<div class="box box-solid"><div class="box-body row">
    <div class="col-md-4"><label>@lang('stocktransfernew::lang.name')</label><input name="schedule_name" class="form-control" required></div>
    <div class="col-md-2"><label>@lang('stocktransfernew::lang.type')</label><select name="schedule_type" class="form-control"><option value="one_time">One Time</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
    <div class="col-md-3"><label>@lang('stocktransfernew::lang.next_run')</label><input type="date" name="next_run_date" class="form-control" required></div>
    <div class="col-md-3"><label>@lang('stocktransfernew::lang.run_time')</label><input type="time" name="run_time" class="form-control"></div>
    <div class="col-md-12"><label>@lang('stocktransfernew::lang.remarks')</label><textarea name="remarks" class="form-control"></textarea></div>
</div></div>
<div class="box box-solid"><div class="box-header"><h3 class="box-title">@lang('stocktransfernew::lang.schedule_items')</h3></div><div class="box-body">
    <p class="text-muted">Use Product module lookup/selector in server screen. Product master is not duplicated here.</p>
    <table class="table table-bordered stn-repeat-table"><thead><tr><th>Product ID</th><th>Variation ID</th><th>Unit ID</th><th>Qty</th><th>Remarks</th></tr></thead><tbody>
    @for($i=0;$i<5;$i++)<tr><td><input name="items[{{ $i }}][product_id]" class="form-control"></td><td><input name="items[{{ $i }}][variation_id]" class="form-control"></td><td><input name="items[{{ $i }}][unit_id]" class="form-control"></td><td><input name="items[{{ $i }}][qty]" class="form-control input_number"></td><td><input name="items[{{ $i }}][remarks]" class="form-control"></td></tr>@endfor
    </tbody></table>
    <button class="btn btn-primary btn-lg">@lang('messages.save')</button>
</div></div>
</form>
</section>
@endsection
