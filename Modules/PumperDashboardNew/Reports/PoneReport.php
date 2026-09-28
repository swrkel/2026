<?php

namespace Modules\PumperDashboardNew\Reports;

use Illuminate\Support\Collection;

interface PoneReport
{
    public function key(): string;

    public function label(): string;

    public function rows(int $businessId, array $filters = []): Collection;
}
