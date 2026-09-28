@extends('autoservice::layouts.master')
@section('title',$inspection->id ? 'Edit Inspection' : 'Add Inspection')
@section('autoservice_content')
<form method="post" action="{{ $inspection->id ? route('autoservice.inspections.update',$inspection->id) : route('autoservice.inspections.store') }}">@csrf @if($inspection->id) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="col-md-4"><label>Customer</label><select name="contact_id" class="form-control"><option value="">Please Select</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected($inspection->contact_id==$c->id)>{{ $c->name }} {{ $c->mobile ? ' - '.$c->mobile : '' }}</option>@endforeach</select></div>
<div class="col-md-4"><label>Vehicle</label><select name="vehicle_id" class="form-control"><option value="">Please Select</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected($inspection->vehicle_id==$v->id)>{{ $v->registration_no }} - {{ $v->make }} {{ $v->model }}</option>@endforeach</select></div>
<div class="col-md-2"><label>Date</label><input type="date" name="inspection_date" value="{{ $inspection->inspection_date ?: date('Y-m-d') }}" class="form-control"></div>
<div class="col-md-2"><label>Status</label><select name="status" class="form-control">@foreach(['draft','completed','approved'] as $st)<option value="{{ $st }}" @selected(($inspection->status ?: 'draft')==$st)>{{ ucfirst($st) }}</option>@endforeach</select></div>
<div class="col-md-3"><label>Odometer</label><input name="odometer" class="form-control" value="{{ $inspection->odometer }}"></div>
<div class="col-md-3"><label>Fuel Level</label><input name="fuel_level" class="form-control" value="{{ $inspection->fuel_level }}"></div>
<div class="col-md-12"><label>Customer Remarks</label><textarea name="customer_remarks" class="form-control">{{ $inspection->customer_remarks }}</textarea></div>
<div class="col-md-12"><label>Advisor Remarks</label><textarea name="advisor_remarks" class="form-control">{{ $inspection->advisor_remarks }}</textarea></div>
<div class="col-md-12"><h4>Checklist</h4><table class="table table-bordered"><thead><tr><th>Section</th><th>Item</th><th>Condition</th><th>Note</th></tr></thead><tbody>
@php($defaultItems = [['Engine','Engine Oil'],['Brakes','Brake Pads'],['Tyres','Tyre Condition'],['Electrical','Lights'],['Battery','Battery'],['Suspension','Suspension'],['AC','Air Conditioning']])
@php($items = $inspection->items && $inspection->items->count() ? $inspection->items->map(fn($x)=>[$x->section,$x->item_name,$x->condition,$x->note])->toArray() : $defaultItems)
@foreach($items as $i=>$it)<tr><td><input name="items[{{ $i }}][section]" class="form-control" value="{{ $it[0] ?? '' }}"></td><td><input name="items[{{ $i }}][item_name]" class="form-control" value="{{ $it[1] ?? '' }}"></td><td><select name="items[{{ $i }}][condition]" class="form-control"><option value="">Please Select</option>@foreach(['good','attention','urgent','not_applicable'] as $cond)<option value="{{ $cond }}" @selected(($it[2] ?? '')==$cond)>{{ ucfirst(str_replace('_',' ',$cond)) }}</option>@endforeach</select></td><td><input name="items[{{ $i }}][note]" class="form-control" value="{{ $it[3] ?? '' }}"></td></tr>@endforeach
</tbody></table></div>
</div><div class="box-footer"><button class="btn btn-primary">Save Inspection</button><a href="{{ route('autoservice.inspections.index') }}" class="btn btn-default">Back</a></div></div>
</form>
@endsection
