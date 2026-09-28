@extends('petropdnew::layouts.app')
@section('title','Add Petro PD-New Settlement')
@section('page_title','Add PD Settlement')
@section('pdnew_content')
<div class="pdn-page-head"><div><h2>Create from PONE Closed Shift</h2><p>Manual settlements without a Pumper Dashboard-New source are not permitted.</p></div><a class="pdn-btn light" href="{{ route('petro-pd-new.settlements.index') }}">Back</a></div>
<div class="pdn-card">
<form method="post" action="{{ route('petro-pd-new.settlements.store') }}" data-prevent-double-submit>@csrf
<div class="pdn-form-grid">
    <div class="pdn-field full"><label>Closed Pumper Dashboard-New Shift</label><select class="pdn-select" name="shift_id" required>
        <option value="">Select closed shift</option>
        @foreach($shifts as $shift)<option value="{{ $shift->id }}" @selected(old('shift_id')==$shift->id)>
            {{ $shift->shift_number }} — {{ $shift->operator_name ?: 'Operator #'.$shift->operator_profile_id }} — {{ $shift->closed_at }} — {{ number_format((float)$shift->expected_total,4) }}
        </option>@endforeach
    </select></div>
    <div class="pdn-field"><label>Settlement Date</label><input class="pdn-input" type="date" name="settlement_date" value="{{ old('settlement_date',now()->toDateString()) }}"></div>
    <div class="pdn-field full"><label>Notes</label><textarea class="pdn-textarea" name="notes">{{ old('notes') }}</textarea></div>
</div>
<div class="pdn-actions" style="margin-top:14px"><button class="pdn-btn success">Create Settlement</button></div>
</form>
@if($shifts->isEmpty())<div class="pdn-alert warning" style="margin-top:14px">There are no unlinked closed Pumper Dashboard-New shifts.</div>@endif
</div>
@endsection
