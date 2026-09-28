@extends('autoservice::layouts.master')
@section('title',$estimate->id ? 'Edit Estimate' : 'Add Estimate')
@section('autoservice_content')
<form method="post" action="{{ $estimate->id ? route('autoservice.estimates.update',$estimate->id) : route('autoservice.estimates.store') }}">@csrf @if($estimate->id) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="col-md-4"><label>Customer</label><select name="contact_id" class="form-control"><option value="">Please Select</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected($estimate->contact_id==$c->id)>{{ $c->name }} {{ $c->mobile ? ' - '.$c->mobile : '' }}</option>@endforeach</select></div>
<div class="col-md-4"><label>Vehicle</label><select name="vehicle_id" class="form-control"><option value="">Please Select</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected($estimate->vehicle_id==$v->id)>{{ $v->registration_no }} - {{ $v->make }} {{ $v->model }}</option>@endforeach</select></div>
<div class="col-md-2"><label>Estimate Date</label><input type="date" name="estimate_date" value="{{ $estimate->estimate_date ?: date('Y-m-d') }}" class="form-control"></div>
<div class="col-md-2"><label>Valid Until</label><input type="date" name="valid_until" value="{{ $estimate->valid_until }}" class="form-control"></div>
<div class="col-md-12"><label>Customer Complaint</label><textarea name="customer_complaint" class="form-control">{{ $estimate->customer_complaint }}</textarea></div>
<div class="col-md-12"><label>Advisor Notes</label><textarea name="advisor_notes" class="form-control">{{ $estimate->advisor_notes }}</textarea></div>
<div class="col-md-12"><h4>Estimate Lines</h4><table class="table table-bordered autoservice-lines"><thead><tr><th>Type</th><th>Product/Part</th><th>Description</th><th>Qty</th><th>Unit Price</th></tr></thead><tbody>
@php($lines = $estimate->lines && $estimate->lines->count() ? $estimate->lines : collect([null,null,null]))
@foreach($lines as $i=>$line)<tr><td><select name="lines[{{ $i }}][line_type]" class="form-control"><option value="service">Service</option><option value="part" @selected(optional($line)->line_type=='part')>Part</option><option value="other" @selected(optional($line)->line_type=='other')>Other</option></select></td><td><select name="lines[{{ $i }}][product_id]" class="form-control"><option value="">Manual/None</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(optional($line)->product_id==$p->id)>{{ $p->name }} {{ isset($p->sku)?' - '.$p->sku:'' }}</option>@endforeach</select></td><td><input name="lines[{{ $i }}][description]" class="form-control" value="{{ optional($line)->description }}"></td><td><input name="lines[{{ $i }}][quantity]" class="form-control" value="{{ optional($line)->quantity ?: 1 }}"></td><td><input name="lines[{{ $i }}][unit_price]" class="form-control" value="{{ optional($line)->unit_price ?: 0 }}"></td></tr>@endforeach
</tbody></table></div>
<div class="col-md-3"><label>Discount</label><input name="discount_amount" class="form-control" value="{{ $estimate->discount_amount ?: 0 }}"></div>
<div class="col-md-3"><label>Tax</label><input name="tax_amount" class="form-control" value="{{ $estimate->tax_amount ?: 0 }}"></div>
<div class="col-md-3"><label>Status</label><select name="status" class="form-control">@foreach(['draft','sent','approved','rejected','converted'] as $st)<option value="{{ $st }}" @selected(($estimate->status ?: 'draft')==$st)>{{ ucfirst($st) }}</option>@endforeach</select></div>
</div><div class="box-footer"><button class="btn btn-primary">Save Estimate</button><a href="{{ route('autoservice.estimates.index') }}" class="btn btn-default">Back</a></div></div>
</form>
@endsection
