<?php

namespace Modules\Pawning\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Pawning\Models\Article;
use Modules\Pawning\Models\CollateralType;
use Modules\Pawning\Models\PawningProduct;
use Modules\Pawning\Models\Pledge;
use Modules\Pawning\Models\PawningTransaction;

class PawningSummaryService
{
    public function dashboard()
    {
        if (! Schema::hasTable('pawning_pledges')) {
            return $this->emptySummary();
        }

        return [
            'products' => Schema::hasTable('pawning_products') ? PawningProduct::count() : 0,
            'collateral_types' => Schema::hasTable('pawning_collateral_types') ? CollateralType::count() : 0,
            'articles' => Schema::hasTable('pawning_articles') ? Article::count() : 0,
            'active_pledges' => Pledge::where('status', 'active')->count(),
            'redeemed_pledges' => Pledge::where('status', 'redeemed')->count(),
            'auction_due' => Pledge::where('status', 'active')->whereDate('due_on', '<', date('Y-m-d'))->count(),
            'advance_amount' => Pledge::sum('advance_amount'),
            'outstanding_amount' => Pledge::sum('outstanding_amount'),
            'today_transactions' => Schema::hasTable('pawning_transactions') ? PawningTransaction::whereDate('transaction_date', date('Y-m-d'))->sum('amount') : 0,
            'recent_pledges' => Pledge::orderBy('id', 'desc')->limit(5)->get(),
            'due_pledges' => Pledge::where('status', 'active')->whereDate('due_on', '<=', date('Y-m-d', strtotime('+7 days')))->orderBy('due_on')->limit(5)->get(),
        ];
    }

    protected function emptySummary()
    {
        return [
            'products' => 0,
            'collateral_types' => 0,
            'articles' => 0,
            'active_pledges' => 0,
            'redeemed_pledges' => 0,
            'auction_due' => 0,
            'advance_amount' => 0,
            'outstanding_amount' => 0,
            'today_transactions' => 0,
            'recent_pledges' => collect(),
            'due_pledges' => collect(),
        ];
    }
}
