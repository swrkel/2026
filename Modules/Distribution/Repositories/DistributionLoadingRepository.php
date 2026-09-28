<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionLoading;

class DistributionLoadingRepository extends DistributionBaseRepository
{
    protected string $modelClass = DistributionLoading::class;
}
