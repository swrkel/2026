<?php

namespace Modules\POS\Services;

use Modules\POS\Plugins\Contracts\POSPluginInterface;

class POSPluginRegistry
{
    protected array $plugins = [];

    public function register(POSPluginInterface $plugin): void
    {
        $this->plugins[$plugin->code()] = $plugin;
    }

    public function all(): array
    {
        return $this->plugins;
    }

    public function enabled(?int $businessId = null): array
    {
        return array_filter($this->plugins, fn ($plugin) => $plugin->isEnabled($businessId));
    }

    public function menus(?int $businessId = null): array
    {
        $menus = [];
        foreach ($this->enabled($businessId) as $plugin) {
            $menus[$plugin->code()] = $plugin->menu();
        }
        return $menus;
    }
}
