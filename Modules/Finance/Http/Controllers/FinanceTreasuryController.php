<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceTreasuryTransaction;
use Modules\Finance\Services\FinanceAuditService;
use Modules\Finance\Services\FinanceNotificationService;
use Modules\Finance\Services\FinanceRiskService;
use Modules\Finance\Services\FinanceBudgetControlService;

class FinanceTreasuryController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where(
            'business_id',
            $business_id
        )->pluck('name', 'location_id');

        $accounts = Account::where(
            'business_id',
            $business_id
        )->pluck('name', 'id');

        $query = FinanceTreasuryTransaction::where(
            'business_id',
            $business_id
        )
        ->with([
            'location',
            'account',
            'createdBy'
        ])
        ->latest();

        if (!empty($request->location_id)
            && $request->location_id != 'all') {

            $query->where(
                'location_id',
                $request->location_id
            );
        }

        if (!empty($request->treasury_type)) {

            $query->where(
                'treasury_type',
                $request->treasury_type
            );
        }

        if (!empty($request->account_id)) {

            $query->where(
                'account_id',
                $request->account_id
            );
        }

        if (!empty($request->from_date)) {

            $query->whereDate(
                'transaction_date',
                '>=',
                $request->from_date
            );
        }

        if (!empty($request->to_date)) {

            $query->whereDate(
                'transaction_date',
                '<=',
                $request->to_date
            );
        }

        $transactions = $query->paginate(25);

        $total_cash_in = FinanceTreasuryTransaction::where(
            'business_id',
            $business_id
        )
        ->whereIn('treasury_type', [
            'cash_in',
            'bank_deposit'
        ])
        ->sum('amount');

        $total_cash_out = FinanceTreasuryTransaction::where(
            'business_id',
            $business_id
        )
        ->whereIn('treasury_type', [
            'cash_out',
            'bank_withdrawal'
        ])
        ->sum('amount');

        $net_cash_position =
            $total_cash_in - $total_cash_out;

        return view('finance::treasury.index')
            ->with(compact(
                'transactions',
                'locations',
                'accounts',
                'total_cash_in',
                'total_cash_out',
                'net_cash_position'
            ));
    }

    public function create()
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where(
            'business_id',
            $business_id
        )->pluck('name', 'location_id');

        $accounts = Account::where(
            'business_id',
            $business_id
        )->pluck('name', 'id');

        return view('finance::treasury.create')
            ->with(compact(
                'locations',
                'accounts'
            ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'treasury_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $business_id = session()->get('user.business_id');

        $transaction =
            FinanceTreasuryTransaction::create([

                'business_id' => $business_id,

                'location_id' => $request->location_id,

                'transaction_no' =>
                    'FTX-' . time(),

                'transaction_date' =>
                    $request->transaction_date,

                'treasury_type' =>
                    $request->treasury_type,

                'account_id' =>
                    $request->account_id,

                'amount' =>
                    $request->amount,

                'description' =>
                    $request->description,

                'reference_type' =>
                    $request->reference_type,

                'reference_id' =>
                    $request->reference_id,

                'created_by' =>
                    auth()->id(),
            ]);

        FinanceAuditService::log(
            'Treasury',
            'Created',
            'Treasury transaction created: '
                . $transaction->transaction_no,
            'finance_treasury_transactions',
            $transaction->id,
            null,
            $transaction->toArray(),
            $transaction->location_id
        );
        
        FinanceBudgetControlService::checkBudgetUsage(
    $business_id,
    $transaction->location_id,
    $transaction->account_id,
    $transaction->amount,
    'finance_treasury_transactions',
    $transaction->id
    
    
);

FinanceRiskService::monitorLargeTransaction(
    $business_id,
    $transaction->location_id,
    $transaction->amount,
    'Treasury',
    'Treasury Transaction Monitoring',
    'finance_treasury_transactions',
    $transaction->id
);

        if ($transaction->amount >= 1000000) {

            FinanceNotificationService::create(
                'Treasury Alert',
                'High Value Treasury Transaction',
                'Transaction amount exceeded limit: '
                    . number_format(
                        $transaction->amount,
                        2
                    ),
                null,
                'high',
                'finance_treasury_transactions',
                $transaction->id,
                $transaction->location_id
            );
        }

        return redirect()
            ->route('finance.treasury.index')
            ->with('status', [
                'success' => 1,
                'msg' =>
                    'Treasury transaction created successfully'
            ]);
    }
}