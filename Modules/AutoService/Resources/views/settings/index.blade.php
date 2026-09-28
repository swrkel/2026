@extends('autoservice::layouts.master')
@section('title','Auto Service Settings')
@section('autoservice_content')
<form method="post" action="{{ route('autoservice.settings.store') }}">
    @csrf
    <div class="box">
        <div class="box-body row">
            <div class="form-group col-md-4">
                <label>SMS Reminder Days Before Due Date</label>
                <input name="sms_reminder_days_before" type="number" class="form-control" value="{{ $settings['sms_reminder_days_before'] ?? 7 }}">
            </div>
            <div class="form-group col-md-4">
                <label>Enable Customer Portal</label>
                <select name="enable_customer_portal" class="form-control">
                    <option value="1" {{ ($settings['enable_customer_portal'] ?? 1) == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ ($settings['enable_customer_portal'] ?? 1) == 0 ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Enable QR Tracking</label>
                <select name="enable_qr_tracking" class="form-control">
                    <option value="1" {{ ($settings['enable_qr_tracking'] ?? 1) == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ ($settings['enable_qr_tracking'] ?? 1) == 0 ? 'selected' : '' }}>No</option>
                </select>
            </div>

            <div class="form-group col-md-4">
                <label>Enable Auto Service Accounting Posting</label>
                <select name="enable_accounting_posting" class="form-control">
                    <option value="1" {{ ($settings['enable_accounting_posting'] ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ ($settings['enable_accounting_posting'] ?? 0) == 0 ? 'selected' : '' }}>No</option>
                </select>
                <small class="help-block">Keeps accounting integration disabled until the business confirms the account mappings.</small>
            </div>
            <div class="clearfix"></div>
            <div class="form-group col-md-4"><label>Labour Income Account ID</label><input name="account_labour_income" class="form-control" value="{{ $settings['account_labour_income'] ?? '' }}"></div>
            <div class="form-group col-md-4"><label>Parts Income Account ID</label><input name="account_parts_income" class="form-control" value="{{ $settings['account_parts_income'] ?? '' }}"></div>
            <div class="form-group col-md-4"><label>Tax Payable Account ID</label><input name="account_tax_payable" class="form-control" value="{{ $settings['account_tax_payable'] ?? '' }}"></div>
            <div class="form-group col-md-4"><label>Cash / Bank Account ID</label><input name="account_cash_bank" class="form-control" value="{{ $settings['account_cash_bank'] ?? '' }}"></div>
            <div class="form-group col-md-4"><label>Customer Receivable Account ID</label><input name="account_customer_receivable" class="form-control" value="{{ $settings['account_customer_receivable'] ?? '' }}"></div>
            <div class="form-group col-md-4"><label>Inventory Account ID</label><input name="account_inventory" class="form-control" value="{{ $settings['account_inventory'] ?? '' }}"></div>
            <div class="form-group col-md-4"><label>Parts Cost Account ID</label><input name="account_parts_cost" class="form-control" value="{{ $settings['account_parts_cost'] ?? '' }}"></div>
            <div class="form-group col-md-4">
                <label>Allow Customer to View Current Invoice</label>
                <select name="enable_customer_current_invoice_view" class="form-control">
                    <option value="1" {{ ($settings['enable_customer_current_invoice_view'] ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ ($settings['enable_customer_current_invoice_view'] ?? 0) == 0 ? 'selected' : '' }}>No</option>
                </select>
                <small class="help-block">Previous invoices are visible in the customer portal. Current job invoice details are shown only when this is enabled by the business.</small>
            </div>
            <div class="form-group col-md-4">
                <label>Allow Customer Invoice PDF / Print</label>
                <select name="allow_customer_invoice_pdf" class="form-control"><option value="1" {{ ($settings['allow_customer_invoice_pdf'] ?? 1) == 1 ? 'selected' : '' }}>Yes</option><option value="0" {{ ($settings['allow_customer_invoice_pdf'] ?? 1) == 0 ? 'selected' : '' }}>No</option></select>
            </div>
            <div class="form-group col-md-4">
                <label>Allow Customer Fleet View</label>
                <select name="allow_customer_fleet_view" class="form-control"><option value="1" {{ ($settings['allow_customer_fleet_view'] ?? 1) == 1 ? 'selected' : '' }}>Yes</option><option value="0" {{ ($settings['allow_customer_fleet_view'] ?? 1) == 0 ? 'selected' : '' }}>No</option></select>
            </div>
            <div class="form-group col-md-4">
                <label>Allow Customer Feedback</label>
                <select name="allow_customer_feedback" class="form-control"><option value="1" {{ ($settings['allow_customer_feedback'] ?? 1) == 1 ? 'selected' : '' }}>Yes</option><option value="0" {{ ($settings['allow_customer_feedback'] ?? 1) == 0 ? 'selected' : '' }}>No</option></select>
            </div>
        </div>
        <div class="box-footer"><button class="btn btn-primary">Save Settings</button></div>
    </div>
</form>
@endsection
