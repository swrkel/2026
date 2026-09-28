<?php

namespace Modules\SettlementCore\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * MA-002: SettlementCore is a code-only shared module.
 *
 * It deliberately registers nothing - no routes, views, translations,
 * migrations, config or bindings. Its only purpose is to hold the shared
 * settlement payment reconciler engine and the write-guard trait so that
 * Petro and Vat can both depend on it instead of on each other.
 *
 * The provider exists solely because every module in this application is
 * registered through module.json with a provider, and keeping the shape
 * consistent avoids surprising the module loader.
 */
class SettlementCoreServiceProvider extends ServiceProvider
{
    /** @var string */
    protected $moduleName = 'SettlementCore';

    /** @var string */
    protected $moduleNameLower = 'settlementcore';

    public function register(): void
    {
        // Intentionally empty.
    }

    public function boot(): void
    {
        // Intentionally empty.
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
