<?php

namespace Modules\POS\Plugins\Core;

use Modules\POS\Plugins\Contracts\POSPluginInterface;

class CorePOSPlugin implements POSPluginInterface
{
    public function code(): string { return 'core'; }
    public function name(): string { return 'Core POS'; }
    public function version(): string { return '1.0.0'; }
    public function menu(): array { return ['sales', 'returns', 'shifts', 'receipts', 'reports']; }
    public function routes(): array { return ['pos.sales.index', 'pos.returns.index', 'pos.shifts.index']; }
    public function hooks(): array { return ['before_sale_save', 'after_sale_save', 'after_payment_save']; }
    public function isEnabled(?int $businessId = null): bool { return true; }
}
