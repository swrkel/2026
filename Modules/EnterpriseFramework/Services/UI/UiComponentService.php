<?php

namespace Modules\EnterpriseFramework\Services\UI;

class UiComponentService
{
    public function toolbar(): array
    {
        return ['search', 'branch', 'consolidated', 'financial_year', 'date_range', 'export', 'print', 'columns'];
    }
}
