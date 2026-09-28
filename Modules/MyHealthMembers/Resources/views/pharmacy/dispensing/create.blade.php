@extends('layouts.app')
@section('title', 'New Medicine Dispense')
@section('content')
<section class="content-header"><h1>New Medicine Dispense</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="GET" action="{{ route('myhealth.pharmacy.dispensing.create') }}" class="mb-3"><div class="row"><div class="col-md-6"><label>Select Member to Load Prescriptions</label><select name="member_id" class="form-control" onchange="this.form.submit()"><option value="">Select Member</option>@foreach($members as $member)<option value="{{ $member->id }}" {{ request('member_id') == $member->id ? 'selected' : '' }}>{{ $member->myhealth_code }} - {{ $member->name }} - {{ $member->mobile }}</option>@endforeach</select></div></div></form>
<form method="POST" action="{{ route('myhealth.pharmacy.dispensing.store') }}">@csrf
<input type="hidden" name="member_id" value="{{ request('member_id') }}">
<div class="row">
<div class="col-md-4 form-group"><label>Dispense Date</label><input type="date" name="dispense_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="col-md-8 form-group"><label>Prescription</label><select name="prescription_id" class="form-control"><option value="">Manual / No Prescription</option>@foreach($prescriptions as $prescription)<option value="{{ $prescription->id }}">{{ $prescription->prescription_date }} - {{ Str::limit($prescription->prescription_details, 80) }}</option>@endforeach</select></div>
</div>
<div class="table-responsive"><table class="table table-bordered" id="dispense-items"><thead><tr><th>Medicine</th><th>Batch</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Qty</th><th>Unit Price</th><th>Instructions</th></tr></thead><tbody>
@for($i=0; $i<5; $i++)<tr>
<td><select name="items[{{ $i }}][medicine_id]" class="form-control"><option value="">Select</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->medicine_name }}</option>@endforeach</select></td>
<td><select name="items[{{ $i }}][batch_id]" class="form-control"><option value="">FIFO Auto</option>@foreach($medicines as $medicine) @foreach($medicine->batches->where('available_qty', '>', 0) as $batch)<option value="{{ $batch->id }}">{{ $medicine->medicine_name }} / {{ $batch->batch_no }} / {{ number_format($batch->available_qty, 4) }}</option>@endforeach @endforeach</select></td>
<td><input name="items[{{ $i }}][dosage]" class="form-control"></td><td><input name="items[{{ $i }}][frequency]" class="form-control"></td><td><input name="items[{{ $i }}][duration]" class="form-control"></td><td><input type="number" step="0.0001" name="items[{{ $i }}][quantity]" class="form-control"></td><td><input type="number" step="0.0001" name="items[{{ $i }}][unit_price]" class="form-control"></td><td><input name="items[{{ $i }}][instructions]" class="form-control"></td>
</tr>@endfor
</tbody></table></div>
<div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
<button class="btn btn-primary" {{ request('member_id') ? '' : 'disabled' }}>Save Dispense</button> <a href="{{ route('myhealth.pharmacy.dispensing.index') }}" class="btn btn-default">Cancel</a>
</form></div></div></section>
@endsection
