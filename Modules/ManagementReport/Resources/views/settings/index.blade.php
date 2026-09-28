@extends('managementreport::layouts.master', [
    'pageTitle' => 'Management Report Settings',
    'pageSubtitle' => 'Business-specific defaults for reports and delivery.'
])

@section('page_actions')
<a href="{{ route('managementreport.dashboard') }}" class="mgmt-btn mgmt-btn-default"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('managementreport.daily.index') }}" class="mgmt-btn mgmt-btn-primary"><i class="fa fa-plus"></i> New Report</a>
<a href="{{ route('managementreport.saved.index') }}" class="mgmt-btn mgmt-btn-success"><i class="fa fa-archive"></i> Saved Reports</a>
@endsection

@section('managementreport_content')
<form method="POST" action="{{ route('managementreport.settings.update') }}">
    @csrf
    <div class="mgmt-settings-grid">
        <div class="mgmt-panel">
            <div class="mgmt-panel-header"><h3>Report Defaults</h3></div>
            <div class="mgmt-field"><label>Public Link Expiry (hours)</label><input type="number" min="1" max="720" name="link_expiry_hours" value="{{ data_get($settings, 'link_expiry_hours', 72) }}"></div>
            <div class="mgmt-field"><label>Currency Decimals</label><select name="currency_decimals">@foreach([0,2,3,4] as $decimal)<option value="{{ $decimal }}" {{ (int) data_get($settings, 'currency_decimals', 2) === $decimal ? 'selected' : '' }}>{{ $decimal }}</option>@endforeach</select></div>
            <label class="mgmt-checkbox"><input type="checkbox" name="show_zero_rows" value="1" {{ data_get($settings, 'show_zero_rows', true) ? 'checked' : '' }}> Show rows with zero values</label>
            <label class="mgmt-checkbox"><input type="checkbox" name="print_logo" value="1" {{ data_get($settings, 'print_logo', true) ? 'checked' : '' }}> Show business logo in print/PDF</label>
        </div>
        <div class="mgmt-panel">
            <div class="mgmt-panel-header"><h3>Default Sections</h3></div>
            <div class="mgmt-settings-sections">
                @php($defaults = (array) data_get($settings, 'default_sections', array_keys($sections)))
                @foreach($sections as $key => $section)
                    <label class="mgmt-checkbox"><input type="checkbox" name="default_sections[]" value="{{ $key }}" {{ in_array($key, $defaults) ? 'checked' : '' }}> {{ $section['label'] }}</label>
                @endforeach
            </div>
        </div>
        <div class="mgmt-panel mgmt-settings-wide">
            <div class="mgmt-panel-header"><h3>Delivery Message Defaults</h3></div>
            <div class="mgmt-field"><label>Email Subject</label><input type="text" name="email_subject" value="{{ data_get($settings, 'email_subject', 'Daily Management Report') }}"></div>
            <div class="mgmt-field"><label>SMS Message</label><textarea name="sms_message" rows="2">{{ data_get($settings, 'sms_message', 'Your daily management report is ready: {link}') }}</textarea></div>
            <div class="mgmt-field"><label>WhatsApp Message</label><textarea name="whatsapp_message" rows="2">{{ data_get($settings, 'whatsapp_message', 'Your daily management report is ready: {link}') }}</textarea></div>
        </div>
    </div>
    <div class="mgmt-action-bar"><span class="mgmt-spacer"></span><button class="mgmt-btn mgmt-btn-primary" type="submit"><i class="fa fa-save"></i> Save Settings</button></div>
</form>
@endsection
