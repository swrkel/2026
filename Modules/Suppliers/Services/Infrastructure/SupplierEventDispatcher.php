<?php

namespace Modules\Suppliers\Services\Infrastructure;

use Illuminate\Support\Facades\Event;

/**
 * Suppliers module event gateway.
 */
class SupplierEventDispatcher
{
    public function dispatch(object $event): array
    {
        return Event::dispatch($event);
    }

    public function until(object $event)
    {
        return Event::until($event);
    }
}
