<?php

namespace Modules\StockTransferNew\Services;

class StockTransferPermissionService
{
    public const PERMISSIONS = [
        'stocktransfernew.view' => 'View Stock Transfer-New',
        'stocktransfernew.create' => 'Create Stock Transfer-New',
        'stocktransfernew.edit' => 'Edit Stock Transfer-New Drafts',
        'stocktransfernew.submit' => 'Submit Stock Transfer-New',
        'stocktransfernew.approve' => 'Approve / Reject Stock Transfer-New',
        'stocktransfernew.dispatch' => 'Dispatch Stock Transfer-New',
        'stocktransfernew.receive' => 'Receive Stock Transfer-New',
        'stocktransfernew.reports' => 'View Stock Transfer-New Reports',
        'stocktransfernew.settings' => 'Manage Stock Transfer-New Settings',
        'stocktransfernew.export' => 'Export Stock Transfer-New Reports',
    ];

    public function all(): array
    {
        return self::PERMISSIONS;
    }

    public function can(?object $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }
        if (method_exists($user, 'can')) {
            try {
                return (bool) $user->can($permission);
            } catch (\Throwable $e) {
                return true;
            }
        }
        return true;
    }
}
