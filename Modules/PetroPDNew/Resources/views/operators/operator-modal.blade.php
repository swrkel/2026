@php($money = static fn ($value) => number_format((float) $value, 4, '.', ','))
<div class="modal-dialog modal-lg pdn-operator-modal-dialog" role="document">
    <div class="modal-content pdn-operator-modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">
                @switch($section)
                    @case('edit') Edit PD Operator @break
                    @case('commission') Pay Excess &amp; Commission @break
                    @case('recovery') Recover Shortages @break
                    @case('contact') Contact Information @break
                    @case('ledger') Operator Ledger @break
                    @case('commissions') List Commission @break
                    @case('documents') Documents &amp; Notes @break
                    @default PD Operator Details
                @endswitch
            </h4>
        </div>
        <div class="modal-body">
            <div class="pdn-modal-operator-summary">
                <div><span>Pump Operator</span><strong>{{ $contact['name'] }}</strong></div>
                <div><span>Location</span><strong>{{ $location_name }}</strong></div>
                <div><span>Current Balance</span><strong>{{ $money($current_balance) }}</strong></div>
            </div>

            @if($section === 'view' || $section === 'contact')
                <div class="pdn-detail-grid">
                    <div><span>Status</span><strong>{{ ucfirst($mapping->status) }}</strong></div>
                    <div><span>PD Operator No.</span><strong>{{ $mapping->pone_pd_operator_id }}</strong></div>
                    <div><span>PONE Profile</span><strong>{{ $mapping->pone_operator_profile_id }}</strong></div>
                    <div><span>Mobile</span><strong>{{ $contact['mobile'] ?: '—' }}</strong></div>
                    <div><span>Landline</span><strong>{{ $contact['landline'] ?: '—' }}</strong></div>
                    <div><span>NIC / CNIC</span><strong>{{ $contact['cnic'] ?: '—' }}</strong></div>
                    <div><span>Date of Birth</span><strong>{{ $contact['dob'] ?: '—' }}</strong></div>
                    <div><span>Commission Type</span><strong>{{ ucfirst($contact['commission_type']) }}</strong></div>
                    <div><span>Commission Rate</span><strong>{{ $money($contact['commission_rate']) }}</strong></div>
                    <div class="full"><span>Address</span><strong>{{ $contact['address'] ?: '—' }}</strong></div>
                </div>
            @elseif($section === 'edit')
                <form method="post" action="{{ route('petro-pd-new.operators.profile.update', $mapping->id) }}">
                    @csrf @method('PUT')
                    <div class="pdn-form-grid">
                        <div class="pdn-field"><label>Operator Name *</label><input class="pdn-input" name="display_name" required maxlength="191" value="{{ $contact['name'] }}"></div>
                        <div class="pdn-field"><label>Status *</label><select class="pdn-select" name="status"><option value="active" @selected($mapping->status==='active')>Active</option><option value="inactive" @selected($mapping->status==='inactive')>Inactive</option></select></div>
                        <div class="pdn-field"><label>NIC / CNIC</label><input class="pdn-input" name="cnic" value="{{ $contact['cnic'] }}"></div>
                        <div class="pdn-field"><label>Mobile</label><input class="pdn-input" name="mobile" value="{{ $contact['mobile'] }}"></div>
                        <div class="pdn-field"><label>Landline</label><input class="pdn-input" name="landline" value="{{ $contact['landline'] }}"></div>
                        <div class="pdn-field"><label>Date of Birth</label><input class="pdn-input" type="date" name="dob" value="{{ $contact['dob'] }}"></div>
                        <div class="pdn-field"><label>Commission Type *</label><select class="pdn-select" name="commission_type"><option value="none" @selected($contact['commission_type']==='none')>None</option><option value="fixed" @selected($contact['commission_type']==='fixed')>Fixed</option><option value="percentage" @selected($contact['commission_type']==='percentage')>Percentage</option></select></div>
                        <div class="pdn-field"><label>Commission Rate</label><input class="pdn-input" type="number" step="0.0001" min="0" name="commission_rate" value="{{ $contact['commission_rate'] }}"></div>
                        <div class="pdn-field full"><label>Address</label><textarea class="pdn-textarea" name="address">{{ $contact['address'] }}</textarea></div>
                    </div>
                    <div class="modal-footer pdn-modal-footer"><button type="button" class="pdn-btn light" data-dismiss="modal">Close</button><button class="pdn-btn primary" type="submit">Save Changes</button></div>
                </form>
            @elseif($section === 'commission')
                <form method="post" action="{{ route('petro-pd-new.operators.commission', $mapping->id) }}">
                    @csrf
                    <div class="pdn-form-grid">
                        <div class="pdn-field"><label>Transaction Date *</label><input class="pdn-input" type="date" name="transaction_date" required value="{{ now()->toDateString() }}"></div>
                        <div class="pdn-field"><label>Available / Base Excess</label><input class="pdn-input" type="number" step="0.0001" min="0" name="base_excess_amount" value="{{ max(0, -$current_balance) }}"></div>
                        <div class="pdn-field"><label>Commission Type *</label><select class="pdn-select" name="commission_type"><option value="fixed" @selected($contact['commission_type']!=='percentage')>Fixed</option><option value="percentage" @selected($contact['commission_type']==='percentage')>Percentage</option></select></div>
                        <div class="pdn-field"><label>Commission Rate</label><input class="pdn-input" type="number" step="0.0001" min="0" name="commission_rate" value="{{ $contact['commission_rate'] }}"></div>
                        <div class="pdn-field"><label>Amount *</label><input class="pdn-input" type="number" step="0.0001" min="0.0001" name="amount" required value="{{ max(0, -$current_balance) }}"></div>
                        <div class="pdn-field full"><label>Note</label><textarea class="pdn-textarea" name="note"></textarea></div>
                    </div>
                    <div class="modal-footer pdn-modal-footer"><button type="button" class="pdn-btn light" data-dismiss="modal">Close</button><button class="pdn-btn primary" type="submit">Save Payment</button></div>
                </form>
            @elseif($section === 'recovery')
                <form method="post" action="{{ route('petro-pd-new.operators.recovery', $mapping->id) }}">
                    @csrf
                    <div class="pdn-form-grid">
                        <div class="pdn-field"><label>Transaction Date *</label><input class="pdn-input" type="date" name="transaction_date" required value="{{ now()->toDateString() }}"></div>
                        <div class="pdn-field"><label>Amount *</label><input class="pdn-input" type="number" step="0.0001" min="0.0001" name="amount" required value="{{ max(0, $current_balance) }}"></div>
                        <div class="pdn-field"><label>Payment Method *</label><select class="pdn-select" name="payment_method"><option value="cash">Cash</option><option value="card">Card</option><option value="cheque">Cheque</option><option value="bank_transfer">Bank Transfer</option><option value="other">Other</option></select></div>
                        <div class="pdn-field"><label>Reference No.</label><input class="pdn-input" name="reference_no" maxlength="191"></div>
                        <div class="pdn-field full"><label>Note</label><textarea class="pdn-textarea" name="note"></textarea></div>
                    </div>
                    <div class="modal-footer pdn-modal-footer"><button type="button" class="pdn-btn light" data-dismiss="modal">Close</button><button class="pdn-btn primary" type="submit">Save Recovery</button></div>
                </form>
            @elseif($section === 'ledger')
                <div class="pdn-modal-filter-note">Period: {{ $from }} to {{ $to }}</div>
                <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date</th><th>Reference</th><th>Description</th><th class="amount">Debit</th><th class="amount">Credit</th><th class="amount">Balance</th></tr></thead><tbody>
                @forelse($ledger as $entry)<tr><td>{{ \Carbon\Carbon::parse($entry->entry_at)->format('d M Y H:i') }}</td><td>{{ $entry->reference_no ?: '—' }}</td><td>{{ $entry->description }}</td><td class="amount">{{ $money($entry->debit) }}</td><td class="amount">{{ $money($entry->credit) }}</td><td class="amount">{{ $money($entry->running_balance) }}</td></tr>@empty<tr><td colspan="6" class="pdn-empty">No ledger entries found.</td></tr>@endforelse
                </tbody></table></div>
            @elseif($section === 'commissions')
                <div class="pdn-modal-filter-note">Period: {{ $from }} to {{ $to }}</div>
                <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date</th><th>Reference</th><th>Type</th><th class="amount">Base Excess</th><th class="amount">Rate</th><th class="amount">Commission</th><th>Status</th></tr></thead><tbody>
                @forelse($commissions as $entry)<tr><td>{{ $entry->commission_date }}</td><td>{{ $entry->commission_number }}</td><td>{{ ucfirst($entry->commission_type) }}</td><td class="amount">{{ $money($entry->base_excess_amount) }}</td><td class="amount">{{ $money($entry->commission_rate) }}</td><td class="amount">{{ $money($entry->commission_amount) }}</td><td><span class="pdn-badge {{ $entry->status }}">{{ $entry->status }}</span></td></tr>@empty<tr><td colspan="7" class="pdn-empty">No commission entries found.</td></tr>@endforelse
                </tbody></table></div>
            @elseif($section === 'documents')
                <div class="pdn-grid two">
                    <div class="pdn-card">
                        <h3>Add Note</h3>
                        <form method="post" action="{{ route('petro-pd-new.operators.notes.store', $mapping->id) }}">@csrf
                            <div class="pdn-field"><label>Type</label><select class="pdn-select" name="note_type"><option value="general">General</option><option value="incident">Incident</option><option value="hand_over">Hand Over</option><option value="settlement">Settlement</option></select></div>
                            <div class="pdn-field"><label>Title *</label><input class="pdn-input" name="title" required></div>
                            <div class="pdn-field"><label>Note *</label><textarea class="pdn-textarea" name="body" required></textarea></div>
                            <button class="pdn-btn primary" type="submit">Add Note</button>
                        </form>
                    </div>
                    <div class="pdn-card">
                        <h3>Upload Document</h3>
                        <form method="post" enctype="multipart/form-data" action="{{ route('petro-pd-new.operators.documents.store', $mapping->id) }}">@csrf
                            <div class="pdn-field"><label>Title</label><input class="pdn-input" name="title"></div>
                            <div class="pdn-field"><label>Category *</label><input class="pdn-input" name="category" value="general" required></div>
                            <div class="pdn-field"><label>Document *</label><input class="pdn-input" type="file" name="document" required></div>
                            <button class="pdn-btn primary" type="submit">Upload</button>
                        </form>
                    </div>
                </div>
                <h3 class="pdn-modal-section-title">Notes</h3>
                <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date</th><th>Type</th><th>Title</th><th>Note</th><th>Status</th></tr></thead><tbody>@forelse($notes as $note)<tr><td>{{ $note->created_at }}</td><td>{{ ucfirst(str_replace('_',' ',$note->note_type)) }}</td><td>{{ $note->title }}</td><td>{{ $note->body }}</td><td>{{ $note->status }}</td></tr>@empty<tr><td colspan="5" class="pdn-empty">No notes found.</td></tr>@endforelse</tbody></table></div>
                <h3 class="pdn-modal-section-title">Documents</h3>
                <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Date</th><th>Title</th><th>Category</th><th>File</th><th>Action</th></tr></thead><tbody>@forelse($documents as $document)<tr><td>{{ $document->created_at }}</td><td>{{ $document->title }}</td><td>{{ $document->category }}</td><td>{{ $document->original_name }}</td><td><a class="pdn-btn small light" href="{{ route('petro-pd-new.operators.documents.download', [$mapping->id, $document->id]) }}">Download</a></td></tr>@empty<tr><td colspan="5" class="pdn-empty">No documents found.</td></tr>@endforelse</tbody></table></div>
            @endif
        </div>
        @unless(in_array($section, ['edit','commission','recovery','documents'], true))
            <div class="modal-footer pdn-modal-footer"><button type="button" class="pdn-btn light" data-dismiss="modal">Close</button></div>
        @endunless
    </div>
</div>
