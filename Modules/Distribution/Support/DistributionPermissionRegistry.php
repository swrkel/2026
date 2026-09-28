<?php

namespace Modules\Distribution\Support;

/**
 * Distribution-owned permission registry.
 *
 * This class keeps Distribution permission keys inside the module. It is a
 * non-invasive ownership seam: existing permission checks continue to work,
 * while future cleanup can read module permissions from here instead of from
 * scattered main-system files.
 */
class DistributionPermissionRegistry
{
    public function all(): array
    {
        return config('distribution.permissions', []);
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }

    public function label(string $permission): string
    {
        return $this->all()[$permission] ?? $permission;
    }

    public function has(string $permission): bool
    {
        return array_key_exists($permission, $this->all());
    }
}
