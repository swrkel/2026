@extends('suppliers::layouts.app')
@section('title', 'Supplier Payment Reference Settings')
@section('suppliers_content')

<style>
.payment-ref-settings .prs-hero{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:22px 25px;margin-bottom:18px;border-radius:16px;background:linear-gradient(135deg,#173b6c,#2868b8);color:#fff;box-shadow:0 12px 28px rgba(20,57,105,.16)}
.payment-ref-settings .prs-hero h2{margin:0 0 6px;font-weight:800;color:#fff}.payment-ref-settings .prs-hero p{margin:0;color:rgba(255,255,255,.86)}
.payment-ref-settings .box{border-radius:13px;border:1px solid #e3eaf4;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.payment-ref-settings .box-header{padding:16px 18px}.payment-ref-settings .box-body{padding:18px}.payment-ref-settings th{white-space:nowrap;background:#f5f8fc}
.payment-ref-settings .status-pill{display:inline-block;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700}.payment-ref-settings .pill-active{background:#e7f7ed;color:#17783d}.payment-ref-settings .pill-inactive{background:#eef2f7;color:#637083}.payment-ref-settings .pill-used{background:#fff2e0;color:#a65c00}.payment-ref-settings .pill-unused{background:#edf5ff;color:#1d62a8}
.payment-ref-settings .btn[disabled]{opacity:.45;cursor:not-allowed}.payment-ref-settings .locked-note{font-size:11px;color:#8a5a00;display:block;margin-top:3px}
</style>

<section class="content payment-ref-settings">
    <div class="prs-hero">
        <div><h2>Supplier Payment Reference Settings</h2><p>Set the prefix and starting number used by Supplier Pay Due, Purchase Entry payments and later Purchase payments.</p></div>
        <a href="{{ route('suppliers.dashboard') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Supplier Dashboard</a>
    </div>

    @if(session('status'))
        <div class="alert {{ session('status.success') ? 'alert-success' : 'alert-danger' }}">{{ session('status.msg') }}</div>
    @endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(!$tableAvailable)
        <div class="alert alert-warning"><strong>Database setup required.</strong> Import the S710 SQL or run the Suppliers migrations, then reopen this page.</div>
    @else
    <div class="row">
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">{{ $editing ? 'Edit Prefix' : 'Add Prefix' }}</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ $editing ? route('suppliers.settings.payment_references.update', $editing->id) : route('suppliers.settings.payment_references.store') }}">
                        @csrf
                        @if($editing) @method('PUT') @endif
                        <div class="form-group">
                            <label>Payment Source</label>
                            @if($editing)
                                <input class="form-control" value="{{ $editing->context_label }}" readonly>
                            @else
                                <select name="context_key" class="form-control" required>
                                    <option value="">Please Select</option>
                                    @foreach($contexts as $meta)<option value="{{ $meta['key'] }}" {{ old('context_key')===$meta['key']?'selected':'' }}>{{ $meta['label'] }}</option>@endforeach
                                </select>
                            @endif
                        </div>
                        <div class="form-group"><label>Prefix</label><input type="text" name="prefix" class="form-control" maxlength="20" required value="{{ old('prefix', $editing->prefix ?? '') }}" placeholder="Example: SLP"></div>
                        <div class="form-group"><label>Starting Number</label><input type="number" name="starting_number" class="form-control" min="1" max="999999999" required value="{{ old('starting_number', $editing->starting_number ?? 1) }}"></div>
                        <div class="alert alert-info" style="margin-bottom:15px">Reference format: <strong>Prefix + Year + Number</strong>, e.g. <code>SLP2026-0001</code>.</div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ $editing ? 'Update' : 'Add Prefix' }}</button>
                        @if($editing)<a href="{{ route('suppliers.settings.payment_references.index') }}" class="btn btn-default">Cancel</a>@endif
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="box box-default">
                <div class="box-header with-border"><h3 class="box-title">Prefix List</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Payment Source</th><th>Prefix</th><th class="text-right">Starting No.</th><th>Status</th><th>Usage</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->context_label }}</td><td><strong>{{ $row->prefix }}</strong></td><td class="text-right">{{ number_format($row->starting_number) }}</td>
                                <td><span class="status-pill {{ $row->is_active ? 'pill-active' : 'pill-inactive' }}">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td><span class="status-pill {{ $row->is_used ? 'pill-used' : 'pill-unused' }}">{{ $row->is_used ? 'Used - Locked' : 'Not Used' }}</span></td>
                                <td style="white-space:nowrap">
                                    @if(!$row->is_used)
                                        <a class="btn btn-xs btn-primary" href="{{ route('suppliers.settings.payment_references.edit', $row->id) }}"><i class="fa fa-edit"></i> Edit</a>
                                        <form method="POST" action="{{ route('suppliers.settings.payment_references.destroy', $row->id) }}" style="display:inline" onsubmit="return confirm('Delete this unused prefix?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button></form>
                                    @else
                                        <button class="btn btn-xs btn-default" disabled><i class="fa fa-lock"></i> Edit</button>
                                        <button class="btn btn-xs btn-default" disabled><i class="fa fa-lock"></i> Delete</button>
                                        <span class="locked-note">Locked because a transaction already uses this prefix.</span>
                                    @endif
                                </td>
                            </tr>
                        @empty<tr><td colspan="6" class="text-center text-muted">No prefixes found.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>
@endsection
