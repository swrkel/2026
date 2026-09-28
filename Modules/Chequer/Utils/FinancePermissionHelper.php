<?php

namespace Modules\Chequer\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class FinancePermissionHelper
{
    public static function all(): array
    {
        return Config::get('finance.permissions', require __DIR__ . '/../Config/permissions.php');
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function can(string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'can')) {
            try {
                if ($user->can($permission)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Continue to legacy fallback below.
            }
        }

        // Temporary bridge during Finance separation:
        // allow old accounting permissions until FIN-011 removes legacy dependencies.
        $legacyMap = self::legacyPermissionMap();
        foreach ($legacyMap[$permission] ?? [] as $legacyPermission) {
            try {
                if (method_exists($user, 'can') && $user->can($legacyPermission)) {
                    return true;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return false;
    }

    public static function any(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function legacyPermissionMap(): array
    {
        return [
            'finance.dashboard' => ['account.access', 'access_account'],
            'finance.accounts.view' => ['account.access', 'view_account'],
            'finance.accounts.create' => ['account.create', 'add_account'],
            'finance.accounts.update' => ['account.update', 'edit_account'],
            'finance.accounts.delete' => ['account.delete', 'delete_account'],
            'finance.account_groups.view' => ['account.access', 'view_account_group'],
            'finance.account_types.view' => ['account.access', 'view_account_type'],
            'finance.account_settings.view' => ['account.access', 'account_settings'],
            'finance.journal.view' => ['account.access', 'view_account_transaction'],
            'finance.reports.account_book' => ['account.access', 'account_book'],
            'finance.cheques.view' => ['cheque.view', 'access_cheque'],
            'finance.cheques.create' => ['cheque.create', 'add_cheque'],
            'finance.cheques.update' => ['cheque.update', 'edit_cheque'],
            'finance.cheques.delete' => ['cheque.delete', 'delete_cheque'],
            'finance.deposits.view' => ['cheque_deposit.view', 'access_cheque_deposit'],
            'finance.payments.view' => ['payment.view', 'access_payment'],
            'finance.expenses.view' => ['expense.access', 'view_expense'],
        ];
    }
}
