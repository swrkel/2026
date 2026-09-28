@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Edit Stock Transfer','subtitle'=>$transfer->transfer_no])
<form method="post" action="{{ route('stock-transfer-new.transfers.update',$transfer) }}" class="stn-card">@csrf @method('PUT')
 <div class="stn-grid-4">
  <label>Transfer No<input name="transfer_no" value="{{ $transfer->transfer_no }}" required></label>
  <label>Date<input type="date" name="transfer_date" value="{{ optional($transfer->transfer_date)->format('Y-m-d') ?: $transfer->transfer_date }}" required></label>
  <label>From Location<select name="from_location_id"><option value="">All / None</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" @selected($transfer->from_location_id==$id)>{{ $name }}</option>@endforeach</select></label>
  <label>To Location<select name="to_location_id"><option value="">All / None</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" @selected($transfer->to_location_id==$id)>{{ $name }}</option>@endforeach</select></label>
  <label>From Store<select name="from_store_id"><option value="">Main Store</option>@foreach($stores as $id=>$name)<option value="{{ $id }}" @selected($transfer->from_store_id==$id)>{{ $name }}</option>@endforeach</select></label>
  <label>To Store<select name="to_store_id"><option value="">Main Store</option>@foreach($stores as $id=>$name)<option value="{{ $id }}" @selected($transfer->to_store_id==$id)>{{ $name }}</option>@endforeach</select></label>
  <label>Vehicle No<input name="vehicle_no" value="{{ $transfer->vehicle_no }}"></label>
  <label>Driver Mobile<input name="driver_mobile" value="{{ $transfer->driver_mobile }}"></label>
 </div>
 <label>Driver Name<input name="driver_name" value="{{ $transfer->driver_name }}"></label>
 <label>Reason<textarea name="reason">{{ $transfer->reason }}</textarea></label>
 <label>Remarks<textarea name="remarks">{{ $transfer->remarks }}</textarea></label>
 <h4>Items</h4>
 <table class="stn-table" id="stn-lines"><thead><tr><th>Product</th><th>Requested Qty</th><th>Unit Cost</th><th>Batch</th><th>Expiry</th><th>Remarks</th></tr></thead><tbody>
 @foreach($transfer->lines as $i=>$line)
  <tr><td><select name="lines[{{ $i }}][product_id]"><option value="">Select</option>@foreach($products as $id=>$name)<option value="{{ $id }}" @selected($line->product_id==$id)>{{ $name }}</option>@endforeach</select></td><td><input type="number" step="0.0001" name="lines[{{ $i }}][qty_requested]" value="{{ $line->qty_requested }}"></td><td><input type="number" step="0.0001" name="lines[{{ $i }}][unit_cost]" value="{{ $line->unit_cost }}"></td><td><input name="lines[{{ $i }}][batch_no]" value="{{ $line->batch_no }}"></td><td><input type="date" name="lines[{{ $i }}][expiry_date]" value="{{ $line->expiry_date }}"></td><td><input name="lines[{{ $i }}][remarks]" value="{{ $line->remarks }}"></td></tr>
 @endforeach
 @for($j=$transfer->lines->count();$j<$transfer->lines->count()+3;$j++)
  <tr><td><select name="lines[{{ $j }}][product_id]"><option value="">Select</option>@foreach($products as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></td><td><input type="number" step="0.0001" name="lines[{{ $j }}][qty_requested]"></td><td><input type="number" step="0.0001" name="lines[{{ $j }}][unit_cost]"></td><td><input name="lines[{{ $j }}][batch_no]"></td><td><input type="date" name="lines[{{ $j }}][expiry_date]"></td><td><input name="lines[{{ $j }}][remarks]"></td></tr>
 @endfor
 </tbody></table>
 <button class="stn-btn primary">Update Draft</button>
 <a class="stn-btn" href="{{ route('stock-transfer-new.transfers.show',$transfer) }}">Back</a>
</form>
@endsection
