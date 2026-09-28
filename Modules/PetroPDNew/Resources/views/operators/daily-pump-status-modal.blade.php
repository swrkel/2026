<div class="modal-dialog modal-lg pdn-daily-status-modal" role="document">
    <div class="modal-content">
        @if($mode === 'assign')
            <form method="post" action="{{ route('petro-pd-new.operators.daily-status.store') }}" data-prevent-double-submit>
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus-circle"></i> Assign Pumps</h4>
                </div>
                <div class="modal-body">
                    <div class="pdn-form-grid">
                        <div class="pdn-field">
                            <label>Pump Operator *</label>
                            <select class="pdn-select" name="operator_profile_id" required>
                                <option value="">Please Select</option>
                                @foreach($operators as $operator)
                                    <option value="{{ $operator->id }}">{{ $operator->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(!$active_location_id)
                            <div class="pdn-field">
                                <label>Business Location</label>
                                <select class="pdn-select" name="location_id">
                                    <option value="">Use operator location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name ?? ('Location #'.$location->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="location_id" value="{{ $active_location_id }}">
                        @endif
                        <div class="pdn-field">
                            <label>Shift Number</label>
                            <input class="pdn-input" name="shift_number" maxlength="80" placeholder="Auto generated when blank">
                        </div>
                        <div class="pdn-field">
                            <label>Opened At *</label>
                            <input class="pdn-input" type="datetime-local" name="opened_at" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="pdn-field full">
                            <label>Note</label>
                            <textarea class="pdn-textarea" name="notes" maxlength="4000"></textarea>
                        </div>
                    </div>

                    <h4 class="pdn-modal-section-title">Select Pumps</h4>
                    <div class="pdn-daily-pump-picker">
                        @forelse($pumps as $pump)
                            <label class="pdn-daily-pump-choice">
                                <input type="checkbox" name="pump_ids[]" value="{{ $pump->id }}">
                                <span>
                                    <strong>{{ $pump->pump_no ?: ($pump->pump_name ?: 'Pump #'.$pump->id) }}</strong>
                                    <small>{{ $pump->product_name ?: 'Fuel product not linked' }}</small>
                                    <small>Current meter: {{ number_format((float)($pump->last_meter_reading ?? $pump->pod_last_meter ?? $pump->starting_meter ?? 0), 3) }}</small>
                                </span>
                            </label>
                        @empty
                            <div class="pdn-empty">No unassigned pumps are available for the active business location.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer pdn-modal-footer">
                    <button type="button" class="pdn-btn light" data-dismiss="modal">Close</button>
                    <button class="pdn-btn primary" type="submit" {{ $pumps->isEmpty() || $operators->isEmpty() ? 'disabled' : '' }}>
                        <i class="fa fa-check"></i> Assign Pumps
                    </button>
                </div>
            </form>
        @elseif($mode === 'edit')
            <form method="post" action="{{ route('petro-pd-new.operators.daily-status.update', $assignment->id) }}" data-prevent-double-submit>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-pencil-square-o"></i> Edit Pump Assignment</h4>
                </div>
                <div class="modal-body">
                    <div class="pdn-daily-status-context">
                        <span><strong>Shift:</strong> {{ $assignment->shift->shift_number ?? ('#'.$assignment->shift_id) }}</span>
                        <span><strong>Pump:</strong> {{ $pump->pump_no ?? $pump->pump_name ?? ('#'.$assignment->pump_id) }}</span>
                        <span><strong>Status:</strong> {{ ucfirst($assignment->status) }}</span>
                    </div>
                    <div class="pdn-form-grid">
                        <div class="pdn-field">
                            <label>Opening Meter *</label>
                            <input class="pdn-input" type="number" min="0" step="0.001" name="opening_meter" required value="{{ $assignment->opening_meter }}">
                        </div>
                        <div class="pdn-field">
                            <label>Unit Price</label>
                            <input class="pdn-input" type="number" min="0" step="0.0001" name="unit_price" value="{{ $assignment->unit_price }}">
                        </div>
                        <div class="pdn-field full">
                            <label>Reason / Note</label>
                            <textarea class="pdn-textarea" name="note" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="pdn-alert warning" style="margin-top:12px">Editing is allowed only before the pump operator receives the assignment.</div>
                </div>
                <div class="modal-footer pdn-modal-footer">
                    <button type="button" class="pdn-btn light" data-dismiss="modal">Close</button>
                    <button class="pdn-btn primary" type="submit">Save Assignment</button>
                </div>
            </form>
        @else
            <form method="post" action="{{ route('petro-pd-new.operators.daily-status.destroy', $assignment->id) }}" data-prevent-double-submit>
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-trash"></i> Cancel Pump Assignment</h4>
                </div>
                <div class="modal-body">
                    <div class="pdn-daily-status-context">
                        <span><strong>Shift:</strong> {{ $assignment->shift->shift_number ?? ('#'.$assignment->shift_id) }}</span>
                        <span><strong>Pump:</strong> {{ $pump->pump_no ?? $pump->pump_name ?? ('#'.$assignment->pump_id) }}</span>
                    </div>
                    <div class="pdn-field">
                        <label>Cancellation Reason *</label>
                        <textarea class="pdn-textarea" name="reason" required maxlength="1000"></textarea>
                    </div>
                    <div class="pdn-alert warning" style="margin-top:12px">Only an unreceived assignment can be cancelled. Historical records remain in the audit trail.</div>
                </div>
                <div class="modal-footer pdn-modal-footer">
                    <button type="button" class="pdn-btn light" data-dismiss="modal">Close</button>
                    <button class="pdn-btn danger" type="submit">Cancel Assignment</button>
                </div>
            </form>
        @endif
    </div>
</div>
