<?php

namespace Modules\Distribution\Services\Printing;

/**
 * Distribution-owned print service seam.
 *
 * Provides a common place to resolve Distribution print view names and payloads
 * instead of scattering print view logic across controllers.
 */
class DistributionPrintService
{
    public function view(string $name): string
    {
        return 'distribution::' . ltrim($name, '.');
    }

    public function payload(array $data = []): array
    {
        return array_merge([
            'printed_at' => date('Y-m-d H:i:s'),
            'module' => 'Distribution',
        ], $data);
    }
}
