<?php

namespace Modules\Leasing\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Leasing\Models\LeaseAsset;
use Modules\Leasing\Models\LeaseAssetType;
use Modules\Leasing\Models\LeasingProduct;
use Modules\Leasing\Models\LeaseContract;
use Modules\Leasing\Models\LeasingTransaction;

class LeasingSummaryService
{
    public function dashboard()
    {
        if (! Schema::hasTable('leasing_lease_contracts')) {
            return $this->emptySummary();
        }

        return [
            'products' => Schema::hasTable('leasing_products') ? LeasingProduct::count() : 0,
            'lease_asset_types' => Schema::hasTable('leasing_lease_asset_types') ? LeaseAssetType::count() : 0,
            'lease_assets' => Schema::hasTable('leasing_lease_assets') ? LeaseAsset::count() : 0,
            'active_lease_contracts' => LeaseContract::where('status', 'active')->count(),
            'redeemed_lease_contracts' => LeaseContract::where('status', 'redeemed')->count(),
            'insurance_due' => LeaseContract::where('status', 'active')->whereDate('due_on', '<', date('Y-m-d'))->count(),
            'advance_amount' => LeaseContract::sum('advance_amount'),
            'outstanding_amount' => LeaseContract::sum('outstanding_amount'),
            'today_transactions' => Schema::hasTable('leasing_transactions') ? LeasingTransaction::whereDate('transaction_date', date('Y-m-d'))->sum('amount') : 0,
            'recent_lease_contracts' => LeaseContract::orderBy('id', 'desc')->limit(5)->get(),
            'due_lease_contracts' => LeaseContract::where('status', 'active')->whereDate('due_on', '<=', date('Y-m-d', strtotime('+7 days')))->orderBy('due_on')->limit(5)->get(),
        ];
    }

    protected function emptySummary()
    {
        return [
            'products' => 0,
            'lease_asset_types' => 0,
            'lease_assets' => 0,
            'active_lease_contracts' => 0,
            'redeemed_lease_contracts' => 0,
            'insurance_due' => 0,
            'advance_amount' => 0,
            'outstanding_amount' => 0,
            'today_transactions' => 0,
            'recent_lease_contracts' => collect(),
            'due_lease_contracts' => collect(),
        ];
    }
}
