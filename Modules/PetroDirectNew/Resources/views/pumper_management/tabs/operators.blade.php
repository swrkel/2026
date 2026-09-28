<div class="pdn-section-title pdn-operator-heading">
    <div>
        <h2><i class="fa fa-users"></i> Pump Operators</h2>
        <small class="text-muted">Operator master, access, commissions and status for the selected business location.</small>
    </div>
    <button type="button" class="btn btn-primary" data-pdn-open-operator-modal="add">
        <i class="fa fa-plus"></i> Add Pump Operator
    </button>
</div>

<div class="pdn-summary-cards pdn-operator-summary">
    <div class="pdn-summary-card">Total Operators<strong>{{ $operators->count() }}</strong></div>
    <div class="pdn-summary-card">Active Operators<strong>{{ $operators->where('is_active', 1)->count() }}</strong></div>
    <div class="pdn-summary-card">Inactive Operators<strong>{{ $operators->where('is_active', 0)->count() }}</strong></div>
    <div class="pdn-summary-card">Dashboard Login Enabled<strong>{{ $operators->where('can_login', 1)->count() }}</strong></div>
</div>

<div class="pdn-card pdn-compact-card pdn-operator-table-card">
    <div class="pdn-table-toolbar">
        <div class="pdn-table-search-wrap">
            <i class="fa fa-search"></i>
            <input type="search" class="form-control" id="pdn-operator-search" placeholder="Search name, number, mobile, NIC, username or location">
        </div>
        <div class="pdn-table-count"><span id="pdn-operator-visible-count">{{ $operators->count() }}</span> records</div>
    </div>

    <div class="pdn-scroll pdn-operator-table-scroll">
        <table class="table table-bordered table-striped mb-0 pdn-operator-table" id="pdn-operator-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Operator No.</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Landline</th>
                    <th>NIC</th>
                    <th>Username</th>
                    <th>Location</th>
                    <th>Commission Type</th>
                    <th class="text-right">Commission Value</th>
                    <th class="text-right">Opening Balance</th>
                    <th class="text-right">Shortage</th>
                    <th class="text-right">Excess</th>
                    <th>Dashboard Login</th>
                    <th>Fullscreen</th>
                    <th>Status</th>
                    <th>Last Synchronized</th>
                </tr>
            </thead>
            <tbody>
            @forelse($operators as $operator)
                @php
                    $userName = trim((string) ($operator->linked_user_name ?? ''));
                    $username = $operator->username ?: ($operator->linked_username ?? '');
                    $email = $operator->email ?: ($operator->linked_email ?? '');
                    $searchText = strtolower(implode(' ', array_filter([
                        $operator->operator_no, $operator->name, $operator->mobile, $operator->landline,
                        $operator->nic, $username, $email, $operator->location_name,
                    ])));
                @endphp
                <tr data-pdn-operator-row data-search="{{ e($searchText) }}">
                    <td class="pdn-action-cell">
                        <div class="btn-group">
                            <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left pdn-action-menu">
                                <li><a href="#" data-pdn-view-operator="{{ route('petro-direct-new.pumper-management.operators.show', $operator->id) }}"><i class="fa fa-eye"></i> View</a></li>
                                <li><a href="#" data-pdn-edit-operator="{{ route('petro-direct-new.pumper-management.operators.show', $operator->id) }}" data-update-url="{{ route('petro-direct-new.pumper-management.operators.update', $operator->id) }}"><i class="fa fa-pencil-square-o"></i> Edit</a></li>
                                <li><a href="#" data-pdn-toggle-operator="{{ route('petro-direct-new.pumper-management.operators.toggle', $operator->id) }}"><i class="fa {{ $operator->is_active ? 'fa-times' : 'fa-check' }}"></i> {{ $operator->is_active ? 'Deactivate' : 'Activate' }}</a></li>
                                <li class="divider"></li>
                                <li><a href="#" data-pdn-jump-tab="daily_pump_status" data-operator-id="{{ $operator->id }}"><i class="fa fa-random"></i> Pump Status / Assignment</a></li>
                                <li><a href="#" data-pdn-jump-tab="payments" data-operator-id="{{ $operator->id }}"><i class="fa fa-plus-circle"></i> Excess / Shortage Payments</a></li>
                                <li><a href="{{ route('petro-direct-new.reports.index', ['report' => 'operators', 'operator_id' => $operator->id]) }}"><i class="fa fa-bar-chart"></i> Operator Report</a></li>
                                <li class="divider"></li>
                                <li><a href="#" class="text-danger" data-pdn-delete-operator="{{ route('petro-direct-new.pumper-management.operators.destroy', $operator->id) }}"><i class="fa fa-trash"></i> Delete / Deactivate</a></li>
                            </ul>
                        </div>
                    </td>
                    <td>{{ $operator->operator_no }}</td>
                    <td><strong>{{ $operator->name }}</strong><br><small class="text-muted">{{ $email ?: '—' }}</small></td>
                    <td>{{ $operator->mobile ?: '—' }}</td>
                    <td>{{ $operator->landline ?: '—' }}</td>
                    <td>{{ $operator->nic ?: '—' }}</td>
                    <td>{{ $username ?: ($userName ?: '—') }}</td>
                    <td>{{ $operator->location_name ?: 'All Locations' }}</td>
                    <td>{{ ucfirst($operator->commission_type ?: 'none') }}</td>
                    <td class="pdn-money">{{ number_format((float) $operator->commission_value, 4) }}</td>
                    <td class="pdn-money">{{ number_format((float) $operator->opening_balance, 4) }}</td>
                    <td class="pdn-money pdn-negative">{{ number_format((float) $operator->short_amount, 4) }}</td>
                    <td class="pdn-money pdn-positive">{{ number_format((float) $operator->excess_amount, 4) }}</td>
                    <td><span class="label {{ $operator->can_login ? 'label-success' : 'label-default' }}">{{ $operator->can_login ? 'Enabled' : 'Disabled' }}</span></td>
                    <td>{{ $operator->can_fullscreen ? 'Yes' : 'No' }}</td>
                    <td><span class="pdn-status {{ $operator->is_active ? 'finalized' : 'draft' }}">{{ $operator->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $operator->source_updated_at ? \Carbon\Carbon::parse($operator->source_updated_at)->format('d/m/Y H:i') : ($operator->updated_at ? \Carbon\Carbon::parse($operator->updated_at)->format('d/m/Y H:i') : '—') }}</td>
                </tr>
            @empty
                <tr><td colspan="17" class="pdn-table-empty">No Pump Operators are available for this business/location.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="pdn-modal" id="pdn-operator-form-modal" aria-hidden="true">
    <div class="pdn-modal-backdrop" data-pdn-close-modal></div>
    <div class="pdn-modal-dialog pdn-modal-lg" role="dialog" aria-modal="true" aria-labelledby="pdn-operator-modal-title">
        <div class="pdn-modal-header">
            <h3 id="pdn-operator-modal-title"><i class="fa fa-user-plus"></i> Add Pump Operator</h3>
            <button type="button" class="pdn-modal-close" data-pdn-close-modal aria-label="Close">&times;</button>
        </div>
        <form id="pdn-operator-form" method="post" action="{{ route('petro-direct-new.pumper-management.operators.store') }}" data-store-url="{{ route('petro-direct-new.pumper-management.operators.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST" id="pdn-operator-method">
            <div class="pdn-modal-body">
                <div class="alert alert-danger pdn-operator-errors" hidden></div>
                <div class="pdn-form-grid pdn-operator-form-grid">
                    <div>
                        <label>Business Location *</label>
                        <select class="form-control" name="location_id" required>
                            <option value="">Please Select</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" @selected((int) $locationId === (int) $loc->id)>{{ $loc->name ?? ('Location '.$loc->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>Operator No.</label><input class="form-control" name="operator_no" placeholder="Auto if blank"></div>
                    <div><label>Name *</label><input class="form-control" name="name" required></div>
                    <div><label>Mobile</label><input class="form-control" name="mobile"></div>
                    <div><label>Landline</label><input class="form-control" name="landline"></div>
                    <div><label>Date of Birth</label><input type="date" class="form-control" name="dob"></div>
                    <div><label>NIC / CNIC</label><input class="form-control" name="nic"></div>
                    <div><label>Email</label><input type="email" class="form-control" name="email"></div>
                    <div><label>Username</label><input class="form-control" name="username"></div>
                    <div><label>Linked User</label>
                        <select class="form-control" name="user_id" data-pdn-user-picker data-search-url="{{ route('petro-direct-new.pumper-management.operator-users.search') }}">
                            <option value="">Not Linked</option>
                        </select>
                        <small class="text-muted">Searches users only when the popup is opened.</small>
                    </div>
                    <div><label>Passcode</label><input type="password" class="form-control" name="passcode" minlength="4" autocomplete="new-password"><small class="text-muted">Leave blank during edit to keep the current passcode.</small></div>
                    <div><label>Opening Balance</label><input type="number" step="0.0001" class="form-control" name="opening_balance" value="0"></div>
                    <div><label>Commission Type</label><select class="form-control" name="commission_type"><option value="none">None</option><option value="fixed">Fixed</option><option value="percentage">Percentage</option></select></div>
                    <div><label>Commission Value</label><input type="number" min="0" step="0.0001" class="form-control" name="commission_value" value="0"></div>
                    <div><label>Shortage Amount</label><input type="number" min="0" step="0.0001" class="form-control" name="short_amount" value="0"></div>
                    <div><label>Excess Amount</label><input type="number" min="0" step="0.0001" class="form-control" name="excess_amount" value="0"></div>
                    <div><label>Transaction Date</label><input type="date" class="form-control" name="transaction_date" value="{{ now()->format('Y-m-d') }}"></div>
                    <div class="pdn-span-2"><label>Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>
                    <div class="pdn-check-field"><input type="hidden" name="can_login" value="0"><label><input type="checkbox" name="can_login" value="1"> Dashboard Login</label></div>
                    <div class="pdn-check-field"><input type="hidden" name="is_default" value="0"><label><input type="checkbox" name="is_default" value="1"> Admin Operator Dashboard Login</label></div>
                    <div class="pdn-check-field"><input type="hidden" name="can_fullscreen" value="0"><label><input type="checkbox" name="can_fullscreen" value="1"> Can Minimize / Fullscreen</label></div>
                    <div class="pdn-check-field"><input type="hidden" name="hide_in_direct_settlement_if_pending_shifts" value="0"><label><input type="checkbox" name="hide_in_direct_settlement_if_pending_shifts" value="1"> Hide in Direct Settlement if shifts are pending</label></div>
                    <div class="pdn-check-field"><input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
                </div>
            </div>
            <div class="pdn-modal-footer">
                <button type="button" class="btn btn-default" data-pdn-close-modal>Close</button>
                <button type="submit" class="btn btn-primary" id="pdn-operator-save"><i class="fa fa-save"></i> Save Pump Operator</button>
            </div>
        </form>
    </div>
</div>

<div class="pdn-modal" id="pdn-operator-view-modal" aria-hidden="true">
    <div class="pdn-modal-backdrop" data-pdn-close-modal></div>
    <div class="pdn-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pdn-operator-view-title">
        <div class="pdn-modal-header">
            <h3 id="pdn-operator-view-title"><i class="fa fa-user"></i> Pump Operator Details</h3>
            <button type="button" class="pdn-modal-close" data-pdn-close-modal aria-label="Close">&times;</button>
        </div>
        <div class="pdn-modal-body" id="pdn-operator-view-body"></div>
        <div class="pdn-modal-footer"><button type="button" class="btn btn-default" data-pdn-close-modal>Close</button></div>
    </div>
</div>
