<?php

namespace Modules\TeaEstateManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\TeaFinanceBridgeService;

class FinanceController extends BaseTeaController
{
    private function purposes(): array
    {
        return [
            'green_leaf_inventory' => 'Green Leaf Inventory',
            'estate_operating_expense' => 'Tea Estate Operating Expense',
            'estate_cost_recovery' => 'Estate Production Cost Recovery',
            'processing_wip' => 'Tea Processing WIP',
            'supplier_payable' => 'Tea Supplier Payable',
            'finished_tea_inventory' => 'Finished Tea Inventory',
            'accounts_receivable' => 'Tea Accounts Receivable',
            'sales_revenue' => 'Tea Sales Revenue',
            'tax_payable' => 'Tax Payable',
            'cogs' => 'Cost of Tea Sold',
        ];
    }

    public function index(TeaFinanceBridgeService $finance)
    {
        $d = $this->common();
        $b = $this->businessId();
        $d['financeAvailable'] = $finance->available();
        $d['accountOptions'] = $finance->accountOptions();
        $d['purposes'] = $this->purposes();
        $d['mappings'] = $d['installed']
            ? DB::table('tea_finance_account_mappings')->where('business_id', $b)->get()
            : collect();
        $d['mappingMatrix'] = $d['mappings']->groupBy('location_id')->map(function ($rows) {
            return $rows->pluck('account_id', 'purpose')->map(fn ($id) => (int) $id)->all();
        })->all();
        $d['events'] = $d['installed']
            ? $this->scopeLocations(DB::table('tea_finance_events')->where('business_id', $b))
                ->orderByDesc('id')->limit(500)->get()
            : collect();

        return view('teaestate::finance.index', $d);
    }

    public function saveMappings(Request $r, TeaFinanceBridgeService $finance)
    {
        $r->validate(['mappings' => 'array', 'location_id' => 'nullable|integer|min:0']);
        $b = $this->businessId();
        $loc = (int) ($r->location_id ?? 0);
        if ($loc > 0) {
            $loc = $this->locations->resolveRequired($loc);
        }
        $allowed = array_keys($this->purposes());

        DB::transaction(function () use ($r, $finance, $b, $loc, $allowed) {
            foreach ((array) $r->mappings as $purpose => $accountId) {
                if (!in_array($purpose, $allowed, true)) {
                    continue;
                }
                $accountId = (int) $accountId;
                if ($accountId <= 0) {
                    DB::table('tea_finance_account_mappings')
                        ->where('business_id', $b)->where('location_id', $loc)->where('purpose', $purpose)->delete();
                    continue;
                }
                abort_if(!$finance->isValidAccount($accountId), 422, 'Invalid or closed Finance account selected.');
                DB::table('tea_finance_account_mappings')->updateOrInsert(
                    ['business_id' => $b, 'location_id' => $loc, 'purpose' => $purpose],
                    ['account_id' => $accountId, 'updated_by' => $this->context->userId(), 'updated_at' => now()]
                );
                DB::table('tea_finance_account_mappings')
                    ->where('business_id', $b)->where('location_id', $loc)->where('purpose', $purpose)
                    ->whereNull('created_at')
                    ->update(['created_by' => $this->context->userId(), 'created_at' => now()]);
            }
        });

        $n = $finance->retryPending();
        return back()->with('tea_success', 'Finance account mappings saved. '.$n.' pending event(s) posted.');
    }

    public function retry(TeaFinanceBridgeService $finance)
    {
        $n = $finance->retryPending();
        return back()->with('tea_success', $n.' Tea finance event(s) posted/reconciled.');
    }
}
