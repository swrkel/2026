<?php

namespace Modules\SimpleAudit\Http\Middleware;

use Closure;
use Modules\SimpleAudit\Services\AccessService;

class EnsureCentralSimpleAuditAccess
{
    protected $access;

    public function __construct(AccessService $access)
    {
        $this->access = $access;
    }

    public function handle($request, Closure $next)
    {
        $this->access->assertCentralAccess();

        return $next($request);
    }
}
