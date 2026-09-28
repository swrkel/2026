<?php

namespace Modules\CommunicationHub\Services\Providers;

use Modules\CommunicationHub\Contracts\CommunicationHubChannelInterface;
use Modules\CommunicationHub\Entities\CommunicationHubProvider;
use Modules\CommunicationHub\Services\Cost\CommunicationCostEstimator;
use Modules\CommunicationHub\Services\Routing\IntelligentRoutingService;

class ChannelProviderManager
{
    protected array $runtimeProviders = [];

    public function __construct(
        protected ?ProviderDriverRegistry $registry = null,
        protected ?IntelligentRoutingService $routing = null,
        protected ?CommunicationCostEstimator $costEstimator = null
    ) {
        $this->registry = $this->registry ?: new ProviderDriverRegistry();
        $this->routing = $this->routing ?: new IntelligentRoutingService();
        $this->costEstimator = $this->costEstimator ?: new CommunicationCostEstimator();
    }

    public function register(CommunicationHubChannelInterface $provider): void
    {
        $this->runtimeProviders[$provider->channel()][] = $provider;
    }

    public function activeProviders(string $channel, array $message = []): array
    {
        $providers = $this->routing->providersFor($channel, $message);

        if (! empty($providers)) {
            return $providers;
        }

        return $this->runtimeProviders[$channel] ?? [new NullChannelProvider($channel)];
    }

    public function send(string $channel, array $message): array
    {
        $last = null;

        foreach ($this->activeProviders($channel, $message) as $provider) {
            try {
                if ($provider instanceof CommunicationHubProvider) {
                    $driver = $this->registry->get($provider->driver) ?: new NullChannelProvider($channel);
                    $startedAt = microtime(true);
                    $result = $driver->send($message, $provider->provider_config ?? []);
                    $responseMs = (int) round((microtime(true) - $startedAt) * 1000);

                    $provider->forceFill([
                        'last_used_at' => now(),
                        'last_success_at' => ! empty($result['success']) ? now() : $provider->last_success_at,
                        'last_failure_at' => empty($result['success']) ? now() : $provider->last_failure_at,
                        'last_response_code' => $result['response_code'] ?? null,
                        'last_response_message' => $result['response_message'] ?? null,
                        'average_response_ms' => $responseMs,
                    ])->save();

                    $result['provider_id'] = $provider->id;
                    $result['provider_name'] = $provider->name;
                    $result['driver'] = $provider->driver;
                    $result['cost'] = $result['cost'] ?? $this->costEstimator->estimate($channel, $provider, $message);

                    if (! empty($result['success'])) {
                        return $result;
                    }

                    $last = new \RuntimeException($result['response_message'] ?? 'Provider failed.');
                    continue;
                }

                if ($provider instanceof CommunicationHubChannelInterface) {
                    return $provider->send($message, []);
                }
            } catch (\Throwable $e) {
                $last = $e;
                continue;
            }
        }

        return [
            'success' => false,
            'response_code' => 'NO_PROVIDER',
            'response_message' => $last ? $last->getMessage() : 'No active provider available.',
            'cost' => 0,
        ];
    }
}
