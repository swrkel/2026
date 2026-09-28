<?php

namespace Modules\POS\Plugins\Contracts;

interface POSPluginInterface
{
    public function code(): string;
    public function name(): string;
    public function version(): string;
    public function menu(): array;
    public function routes(): array;
    public function hooks(): array;
    public function isEnabled(?int $businessId = null): bool;
}
