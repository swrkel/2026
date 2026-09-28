<?php

namespace Modules\EnterpriseFramework\Contracts;

interface ReportProviderContract
{
    public function moduleName(): string;

    public function reports(): array;

    public function dashboardWidgets(array $context = []): array;

    public function alerts(array $context = []): array;
}
