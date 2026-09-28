@extends('petropdnew::layouts.app')
@section('title','Edit Petro PD-New Settlement')
@section('page_title','Edit PD Settlement')
@section('pdnew_content')
<div class="pdn-page-head"><div><h2>{{ $settlement->settlement_number }}</h2><p>Only settlement date and notes are editable; PONE source values remain immutable.</p></div><a class="pdn-btn light" href="{{ route('petro-pd-new.settlements.show',$settlement->id) }}">Back</a></div>
<div class="pdn-card"><form method="post" action="{{ route('petro-pd-new.settlements.update',$settlement->id) }}">@csrf @method('put')
<div class="pdn-form-grid">
    <div class="pdn-field"><label>Settlement Date</label><input class="pdn-input" type="date" name="settlement_date" value="{{ old('settlement_date',optional($settlement->settlement_date)->format('Y-m-d')) }}" required></div>
    <div class="pdn-field"><label>PONE Shift</label><input class="pdn-input" value="{{ $settlement->pone_shift_number }}" disabled></div>
    <div class="pdn-field"><label>Operator</label><input class="pdn-input" value="{{ $settlement->operator_name }}" disabled></div>
    <div class="pdn-field full"><label>Notes</label><textarea class="pdn-textarea" name="notes">{{ old('notes',$settlement->notes) }}</textarea></div>
</div><div class="pdn-actions" style="margin-top:14px"><button class="pdn-btn primary">Save Changes</button></div>
</form></div>
@endsection
