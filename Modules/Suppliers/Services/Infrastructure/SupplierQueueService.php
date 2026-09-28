<?php

namespace Modules\Suppliers\Services\Infrastructure;

use Illuminate\Support\Facades\Bus;

/**
 * Suppliers module queue gateway.
 *
 * Keeps queued job dispatching behind a module-owned service so supplier logic
 * does not depend on scattered Bus/dispatch helper calls.
 */
class SupplierQueueService
{
    public function dispatch($job)
    {
        return Bus::dispatch($job);
    }

    public function dispatchSync($job)
    {
        return Bus::dispatchSync($job);
    }

    public function chain(array $jobs)
    {
        return Bus::chain($jobs);
    }
}
