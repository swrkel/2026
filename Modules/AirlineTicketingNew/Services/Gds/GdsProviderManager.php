<?php
namespace Modules\AirlineTicketingNew\Services\Gds;

use InvalidArgumentException;
use Modules\AirlineTicketingNew\Contracts\Gds\GdsProviderInterface;

class GdsProviderManager
{
    private array $providers = [];

    public function register(GdsProviderInterface $provider): void
    {
        $this->providers[$provider->code()] = $provider;
    }

    public function provider(string $code): GdsProviderInterface
    {
        if (!isset($this->providers[$code])) {
            throw new InvalidArgumentException("GDS provider [$code] is not registered.");
        }

        return $this->providers[$code];
    }

    public function codes(): array
    {
        return array_keys($this->providers);
    }
}
