<?php

namespace Modules\POS\Plugins\BeautySaloons;

use Modules\POS\Plugins\Contracts\POSPluginInterface;

class BeautySaloonsPOSPlugin implements POSPluginInterface
{
    public function code(): string { return strtolower('BeautySaloons'); }
    public function name(): string { return 'BeautySaloons POS Plugin'; }
    public function version(): string { return '1.0.0'; }
    public function menu(): array { return []; }
    public function routes(): array { return []; }
    public function hooks(): array { return ['after_sale_save']; }
    public function isEnabled(?int $businessId = null): bool { return false; }
}
