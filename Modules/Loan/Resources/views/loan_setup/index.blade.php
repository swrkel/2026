@extends('layouts.app')
@section('title', 'Loan Setup')

@section('content')
<section class="content-header">
    <h1>Loan Setup <small>Loan Module</small></h1>
</section>

<section class="content loan-setup-standard-page">
    @if (session('status'))
        @php $status = session('status'); @endphp
        <div class="alert alert-{{ !empty($status['success']) ? 'success' : 'danger' }} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ $status['msg'] ?? '' }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="loan-setup-header-card">
        <div>
            <h2><i class="fa fa-cogs"></i> Loan Setup</h2>
            <p>Maintain all standard Loan Module setup pages from one clean tabbed screen.</p>
        </div>
    </div>

    <div class="loan-setup-card">
        <ul class="nav nav-tabs loan-setup-tabs" role="tablist">
            <li role="presentation" class="{{ $active_tab == 'application_no' ? 'active' : '' }}">
                <a href="#loan-application-no-tab" role="tab" data-toggle="tab"><i class="fa fa-hashtag"></i> Application Starting No</a>
            </li>
            <li role="presentation" class="{{ $active_tab == 'loan_officers' ? 'active' : '' }}">
                <a href="#loan-officers-tab" role="tab" data-toggle="tab"><i class="fa fa-user-circle"></i> Loan Officers</a>
            </li>
            @foreach($setup_types as $type => $config)
                <li role="presentation" class="{{ $active_tab == $config['tab'] ? 'active' : '' }}">
                    <a href="#{{ $config['tab'] }}-tab" role="tab" data-toggle="tab"><i class="fa {{ $config['icon'] }}"></i> {{ $config['label'] }}</a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content loan-setup-tab-content">
            <div role="tabpanel" class="tab-pane {{ $active_tab == 'application_no' ? 'active' : '' }}" id="loan-application-no-tab">
                <div class="row">
                    <div class="col-md-7">
                        <div class="loan-section-card">
                            <div class="loan-section-title"><i class="fa fa-edit"></i> Loan Application Number Settings</div>
                            <form method="POST" action="{{ route('loan.setup.application_no.save') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Prefix *</label>
                                            <input type="text" name="loan_application_prefix" class="form-control" value="{{ old('loan_application_prefix', $settings['loan_application_prefix']) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Starting No *</label>
                                            <input type="number" name="loan_application_starting_no" class="form-control" value="{{ old('loan_application_starting_no', $settings['loan_application_starting_no']) }}" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Next Preview</label>
                                            <input type="text" class="form-control" value="{{ $settings['loan_application_next_no_preview'] }}" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="loan-info-card">
                            <h4><i class="fa fa-info-circle"></i> Number Format</h4>
                            <p>The system uses this prefix and starting number when creating new loan applications.</p>
                            <strong>Example:</strong> {{ $settings['loan_application_next_no_preview'] }}
                        </div>
                    </div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane {{ $active_tab == 'loan_officers' ? 'active' : '' }}" id="loan-officers-tab">
                <div class="row">
                    <div class="col-md-5">
                        <div class="loan-section-card">
                            <div class="loan-section-title"><i class="fa fa-plus-circle"></i> Add Loan Officer</div>
                            <form method="POST" action="{{ route('loan.setup.officers.store') }}">
                                @csrf
                                <div class="form-group">
                                    <label>Link System User</label>
                                    <select name="user_id" class="form-control select2 loan-user-selector" style="width:100%">
                                        <option value="">Please Select</option>
                                        @foreach($users as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Officer Name *</label>
                                    <input type="text" name="name" class="form-control loan-officer-name" value="{{ old('name') }}" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><div class="form-group"><label>Email</label><input type="text" name="email" class="form-control" value="{{ old('email') }}"></div></div>
                                    <div class="col-md-6"><div class="form-group"><label>Mobile</label><input type="text" name="mobile" class="form-control" value="{{ old('mobile') }}"></div></div>
                                </div>
                                <div class="form-group">
                                    <label>Status *</label>
                                    <select name="status" class="form-control" required><option value="active">Active</option><option value="inactive">Inactive</option></select>
                                </div>
                                <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
                                <div class="text-right"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Loan Officer</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="loan-section-card">
                            <div class="loan-section-title"><i class="fa fa-list"></i> Loan Officer List</div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped loan-setup-table">
                                    <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Status</th><th class="text-center">Action</th></tr></thead>
                                    <tbody>
                                    @forelse($officers as $officer)
                                        <tr>
                                            <td>{{ $officer->name }}</td><td>{{ $officer->email }}</td><td>{{ $officer->mobile }}</td>
                                            <td><span class="label label-{{ $officer->status == 'active' ? 'success' : 'default' }}">{{ ucfirst($officer->status) }}</span></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-xs btn-primary loan-edit-officer" data-id="{{ $officer->id }}" data-user_id="{{ $officer->user_id }}" data-name="{{ e($officer->name) }}" data-email="{{ e($officer->email) }}" data-mobile="{{ e($officer->mobile) }}" data-status="{{ $officer->status }}" data-notes="{{ e($officer->notes) }}"><i class="fa fa-edit"></i></button>
                                                <form method="POST" action="{{ route('loan.setup.officers.delete', $officer->id) }}" style="display:inline">@csrf<button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this loan officer?')"><i class="fa fa-trash"></i></button></form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center">No loan officers found.</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right">{{ $officers->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @foreach($setup_types as $type => $config)
                <div role="tabpanel" class="tab-pane {{ $active_tab == $config['tab'] ? 'active' : '' }}" id="{{ $config['tab'] }}-tab">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="loan-section-card">
                                <div class="loan-section-title"><i class="fa fa-plus-circle"></i> Add {{ $config['single'] }}</div>
                                <form method="POST" action="{{ route('loan.setup.items.store') }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <div class="form-group">
                                        <label>Name *</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    @if($type == 'loan_charges')
                                        <div class="row">
                                            <div class="col-md-6"><div class="form-group"><label>Amount</label><input type="number" step="0.0001" name="amount" class="form-control" value="0.0000"></div></div>
                                            <div class="col-md-6"><div class="form-group"><label>Charge Type</label><select name="charge_type" class="form-control"><option value="fixed">Fixed</option><option value="percentage">Percentage</option><option value="manual">Manual</option></select></div></div>
                                        </div>
                                    @endif
                                    <div class="form-group">
                                        <label>Status *</label>
                                        <select name="status" class="form-control" required><option value="active">Active</option><option value="inactive">Inactive</option></select>
                                    </div>
                                    <div class="form-group"><label>Description / Notes</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                                    <div class="text-right"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save {{ $config['single'] }}</button></div>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="loan-section-card">
                                <div class="loan-section-title"><i class="fa fa-list"></i> {{ $config['label'] }} List</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped loan-setup-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                @if($type == 'loan_charges')<th class="text-right">Amount</th><th>Charge Type</th>@endif
                                                <th>Status</th>
                                                <th>Description</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @forelse(($setup_items[$type] ?? collect()) as $item)
                                            @php
                                                $itemStatus = $item->status ?? (isset($item->active) && $item->active ? 'active' : 'inactive');
                                                $itemDescription = $item->description ?? '';
                                            @endphp
                                            <tr>
                                                <td>{{ $item->name }}</td>
                                                @if($type == 'loan_charges')<td class="text-right">{{ number_format((float)($item->amount ?? 0), 4) }}</td><td>{{ ucfirst($item->charge_type ?? '') }}</td>@endif
                                                <td><span class="label label-{{ $itemStatus == 'active' ? 'success' : 'default' }}">{{ ucfirst($itemStatus) }}</span></td>
                                                <td>{{ $itemDescription }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-primary loan-edit-setup-item" data-id="{{ $item->id }}" data-type="{{ $type }}" data-label="{{ $config['single'] }}" data-name="{{ e($item->name) }}" data-description="{{ e($itemDescription) }}" data-status="{{ $itemStatus }}" data-amount="{{ $item->amount ?? 0 }}" data-charge_type="{{ $item->charge_type ?? '' }}"><i class="fa fa-edit"></i></button>
                                                    <form method="POST" action="{{ route('loan.setup.items.delete', $item->id) }}" style="display:inline">@csrf<input type="hidden" name="type" value="{{ $type }}"><button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete this {{ strtolower($config['single']) }}?')"><i class="fa fa-trash"></i></button></form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ $type == 'loan_charges' ? 6 : 4 }}" class="text-center">No {{ strtolower($config['label']) }} found.</td></tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="modal fade" id="loanOfficerEditModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
            <form method="POST" action="#" id="loan-officer-edit-form">@csrf
                <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h4 class="modal-title"><i class="fa fa-edit"></i> Edit Loan Officer</h4></div>
                <div class="modal-body">
                    <div class="row"><div class="col-md-6"><div class="form-group"><label>Link System User</label><select name="user_id" class="form-control select2" id="edit_user_id" style="width:100%"><option value="">Please Select</option>@foreach($users as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div></div><div class="col-md-6"><div class="form-group"><label>Status *</label><select name="status" class="form-control" id="edit_status" required><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div></div>
                    <div class="form-group"><label>Officer Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                    <div class="row"><div class="col-md-6"><div class="form-group"><label>Email</label><input type="text" name="email" id="edit_email" class="form-control"></div></div><div class="col-md-6"><div class="form-group"><label>Mobile</label><input type="text" name="mobile" id="edit_mobile" class="form-control"></div></div></div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Loan Officer</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="loanSetupItemEditModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
            <form method="POST" action="#" id="loan-setup-item-edit-form">@csrf
                <div class="modal-header"><button type="button" class="close" data-dismiss="modal">×</button><h4 class="modal-title"><i class="fa fa-edit"></i> Edit <span id="edit_setup_label"></span></h4></div>
                <div class="modal-body">
                    <input type="hidden" name="type" id="edit_setup_type">
                    <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit_setup_name" class="form-control" required></div>
                    <div class="row edit-charge-row" style="display:none;"><div class="col-md-6"><div class="form-group"><label>Amount</label><input type="number" step="0.0001" name="amount" id="edit_setup_amount" class="form-control"></div></div><div class="col-md-6"><div class="form-group"><label>Charge Type</label><select name="charge_type" id="edit_setup_charge_type" class="form-control"><option value="fixed">Fixed</option><option value="percentage">Percentage</option><option value="manual">Manual</option></select></div></div></div>
                    <div class="form-group"><label>Status *</label><select name="status" id="edit_setup_status" class="form-control" required><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    <div class="form-group"><label>Description / Notes</label><textarea name="description" id="edit_setup_description" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button></div>
            </form>
        </div></div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function () {
    if ($.fn.select2) $('.select2').select2();
    var userNames = @json($users);
    $('.loan-user-selector').on('change', function () {
        var selected = $(this).val();
        if (selected && userNames[selected]) $(this).closest('form').find('.loan-officer-name').val(userNames[selected]);
    });
    $('.loan-edit-officer').on('click', function () {
        var btn = $(this);
        $('#loan-officer-edit-form').attr('action', '{{ url('/loan/setup/loan-officers') }}/' + btn.data('id') + '/update');
        $('#edit_user_id').val(btn.data('user_id')).trigger('change');
        $('#edit_name').val(btn.data('name'));
        $('#edit_email').val(btn.data('email'));
        $('#edit_mobile').val(btn.data('mobile'));
        $('#edit_status').val(btn.data('status'));
        $('#edit_notes').val(btn.data('notes'));
        $('#loanOfficerEditModal').modal('show');
    });
    $('.loan-edit-setup-item').on('click', function () {
        var btn = $(this), type = btn.data('type');
        $('#loan-setup-item-edit-form').attr('action', '{{ url('/loan/setup/setup-items') }}/' + btn.data('id') + '/update');
        $('#edit_setup_type').val(type);
        $('#edit_setup_label').text(btn.data('label'));
        $('#edit_setup_name').val(btn.data('name'));
        $('#edit_setup_description').val(btn.data('description'));
        $('#edit_setup_status').val(btn.data('status'));
        $('#edit_setup_amount').val(btn.data('amount'));
        $('#edit_setup_charge_type').val(btn.data('charge_type') || 'fixed');
        $('.edit-charge-row').toggle(type === 'loan_charges');
        $('#loanSetupItemEditModal').modal('show');
    });
});
</script>
<style>
.loan-setup-standard-page .loan-setup-header-card,
.loan-setup-standard-page .loan-setup-card,
.loan-setup-standard-page .loan-section-card,
.loan-setup-standard-page .loan-info-card {
    background:#fff;border-radius:18px;padding:24px;margin-bottom:22px;box-shadow:0 12px 35px rgba(15,23,42,.08);border:1px solid #e8eef5;
}
.loan-setup-standard-page .loan-setup-header-card h2{margin:0;font-weight:800;color:#1f3349;}
.loan-setup-standard-page .loan-setup-header-card p{margin:8px 0 0;color:#718096;}
.loan-setup-standard-page .loan-setup-tabs{border-bottom:1px solid #e5edf5;margin-bottom:22px;}
.loan-setup-standard-page .loan-setup-tabs>li>a{font-weight:700;color:#4f6eea;border-radius:10px 10px 0 0;padding:13px 16px;}
.loan-setup-standard-page .loan-setup-tabs>li.active>a{background:#fff;color:#1f3349;border:1px solid #e5edf5;border-bottom-color:#fff;}
.loan-setup-standard-page .loan-section-title{font-weight:800;font-size:17px;color:#1f3349;border-bottom:1px solid #edf2f7;padding-bottom:12px;margin-bottom:18px;}
.loan-setup-standard-page label{font-weight:700;color:#2d4059;}
.loan-setup-standard-page .form-control{height:46px;border-radius:10px;border-color:#dbe5ef;box-shadow:none;}
.loan-setup-standard-page textarea.form-control{height:auto;}
.loan-setup-standard-page .btn{border-radius:10px;font-weight:700;padding:10px 16px;}
.loan-setup-standard-page .loan-setup-table th{text-transform:uppercase;font-size:12px;color:#64748b;background:#f8fafc;}
.loan-setup-standard-page .loan-setup-table td{vertical-align:middle!important;}
</style>
@endsection
