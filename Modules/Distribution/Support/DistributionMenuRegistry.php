<?php

namespace Modules\Distribution\Support;

/**
 * Distribution-owned menu registry.
 *
 * Keeps Distribution sidebar/menu entries in the module so menu ownership can
 * be separated without changing existing UI behavior in this stage.
 */
class DistributionMenuRegistry
{
    public function all(): array
    {
        return config('distribution.menu', []);
    }

    public function enabled(): array
    {
        return array_values(array_filter($this->all(), function ($item) {
            return ! array_key_exists('enabled', $item) || (bool) $item['enabled'];
        }));
    }
}
