@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'Price Change Settings')
@section('pcn_page_subtitle', 'Configure approval, application and conflict controls for this business.')
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
@endsection
@section('pcn_content')
<form method="POST" action="{{ route('pricechangenew.settings.update') }}" class="pcn-settings-form">
    @csrf
    @method('PUT')

    <div class="pcn-settings-grid">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-check-square-o text-primary"></i> Approval Controls</h3><div class="ch-card-subtitle">Decide whether submitted price changes need a second user’s approval.</div></div></div>
            <div class="ch-card-body">
                <label class="pcn-switch-row"><span><strong>Approval required</strong><small>Submitted drafts remain in the approval queue until an authorized user approves or rejects them.</small></span><span><input type="hidden" name="approval_required" value="0"><input type="checkbox" name="approval_required" value="1" {{ !empty($settings['approval_required']) ? 'checked' : '' }}></span></label>
                <label class="pcn-switch-row"><span><strong>Allow self approval</strong><small>Permit the user who submitted the record to approve it. Leave disabled for dual control.</small></span><span><input type="hidden" name="allow_self_approval" value="0"><input type="checkbox" name="allow_self_approval" value="1" {{ !empty($settings['allow_self_approval']) ? 'checked' : '' }}></span></label>
                <label class="pcn-switch-row"><span><strong>Auto apply on approval</strong><small>Apply immediately only when the effective time is due. Scheduled future records remain scheduled.</small></span><span><input type="hidden" name="auto_apply_on_approval" value="0"><input type="checkbox" name="auto_apply_on_approval" value="1" {{ !empty($settings['auto_apply_on_approval']) ? 'checked' : '' }}></span></label>
            </div>
        </div>

        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-cogs text-primary"></i> Application Controls</h3><div class="ch-card-subtitle">Control scheduled processing and live-price conflict handling.</div></div></div>
            <div class="ch-card-body">
                <label class="pcn-switch-row"><span><strong>Auto apply due records</strong><small>Allows the module command to process approved/scheduled records when their effective time is due.</small></span><span><input type="hidden" name="auto_apply_due" value="0"><input type="checkbox" name="auto_apply_due" value="1" {{ !empty($settings['auto_apply_due']) ? 'checked' : '' }}></span></label>
                <div class="form-group"><label>Conflict Policy</label><select name="conflict_policy" class="form-control select2" required><option value="stop_all" {{ ($settings['conflict_policy'] ?? 'stop_all') === 'stop_all' ? 'selected' : '' }}>Stop all lines when any live price changed</option><option value="skip_conflicts" {{ ($settings['conflict_policy'] ?? '') === 'skip_conflicts' ? 'selected' : '' }}>Apply valid lines and skip conflicting lines</option></select><p class="help-block">A conflict occurs when the current live price no longer matches the snapshot captured in the draft.</p></div>
                <div class="form-group"><label>Default Application Scope</label><select name="default_application_scope" class="form-control select2" required>@foreach(config('pricechangenew.application_scopes', []) as $key => $label)<option value="{{ $key }}" {{ ($settings['default_application_scope'] ?? 'business_base') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-hashtag text-primary"></i> Reference Number</h3><div class="ch-card-subtitle">Controls the automatically generated price-change reference.</div></div></div>
        <div class="ch-card-body">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Reference Prefix</label><input type="text" name="reference_prefix" class="form-control" required maxlength="20" value="{{ old('reference_prefix', $settings['reference_prefix'] ?? 'PCN') }}"></div></div>
                <div class="col-md-4"><div class="form-group"><label>Number Padding</label><input type="number" name="reference_padding" class="form-control" min="3" max="12" required value="{{ old('reference_padding', $settings['reference_padding'] ?? 6) }}"><p class="help-block">Example: prefix PCN and padding 6 produces PCN-000001.</p></div></div>
                <div class="col-md-4"><div class="pcn-note-box"><i class="fa fa-info-circle"></i><span>Sequence values are maintained separately for every business in every tenant database.</span></div></div>
            </div>
        </div>
        <div class="pcn-form-footer"><a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default"><i class="fa fa-times"></i> Cancel</a><button type="submit" class="btn btn-success pos-large-save"><i class="fa fa-save"></i> Save Settings</button></div>
    </div>
</form>
@endsection
