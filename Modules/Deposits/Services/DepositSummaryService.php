<?php

namespace Modules\Deposits\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositProduct;
use Modules\Deposits\Models\DepositTransaction;

class DepositSummaryService
{
    public function dashboard(): array
    {
        if (! Schema::hasTable('deposit_accounts')) {
            return $this->emptySummary();
        }

        return [
            'products' => Schema::hasTable('deposit_products') ? DepositProduct::count() : 0,
            'accounts' => DepositAccount::count(),
            'active_accounts' => DepositAccount::where('status', 'active')->count(),
            'closed_accounts' => DepositAccount::where('status', 'closed')->count(),
            'renewed_accounts' => DepositAccount::where('status', 'renewed')->count(),
            'maturity_due' => DepositAccount::where('status', 'active')->whereNotNull('maturity_on')->whereDate('maturity_on', '<=', date('Y-m-d'))->count(),
            'maturity_next_30' => DepositAccount::where('status', 'active')->whereNotNull('maturity_on')->whereBetween('maturity_on', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])->count(),
            'principal' => DepositAccount::sum('principal_amount'),
            'balance' => DepositAccount::sum('current_balance'),
            'interest' => DepositAccount::sum('interest_accrued'),
            'transactions' => Schema::hasTable('deposit_transactions') ? DepositTransaction::count() : 0,
            'today_transactions' => Schema::hasTable('deposit_transactions') ? DepositTransaction::whereDate('transaction_date', date('Y-m-d'))->sum('amount') : 0,
            'recent_accounts' => DepositAccount::with('product')->latest()->limit(5)->get(),
            'upcoming_maturities' => DepositAccount::with('product')->where('status', 'active')->whereNotNull('maturity_on')->whereBetween('maturity_on', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])->orderBy('maturity_on')->limit(5)->get(),
        ];
    }

    public function report(string $type = 'summary', array $filters = [])
    {
        if (! Schema::hasTable('deposit_accounts')) {
            return collect();
        }

        $query = DepositAccount::with('product')->latest();
        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        switch ($type) {
            case 'active':
                $query->where('status', 'active');
                break;
            case 'closed':
                $query->where('status', 'closed');
                break;
            case 'renewed':
                $query->where('status', 'renewed');
                break;
            case 'maturity_due':
                $query->where('status', 'active')->whereNotNull('maturity_on')->whereDate('maturity_on', '<=', date('Y-m-d'));
                break;
            case 'maturity_next_30':
                $query->where('status', 'active')->whereNotNull('maturity_on')->whereBetween('maturity_on', [date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]);
                break;
            default:
                break;
        }

        return $query->paginate(50);
    }

    private function emptySummary(): array
    {
        return [
            'products' => 0,
            'accounts' => 0,
            'active_accounts' => 0,
            'closed_accounts' => 0,
            'renewed_accounts' => 0,
            'maturity_due' => 0,
            'maturity_next_30' => 0,
            'principal' => 0,
            'balance' => 0,
            'interest' => 0,
            'transactions' => 0,
            'today_transactions' => 0,
            'recent_accounts' => collect(),
            'upcoming_maturities' => collect(),
        ];
    }
}
