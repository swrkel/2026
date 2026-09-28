@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.settings'),
    'heading' => __('churchmanagement::lang.settings'),
    'subheading' => 'Church details for this business. Used on printed and exported records.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-cog text-primary"></i> Church Details</h3>
            <div class="ch-card-subtitle">
                Saved per business, so each congregation on this system keeps its own.
            </div>
        </div>
    </div>
    <div class="ch-card-body">
        <form method="post" action="{{ route('churchmanagement.settings.update') }}">
            @csrf @method('PUT')

            <div class="chc-form-grid two">
                <div class="chc-field">
                    <label>Church Name</label>
                    <input type="text" name="church_name" maxlength="191"
                           value="{{ old('church_name', $settings['church_name']) }}">
                </div>
                <div class="chc-field">
                    <label>Pastor / Minister</label>
                    <input type="text" name="pastor_name" maxlength="191"
                           value="{{ old('pastor_name', $settings['pastor_name']) }}">
                </div>

                <div class="chc-field">
                    <label>Contact Phone</label>
                    <input type="text" name="contact_phone" maxlength="50"
                           value="{{ old('contact_phone', $settings['contact_phone']) }}">
                </div>
                <div class="chc-field">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" maxlength="191"
                           value="{{ old('contact_email', $settings['contact_email']) }}">
                </div>

                <div class="chc-field">
                    <label>Service Days</label>
                    <input type="text" name="service_days" maxlength="191"
                           value="{{ old('service_days', $settings['service_days']) }}"
                           placeholder="Sunday, Wednesday…">
                    <div class="chc-hint">Free text, so a congregation can describe its own pattern.</div>
                </div>
                <div class="chc-field">
                    <label>Address</label>
                    <input type="text" name="address" maxlength="1000"
                           value="{{ old('address', $settings['address']) }}">
                </div>

                <div class="chc-field" style="grid-column:1 / -1">
                    <label>Membership Notes</label>
                    <textarea name="membership_notes" maxlength="2000">{{ old('membership_notes', $settings['membership_notes']) }}</textarea>
                    <div class="chc-hint">Internal guidance for whoever maintains the roll.</div>
                </div>
            </div>

            <div class="chc-form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ __('churchmanagement::lang.save') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
