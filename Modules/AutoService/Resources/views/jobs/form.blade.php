@extends('autoservice::layouts.master')
@section('title', $job->id ? 'Edit Job' : 'Add Job')
@section('autoservice_content')
<form method="post" action="{{ $job->id ? route('autoservice.jobs.update',$job->id) : route('autoservice.jobs.store') }}">@csrf @if($job->id) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="form-group col-md-3"><label>Customer</label><select name="contact_id" class="form-control"><option value="">Please Select</option>@foreach($customers as $c)<option value="{{ $c->id }}" {{ $job->contact_id==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
<div class="form-group col-md-3"><label>Vehicle</label><select required name="vehicle_id" class="form-control"><option value="">Please Select</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" {{ $job->vehicle_id==$v->id?'selected':'' }}>{{ $v->registration_no }} - {{ $v->make }} {{ $v->model }}</option>@endforeach</select></div>
<div class="form-group col-md-2"><label>Job Date</label><input type="date" name="job_date" class="form-control" value="{{ old('job_date',$job->job_date ?: date('Y-m-d')) }}"></div>
<div class="form-group col-md-2"><label>Job Type</label><input name="job_type" class="form-control" value="{{ old('job_type',$job->job_type) }}"></div>
<div class="form-group col-md-2"><label>Status</label><select name="status" class="form-control">@foreach(['received','inspection','estimate','approved','work_in_progress','quality_check','ready','delivered'] as $s)<option value="{{ $s }}" {{ $job->status==$s?'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group col-md-3"><label>Odometer</label><input type="number" name="odometer" class="form-control" value="{{ old('odometer',$job->odometer) }}"></div>
<div class="form-group col-md-3"><label>Estimated Delivery</label><input type="datetime-local" name="estimated_delivery_at" class="form-control" value="{{ old('estimated_delivery_at',$job->estimated_delivery_at ? str_replace(' ','T',substr($job->estimated_delivery_at,0,16)) : '') }}"></div>
<div class="form-group col-md-3"><label>Next Service Date</label><input type="date" name="next_service_date" class="form-control" value="{{ old('next_service_date',$job->next_service_date) }}"></div>
<div class="form-group col-md-3"><label>Next Service Odometer</label><input type="number" name="next_service_odometer" class="form-control" value="{{ old('next_service_odometer',$job->next_service_odometer) }}"></div>
<div class="form-group col-md-6"><label>Customer Complaint</label><textarea name="customer_complaint" class="form-control">{{ old('customer_complaint',$job->customer_complaint) }}</textarea></div>
<div class="form-group col-md-6"><label>Advisor Notes</label><textarea name="advisor_notes" class="form-control">{{ old('advisor_notes',$job->advisor_notes) }}</textarea></div>
</div></div>

<div class="box"><div class="box-header"><h3 class="box-title">Load Service Package</h3></div><div class="box-body row">
<div class="col-md-6"><select id="service-package-select" class="form-control"><option value="">Select a package</option>@foreach($packages as $p)<option value="{{ $p->id }}">{{ $p->package_code }} - {{ $p->name }} ({{ number_format($p->selling_price ?: $p->total_amount,2) }})</option>@endforeach</select></div>
<div class="col-md-2"><button type="button" id="load-service-package" class="btn btn-success">Load Package</button></div>
<div class="col-md-4"><small>Mandatory components load automatically. Optional components can be removed before saving.</small></div>
<div id="selected-package-ids"></div>
</div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Service / Parts Lines</h3></div><div class="box-body table-responsive"><table class="table table-bordered auto-lines"><thead><tr><th>Type</th><th>Product</th><th>Description</th><th>Qty</th><th>Unit Price</th></tr></thead><tbody>
@php $lines = old('lines', $job->lines ? $job->lines->toArray() : [[]]); @endphp
@for($i=0;$i<max(1,count($lines));$i++)<tr><td><select name="lines[{{ $i }}][line_type]" class="form-control"><option value="service">Service</option><option value="part">Part</option><option value="manual">Manual</option></select></td><td><select name="lines[{{ $i }}][product_id]" class="form-control"><option value="">Manual / Please Select</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td><td><input name="lines[{{ $i }}][description]" class="form-control" value="{{ $lines[$i]['description'] ?? '' }}"></td><td><input name="lines[{{ $i }}][quantity]" type="number" step="0.0001" class="form-control" value="{{ $lines[$i]['quantity'] ?? 1 }}"></td><td><input name="lines[{{ $i }}][unit_price]" type="number" step="0.0001" class="form-control" value="{{ $lines[$i]['unit_price'] ?? 0 }}"></td></tr>@endfor
</tbody></table><button type="button" class="btn btn-default btn-sm auto-add-line">Add Line</button></div></div>
<div class="box"><div class="box-body row"><div class="form-group col-md-3"><label>Discount</label><input name="discount_amount" type="number" step="0.0001" class="form-control" value="{{ old('discount_amount',$job->discount_amount ?: 0) }}"></div><div class="form-group col-md-3"><label>Tax</label><input name="tax_amount" type="number" step="0.0001" class="form-control" value="{{ old('tax_amount',$job->tax_amount ?: 0) }}"></div><div class="form-group col-md-3"><label>Payment Amount</label><input name="payments[0][amount]" type="number" step="0.0001" class="form-control" value="{{ optional($job->payments->first())->amount ?? 0 }}"></div><div class="form-group col-md-3"><label>Payment Method</label><select name="payments[0][payment_method]" class="form-control"><option value="cash">Cash</option><option value="card">Card</option><option value="credit">Credit</option><option value="cheque">Cheque</option></select></div></div><div class="box-footer"><button class="btn btn-primary">Save Job</button></div></div>
</form>
@endsection

<script>
(function(){
 let nextIndex=document.querySelectorAll('.auto-lines tbody tr').length;
 const tbody=document.querySelector('.auto-lines tbody');
 document.getElementById('load-service-package')?.addEventListener('click',async function(){
   const id=document.getElementById('service-package-select').value;
   if(!id) return;
   const res=await fetch("{{ url('auto-service/packages') }}/"+id+"/payload",{headers:{'Accept':'application/json'}});
   if(!res.ok){ alert('Unable to load package.'); return; }
   const data=await res.json();
   const hidden=document.createElement('input'); hidden.type='hidden'; hidden.name='package_ids[]'; hidden.value=data.id;
   document.getElementById('selected-package-ids').appendChild(hidden);
   data.lines.forEach(function(line){
      const tr=document.createElement('tr');
      tr.innerHTML=`<td><select name="lines[${nextIndex}][line_type]" class="form-control"><option value="${line.line_type}">${line.line_type}</option></select>
      <input type="hidden" name="lines[${nextIndex}][component_type]" value="${line.component_type||''}">
      <input type="hidden" name="lines[${nextIndex}][package_id]" value="${line.package_id||''}">
      <input type="hidden" name="lines[${nextIndex}][package_line_id]" value="${line.package_line_id||''}">
      <input type="hidden" name="lines[${nextIndex}][variation_id]" value="${line.variation_id||''}">
      <input type="hidden" name="lines[${nextIndex}][is_stock_item]" value="${line.is_stock_item?1:0}"></td>
      <td><input type="hidden" name="lines[${nextIndex}][product_id]" value="${line.product_id||''}">${line.product_id||'Manual'}</td>
      <td><input name="lines[${nextIndex}][description]" class="form-control" value="${String(line.description||'').replace(/"/g,'&quot;')}">${line.is_optional?'<small>Optional</small>':''}</td>
      <td><input name="lines[${nextIndex}][quantity]" type="number" step="0.0001" class="form-control" value="${line.quantity}"></td>
      <td><input name="lines[${nextIndex}][unit_price]" type="number" step="0.0001" class="form-control" value="${line.unit_price}"></td>`;
      tbody.appendChild(tr); nextIndex++;
   });
 });
})();
</script>
