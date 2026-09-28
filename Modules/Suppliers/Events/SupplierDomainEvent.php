<?php

namespace Modules\Suppliers\Events;

class SupplierDomainEvent
{
    public string $name;
    public array $payload;

    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
    }
}
