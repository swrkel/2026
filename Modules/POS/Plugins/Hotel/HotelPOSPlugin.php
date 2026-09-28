<?php

namespace Modules\POS\Plugins\Hotel;

use Modules\POS\Plugins\Contracts\POSPluginInterface;

class HotelPOSPlugin implements POSPluginInterface
{
    public function code(): string { return strtolower('Hotel'); }
    public function name(): string { return 'Hotel POS Plugin'; }
    public function version(): string { return '1.0.0'; }
    public function menu(): array { return []; }
    public function routes(): array { return []; }
    public function hooks(): array { return ['after_sale_save']; }
    public function isEnabled(?int $businessId = null): bool { return false; }
}
