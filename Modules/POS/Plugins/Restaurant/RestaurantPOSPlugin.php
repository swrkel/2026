<?php

namespace Modules\POS\Plugins\Restaurant;

use Modules\POS\Plugins\Contracts\POSPluginInterface;

class RestaurantPOSPlugin implements POSPluginInterface
{
    public function code(): string { return strtolower('Restaurant'); }
    public function name(): string { return 'Restaurant POS Plugin'; }
    public function version(): string { return '1.0.0'; }
    public function menu(): array { return []; }
    public function routes(): array { return []; }
    public function hooks(): array { return ['after_sale_save']; }
    public function isEnabled(?int $businessId = null): bool { return false; }
}
