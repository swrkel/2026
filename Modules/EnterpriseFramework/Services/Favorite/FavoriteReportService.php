<?php

namespace Modules\EnterpriseFramework\Services\Favorite;

class FavoriteReportService
{
    public function list(int $userId): array
    {
        return [];
    }

    public function toggle(int $userId, string $moduleKey, string $reportKey): array
    {
        return compact('userId', 'moduleKey', 'reportKey') + ['status' => 'placeholder'];
    }
}
