<?php

namespace Modules\ProductsNew\Services\Deployment;

class RollbackGuideService
{
    public function steps(): array
    {
        return [
            'Disable only Products New permissions from roles if testing must stop.',
            'Remove/disable the Products New sidebar entry; do not remove legacy Product module.',
            'Keep all products_new_* tables for audit unless a full cleanup is approved.',
            'If a full cleanup is approved, run rollback SQL only against the selected tenant database.',
            'Clear route/config/view cache after disabling module visibility.',
            'Re-test legacy Product module list/add/edit before closing rollback activity.',
        ];
    }
}
