@php
    $record = $record ?? null;
    $rows = $rows ?? collect();
    $options = $options ?? [];
    $readonly = in_array($action, ['view', 'print'], true);
    $isVoid = $action === 'void';
    $title = ucwords(str_replace('-', ' ', $type)) . ' — ' . ucfirst($action);
@endphp
<div class="modal-dialog modal-lg pdn-workspace-modal" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">{{ $title }}</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            @if($isVoid)
                @php
                    $voidRoute = match($type) {
                        'day-entry' => route('petro-pd-new.operators.workspace.day-entries.void', $record->id),
                        'payment' => route('petro-pd-new.operators.workspace.payments.void', $record->id),
                        'unload' => route('petro-pd-new.operators.workspace.unloads.void', $record->id),
                        'variance' => route('petro-pd-new.operators.workspace.variance.void', [$record->variance_kind, $record->id]),
                        default => '#',
                    };
                @endphp
                <form action="{{ $voidRoute }}" method="post" data-pdn-workspace-form>
                    @csrf @method('DELETE')
                    <div class="alert alert-warning">This action preserves the audit trail and marks the record as void. It does not erase historical data.</div>
                    <div class="pdn-field"><label>Reason <span class="text-danger">*</span></label><textarea class="pdn-input" name="reason" rows="4" required></textarea></div>
                    <div class="pdn-modal-actions"><button class="pdn-btn danger" type="submit">Confirm Void</button></div>
                </form>
            @elseif($type === 'day-entry')
                @php
                    $create = $action === 'create';
                    $url = $create ? route('petro-pd-new.operators.workspace.day-entries.store') : route('petro-pd-new.operators.workspace.day-entries.update', $record->id);
                @endphp
                <form action="{{ $url }}" method="post" data-pdn-workspace-form>
                    @csrf @unless($create) @method('PUT') @endunless
                    <div class="pdn-form-grid three">
                        @if($create)
                            <div class="pdn-field"><label>Shift</label><select class="pdn-select" name="shift_id" required>@foreach(data_get($options,'shifts',collect()) as $shift)<option value="{{ $shift->id }}">{{ $shift->shift_number }} — {{ $shift->operator_name }}</option>@endforeach</select></div>
                        @else
                            <div class="pdn-field"><label>Shift</label><input class="pdn-input" value="{{ $record->shift_number }}" readonly></div>
                        @endif
                        <div class="pdn-field"><label>Entry Type</label><select class="pdn-select" name="entry_type" @disabled($readonly)>@foreach(['note','incident','expense','deposit','meter','testing','other'] as $entryType)<option value="{{ $entryType }}" @selected(($record->entry_type ?? 'other') === $entryType)>{{ ucfirst($entryType) }}</option>@endforeach</select></div>
                        <div class="pdn-field"><label>Entry Date / Time</label><input class="pdn-input" type="datetime-local" name="entry_at" value="{{ $record && $record->entry_at ? \Carbon\Carbon::parse($record->entry_at)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Assignment</label><select class="pdn-select" name="assignment_id" @disabled($readonly)><option value="">None</option>@foreach(data_get($options,'assignments',collect()) as $assignment)<option value="{{ $assignment->id }}" @selected((int)($record->assignment_id ?? 0)===(int)$assignment->id)>Pump #{{ $assignment->pump_id }}</option>@endforeach</select></div>
                        <div class="pdn-field"><label>Reference No.</label><input class="pdn-input" name="reference_no" value="{{ $record->reference_no ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Quantity</label><input class="pdn-input" type="number" step="0.000001" min="0" name="quantity" value="{{ $record->quantity ?? 0 }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Amount</label><input class="pdn-input" type="number" step="0.0001" min="0" name="amount" value="{{ $record->amount ?? 0 }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Starting Meter</label><input class="pdn-input" type="number" step="0.000001" min="0" name="starting_meter" value="{{ $record->starting_meter ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Closing Meter</label><input class="pdn-input" type="number" step="0.000001" min="0" name="closing_meter" value="{{ $record->closing_meter ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Testing Qty</label><input class="pdn-input" type="number" step="0.000001" min="0" name="testing_quantity" value="{{ $record->testing_quantity ?? 0 }}" @readonly($readonly)></div>
                    </div>
                    <div class="pdn-field"><label>Note</label><textarea class="pdn-input" name="note" rows="3" @readonly($readonly)>{{ $record->note ?? '' }}</textarea></div>
                    @if(!$create && !$readonly)<div class="pdn-field"><label>Edit Reason <span class="text-danger">*</span></label><textarea class="pdn-input" name="edit_reason" required></textarea></div>@endif
                    @unless($readonly)<div class="pdn-modal-actions"><button class="pdn-btn primary" type="submit">Save Day Entry</button></div>@endunless
                </form>
            @elseif($type === 'payment')
                @php
                    $create = $action === 'create';
                    $url = $create ? route('petro-pd-new.operators.workspace.payments.store') : route('petro-pd-new.operators.workspace.payments.update', $record->id);
                @endphp
                <form action="{{ $url }}" method="post" data-pdn-workspace-form>
                    @csrf @unless($create) @method('PUT') @endunless
                    <div class="pdn-form-grid three">
                        @if($create)<div class="pdn-field"><label>Shift</label><select class="pdn-select" name="shift_id" required>@foreach(data_get($options,'shifts',collect()) as $shift)<option value="{{ $shift->id }}">{{ $shift->shift_number }} — {{ $shift->operator_name }}</option>@endforeach</select></div>@else<div class="pdn-field"><label>Shift</label><input class="pdn-input" value="{{ $record->shift_number }}" readonly></div>@endif
                        <div class="pdn-field"><label>Payment Type</label><select class="pdn-select" name="payment_type" @disabled(!$create || $readonly)>@foreach(['cash','card','cheque','credit','shortage','excess','other'] as $paymentType)<option value="{{ $paymentType }}" @selected(($record->payment_type ?? 'cash') === $paymentType)>{{ ucfirst($paymentType) }}</option>@endforeach</select>@unless($create)<input type="hidden" name="payment_type" value="{{ $record->payment_type }}">@endunless</div>
                        <div class="pdn-field"><label>Amount</label><input class="pdn-input" name="amount" type="number" min="0" step="0.0001" value="{{ $record->amount ?? 0 }}" required @readonly($readonly)></div>
                        <div class="pdn-field"><label>Reference No.</label><input class="pdn-input" name="reference_no" value="{{ $record->reference_no ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Slip No.</label><input class="pdn-input" name="slip_no" value="{{ $record->slip_no ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Cheque No.</label><input class="pdn-input" name="cheque_no" value="{{ $record->cheque_no ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Cheque Date</label><input class="pdn-input" type="date" name="cheque_date" value="{{ $record->cheque_date ?? '' }}" @readonly($readonly)></div>
                        <div class="pdn-field"><label>Transaction Date / Time</label><input class="pdn-input" type="datetime-local" name="transaction_at" value="{{ $record && $record->transaction_at ? \Carbon\Carbon::parse($record->transaction_at)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}" @readonly($readonly)></div>
                    </div>
                    <div class="pdn-field"><label>Note</label><textarea class="pdn-input" name="note" rows="3" @readonly($readonly)>{{ $record->note ?? '' }}</textarea></div>
                    @if($rows->isNotEmpty())<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Detail Type</th><th>Description</th><th class="amount">Amount</th></tr></thead><tbody>@foreach($rows as $detail)<tr><td>{{ ucfirst($detail->detail_type) }}</td><td>{{ $detail->description }}</td><td class="amount">{{ number_format((float)$detail->amount,4) }}</td></tr>@endforeach</tbody></table></div>@endif
                    @if(!$create && !$readonly)<div class="pdn-field"><label>Edit Reason <span class="text-danger">*</span></label><textarea class="pdn-input" name="edit_reason" required></textarea></div>@endif
                    @unless($readonly)<div class="pdn-modal-actions"><button class="pdn-btn primary" type="submit">Save Payment</button></div>@endunless
                </form>
            @elseif(in_array($type, ['assignment','meter-payment'], true))
                <div class="pdn-form-grid three">
                    <div class="pdn-field"><label>Shift</label><input class="pdn-input" value="{{ $record->shift_number }}" readonly></div>
                    <div class="pdn-field"><label>Operator</label><input class="pdn-input" value="{{ $record->operator_name }}" readonly></div>
                    <div class="pdn-field"><label>Pump</label><input class="pdn-input" value="Pump #{{ $record->pump_id }}" readonly></div>
                    <div class="pdn-field"><label>Opening Meter</label><input class="pdn-input" value="{{ number_format((float)$record->opening_meter,3) }}" readonly></div>
                    <div class="pdn-field"><label>Current Meter</label><input class="pdn-input" value="{{ number_format((float)$record->current_meter,3) }}" readonly></div>
                    <div class="pdn-field"><label>Status</label><input class="pdn-input" value="{{ ucfirst($record->status) }}" readonly></div>
                </div>
                @if(in_array($action,['current','close'],true))
                    <form action="{{ $action === 'current' ? route('petro-pd-new.operators.workspace.assignments.current-meter',$record->id) : route('petro-pd-new.operators.workspace.assignments.close',$record->id) }}" method="post" data-pdn-workspace-form>
                        @csrf
                        <div class="pdn-form-grid three">
                            <div class="pdn-field"><label>{{ $action === 'close' ? 'Closing' : 'Current' }} Meter</label><input class="pdn-input" type="number" min="0" step="0.000001" name="meter" value="{{ $action === 'close' ? ($record->closing_meter ?? $record->current_meter) : $record->current_meter }}" required></div>
                            <div class="pdn-field"><label>Testing Qty</label><input class="pdn-input" type="number" min="0" step="0.000001" name="testing_quantity" value="{{ $record->testing_quantity }}"></div>
                            <div class="pdn-field"><label>Unit Price</label><input class="pdn-input" type="number" min="0" step="0.000001" name="unit_price" value="{{ $record->unit_price }}"></div>
                        </div>
                        <div class="pdn-field"><label>Note</label><textarea class="pdn-input" name="note"></textarea></div>
                        <div class="pdn-modal-actions"><button class="pdn-btn {{ $action === 'close' ? 'danger' : 'primary' }}" type="submit">{{ $action === 'close' ? 'Close Pump' : 'Save Current Meter' }}</button></div>
                    </form>
                @endif
                @if($rows->isNotEmpty())<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date / Time</th><th>Type</th><th class="amount">Meter</th><th class="amount">Testing</th><th>Source</th></tr></thead><tbody>@foreach($rows as $detail)<tr><td>{{ $detail->recorded_at }}</td><td>{{ ucfirst($detail->reading_type) }}</td><td class="amount">{{ $detail->meter_value !== null ? number_format((float)$detail->meter_value,3) : '—' }}</td><td class="amount">{{ number_format((float)$detail->testing_quantity,3) }}</td><td>{{ ucfirst($detail->source) }}</td></tr>@endforeach</tbody></table></div>@endif
            @elseif($type === 'shift')
                <div class="pdn-form-grid three"><div class="pdn-field"><label>Shift No.</label><input class="pdn-input" value="{{ $record->shift_number }}" readonly></div><div class="pdn-field"><label>Operator</label><input class="pdn-input" value="{{ $record->operator_name }}" readonly></div><div class="pdn-field"><label>Status</label><input class="pdn-input" value="{{ ucfirst($record->status) }}" readonly></div><div class="pdn-field"><label>Expected</label><input class="pdn-input" value="{{ number_format((float)$record->expected_total,4) }}" readonly></div><div class="pdn-field"><label>Payments</label><input class="pdn-input" value="{{ number_format((float)$record->payments_total,4) }}" readonly></div><div class="pdn-field"><label>Variance</label><input class="pdn-input" value="{{ number_format((float)$record->expected_total-(float)$record->payments_total,4) }}" readonly></div></div>
                @if($rows->isNotEmpty())<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Pump</th><th>Status</th><th class="amount">Opening</th><th class="amount">Closing</th><th class="amount">Testing</th><th class="amount">Sold Qty</th><th class="amount">Amount</th></tr></thead><tbody>@foreach($rows as $detail)<tr><td>Pump #{{ $detail->pump_id }}</td><td>{{ ucfirst($detail->status) }}</td><td class="amount">{{ number_format((float)$detail->opening_meter,3) }}</td><td class="amount">{{ $detail->closing_meter !== null ? number_format((float)$detail->closing_meter,3) : '—' }}</td><td class="amount">{{ number_format((float)$detail->testing_quantity,3) }}</td><td class="amount">{{ number_format((float)$detail->sold_quantity,3) }}</td><td class="amount">{{ number_format((float)$detail->amount,4) }}</td></tr>@endforeach</tbody></table></div>@endif
                @if($action === 'close')<form action="{{ route('petro-pd-new.operators.workspace.shifts.close',$record->id) }}" method="post" data-pdn-workspace-form>@csrf<div class="pdn-field"><label>Closing Note</label><textarea class="pdn-input" name="note"></textarea></div><label class="pdn-check"><input type="checkbox" name="confirmed" value="1" required> I confirm that every pump is closed and the amounts are correct.</label><div class="pdn-modal-actions"><button class="pdn-btn danger" type="submit">Close Shift</button></div></form>@endif
            @elseif($type === 'unload')
                @php $create=$action==='create'; $url=$create?route('petro-pd-new.operators.workspace.unloads.store'):route('petro-pd-new.operators.workspace.unloads.update',$record->id); @endphp
                <form action="{{ $url }}" method="post" data-pdn-workspace-form>@csrf @unless($create) @method('PUT') @endunless
                    <div class="pdn-form-grid three">@if($create)<div class="pdn-field"><label>Shift</label><select class="pdn-select" name="shift_id" required>@foreach(data_get($options,'shifts',collect()) as $shift)<option value="{{ $shift->id }}">{{ $shift->shift_number }} — {{ $shift->operator_name }}</option>@endforeach</select></div>@else<div class="pdn-field"><label>Shift</label><input class="pdn-input" value="{{ $record->shift_number }}" readonly></div>@endif<div class="pdn-field"><label>Bill No.</label><input class="pdn-input" name="bill_number" value="{{ $record->bill_number ?? '' }}" @readonly($readonly)></div><div class="pdn-field"><label>Supplier Reference</label><input class="pdn-input" name="supplier_reference" value="{{ $record->supplier_reference ?? '' }}" @readonly($readonly)></div><div class="pdn-field"><label>Unload Date / Time</label><input class="pdn-input" type="datetime-local" name="unloaded_at" value="{{ $record && $record->unloaded_at ? \Carbon\Carbon::parse($record->unloaded_at)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}" @readonly($readonly)></div></div>
                    <div class="pdn-table-wrap"><table class="pdn-table" data-pdn-unload-lines><thead><tr><th>Product</th><th>Tank</th><th>Quantity</th><th>Unit Cost</th><th>Dip</th><th>Current Stock</th></tr></thead><tbody><tr><td><select class="pdn-select" name="lines[0][product_id]" required @disabled($readonly)>@foreach(data_get($options,'products',collect()) as $product)<option value="{{ $product->id }}" @selected((int)optional($rows->first())->product_id===(int)$product->id)>{{ $product->name }}</option>@endforeach</select></td><td><select class="pdn-select" name="lines[0][tank_id]" @disabled($readonly)><option value="">None</option>@foreach(data_get($options,'tanks',collect()) as $tank)<option value="{{ $tank->id }}" @selected((int)optional($rows->first())->tank_id===(int)$tank->id)>{{ $tank->fuel_tank_number ?? $tank->name ?? ('Tank #'.$tank->id) }}</option>@endforeach</select></td><td><input class="pdn-input" type="number" step="0.000001" min="0.000001" name="lines[0][quantity]" value="{{ optional($rows->first())->quantity ?? '' }}" required @readonly($readonly)></td><td><input class="pdn-input" type="number" step="0.000001" min="0" name="lines[0][unit_cost]" value="{{ optional($rows->first())->unit_cost ?? 0 }}" @readonly($readonly)></td><td><input class="pdn-input" type="number" step="0.000001" min="0" name="lines[0][dip_reading]" value="{{ optional($rows->first())->dip_reading ?? '' }}" @readonly($readonly)></td><td><input class="pdn-input" type="number" step="0.000001" min="0" name="lines[0][current_stock]" value="{{ optional($rows->first())->current_stock ?? '' }}" @readonly($readonly)></td></tr></tbody></table></div>
                    <div class="pdn-field"><label>Note</label><textarea class="pdn-input" name="note" @readonly($readonly)>{{ $record->note ?? '' }}</textarea></div>@if(!$create&&!$readonly)<div class="pdn-field"><label>Edit Reason</label><textarea class="pdn-input" name="edit_reason" required></textarea></div>@endif @unless($readonly)<div class="pdn-modal-actions"><button class="pdn-btn primary" type="submit">Save Unload Stock</button></div>@endunless
                </form>
            @elseif($type === 'day-end')
                <div class="pdn-form-grid three"><div class="pdn-field"><label>Day End No.</label><input class="pdn-input" value="{{ $record->day_end_number }}" readonly></div><div class="pdn-field"><label>Date</label><input class="pdn-input" value="{{ $record->day_end_date }}" readonly></div><div class="pdn-field"><label>Status</label><input class="pdn-input" value="{{ ucfirst($record->status) }}" readonly></div></div>
                @if($rows->isNotEmpty())<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Settlement No.</th><th>Status</th><th class="amount">Expected</th><th class="amount">Received</th><th class="amount">Variance</th></tr></thead><tbody>@foreach($rows as $settlement)<tr><td>{{ $settlement->settlement_number }}</td><td>{{ ucfirst($settlement->status) }}</td><td class="amount">{{ number_format((float)$settlement->expected_amount,4) }}</td><td class="amount">{{ number_format((float)$settlement->received_amount,4) }}</td><td class="amount">{{ number_format((float)$settlement->variance_amount,4) }}</td></tr>@endforeach</tbody></table></div>@endif
                <div class="pdn-modal-actions"><a class="pdn-btn primary" href="{{ route('petro-pd-new.day-ends.show',$record->id) }}">Open Day End</a></div>
            @elseif($type === 'variance')
                <div class="pdn-form-grid three">@foreach((array)$record as $key=>$value)@if(!in_array($key,['created_at','updated_at','deleted_at'],true)&&!is_array($value)&&!is_object($value))<div class="pdn-field"><label>{{ ucwords(str_replace('_',' ',$key)) }}</label><input class="pdn-input" value="{{ $value }}" readonly></div>@endif @endforeach</div>
            @endif
        </div>
        <div class="modal-footer"><button type="button" class="pdn-btn light" data-dismiss="modal">Close</button></div>
    </div>
</div>
