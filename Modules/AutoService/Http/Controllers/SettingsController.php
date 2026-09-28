<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends AutoServiceBaseController
{
    protected array $settingKeys = [
        'sms_reminder_days_before',
        'enable_customer_portal',
        'enable_qr_tracking',
        'enable_customer_current_invoice_view',
        'enable_accounting_posting',
        'account_labour_income',
        'account_parts_income',
        'account_tax_payable',
        'account_cash_bank',
        'account_customer_receivable',
        'account_parts_cost',
        'account_inventory',
        'allow_customer_invoice_pdf',
        'allow_customer_job_documents',
        'allow_customer_feedback',
        'allow_customer_fleet_view',
    ];

    public function index()
    {
        $rows = DB::table('auto_service_settings')
            ->where('business_id', $this->businessId())
            ->pluck('value', 'key');

        return view('autoservice::settings.index', ['settings' => $rows]);
    }

    public function store(Request $request)
    {
        foreach ($this->settingKeys as $key) {
            DB::table('auto_service_settings')->updateOrInsert(
                ['business_id' => $this->businessId(), 'key' => $key],
                [
                    'value' => $request->input($key, in_array($key, ['enable_customer_portal', 'enable_qr_tracking', 'enable_customer_current_invoice_view', 'enable_accounting_posting', 'allow_customer_invoice_pdf', 'allow_customer_job_documents', 'allow_customer_feedback', 'allow_customer_fleet_view'], true) ? 0 : null),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('status', 'Auto Service settings saved.');
    }
}
