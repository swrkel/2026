<?php

namespace Modules\EnterpriseFramework\Contracts;

interface ModuleReportAdapterContract
{
    public function moduleKey(): string;
    public function moduleName(): string;
    public function reports(): array;
    public function dashboards(): array;
    public function metrics(array $context = []): array;
    public function data(string $reportKey, array $context = []): array;
    public function health(): array;
}
