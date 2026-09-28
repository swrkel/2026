<?php

namespace Modules\EnterpriseFramework\Services\Security;

use RuntimeException;

class ReadOnlyGuardService
{
    protected array $blockedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function assertReportingRequestIsReadOnly(string $method, string $routeName = ''): void
    {
        if (in_array(strtoupper($method), $this->blockedMethods, true) && str_contains($routeName, 'reports')) {
            throw new RuntimeException('Enterprise Framework reporting routes are read-only unless explicitly whitelisted.');
        }
    }
}
