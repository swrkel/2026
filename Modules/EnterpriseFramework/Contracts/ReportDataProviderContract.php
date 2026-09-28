<?php

namespace Modules\EnterpriseFramework\Contracts;

interface ReportDataProviderContract
{
    public function build(array $context = []): array;
}
