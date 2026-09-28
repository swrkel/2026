@extends('layouts.app')
@section('title', $subscription ? 'Edit Subscription' : 'Add Subscription')
@section('content')
@php
$r1 = $subscription ? $subscription->reminders->firstWhere('reminder_no',1) : null;
$r2 = $subscription ? $subscription->reminders->firstWhere('reminder_no',2) : null;
@endphp
<section class="content-header"><h1>{{ $subscription ? 'Edit Subscription' : 'Add Subscription' }}</h1></section>
<section class="content">
@if($errors->any())<div class="alert alert-danger"><ul style="margin-bottom:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="box box-primary"><form method="post" action="{{ $subscription ? route('subscription.estate.update',$subscription->id) : route('subscription.estate.store') }}">@csrf @if($subscription) @method('PUT') @endif
<div class="box-body">
<div class="alert alert-info"><strong>Mobile numbers:</strong> Add multiple numbers separated by commas and include the country code. Example: +9477XXXXXXX,+9471XXXXXXX</div>
@if(!$subscription)
<div class="row">
<div class="col-md-6"><div class="form-group"><label>Tenant Database</label><select name="tenant_ids[]" id="tenant_ids" class="form-control select2" multiple required><option value="__all__" selected>All</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->label }}</option>@endforeach</select><small class="text-muted">Type to filter; scroll and select one, many, or All.</small></div></div>
<div class="col-md-6"><div class="form-group"><label>Selected Tenant UID(s)</label><textarea id="selected_tenant_uids" class="form-control" rows="2" readonly></textarea></div></div>
</div>
<div class="row"><div class="col-md-12"><div class="form-group"><label>Businesses</label><div class="checkbox"><label><input type="checkbox" name="all_businesses" id="all_businesses" value="1" checked> All businesses in selected tenant database(s)</label></div><select name="business_ids[]" id="business_ids" class="form-control select2" multiple></select><small class="text-muted">Uncheck All to select individual businesses.</small></div></div></div>
@else
<div class="row"><div class="col-md-4"><label>Tenant Database</label><input class="form-control" value="{{ $subscription->tenant_database }}" readonly></div><div class="col-md-4"><label>Tenant UID</label><input class="form-control" value="{{ $subscription->tenant_id }}" readonly></div><div class="col-md-4"><label>Business</label><input class="form-control" value="{{ $subscription->business_name }}" readonly></div></div><br>
@endif
<div class="row">
<div class="col-md-3"><div class="form-group"><label>Business Registered On</label><input type="date" name="business_registered_on" id="business_registered_on" class="form-control" value="{{ old('business_registered_on', $subscription ? \Carbon\Carbon::parse($subscription->business_registered_on)->format('Y-m-d') : date('Y-m-d')) }}" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Subscription Period in Days</label><input type="number" min="1" name="subscription_period_days" id="subscription_period_days" class="form-control" value="{{ old('subscription_period_days',$subscription->subscription_period_days ?? '') }}" required></div></div>
<div class="col-md-3"><div class="form-group"><label>Subscription Amount</label><input type="number" min="0" step="0.0001" name="subscription_amount" class="form-control" value="{{ old('subscription_amount',$subscription->subscription_amount ?? '') }}" required></div></div>
<div class="col-md-3"><div class="form-group"><label>System Expiry Date</label><input type="text" id="calculated_expiry" class="form-control" value="{{ $subscription && $subscription->expiry_date ? \Carbon\Carbon::parse($subscription->expiry_date)->format('Y-m-d') : '' }}" readonly></div></div>
</div>
<div class="form-group"><label>Add Business Mobile Numbers</label><input type="text" name="business_mobile_numbers" class="form-control" value="{{ old('business_mobile_numbers',$subscription->business_mobile_numbers ?? '') }}" placeholder="+9477XXXXXXX,+9471XXXXXXX" required></div>
<hr><h4>Subscription Reminder 1</h4><div class="row"><div class="col-md-3"><label>Days Before the Subscription</label><input type="number" min="0" name="reminder_1_days" class="form-control" value="{{ old('reminder_1_days',$r1->days_before ?? '') }}"></div><div class="col-md-9"><label>Message</label><textarea name="reminder_1_message" class="form-control" rows="3" placeholder="Use dynamic tags: {subscription_amount} and {system_expiry_date}">{{ old('reminder_1_message',$r1->message_body ?? "Subscription Amount: {subscription_amount}\nSystem Expiry Date: {system_expiry_date}\n") }}</textarea><small>Available tags: <code>{subscription_amount}</code> <code>{system_expiry_date}</code></small></div></div>
<hr><h4>Subscription Reminder 2</h4><div class="row"><div class="col-md-3"><label>Days Before the Subscription</label><input type="number" min="0" name="reminder_2_days" class="form-control" value="{{ old('reminder_2_days',$r2->days_before ?? '') }}"></div><div class="col-md-9"><label>Message</label><textarea name="reminder_2_message" class="form-control" rows="3" placeholder="Use dynamic tags: {subscription_amount} and {system_expiry_date}">{{ old('reminder_2_message',$r2->message_body ?? "Subscription Amount: {subscription_amount}\nSystem Expiry Date: {system_expiry_date}\n") }}</textarea><small>Available tags: <code>{subscription_amount}</code> <code>{system_expiry_date}</code></small></div></div>
</div>
<div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save</button> <a href="{{ route('subscription.estate.index') }}" class="btn btn-default">Back</a></div></form></div>
</section>
@endsection
@section('javascript')
<script>
$(function(){
 $('.select2').select2({width:'100%'});
 function uids(){ var v=$('#tenant_ids').val()||[]; $('#selected_tenant_uids').val(v.indexOf('__all__')>=0 ? 'All tenant UIDs' : v.join(', ')); }
 function loadBusinesses(){ var tids=$('#tenant_ids').val()||['__all__']; $.get('{{ route('subscription.estate.business-options') }}',{tenant_ids:tids},function(r){ var $b=$('#business_ids').empty(); $.each(r.data||[],function(_,x){$b.append(new Option(x.text,x.id,false,false));}); $b.trigger('change'); }); }
 $('#tenant_ids').on('change',function(){ var v=$(this).val()||[]; if(v.indexOf('__all__')>=0 && v.length>1) $(this).val(['__all__']).trigger('change.select2'); uids(); loadBusinesses(); });
 $('#all_businesses').on('change',function(){ $('#business_ids').prop('disabled',this.checked); });
 function expiry(){ var d=$('#business_registered_on').val(), days=parseInt($('#subscription_period_days').val()||0,10); if(!d||!days){$('#calculated_expiry').val('');return;} var dt=new Date(d+'T00:00:00'); dt.setDate(dt.getDate()+days); $('#calculated_expiry').val(dt.toISOString().slice(0,10)); }
 $('#business_registered_on,#subscription_period_days').on('change keyup',expiry);
 uids(); @if(!$subscription) loadBusinesses(); $('#business_ids').prop('disabled',true); @endif expiry();
});
</script>
@endsection
