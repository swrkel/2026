<?php

namespace App\Services;

use App\Account;
use App\AccountGroup;
use App\AccountType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BranchAccountingService
{
    public function createBranchAccounts($businessId, $locationId, $branchName)
    {
        try {
            DB::beginTransaction();

            $incomeType = $this->getAccountType($businessId, 'Income');
            $expenseType = $this->getAccountType($businessId, 'Expenses');
            $equityType = $this->getAccountType($businessId, 'Equity');

            $incomeGroup = $this->createOrGetGroup($businessId, $locationId, optional($incomeType)->id, $branchName . ' - Income');
            $directIncomeGroup = $this->createOrGetGroup($businessId, $locationId, optional($incomeType)->id, $branchName . ' - Direct Income');
            $otherIncomeGroup = $this->createOrGetGroup($businessId, $locationId, optional($incomeType)->id, $branchName . ' - Other Income');

            $expenseGroup = $this->createOrGetGroup($businessId, $locationId, optional($expenseType)->id, $branchName . ' - Expenses');
            $directExpenseGroup = $this->createOrGetGroup($businessId, $locationId, optional($expenseType)->id, $branchName . ' - Direct Expenses');
            $financeCostGroup = $this->createOrGetGroup($businessId, $locationId, optional($expenseType)->id, $branchName . ' - Finance Cost');

            $equityGroup = $this->createOrGetGroup($businessId, $locationId, optional($equityType)->id, $branchName . ' - Equity');

            $this->createOrGetAccount($businessId, $locationId, optional($incomeType)->id, optional($incomeGroup)->id, $branchName . ' - Income', 'income');
            $this->createOrGetAccount($businessId, $locationId, optional($incomeType)->id, optional($directIncomeGroup)->id, $branchName . ' - Direct Income', 'direct_income');
            $this->createOrGetAccount($businessId, $locationId, optional($incomeType)->id, optional($otherIncomeGroup)->id, $branchName . ' - Other Income', 'other_income');

            $this->createOrGetAccount($businessId, $locationId, optional($expenseType)->id, optional($expenseGroup)->id, $branchName . ' - Expenses', 'expenses');
            $this->createOrGetAccount($businessId, $locationId, optional($expenseType)->id, optional($directExpenseGroup)->id, $branchName . ' - Direct Expenses', 'direct_expenses');
            $this->createOrGetAccount($businessId, $locationId, optional($expenseType)->id, optional($financeCostGroup)->id, $branchName . ' - Finance Cost', 'finance_cost');

            $this->createOrGetAccount($businessId, $locationId, optional($equityType)->id, optional($equityGroup)->id, $branchName . ' - Profit & Loss', 'profit_loss');
            $this->createOrGetAccount($businessId, $locationId, optional($equityType)->id, optional($equityGroup)->id, $branchName . ' - Retained Earnings', 'retained_earnings');

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::emergency(
                'BranchAccountingService Error | File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage()
            );

            return false;
        }
    }

    public function createBranchSubAccount(
        $businessId,
        $locationId,
        $accountTypeId,
        $accountGroupId,
        $accountName,
        $parentAccountId = null
    ) {
        try {
            $query = Account::where('business_id', $businessId)
                ->where('name', $accountName);

            if (Schema::hasColumn('accounts', 'location_id')) {
                $query->where('location_id', $locationId);
            }

            $existing = $query->first();

            if (! empty($existing)) {
                return $existing;
            }

            $data = [
                'business_id' => $businessId,
                'name' => $accountName,
                'account_type_id' => $accountTypeId,
                'asset_type' => $accountGroupId,
                'created_by' => auth()->id(),
            ];

            if (Schema::hasColumn('accounts', 'location_id')) {
                $data['location_id'] = $locationId;
            }

            if (Schema::hasColumn('accounts', 'parent_account_id')) {
                $data['parent_account_id'] = $parentAccountId;
            }

            if (Schema::hasColumn('accounts', 'is_closed')) {
                $data['is_closed'] = 0;
            }

            return Account::create($data);
        } catch (\Exception $e) {
            Log::emergency(
                'Create Branch Sub Account Error | File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage()
            );

            return null;
        }
    }

    protected function getAccountType($businessId, $name)
    {
        return AccountType::where('business_id', $businessId)
            ->where('name', $name)
            ->first();
    }

    protected function createOrGetGroup($businessId, $locationId, $accountTypeId, $name)
    {
        $query = AccountGroup::where('business_id', $businessId)
            ->where('name', $name);

        if (Schema::hasColumn('account_groups', 'location_id')) {
            $query->where('location_id', $locationId);
        }

        $group = $query->first();

        if (! empty($group)) {
            return $group;
        }

        $data = [
            'business_id' => $businessId,
            'account_type_id' => $accountTypeId,
            'name' => $name,
        ];

        if (Schema::hasColumn('account_groups', 'location_id')) {
            $data['location_id'] = $locationId;
        }

        return AccountGroup::create($data);
    }

    protected function createOrGetAccount($businessId, $locationId, $accountTypeId, $accountGroupId, $name, $systemKey)
    {
        $query = Account::where('business_id', $businessId)
            ->where('name', $name);

        if (Schema::hasColumn('accounts', 'location_id')) {
            $query->where('location_id', $locationId);
        }

        $account = $query->first();

        if (! empty($account)) {
            return $account;
        }

        $data = [
            'business_id' => $businessId,
            'name' => $name,
            'account_type_id' => $accountTypeId,
            'asset_type' => $accountGroupId,
            'created_by' => auth()->id(),
        ];

        if (Schema::hasColumn('accounts', 'location_id')) {
            $data['location_id'] = $locationId;
        }

        if (Schema::hasColumn('accounts', 'system_key')) {
            $data['system_key'] = $systemKey;
        }

        if (Schema::hasColumn('accounts', 'is_closed')) {
            $data['is_closed'] = 0;
        }

        return Account::create($data);
    }
}